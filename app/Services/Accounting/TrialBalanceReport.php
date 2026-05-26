<?php

namespace App\Services\Accounting;

use App\Models\Account;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Support\Collection;

/**
 * Builds the Trial Balance (ميزان المراجعة).
 *
 * For each postable account, computes:
 *   opening_signed  — opening balance converted to debit-direction (+ for asset/expense)
 *   period_debit    — sum of debits in posted journal lines up to `as_of`
 *   period_credit   — sum of credits in same window
 *   net_signed      — opening_signed + period_debit - period_credit
 *   debit_column    — net_signed if positive, else 0
 *   credit_column   — abs(net_signed) if negative, else 0
 *
 * Invariant: Σ debit_column == Σ credit_column (within rounding).
 */
class TrialBalanceReport
{
    public function __construct(private readonly BalanceCalculator $calc) {}

    /**
     * @return array{
     *   as_of: CarbonImmutable,
     *   rows: Collection,
     *   totals: array{debit:float, credit:float, balanced:bool, diff:float},
     *   grouped: Collection,
     * }
     */
    public function build(?DateTimeInterface $asOf = null, bool $includeZero = false): array
    {
        $asOf = $asOf ? CarbonImmutable::instance($asOf) : CarbonImmutable::now();

        $accounts = Account::query()
            ->where('is_group', false)
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'type', 'sub_type', 'opening_balance', 'is_active']);

        $sums = $this->calc->rawSumsBulk($accounts->pluck('id')->all(), null, $asOf);

        $rows = $accounts->map(function (Account $a) use ($sums) {
            $debit  = (float) ($sums[$a->id]['debit']  ?? 0);
            $credit = (float) ($sums[$a->id]['credit'] ?? 0);

            // Convert opening_balance (always natural-side positive) to signed
            // debit direction so we can add it to debit-credit arithmetic.
            $openingSigned = $a->normal_side === 'debit'
                ? (float) $a->opening_balance
                : -(float) $a->opening_balance;

            $netSigned = $openingSigned + $debit - $credit;

            return (object) [
                'id'             => $a->id,
                'code'           => $a->code,
                'name'           => $a->name,
                'type'           => $a->type,
                'type_label'     => $a->type_label,
                'opening_signed' => $openingSigned,
                'period_debit'   => $debit,
                'period_credit'  => $credit,
                'net_signed'     => $netSigned,
                'debit_column'   => $netSigned > 0 ? $netSigned : 0,
                'credit_column'  => $netSigned < 0 ? abs($netSigned) : 0,
                'is_active'      => $a->is_active,
            ];
        });

        if (! $includeZero) {
            $rows = $rows->filter(fn ($r) => abs($r->net_signed) > 0.01 || $r->period_debit > 0 || $r->period_credit > 0);
        }

        $rows = $rows->values();

        $totalDebit  = (float) $rows->sum('debit_column');
        $totalCredit = (float) $rows->sum('credit_column');
        $diff        = $totalDebit - $totalCredit;

        // Group rows by major type for sectioned display
        $typeOrder = ['asset' => 1, 'liability' => 2, 'equity' => 3, 'revenue' => 4, 'expense' => 5];
        $grouped = $rows
            ->groupBy('type')
            ->sortBy(fn ($_, $type) => $typeOrder[$type] ?? 99);

        return [
            'as_of'   => $asOf,
            'rows'    => $rows,
            'grouped' => $grouped,
            'totals'  => [
                'debit'    => $totalDebit,
                'credit'   => $totalCredit,
                'diff'     => $diff,
                'balanced' => abs($diff) < 0.01,
            ],
        ];
    }
}
