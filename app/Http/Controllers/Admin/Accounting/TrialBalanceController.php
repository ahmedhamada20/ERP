<?php

namespace App\Http\Controllers\Admin\Accounting;

use App\Http\Controllers\Controller;
use App\Services\Accounting\TrialBalanceReport;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TrialBalanceController extends Controller
{
    public function index(Request $request, TrialBalanceReport $report)
    {
        $asOf        = $request->date('as_of') ?: now()->endOfDay();
        $includeZero = $request->boolean('include_zero');

        $data = $report->build($asOf, $includeZero);

        return view('admin.accounting.reports.trial_balance', $data + [
            'includeZero' => $includeZero,
        ]);
    }

    public function print(Request $request, TrialBalanceReport $report)
    {
        $asOf = $request->date('as_of') ?: now()->endOfDay();
        $data = $report->build($asOf, $request->boolean('include_zero'));

        return view('admin.accounting.reports.trial_balance_print', $data);
    }

    public function downloadCsv(Request $request, TrialBalanceReport $report): StreamedResponse
    {
        $asOf = $request->date('as_of') ?: now()->endOfDay();
        $data = $report->build($asOf, $request->boolean('include_zero'));

        $filename = 'trial-balance-' . $asOf->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($data, $asOf) {
            $out = fopen('php://output', 'w');
            // BOM for Excel to render UTF-8 Arabic correctly
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, ['ميزان المراجعة كما في', $asOf->format('Y-m-d')]);
            fputcsv($out, []);
            fputcsv($out, ['الكود', 'الحساب', 'التصنيف', 'مدين', 'دائن']);

            foreach ($data['rows'] as $row) {
                fputcsv($out, [
                    $row->code,
                    $row->name,
                    $row->type_label,
                    $row->debit_column  > 0 ? number_format($row->debit_column,  2, '.', '') : '',
                    $row->credit_column > 0 ? number_format($row->credit_column, 2, '.', '') : '',
                ]);
            }

            fputcsv($out, []);
            fputcsv($out, ['', '', 'الإجمالي',
                number_format($data['totals']['debit'],  2, '.', ''),
                number_format($data['totals']['credit'], 2, '.', ''),
            ]);
            fputcsv($out, ['', '', $data['totals']['balanced'] ? 'متوازن ✓' : 'فرق: ' . number_format($data['totals']['diff'], 2)]);

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
