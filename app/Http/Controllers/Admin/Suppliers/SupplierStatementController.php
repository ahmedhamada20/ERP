<?php

namespace App\Http\Controllers\Admin\Suppliers;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Services\Suppliers\SupplierStatementReport;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SupplierStatementController extends Controller
{
    public function index(Request $request, SupplierStatementReport $report)
    {
        $supplierId = $request->input('supplier_id');

        if (! $supplierId) {
            return view('admin.suppliers.statement.picker', [
                'suppliers' => Supplier::active()->orderBy('name')->get(['id', 'code', 'name', 'type']),
            ]);
        }

        $supplier = Supplier::findOrFail($supplierId);
        [$from, $to] = $this->datesFromRequest($request);

        $data = $report->build($supplier, $from, $to);

        return view('admin.suppliers.statement.detail', $data + [
            'suppliers' => Supplier::active()->orderBy('name')->get(['id', 'code', 'name', 'type']),
        ]);
    }

    public function print(Request $request, SupplierStatementReport $report)
    {
        $supplier = Supplier::findOrFail($request->input('supplier_id'));
        [$from, $to] = $this->datesFromRequest($request);
        $data = $report->build($supplier, $from, $to);

        return view('admin.suppliers.statement.print', $data);
    }

    public function downloadCsv(Request $request, SupplierStatementReport $report): StreamedResponse
    {
        $supplier = Supplier::findOrFail($request->input('supplier_id'));
        [$from, $to] = $this->datesFromRequest($request);
        $data = $report->build($supplier, $from, $to);

        $filename = 'supplier-' . $supplier->code . '-' . $data['from']->format('Y-m-d') . '-' . $data['to']->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($data) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, ['كشف حساب مورد']);
            fputcsv($out, ['المورد', $data['supplier']->code . ' — ' . $data['supplier']->name]);
            fputcsv($out, ['الفترة', $data['from']->format('Y-m-d') . ' إلى ' . $data['to']->format('Y-m-d')]);
            fputcsv($out, []);
            fputcsv($out, ['التاريخ', 'النوع', 'الرقم', 'البيان', 'المرجع', 'مدين (سداد)', 'دائن (فاتورة)', 'الرصيد']);

            fputcsv($out, ['', 'افتتاحي', '', '', '', '', '', number_format($data['opening'], 2, '.', '')]);

            foreach ($data['lines'] as $line) {
                fputcsv($out, [
                    $line->date->format('Y-m-d'),
                    $line->type === 'invoice' ? 'فاتورة' : 'سداد',
                    $line->number,
                    $line->description,
                    $line->reference ?? '',
                    $line->debit  > 0 ? number_format($line->debit,  2, '.', '') : '',
                    $line->credit > 0 ? number_format($line->credit, 2, '.', '') : '',
                    number_format($line->running_balance, 2, '.', ''),
                ]);
            }

            fputcsv($out, []);
            fputcsv($out, ['', 'الإجمالي', '', '', '',
                number_format($data['total_payments'], 2, '.', ''),
                number_format($data['total_invoices'], 2, '.', ''),
                '',
            ]);
            fputcsv($out, ['', 'الرصيد الختامي', '', '', '', '', '', number_format($data['closing'], 2, '.', '')]);

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** @return array{0:?\DateTime, 1:?\DateTime} */
    private function datesFromRequest(Request $request): array
    {
        return [$request->date('from'), $request->date('to')];
    }
}
