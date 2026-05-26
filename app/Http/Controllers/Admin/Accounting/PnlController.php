<?php

namespace App\Http\Controllers\Admin\Accounting;

use App\Http\Controllers\Controller;
use App\Services\Accounting\PnlReport;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PnlController extends Controller
{
    public function index(Request $request, PnlReport $report)
    {
        [$from, $to] = $this->datesFromRequest($request);
        $data = $report->build($from, $to);

        return view('admin.accounting.reports.pnl', $data);
    }

    public function print(Request $request, PnlReport $report)
    {
        [$from, $to] = $this->datesFromRequest($request);
        $data = $report->build($from, $to);

        return view('admin.accounting.reports.pnl_print', $data);
    }

    public function downloadCsv(Request $request, PnlReport $report): StreamedResponse
    {
        [$from, $to] = $this->datesFromRequest($request);
        $data = $report->build($from, $to);

        $filename = 'pnl-' . $data['from']->format('Y-m-d') . '-to-' . $data['to']->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($data) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, ['قائمة الدخل من', $data['from']->format('Y-m-d'), 'إلى', $data['to']->format('Y-m-d')]);
            fputcsv($out, []);
            fputcsv($out, ['الكود', 'البند', 'القيمة']);

            $section = function (string $title, $rows, float $total) use ($out) {
                fputcsv($out, []);
                fputcsv($out, [$title]);
                foreach ($rows as $r) {
                    fputcsv($out, [$r->code, $r->name, number_format($r->amount, 2, '.', '')]);
                }
                fputcsv($out, ['', 'الإجمالي', number_format($total, 2, '.', '')]);
            };

            $section('الإيرادات',           $data['revenue']['rows'],          $data['revenue']['total']);
            $section('تكلفة الخدمات',       $data['cost_of_services']['rows'], $data['cost_of_services']['total']);
            fputcsv($out, []);
            fputcsv($out, ['', 'مجمل الربح', number_format($data['gross_profit'], 2, '.', '')]);

            $section('مصروفات تشغيلية',     $data['operating_expense']['rows'], $data['operating_expense']['total']);
            $section('مصروفات أخرى',        $data['other_expense']['rows'],     $data['other_expense']['total']);

            fputcsv($out, []);
            fputcsv($out, ['', 'صافي الربح', number_format($data['net_profit'], 2, '.', '')]);
            fputcsv($out, ['', 'هامش الربح %', number_format($data['net_margin'], 2)]);

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** @return array{0:?\DateTime, 1:?\DateTime} */
    private function datesFromRequest(Request $request): array
    {
        $from = $request->date('from');
        $to   = $request->date('to');
        return [$from, $to];
    }
}
