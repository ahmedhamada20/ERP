<?php

namespace App\Services\Suppliers;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\SupplierInvoice;
use App\Services\Accounting\JournalService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Posts/cancels supplier invoices and their linked GL journal entries.
 *
 * Posting matrix:
 *   tax_amount = 0:    DR expense = amount        CR supplier_parent = amount
 *   tax_amount > 0:    DR expense = amount
 *                      DR VAT (2131) = tax_amount
 *                      CR supplier_parent = amount + tax
 *
 * All amounts converted to EGP via the invoice's exchange_rate before posting.
 */
class SupplierInvoiceService
{
    public function __construct(private readonly JournalService $journals) {}

    public function post(SupplierInvoice $invoice): SupplierInvoice
    {
        if (! $invoice->isDraft()) {
            throw new RuntimeException("لا يمكن ترحيل فاتورة حالتها [{$invoice->status_label}]");
        }

        $invoice->loadMissing('supplier', 'expenseAccount');

        $supplierParent = Account::where('code', $invoice->supplier->parentAccountCode())
            ->where('is_active', true)
            ->where('is_group', false)
            ->first();

        if (! $supplierParent) {
            throw new RuntimeException(
                "حساب المورد الأب [{$invoice->supplier->parentAccountCode()}] غير موجود أو غير نشط"
            );
        }

        if (! $invoice->expenseAccount || $invoice->expenseAccount->is_group || ! $invoice->expenseAccount->is_active) {
            throw new RuntimeException("حساب المصروف غير صالح للترحيل");
        }

        // Convert amounts to EGP for the journal lines
        $rate          = $invoice->currency === 'EGP' ? 1 : (float) $invoice->exchange_rate;
        $expenseAmtEgp = round((float) $invoice->amount * $rate, 2);
        $taxAmtEgp     = round((float) $invoice->tax_amount * $rate, 2);
        $totalEgp      = round($expenseAmtEgp + $taxAmtEgp, 2);

        return DB::transaction(function () use ($invoice, $supplierParent, $expenseAmtEgp, $taxAmtEgp, $totalEgp) {
            $entry = JournalEntry::create([
                'date'        => $invoice->invoice_date,
                'description' => sprintf(
                    'فاتورة مورد %s — %s',
                    $invoice->supplier->name,
                    $invoice->number,
                ),
                'reference'   => $invoice->supplier_reference ?: $invoice->number,
                'source_type' => 'supplier_invoice',
                'source_id'   => $invoice->id,
            ]);

            $line = 1;

            // Expense line
            $entry->lines()->create([
                'account_id'  => $invoice->expense_account_id,
                'debit'       => $expenseAmtEgp,
                'credit'      => 0,
                'description' => $invoice->description,
                'line_number' => $line++,
            ]);

            // VAT line (only if tax > 0)
            if ($taxAmtEgp > 0) {
                $vat = Account::where('code', '2131')->where('is_active', true)->first();
                if (! $vat) {
                    throw new RuntimeException('حساب ضريبة القيمة المضافة 2131 غير موجود');
                }
                $entry->lines()->create([
                    'account_id'  => $vat->id,
                    'debit'       => $taxAmtEgp,
                    'credit'      => 0,
                    'description' => "ض.ق.م على فاتورة {$invoice->number}",
                    'line_number' => $line++,
                ]);
            }

            // Supplier parent (credit)
            $entry->lines()->create([
                'account_id'  => $supplierParent->id,
                'debit'       => 0,
                'credit'      => $totalEgp,
                'description' => "مستحق لـ {$invoice->supplier->name}",
                'line_number' => $line++,
            ]);

            $entry = $this->journals->post($entry->fresh());

            $invoice->forceFill([
                'journal_entry_id' => $entry->id,
                'status'           => 'posted',
                'posted_at'        => now(),
                'posted_by'        => auth()->id(),
            ])->saveQuietly();

            return $invoice->refresh();
        });
    }

    public function cancel(SupplierInvoice $invoice, string $reason): SupplierInvoice
    {
        if (! $invoice->isPosted()) {
            throw new RuntimeException("لا يمكن إلغاء فاتورة حالتها [{$invoice->status_label}]");
        }

        DB::transaction(function () use ($invoice, $reason) {
            if ($invoice->journalEntry && $invoice->journalEntry->isPosted()) {
                $this->journals->cancel($invoice->journalEntry, "إلغاء فاتورة المورد: {$reason}");
            }

            $invoice->forceFill([
                'status'              => 'cancelled',
                'cancelled_at'        => now(),
                'cancelled_by'        => auth()->id(),
                'cancellation_reason' => $reason,
            ])->saveQuietly();
        });

        return $invoice->refresh();
    }
}
