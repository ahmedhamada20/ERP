<?php

namespace App\Services\Accounting;

use App\Models\DomesticBooking;
use App\Models\JournalEntry;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Posts a single consolidated journal entry for ALL domestic booking costs at close time.
 *
 * Aggregation:
 *   - Filters: only is_revenue=false rows
 *   - Groups by (expense_account, supplier_account) using domestic category mappings
 *   - One DR line per unique expense account, one CR line per unique supplier account
 *
 * Example: domestic booking with hotel (6000) + transport (800) + activities (500):
 *   DR 511 (فنادق)   = 6000
 *   DR 514 (نقل)     = 800
 *   DR 519 (تشغيلية) = 500
 *   CR 2111 (موردين فنادق)   = 6000
 *   CR 2113 (موردين نقل)     = 800
 *   CR 2115 (موردين متنوعون) = 500
 *   Total: 7300 = 7300 ✓
 */
class DomesticBookingCostJournalPoster
{
    public function __construct(
        private readonly AccountingMappings $mappings,
        private readonly JournalService $journals,
    ) {}

    public function postClosingJournal(DomesticBooking $booking): JournalEntry
    {
        $booking->loadMissing('costs.supplier');
        $costs = $booking->costs->where('is_revenue', false);

        if ($costs->isEmpty()) {
            throw new RuntimeException("لا يوجد بنود تكلفة لترحيلها على الحجز {$booking->booking_number}");
        }

        $debitsByAccount  = [];
        $creditsByAccount = [];

        foreach ($costs as $cost) {
            $amount = (float) $cost->amount_egp;
            if ($amount <= 0) continue;

            $expense = $this->mappings->expenseAccountForDomesticCostCategory($cost->category);

            if ($cost->supplier_id && $cost->supplier) {
                $supplier = $cost->supplier->parentAccountModel()
                    ?? $this->mappings->supplierAccountForDomesticCostCategory($cost->category);
            } else {
                $supplier = $this->mappings->supplierAccountForDomesticCostCategory($cost->category);
            }

            $debitsByAccount[$expense->id]   = ($debitsByAccount[$expense->id]   ?? 0) + $amount;
            $creditsByAccount[$supplier->id] = ($creditsByAccount[$supplier->id] ?? 0) + $amount;
        }

        if (empty($debitsByAccount)) {
            throw new RuntimeException("جميع بنود التكلفة قيمتها صفر — لا شيء للترحيل");
        }

        return DB::transaction(function () use ($booking, $debitsByAccount, $creditsByAccount) {
            $entry = JournalEntry::create([
                'date'        => now()->toDateString(),
                'description' => "إقفال تكاليف حجز داخلي {$booking->booking_number} — {$booking->type_label} — {$booking->destination_city}",
                'reference'   => $booking->booking_number,
                'source_type' => 'domestic_booking_cost',
                'source_id'   => $booking->id,
            ]);

            $line = 1;
            foreach ($debitsByAccount as $accountId => $amount) {
                $entry->lines()->create([
                    'account_id'  => $accountId,
                    'debit'       => round($amount, 2),
                    'credit'      => 0,
                    'description' => "تكاليف الحجز {$booking->booking_number}",
                    'line_number' => $line++,
                ]);
            }
            foreach ($creditsByAccount as $accountId => $amount) {
                $entry->lines()->create([
                    'account_id'  => $accountId,
                    'debit'       => 0,
                    'credit'      => round($amount, 2),
                    'description' => "مستحق على الحجز {$booking->booking_number}",
                    'line_number' => $line++,
                ]);
            }

            $entry = $this->journals->post($entry->fresh());
            $booking->forceFill(['cost_journal_entry_id' => $entry->id])->saveQuietly();

            return $entry;
        });
    }

    public function cancelClosingJournal(DomesticBooking $booking, string $reason): void
    {
        if (! $booking->cost_journal_entry_id) return;

        $entry = JournalEntry::find($booking->cost_journal_entry_id);
        if (! $entry || ! $entry->isPosted()) return;

        $this->journals->cancel($entry, $reason);
    }
}
