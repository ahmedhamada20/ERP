<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DomesticBooking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DomesticReportController extends Controller
{
    /**
     * Profit & Loss per program — aggregates bookings by program with
     * revenue / cost / profit / margin. Falls back to "(بدون برنامج)" for
     * bookings created without a program template.
     */
    public function pnlByProgram(Request $request)
    {
        $filters = $this->parseFilters($request);

        $rows = $this->aggregateByProgram($filters);

        $totals = [
            'bookings'    => $rows->sum('bookings_count'),
            'revenue'     => $rows->sum('revenue'),
            'cost'        => $rows->sum('cost'),
            'profit'      => $rows->sum('profit'),
            'avg_margin'  => $rows->avg('margin_pct') ?? 0,
        ];

        return view('admin.domestic.reports.pnl_by_program', compact('rows', 'totals', 'filters'));
    }

    public function pnlByProgramExport(Request $request): StreamedResponse
    {
        $filters = $this->parseFilters($request);
        $rows    = $this->aggregateByProgram($filters);

        $filename = 'domestic-pnl-by-program-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            // UTF-8 BOM for Excel Arabic support
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['الكود', 'البرنامج', 'الوجهة', 'النوع', 'الحجوزات', 'الإيراد', 'التكلفة', 'الربح', 'هامش %']);
            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->program_code ?? '—',
                    $r->program_name ?? '(بدون برنامج)',
                    $r->destination_city ?? '—',
                    $r->program_type ?? '—',
                    $r->bookings_count,
                    number_format($r->revenue, 2, '.', ''),
                    number_format($r->cost, 2, '.', ''),
                    number_format($r->profit, 2, '.', ''),
                    number_format($r->margin_pct, 2, '.', ''),
                ]);
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function parseFilters(Request $request): array
    {
        return [
            'date_from'     => $request->input('date_from'),
            'date_to'       => $request->input('date_to'),
            'status_filter' => $request->input('status_filter'),
            'city_filter'   => $request->input('city_filter'),
            'type_filter'   => $request->input('type_filter'),
        ];
    }

    /**
     * Returns a Collection keyed numerically, each row has:
     *   program_id, program_code, program_name, program_type,
     *   destination_city, bookings_count, revenue, cost, profit, margin_pct.
     */
    private function aggregateByProgram(array $filters)
    {
        $query = DomesticBooking::query()
            ->leftJoin('domestic_programs', 'domestic_bookings.program_id', '=', 'domestic_programs.id')
            ->selectRaw('
                domestic_bookings.program_id,
                domestic_programs.code        AS program_code,
                domestic_programs.name        AS program_name,
                domestic_programs.type        AS program_type,
                domestic_bookings.destination_city,
                COUNT(*)                              AS bookings_count,
                COALESCE(SUM(selling_price), 0)       AS revenue,
                COALESCE(SUM(total_cost), 0)          AS cost,
                COALESCE(SUM(net_profit), 0)          AS profit
            ')
            ->where('domestic_bookings.status', '!=', 'cancelled')
            ->whereNull('domestic_bookings.deleted_at')
            ->groupBy(
                'domestic_bookings.program_id',
                'domestic_programs.code',
                'domestic_programs.name',
                'domestic_programs.type',
                'domestic_bookings.destination_city',
            );

        if ($filters['date_from']) $query->whereDate('domestic_bookings.trip_date', '>=', $filters['date_from']);
        if ($filters['date_to'])   $query->whereDate('domestic_bookings.trip_date', '<=', $filters['date_to']);
        if ($filters['status_filter']) $query->where('domestic_bookings.status', $filters['status_filter']);
        if ($filters['city_filter'])   $query->where('domestic_bookings.destination_city', $filters['city_filter']);
        if ($filters['type_filter'])   $query->where('domestic_bookings.type', $filters['type_filter']);

        return collect($query->get())
            ->map(function ($r) {
                $r->margin_pct = $r->revenue > 0 ? round(($r->profit / $r->revenue) * 100, 2) : 0;
                return $r;
            })
            ->sortByDesc('profit')
            ->values();
    }
}
