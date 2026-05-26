<?php

namespace App\Http\Controllers\Admin\Suppliers;

use App\Http\Controllers\Controller;
use App\Services\Suppliers\SupplierAgingReport;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SupplierAgingController extends Controller
{
    public function index(Request $request, SupplierAgingReport $report)
    {
        $asOf = $request->date('as_of') ?: now()->endOfDay();
        $data = $report->build($asOf);

        return view('admin.suppliers.aging.index', $data);
    }

    public function print(Request $request, SupplierAgingReport $report)
    {
        $asOf = $request->date('as_of') ?: now()->endOfDay();
        $data = $report->build($asOf);

        return view('admin.suppliers.aging.print', $data);
    }

    public function downloadCsv(Request $request, SupplierAgingReport $report): StreamedResponse
    {
        $asOf = $request->date('as_of') ?: now()->endOfDay();
        $data = $report->build($asOf);

        $filename = 'suppliers-aging-' . $data['as_of']->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($data) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, ['تقرير أعمار ديون الموردين']);
            fputcsv($out, ['كما في', $data['as_of']->format('Y-m-d')]);
            fputcsv($out, []);
            fputcsv($out, ['المورد', 'النوع', 'إجمالي مستحق',
                          'حالي', '1-30', '31-60', '61-90', '91-120', '+120']);

            foreach ($data['rows'] as $row) {
                fputcsv($out, [
                    $row['supplier']->code . ' — ' . $row['supplier']->name,
                    $row['supplier']->type_label,
                    number_format($row['outstanding'], 2, '.', ''),
                    number_format($row['current'],     2, '.', ''),
                    number_format($row['d_1_30'],      2, '.', ''),
                    number_format($row['d_31_60'],     2, '.', ''),
                    number_format($row['d_61_90'],     2, '.', ''),
                    number_format($row['d_91_120'],    2, '.', ''),
                    number_format($row['d_120_plus'],  2, '.', ''),
                ]);
            }

            fputcsv($out, []);
            fputcsv($out, [
                'الإجمالي العام', '',
                number_format($data['totals']['outstanding'], 2, '.', ''),
                number_format($data['totals']['current'],     2, '.', ''),
                number_format($data['totals']['d_1_30'],      2, '.', ''),
                number_format($data['totals']['d_31_60'],     2, '.', ''),
                number_format($data['totals']['d_61_90'],     2, '.', ''),
                number_format($data['totals']['d_91_120'],    2, '.', ''),
                number_format($data['totals']['d_120_plus'],  2, '.', ''),
            ]);

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
