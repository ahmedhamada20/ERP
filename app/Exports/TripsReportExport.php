<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Trips report Excel export — تصدير حصر الرحلات Excel.
 *
 * Uses the FromView strategy so we can format cells with styling
 * in a Blade view, then auto-size + apply header styles.
 */
class TripsReportExport implements FromView, ShouldAutoSize, WithStyles
{
    public function __construct(
        private Collection $bookings,
        private array $totals,
        private array $filters
    ) {}

    public function view(): View
    {
        return view('admin.religious.reports.trips_export', [
            'bookings' => $this->bookings,
            'totals'   => $this->totals,
            'filters'  => $this->filters,
        ]);
    }

    public function styles(Worksheet $sheet)
    {
        // Detect last column dynamically
        $highestColumn = $sheet->getHighestColumn();

        // Sheet right-to-left for Arabic
        $sheet->setRightToLeft(true);

        // Header row styling (row 1 in the view)
        $headerRange = "A1:{$highestColumn}1";
        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => [
                'bold'  => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size'  => 11,
            ],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '0F172A'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
            ],
        ]);

        // Set min row height
        $sheet->getRowDimension(1)->setRowHeight(28);

        return [];
    }
}
