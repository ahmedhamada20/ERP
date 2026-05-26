<?php

namespace App\Services\Suppliers;

use App\Models\Supplier;
use App\Models\SupplierInvoice;
use App\Models\Voucher;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Support\Collection;

/**
 * Suppliers Outstanding + Aging report (تقرير أعمار ديون الموردين).
 *
 * For each supplier with a positive outstanding balance, applies FIFO payment
 * allocation to determine which invoices are still unpaid, then ages each
 * remaining unpaid amount based on its effective due date.
 *
 * Allocation order (oldest first):
 *   1. supplier.opening_balance
 *   2. posted invoices, sorted by invoice_date asc
 *
 * Effective due date = invoice.due_date ?? (invoice_date + supplier.payment_terms_days)
 *
 * Bucket boundaries are days past the effective due date:
 *   current  (0 days overdue or not yet due)
 *   1-30, 31-60, 61-90, 91-120, 120+
 */
class SupplierAgingReport
{
    public const BUCKETS = ['current', 'd_1_30', 'd_31_60', 'd_61_90', 'd_91_120', 'd_120_plus'];

    /**
     * @return array{
     *   as_of: CarbonImmutable,
     *   rows: Collection,
     *   totals: array<string, float>,
     *   grand_total: float,
     * }
     */
    public function build(?DateTimeInterface $asOf = null): array
    {
        $asOf = $asOf ? CarbonImmutable::instance($asOf) : CarbonImmutable::now()->endOfDay();

        $suppliers = Supplier::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $rows = collect();
        $totals = array_fill_keys(self::BUCKETS, 0.0);
        $totals['outstanding'] = 0.0;

        foreach ($suppliers as $supplier) {
            $supplierRow = $this->buildSupplierRow($supplier, $asOf);
            if ($supplierRow['outstanding'] <= 0.01) continue;

            $rows->push($supplierRow);
            foreach (self::BUCKETS as $bucket) {
                $totals[$bucket] += $supplierRow[$bucket];
            }
            $totals['outstanding'] += $supplierRow['outstanding'];
        }

        return [
            'as_of'       => $asOf,
            'rows'        => $rows->sortByDesc('outstanding')->values(),
            'totals'      => $totals,
            'grand_total' => $totals['outstanding'],
        ];
    }

    /**
     * Compute one supplier's aging breakdown.
     *
     * @return array{supplier:Supplier, outstanding:float, current:float, d_1_30:float,
     *                d_31_60:float, d_61_90:float, d_91_120:float, d_120_plus:float}
     */
    private function buildSupplierRow(Supplier $supplier, CarbonImmutable $asOf): array
    {
        $row = [
            'supplier'    => $supplier,
            'outstanding' => 0.0,
            'current'     => 0.0,
            'd_1_30'      => 0.0,
            'd_31_60'     => 0.0,
            'd_61_90'     => 0.0,
            'd_91_120'    => 0.0,
            'd_120_plus'  => 0.0,
        ];

        $invoices = SupplierInvoice::query()
            ->where('supplier_id', $supplier->id)
            ->posted()
            ->whereDate('invoice_date', '<=', $asOf)
            ->orderBy('invoice_date')
            ->orderBy('id')
            ->get(['id', 'invoice_date', 'due_date', 'amount_egp']);

        $paymentsTotal = (float) Voucher::query()
            ->where('supplier_id', $supplier->id)
            ->where('type', 'payment')
            ->posted()
            ->whereDate('date', '<=', $asOf)
            ->sum('amount_egp');

        $pool = $paymentsTotal;

        // 1) Pay down opening balance first (if positive — meaning we owed at startup)
        $opening = (float) $supplier->opening_balance;
        if ($opening > 0) {
            $applied = min($opening, $pool);
            $pool -= $applied;
            $openingRemaining = $opening - $applied;
            if ($openingRemaining > 0.01) {
                // Opening balance is conventionally "120+" since we don't have a date for it
                $row['d_120_plus'] += $openingRemaining;
                $row['outstanding'] += $openingRemaining;
            }
        }

        // 2) Pay down invoices oldest first
        foreach ($invoices as $invoice) {
            $amount  = (float) $invoice->amount_egp;
            $applied = min($amount, $pool);
            $pool   -= $applied;
            $remaining = $amount - $applied;
            if ($remaining <= 0.01) continue;

            // Effective due date
            $effectiveDue = $invoice->due_date
                ? CarbonImmutable::instance($invoice->due_date)
                : CarbonImmutable::instance($invoice->invoice_date)->addDays($supplier->payment_terms_days);

            $daysOverdue = $asOf->greaterThan($effectiveDue)
                ? (int) $effectiveDue->diffInDays($asOf)
                : 0;

            $bucket = $this->bucketFor($daysOverdue);
            $row[$bucket]      += $remaining;
            $row['outstanding'] += $remaining;
        }

        return $row;
    }

    private function bucketFor(int $daysOverdue): string
    {
        return match (true) {
            $daysOverdue === 0         => 'current',
            $daysOverdue <= 30         => 'd_1_30',
            $daysOverdue <= 60         => 'd_31_60',
            $daysOverdue <= 90         => 'd_61_90',
            $daysOverdue <= 120        => 'd_91_120',
            default                    => 'd_120_plus',
        };
    }

    public static function bucketLabel(string $bucket): string
    {
        return match ($bucket) {
            'current'    => 'حالي',
            'd_1_30'     => '1-30 يوم',
            'd_31_60'    => '31-60 يوم',
            'd_61_90'    => '61-90 يوم',
            'd_91_120'   => '91-120 يوم',
            'd_120_plus' => '+120 يوم',
            default      => $bucket,
        };
    }
}
