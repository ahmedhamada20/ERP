<?php

namespace App\Services\Accounting;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\Voucher;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Voucher lifecycle:
 *   create() → atomically creates the Voucher + its balanced JournalEntry,
 *              posts both via JournalService.
 *   cancel() → cancels both voucher and its linked journal entry.
 *
 * Journal mapping:
 *   Receipt (قبض):  DR cash_account   CR counter_account
 *   Payment (صرف):  DR counter_account CR cash_account
 */
class VoucherService
{
    public function __construct(private JournalService $journals) {}

    /**
     * @param  array{
     *   type:string, date:string, cash_account_id:string,
     *   counter_account_id:string, party_type?:?string, party_id?:?string,
     *   party_name:string, currency:string, amount:float|string,
     *   exchange_rate?:float|string|null, description:string,
     *   reference?:?string
     * }  $data
     */
    public function create(array $data): Voucher
    {
        $this->assertValidPayload($data);

        return DB::transaction(function () use ($data) {
            // 1. Create the voucher (draft initially, JE will be linked after)
            $voucher = Voucher::create([
                'type'                 => $data['type'],
                'date'                 => $data['date'],
                'cash_account_id'      => $data['cash_account_id'],
                'counter_account_id'   => $data['counter_account_id'],
                'party_type'           => $data['party_type'] ?? null,
                'party_id'             => $data['party_id']   ?? null,
                'party_name'           => $data['party_name'],
                'supplier_id'          => $data['supplier_id']          ?? null,
                'supplier_invoice_id'  => $data['supplier_invoice_id']  ?? null,
                'currency'             => $data['currency'],
                'amount'               => $data['amount'],
                'exchange_rate'        => $data['exchange_rate'] ?? 1,
                'description'          => $data['description'],
                'reference'            => $data['reference'] ?? null,
                'status'               => 'draft',
            ]);

            // 2. Create the journal entry (header + lines)
            $entry = JournalEntry::create([
                'date'        => $data['date'],
                'description' => sprintf(
                    '%s — %s (%s)',
                    $voucher->type_label,
                    $voucher->party_name,
                    $voucher->number,
                ),
                'reference'   => $data['reference'] ?? $voucher->number,
                'source_type' => 'voucher',
                'source_id'   => $voucher->id,
            ]);

            [$debitAccountId, $creditAccountId] = $data['type'] === 'receipt'
                ? [$voucher->cash_account_id,    $voucher->counter_account_id]   // قبض: مدين خزينة، دائن العميل/إيراد
                : [$voucher->counter_account_id, $voucher->cash_account_id];     // صرف: مدين المصروف/المورد، دائن خزينة

            $entry->lines()->create([
                'account_id' => $debitAccountId,
                'debit'      => $voucher->amount_egp,
                'credit'     => 0,
                'description'=> $data['description'],
                'line_number'=> 1,
            ]);
            $entry->lines()->create([
                'account_id' => $creditAccountId,
                'debit'      => 0,
                'credit'     => $voucher->amount_egp,
                'description'=> $data['description'],
                'line_number'=> 2,
            ]);

            // 3. Post the journal entry (validates balance + active accounts)
            $entry = $this->journals->post($entry->fresh());

            // 4. Mark voucher posted + link
            $voucher->forceFill([
                'journal_entry_id' => $entry->id,
                'status'           => 'posted',
                'posted_at'        => now(),
                'posted_by'        => auth()->id(),
            ])->saveQuietly();

            return $voucher->refresh();
        });
    }

    public function cancel(Voucher $voucher, string $reason): Voucher
    {
        if (! $voucher->isPosted()) {
            throw new RuntimeException("لا يمكن إلغاء سند حالته [{$voucher->status_label}]");
        }

        DB::transaction(function () use ($voucher, $reason) {
            // Cancel the linked journal entry first
            if ($voucher->journalEntry && $voucher->journalEntry->isPosted()) {
                $this->journals->cancel($voucher->journalEntry, "إلغاء السند: {$reason}");
            }

            $voucher->forceFill([
                'status'              => 'cancelled',
                'cancelled_at'        => now(),
                'cancelled_by'        => auth()->id(),
                'cancellation_reason' => $reason,
            ])->saveQuietly();
        });

        return $voucher->refresh();
    }

    private function assertValidPayload(array $data): void
    {
        if (! in_array($data['type'] ?? null, ['receipt', 'payment'], true)) {
            throw new RuntimeException('نوع السند غير صحيح');
        }
        if (($data['amount'] ?? 0) <= 0) {
            throw new RuntimeException('قيمة السند يجب أن تكون أكبر من صفر');
        }
        if (($data['cash_account_id'] ?? null) === ($data['counter_account_id'] ?? null)) {
            throw new RuntimeException('الحساب المقابل لا يمكن أن يكون نفس حساب الخزينة');
        }

        $cash = Account::find($data['cash_account_id'] ?? null);
        if (! $cash || ! in_array($cash->sub_type, ['cash', 'bank'])) {
            throw new RuntimeException('الحساب الأول يجب أن يكون خزينة أو حساب بنكي');
        }

        $counter = Account::find($data['counter_account_id'] ?? null);
        if (! $counter || $counter->is_group || ! $counter->is_active) {
            throw new RuntimeException('الحساب المقابل غير صالح');
        }
    }
}
