<?php

namespace App\Services\Accounting;

use App\Models\JournalEntry;
use App\Models\ReligiousBooking;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Posts a single consolidated journal entry for ALL booking costs at close time.
 *
 * Aggregation:
 *   - Filters: only is_revenue=false rows (revenue lines don't represent costs)
 *   - Groups by (expense_account, supplier_account) and sums amount_egp
 *   - One DR line per unique expense account
 *   - One CR line per unique supplier account
 *
 * Result: a balanced JE where total = Σ all non-revenue cost rows.
 *
 * Example: booking has 3 hotel costs (8000) + 2 flight costs (5000) + 1 visa (1000):
 *   DR 511 (فنادق)   = 8000
 *   DR 512 (طيران)  = 5000
 *   DR 513 (تأشيرات) = 1000
 *   CR 2111 (موردين فنادق)   = 8000
 *   CR 2112 (موردين طيران)  = 5000
 *   CR 2114 (موردين تأشيرات) = 1000
 *   Total: 14000 = 14000 ✓
 */
class BookingCostJournalPoster
{
    public function __construct(
        private readonly AccountingMappings $mappings,
        private readonly JournalService $journals,
    ) {}

    public function postClosingJournal(ReligiousBooking $booking): JournalEntry
    {
        $booking->loadMissing('costs.supplier');
        $costs = $booking->costs->where('is_revenue', false);

        if ($costs->isEmpty()) {
            throw new RuntimeException("لا يوجد بنود تكلفة لترحيلها على الحجز {$booking->booking_number}");
        }

        // Aggregate: ['expenseAccountId' => sum, ...] and ['supplierAccountId' => sum, ...]
        // إذا التكلفة مرتبطة بمورد محدد → نستخدم حساب الأب الخاص بنوع
        // المورد (ما زال aggregate على مستوى الحساب الأب، لكن بدقة أكبر).
        // إذا لا → نستخدم mapping الافتراضي بناءً على category.
        $debitsByAccount  = [];
        $creditsByAccount = [];
        $accountModels    = [];

        foreach ($costs as $cost) {
            $amount = (float) $cost->amount_egp;
            if ($amount <= 0) continue;

            $expense = $this->mappings->expenseAccountForCostCategory($cost->category);

            if ($cost->supplier_id && $cost->supplier) {
                $supplier = $cost->supplier->parentAccountModel()
                    ?? $this->mappings->supplierAccountForCostCategory($cost->category);
            } else {
                $supplier = $this->mappings->supplierAccountForCostCategory($cost->category);
            }

            $debitsByAccount[$expense->id]   = ($debitsByAccount[$expense->id]   ?? 0) + $amount;
            $creditsByAccount[$supplier->id] = ($creditsByAccount[$supplier->id] ?? 0) + $amount;
            $accountModels[$expense->id]     = $expense;
            $accountModels[$supplier->id]    = $supplier;
        }

        if (empty($debitsByAccount)) {
            throw new RuntimeException("جميع بنود التكلفة قيمتها صفر — لا شيء للترحيل");
        }

        return DB::transaction(function () use ($booking, $debitsByAccount, $creditsByAccount, $accountModels) {
            $entry = JournalEntry::create([
                'date'        => now()->toDateString(),
                'description' => "إقفال تكاليف حجز {$booking->booking_number} — {$booking->type_label}",
                'reference'   => $booking->booking_number,
                'source_type' => 'booking_cost',
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

    public function cancelClosingJournal(ReligiousBooking $booking, string $reason): void
    {
        if (! $booking->cost_journal_entry_id) return;

        $entry = JournalEntry::find($booking->cost_journal_entry_id);
        if (! $entry || ! $entry->isPosted()) return;

        $this->journals->cancel($entry, $reason);
    }
}
