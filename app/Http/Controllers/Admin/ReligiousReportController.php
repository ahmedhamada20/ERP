<?php

namespace App\Http\Controllers\Admin;

use App\Exports\TripsReportExport;
use App\Http\Controllers\Controller;
use App\Models\ReligiousBooking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Trip-summary report (حصر الرحلات) — مطابق للصورة المرفقة من العميل.
 *
 * Aggregates: rooms, pilgrims, flight tickets, visas, barcodes, sales,
 * cost, profit, employee commissions.
 *
 * Filters: date range, type (hajj/umrah), program, status, manager/employee.
 */
class ReligiousReportController extends Controller
{
    public function trips(Request $request)
    {
        $filters = $request->only(['date_from', 'date_to', 'type', 'program_id', 'status', 'manager_id', 'employee_id']);

        // Single base query reused for both totals (all matching rows) and
        // paginated display (current page only). clone() preserves the
        // ->when() filters between the two consumers.
        $baseQuery = ReligiousBooking::query()
            ->with(['customer:id,full_name,phone', 'program:id,name', 'employee:id,name', 'manager:id,name'])
            ->when($filters['date_from'] ?? null, fn ($q, $v) => $q->whereDate('trip_date', '>=', $v))
            ->when($filters['date_to']   ?? null, fn ($q, $v) => $q->whereDate('trip_date', '<=', $v))
            ->when($filters['type']      ?? null, fn ($q, $v) => $q->where('type', $v))
            ->when($filters['program_id'] ?? null, fn ($q, $v) => $q->where('program_id', $v))
            ->when($filters['status']    ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($filters['manager_id'] ?? null, fn ($q, $v) => $q->where('responsible_manager_id', $v))
            ->when($filters['employee_id'] ?? null, fn ($q, $v) => $q->where('responsible_employee_id', $v))
            ->orderByDesc('trip_date');

        // Paginate the display rows
        $bookings = (clone $baseQuery)->paginate(25)->withQueryString();

        // Compute totals from ALL matching rows (not just the current page)
        $bookingIds = (clone $baseQuery)->reorder()->pluck('id');
        $bookingsCount = $bookingIds->count();

        $roomsTotal = DB::table('booking_accommodations')
            ->whereIn('booking_id', $bookingIds)
            ->sum('rooms_count');

        $flightTicketsTotal = DB::table('booking_transportation')
            ->whereIn('booking_id', $bookingIds)
            ->where('type', 'flight')
            ->sum('pax_count');

        $visasTotal = DB::table('booking_pilgrims')
            ->whereIn('booking_id', $bookingIds)
            ->where('visa_status', 'issued')
            ->count();

        $barcodesTotal = DB::table('booking_pilgrims')
            ->whereIn('booking_id', $bookingIds)
            ->whereNotNull('safa_barcode')
            ->count();

        // Money / pax totals are aggregated in SQL (not from a paginated collection)
        // so they reflect the FULL filtered set, not just the current page.
        $moneyAgg = DB::table('religious_bookings')
            ->whereIn('id', $bookingIds)
            ->where('status', '!=', 'cancelled')
            ->selectRaw('SUM(selling_price) sales, SUM(total_cost) cost, SUM(net_profit) profit')
            ->first();

        $paxAgg = DB::table('religious_bookings')
            ->whereIn('id', $bookingIds)
            ->selectRaw('SUM(adults_count + children_count) total_pax')
            ->value('total_pax');

        $totals = [
            'bookings_count'  => $bookingsCount,
            'pilgrims_total'  => (int) $paxAgg,
            'rooms_total'     => (int) $roomsTotal,
            'flight_tickets'  => (int) $flightTicketsTotal,
            'visas_issued'    => (int) $visasTotal,
            'barcodes_total'  => (int) $barcodesTotal,
            'sales_total'     => (float) ($moneyAgg->sales  ?? 0),
            'cost_total'      => (float) ($moneyAgg->cost   ?? 0),
            'profit_total'    => (float) ($moneyAgg->profit ?? 0),
            'commissions_total' => (float) DB::table('booking_costs')
                ->whereIn('booking_id', $bookingIds)
                ->where('category', 'commission')
                ->sum('amount_egp'),
        ];

        $programs  = DB::table('religious_programs')->select('id', 'name')->orderBy('name')->get();
        $employees = DB::table('users')->select('id', 'name')->orderBy('name')->get();

        return view('admin.religious.reports.trips', compact('bookings', 'totals', 'filters', 'programs', 'employees'));
    }

    /**
     * Same filters as trips(), but return an Excel file instead of a view.
     */
    public function tripsExport(Request $request)
    {
        $filters = $request->only(['date_from', 'date_to', 'type', 'program_id', 'status', 'manager_id', 'employee_id']);

        $bookings = ReligiousBooking::query()
            ->with(['customer:id,full_name,phone', 'program:id,name', 'employee:id,name', 'manager:id,name'])
            ->when($filters['date_from'] ?? null, fn ($q, $v) => $q->whereDate('trip_date', '>=', $v))
            ->when($filters['date_to']   ?? null, fn ($q, $v) => $q->whereDate('trip_date', '<=', $v))
            ->when($filters['type']      ?? null, fn ($q, $v) => $q->where('type', $v))
            ->when($filters['program_id'] ?? null, fn ($q, $v) => $q->where('program_id', $v))
            ->when($filters['status']    ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($filters['manager_id'] ?? null, fn ($q, $v) => $q->where('responsible_manager_id', $v))
            ->when($filters['employee_id'] ?? null, fn ($q, $v) => $q->where('responsible_employee_id', $v))
            ->orderByDesc('trip_date')
            ->get();

        $totals = [
            'pilgrims_total' => $bookings->sum(fn ($b) => $b->adults_count + $b->children_count),
            'sales_total'    => (float) $bookings->where('status', '!=', 'cancelled')->sum('selling_price'),
            'cost_total'     => (float) $bookings->where('status', '!=', 'cancelled')->sum('total_cost'),
            'profit_total'   => (float) $bookings->where('status', '!=', 'cancelled')->sum('net_profit'),
        ];

        $filename = 'trips-report-' . now()->format('Y-m-d-His') . '.xlsx';

        return Excel::download(new TripsReportExport($bookings, $totals, $filters), $filename);
    }
}
