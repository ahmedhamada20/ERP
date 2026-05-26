<?php

namespace App\Services\Payroll;

use App\Models\DomesticBooking;
use App\Models\Employee;
use App\Models\ReligiousBooking;
use Illuminate\Support\Collection;

/**
 * Computes commission for ONE employee in ONE month, returning both the
 * total amount and a per-booking breakdown the caller turns into
 * payslip_lines.
 *
 * Period filter: booking_date (NOT trip_date) — commission accrues when
 * the sale is made, not when the trip happens.
 *
 * Basis (from employee.effectiveCommissionBasis()):
 *   - 'selling_price' → commission = sum(selling_price) × rate
 *   - 'net_profit'    → commission = sum(net_profit) × rate
 *
 * Per-booking lines preserve the audit trail: which booking, what
 * rate, what base value. This is what makes the deduction reviewable.
 */
class CommissionCalculator
{
    /**
     * @return array{total: float, lines: Collection}
     *   lines = [ ['booking' => Model, 'amount' => float, 'rate' => float, 'base' => float, 'description' => string], ... ]
     */
    public function calculateForEmployee(
        Employee $employee,
        int $year,
        int $month
    ): array {
        $rate  = $employee->effectiveCommissionRate();
        $basis = $employee->effectiveCommissionBasis();

        if ($rate <= 0) {
            return ['total' => 0.0, 'lines' => collect()];
        }

        $eligibleStatuses = config('payroll.commission.eligible_booking_statuses');

        $religious = ReligiousBooking::query()
            ->where('sales_employee_id', $employee->id)
            ->whereYear('booking_date', $year)
            ->whereMonth('booking_date', $month)
            ->whereIn('status', $eligibleStatuses)
            ->get();

        $domestic = DomesticBooking::query()
            ->where('sales_employee_id', $employee->id)
            ->whereYear('booking_date', $year)
            ->whereMonth('booking_date', $month)
            ->whereIn('status', $eligibleStatuses)
            ->get();

        $lines = collect();
        $total = 0.0;

        foreach ($religious as $b) {
            $base = $basis === 'selling_price' ? (float) $b->selling_price : (float) $b->net_profit;
            $amt  = round($base * ($rate / 100), 2);
            if ($amt <= 0) continue;

            $lines->push([
                'booking'     => $b,
                'amount'      => $amt,
                'rate'        => $rate,
                'base'        => $base,
                'description' => 'عمولة حجز ديني ' . $b->booking_number,
            ]);
            $total += $amt;
        }

        foreach ($domestic as $b) {
            $base = $basis === 'selling_price' ? (float) $b->selling_price : (float) $b->net_profit;
            $amt  = round($base * ($rate / 100), 2);
            if ($amt <= 0) continue;

            $lines->push([
                'booking'     => $b,
                'amount'      => $amt,
                'rate'        => $rate,
                'base'        => $base,
                'description' => 'عمولة حجز داخلي ' . $b->booking_number,
            ]);
            $total += $amt;
        }

        return [
            'total' => round($total, 2),
            'lines' => $lines,
        ];
    }
}
