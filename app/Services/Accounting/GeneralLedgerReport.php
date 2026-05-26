<?php

namespace App\Services\Accounting;

use App\Models\Account;
use App\Models\JournalLine;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Support\Collection;

/**
 * Detailed General Ledger (دفتر الأستاذ التفصيلي) for a single account.
 *
 * For the given account and [from..to] window:
 *   - opening balance: natural-side balance up to (from - 1 day) including opening_balance
 *   - movements: every posted journal line in the window, in date+id order
 *   - each line carries a running_balance computed forward from opening
 *   - closing balance: opening + period_debit - period_credit (natural-side)
 *
 * Running balance sign convention:
 *   debit-natured (asset/expense):  running = prev + line.debit - line.credit
 *   credit-natured (liab/equity/rev): running = prev + line.credit - line.debit
 *
 * Result: positive running balance always = "natural" state for the account.
 */
class GeneralLedgerReport
{
    public function __construct(private readonly BalanceCalculator $calc) {}

    /**
     * @return array{
     *   account: Account,
     *   from: CarbonImmutable, to: CarbonImmutable,
     *   opening: float,
     *   closing: float,
     *   period_debit: float,
     *   period_credit: float,
     *   lines: Collection,
     * }
     */
    public function build(Account $account, ?DateTimeInterface $from = null, ?DateTimeInterface $to = null): array
    {
        $from = $from ? CarbonImmutable::instance($from)->startOfDay() : CarbonImmutable::now()->startOfMonth();
        $to   = $to   ? CarbonImmutable::instance($to)->endOfDay()     : CarbonImmutable::now()->endOfDay();

        // Opening balance = natural-side balance as of (from - 1 day)
        $opening = $this->calc->naturalBalance($account, $from->subDay()->endOfDay());

        // All posted lines in the window, joined with entry for date+number ordering
        $lines = JournalLine::query()
            ->where('account_id', $account->id)
            ->whereHas('entry', function ($q) use ($from, $to) {
                $q->where('status', 'posted')
                  ->whereDate('date', '>=', $from)
                  ->whereDate('date', '<=', $to);
            })
            ->with('entry:id,number,date,description,reference,source_type')
            ->get()
            ->sortBy([
                fn ($a, $b) => $a->entry->date <=> $b->entry->date,
                fn ($a, $b) => strcmp($a->entry->number, $b->entry->number),
                fn ($a, $b) => strcmp($a->id, $b->id),
            ])
            ->values();

        $running = $opening;
        $periodDebit  = 0.0;
        $periodCredit = 0.0;

        $lines->transform(function (JournalLine $line) use (&$running, $account, &$periodDebit, &$periodCredit) {
            $debit  = (float) $line->debit;
            $credit = (float) $line->credit;

            $periodDebit  += $debit;
            $periodCredit += $credit;

            $running += $account->normal_side === 'debit'
                ? ($debit - $credit)
                : ($credit - $debit);

            $line->running_balance = round($running, 2);
            return $line;
        });

        return [
            'account'       => $account,
            'from'          => $from,
            'to'            => $to,
            'opening'       => round($opening, 2),
            'closing'       => round($running, 2),
            'period_debit'  => round($periodDebit, 2),
            'period_credit' => round($periodCredit, 2),
            'lines'         => $lines,
        ];
    }
}
