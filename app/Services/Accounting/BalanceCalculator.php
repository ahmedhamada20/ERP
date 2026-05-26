<?php

namespace App\Services\Accounting;

use App\Models\Account;
use App\Models\JournalLine;
use DateTimeInterface;
use Illuminate\Support\Carbon;

/**
 * Computes account balances from posted journal lines.
 *
 * Used by:
 *   - Cash/Bank balance display (Step 5)
 *   - Vouchers (Step 6-7) — show current balance before posting
 *   - Trial Balance (Step 10)
 *   - General Ledger Detail (Step 12)
 *
 * Conventions:
 *   - Only `status='posted'` entries count (drafts and cancelled excluded).
 *   - Opening balance is interpreted as already-natural-side: positive means
 *     normal balance for the account's type.
 *   - Date filters compare against `journal_entries.date` (accounting date),
 *     NOT `created_at` (entry-keying timestamp).
 */
class BalanceCalculator
{
    /**
     * Raw debit and credit totals for an account from posted entries.
     *
     * @return array{debit:float, credit:float}
     */
    public function rawSums(string $accountId, ?DateTimeInterface $from = null, ?DateTimeInterface $to = null): array
    {
        $q = JournalLine::query()
            ->where('account_id', $accountId)
            ->whereHas('entry', function ($e) use ($from, $to) {
                $e->where('status', 'posted');
                if ($from) $e->whereDate('date', '>=', Carbon::instance($from));
                if ($to)   $e->whereDate('date', '<=', Carbon::instance($to));
            });

        return [
            'debit'  => (float) $q->sum('debit'),
            'credit' => (float) $q->sum('credit'),
        ];
    }

    /**
     * Natural-side balance of an account, INCLUDING opening_balance.
     *
     * Debit-natured (asset/expense):  balance = opening + debit - credit
     * Credit-natured (liability/equity/revenue): balance = opening + credit - debit
     *
     * Result is always positive for "normal" account state.
     */
    public function naturalBalance(Account $account, ?DateTimeInterface $upTo = null): float
    {
        $sums = $this->rawSums($account->id, null, $upTo);
        $opening = (float) $account->opening_balance;

        return $account->normal_side === 'debit'
            ? $opening + $sums['debit'] - $sums['credit']
            : $opening + $sums['credit'] - $sums['debit'];
    }

    /**
     * Signed (debit - credit) balance, no normal-side adjustment.
     * Useful for Trial Balance display where we want raw direction.
     */
    public function signedBalance(string $accountId, ?DateTimeInterface $upTo = null): float
    {
        $sums = $this->rawSums($accountId, null, $upTo);
        return $sums['debit'] - $sums['credit'];
    }

    /**
     * Compute balances for many accounts in one query (avoids N+1 in reports).
     *
     * @param  array<int, string>  $accountIds
     * @return array<string, array{debit:float, credit:float}>
     */
    public function rawSumsBulk(array $accountIds, ?DateTimeInterface $from = null, ?DateTimeInterface $to = null): array
    {
        if (empty($accountIds)) return [];

        $rows = JournalLine::query()
            ->whereIn('account_id', $accountIds)
            ->whereHas('entry', function ($e) use ($from, $to) {
                $e->where('status', 'posted');
                if ($from) $e->whereDate('date', '>=', Carbon::instance($from));
                if ($to)   $e->whereDate('date', '<=', Carbon::instance($to));
            })
            ->selectRaw('account_id, SUM(debit) AS d, SUM(credit) AS c')
            ->groupBy('account_id')
            ->get();

        $out = [];
        foreach ($accountIds as $id) {
            $row = $rows->firstWhere('account_id', $id);
            $out[$id] = [
                'debit'  => (float) ($row->d ?? 0),
                'credit' => (float) ($row->c ?? 0),
            ];
        }
        return $out;
    }
}
