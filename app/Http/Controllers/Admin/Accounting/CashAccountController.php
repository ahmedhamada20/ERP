<?php

namespace App\Http\Controllers\Admin\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\JournalLine;
use App\Services\Accounting\BalanceCalculator;

class CashAccountController extends Controller
{
    public function index(BalanceCalculator $calc)
    {
        $accounts = Account::query()
            ->whereIn('sub_type', ['cash', 'bank'])
            ->where('is_active', true)
            ->orderBy('sub_type')
            ->orderBy('code')
            ->get();

        // Compute balances in one query (no N+1)
        $sums = $calc->rawSumsBulk($accounts->pluck('id')->all());

        $accounts->each(function (Account $a) use ($sums) {
            $row = $sums[$a->id] ?? ['debit' => 0, 'credit' => 0];
            // Cash/bank are asset accounts → debit-natured
            $a->current_balance = (float) $a->opening_balance + $row['debit'] - $row['credit'];
            $a->total_in        = $row['debit'];
            $a->total_out       = $row['credit'];
        });

        $totals = [
            'cash_count'  => $accounts->where('sub_type', 'cash')->count(),
            'bank_count'  => $accounts->where('sub_type', 'bank')->count(),
            'cash_total'  => $accounts->where('sub_type', 'cash')->sum('current_balance'),
            'bank_total'  => $accounts->where('sub_type', 'bank')->sum('current_balance'),
        ];

        return view('admin.accounting.cash.index', compact('accounts', 'totals'));
    }

    public function show(Account $account, BalanceCalculator $calc)
    {
        abort_unless(in_array($account->sub_type, ['cash', 'bank']), 404);

        // Last 50 posted movements, newest first
        $movements = JournalLine::query()
            ->where('account_id', $account->id)
            ->whereHas('entry', fn ($e) => $e->where('status', 'posted'))
            ->with('entry:id,number,date,description,reference')
            ->orderByDesc(JournalLine::select('date')
                ->from('journal_entries')
                ->whereColumn('journal_entries.id', 'journal_lines.journal_entry_id'))
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        // Walk forward to compute running balance for the displayed slice
        $sums         = $calc->rawSums($account->id);
        $opening      = (float) $account->opening_balance;
        $totalDebit   = $sums['debit'];
        $totalCredit  = $sums['credit'];
        $current      = $opening + $totalDebit - $totalCredit;

        return view('admin.accounting.cash.show', compact(
            'account', 'movements', 'opening', 'totalDebit', 'totalCredit', 'current'
        ));
    }
}
