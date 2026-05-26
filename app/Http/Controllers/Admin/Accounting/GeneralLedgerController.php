<?php

namespace App\Http\Controllers\Admin\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Services\Accounting\GeneralLedgerReport;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GeneralLedgerController extends Controller
{
    /**
     * If `account_id` is in the query, render the detailed ledger.
     * Otherwise render the account picker.
     */
    public function index(Request $request, GeneralLedgerReport $report)
    {
        $accountId = $request->input('account_id');

        if (! $accountId) {
            return view('admin.accounting.reports.gl_picker', [
                'accounts' => $this->postableAccounts(),
            ]);
        }

        $account = Account::query()
            ->where('is_group', false)
            ->findOrFail($accountId);

        [$from, $to] = $this->datesFromRequest($request);
        $data = $report->build($account, $from, $to);

        return view('admin.accounting.reports.gl_detail', $data + [
            'accounts' => $this->postableAccounts(),
        ]);
    }

    public function print(Request $request, GeneralLedgerReport $report)
    {
        $account = Account::query()->where('is_group', false)->findOrFail($request->input('account_id'));
        [$from, $to] = $this->datesFromRequest($request);
        $data = $report->build($account, $from, $to);

        return view('admin.accounting.reports.gl_print', $data);
    }

    public function downloadCsv(Request $request, GeneralLedgerReport $report): StreamedResponse
    {
        $account = Account::query()->where('is_group', false)->findOrFail($request->input('account_id'));
        [$from, $to] = $this->datesFromRequest($request);
        $data = $report->build($account, $from, $to);

        $filename = 'gl-' . $account->code . '-' . $data['from']->format('Y-m-d') . '-' . $data['to']->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($data) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, ['دفتر الأستاذ التفصيلي']);
            fputcsv($out, ['الحساب', $data['account']->code . ' — ' . $data['account']->name]);
            fputcsv($out, ['الفترة', $data['from']->format('Y-m-d') . ' إلى ' . $data['to']->format('Y-m-d')]);
            fputcsv($out, []);
            fputcsv($out, ['التاريخ', 'القيد', 'المرجع', 'البيان', 'مدين', 'دائن', 'الرصيد التراكمي']);

            fputcsv($out, ['', '', '', 'الرصيد الافتتاحي', '', '', number_format($data['opening'], 2, '.', '')]);

            foreach ($data['lines'] as $line) {
                fputcsv($out, [
                    $line->entry->date->format('Y-m-d'),
                    $line->entry->number,
                    $line->entry->reference ?? '',
                    $line->description ?: $line->entry->description,
                    (float) $line->debit  > 0 ? number_format($line->debit,  2, '.', '') : '',
                    (float) $line->credit > 0 ? number_format($line->credit, 2, '.', '') : '',
                    number_format($line->running_balance, 2, '.', ''),
                ]);
            }

            fputcsv($out, []);
            fputcsv($out, ['', '', '', 'الإجمالي',
                number_format($data['period_debit'],  2, '.', ''),
                number_format($data['period_credit'], 2, '.', ''),
                '',
            ]);
            fputcsv($out, ['', '', '', 'الرصيد الختامي', '', '', number_format($data['closing'], 2, '.', '')]);

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** @return array{0:?\DateTime, 1:?\DateTime} */
    private function datesFromRequest(Request $request): array
    {
        return [$request->date('from'), $request->date('to')];
    }

    private function postableAccounts()
    {
        return Account::query()
            ->where('is_group', false)
            ->where('is_active', true)
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'type']);
    }
}
