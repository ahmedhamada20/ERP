<?php

namespace App\Services\Accounting;

use App\Models\Account;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Support\Collection;

/**
 * Builds the Profit & Loss statement (قائمة الدخل) for a date range.
 *
 * Structure (Egyptian SAS-compatible):
 *   Revenue (إيرادات)            Σ
 *   - Cost of Services           Σ
 *   = Gross Profit
 *   - Operating Expenses         Σ
 *   - Other Expenses             Σ
 *   = Net Profit
 *
 * Per-account amount is the NATURAL-SIDE period balance:
 *   revenue accounts: credits - debits  (positive = earned revenue)
 *   expense accounts: debits - credits  (positive = incurred expense)
 *
 * Only POSTED journal entries within the date range count.
 */
class PnlReport
{
    public function __construct(private readonly BalanceCalculator $calc) {}

    /**
     * @return array{
     *   from: CarbonImmutable, to: CarbonImmutable,
     *   revenue: array{rows: Collection, total: float},
     *   cost_of_services: array{rows: Collection, total: float},
     *   operating_expense: array{rows: Collection, total: float},
     *   other_expense: array{rows: Collection, total: float},
     *   gross_profit: float,
     *   net_profit: float,
     *   gross_margin: float,
     *   net_margin: float,
     * }
     */
    public function build(?DateTimeInterface $from, ?DateTimeInterface $to): array
    {
        $from = $from ? CarbonImmutable::instance($from)->startOfDay() : CarbonImmutable::now()->startOfMonth();
        $to   = $to   ? CarbonImmutable::instance($to)->endOfDay()     : CarbonImmutable::now()->endOfMonth();

        $accounts = Account::query()
            ->where('is_group', false)
            ->whereIn('type', ['revenue', 'expense'])
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'type', 'sub_type']);

        $sums = $this->calc->rawSumsBulk($accounts->pluck('id')->all(), $from, $to);

        $accounts->each(function (Account $a) use ($sums) {
            $debit  = (float) ($sums[$a->id]['debit']  ?? 0);
            $credit = (float) ($sums[$a->id]['credit'] ?? 0);

            // Natural-side amount: positive when the account behaves as expected.
            $a->amount = $a->type === 'revenue'
                ? ($credit - $debit)
                : ($debit  - $credit);
        });

        $bucket = fn (string ...$subTypes) =>
            $accounts->filter(fn ($a) => in_array($a->sub_type, $subTypes, true) && abs($a->amount) > 0.01)->values();

        $revenue = $bucket('operating_revenue', 'other_revenue');
        $cos     = $bucket('cost_of_services');
        $opex    = $bucket('operating_expense');
        $other   = $bucket('other_expense');

        $totalRev   = (float) $revenue->sum('amount');
        $totalCos   = (float) $cos->sum('amount');
        $totalOpex  = (float) $opex->sum('amount');
        $totalOther = (float) $other->sum('amount');

        $grossProfit = $totalRev - $totalCos;
        $netProfit   = $grossProfit - $totalOpex - $totalOther;

        return [
            'from'              => $from,
            'to'                => $to,
            'revenue'           => ['rows' => $revenue, 'total' => $totalRev],
            'cost_of_services'  => ['rows' => $cos,     'total' => $totalCos],
            'operating_expense' => ['rows' => $opex,    'total' => $totalOpex],
            'other_expense'     => ['rows' => $other,   'total' => $totalOther],
            'gross_profit'      => $grossProfit,
            'net_profit'        => $netProfit,
            'gross_margin'      => $totalRev > 0 ? round(($grossProfit / $totalRev) * 100, 2) : 0,
            'net_margin'        => $totalRev > 0 ? round(($netProfit   / $totalRev) * 100, 2) : 0,
        ];
    }
}
