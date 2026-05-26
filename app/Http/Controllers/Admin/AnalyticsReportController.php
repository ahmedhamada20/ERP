<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\DomesticBooking;
use App\Models\DomesticProgram;
use App\Models\Employee;
use App\Models\PayslipLine;
use App\Models\ReligiousBooking;
use App\Models\ReligiousProgram;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * تقارير تحليلية موحدة عبر السياحة الدينية والداخلية.
 *
 * كل تقرير يقبل فلاتر فترة زمنية (from/to) عبر query string، ويعرض
 * النتائج في view مع جدول + ملخص أعلى الصفحة. الفلتر الافتراضي:
 * أول يوم في السنة الحالية → اليوم.
 */
class AnalyticsReportController extends Controller
{
    /**
     * 1. ربحية شهرية مدمجة (Religious + Domestic).
     * يجمع الحجوزات حسب YYYY-MM ويحسب: عدد، إيراد، تكلفة، صافي ربح.
     */
    public function monthlyProfitability(Request $request)
    {
        [$from, $to] = $this->resolveDateRange($request);

        // تجميع PHP-side حتى يعمل على كل قواعد البيانات (MySQL, SQLite, PgSQL)
        // بدون اعتماد على دوال تاريخ مخصوصة بمحرّك.
        $religious = ReligiousBooking::query()
            ->whereBetween('booking_date', [$from, $to])
            ->where('status', '!=', 'cancelled')
            ->get(['booking_date', 'selling_price', 'total_cost', 'net_profit'])
            ->groupBy(fn ($b) => $b->booking_date->format('Y-m'))
            ->map(fn ($group) => (object) [
                'bookings_count' => $group->count(),
                'revenue'        => (float) $group->sum('selling_price'),
                'profit'         => (float) $group->sum('net_profit'),
            ]);

        $domestic = DomesticBooking::query()
            ->whereBetween('booking_date', [$from, $to])
            ->where('status', '!=', 'cancelled')
            ->get(['booking_date', 'selling_price', 'total_cost', 'net_profit'])
            ->groupBy(fn ($b) => $b->booking_date->format('Y-m'))
            ->map(fn ($group) => (object) [
                'bookings_count' => $group->count(),
                'revenue'        => (float) $group->sum('selling_price'),
                'profit'         => (float) $group->sum('net_profit'),
            ]);

        $months = $religious->keys()->merge($domestic->keys())->unique()->sort()->values();
        $rows   = $months->map(function ($month) use ($religious, $domestic) {
            $r = $religious->get($month);
            $d = $domestic->get($month);
            return (object) [
                'month'              => $month,
                'religious_bookings' => $r->bookings_count ?? 0,
                'religious_revenue'  => (float) ($r->revenue ?? 0),
                'religious_profit'   => (float) ($r->profit  ?? 0),
                'domestic_bookings'  => $d->bookings_count ?? 0,
                'domestic_revenue'   => (float) ($d->revenue ?? 0),
                'domestic_profit'    => (float) ($d->profit  ?? 0),
                'total_bookings'     => ($r->bookings_count ?? 0) + ($d->bookings_count ?? 0),
                'total_revenue'      => (float) (($r->revenue ?? 0) + ($d->revenue ?? 0)),
                'total_profit'       => (float) (($r->profit  ?? 0) + ($d->profit  ?? 0)),
            ];
        });

        $summary = [
            'total_revenue' => $rows->sum('total_revenue'),
            'total_profit'  => $rows->sum('total_profit'),
            'bookings'      => $rows->sum('total_bookings'),
            'margin'        => $rows->sum('total_revenue') > 0
                ? round($rows->sum('total_profit') / $rows->sum('total_revenue') * 100, 1)
                : 0,
        ];

        return view('admin.reports.analytics.monthly_profitability', compact('rows', 'summary', 'from', 'to'));
    }

    /**
     * 2. البرامج الأعلى مبيعاً (Religious + Domestic).
     */
    public function topPrograms(Request $request)
    {
        [$from, $to] = $this->resolveDateRange($request);

        $religious = ReligiousProgram::query()
            ->leftJoin('religious_bookings', function ($j) use ($from, $to) {
                $j->on('religious_bookings.program_id', '=', 'religious_programs.id')
                  ->whereBetween('religious_bookings.booking_date', [$from, $to])
                  ->where('religious_bookings.status', '!=', 'cancelled');
            })
            ->selectRaw("religious_programs.id, religious_programs.name, 'religious' as kind")
            ->selectRaw('COUNT(religious_bookings.id) as bookings_count')
            ->selectRaw('COALESCE(SUM(religious_bookings.selling_price), 0) as revenue')
            ->selectRaw('COALESCE(SUM(religious_bookings.net_profit), 0)   as profit')
            ->groupBy('religious_programs.id', 'religious_programs.name')
            ->having('bookings_count', '>', 0)
            ->get();

        $domestic = DomesticProgram::query()
            ->leftJoin('domestic_bookings', function ($j) use ($from, $to) {
                $j->on('domestic_bookings.program_id', '=', 'domestic_programs.id')
                  ->whereBetween('domestic_bookings.booking_date', [$from, $to])
                  ->where('domestic_bookings.status', '!=', 'cancelled');
            })
            ->selectRaw("domestic_programs.id, domestic_programs.name, 'domestic' as kind")
            ->selectRaw('COUNT(domestic_bookings.id) as bookings_count')
            ->selectRaw('COALESCE(SUM(domestic_bookings.selling_price), 0) as revenue')
            ->selectRaw('COALESCE(SUM(domestic_bookings.net_profit), 0)   as profit')
            ->groupBy('domestic_programs.id', 'domestic_programs.name')
            ->having('bookings_count', '>', 0)
            ->get();

        $rows = $religious->merge($domestic)
            ->sortByDesc('revenue')
            ->values();

        return view('admin.reports.analytics.top_programs', compact('rows', 'from', 'to'));
    }

    /**
     * 3. العملاء الأكثر حجزاً.
     */
    public function topCustomers(Request $request)
    {
        [$from, $to] = $this->resolveDateRange($request);

        $rows = Customer::query()
            ->leftJoin('religious_bookings', function ($j) use ($from, $to) {
                $j->on('religious_bookings.customer_id', '=', 'customers.id')
                  ->whereBetween('religious_bookings.booking_date', [$from, $to])
                  ->where('religious_bookings.status', '!=', 'cancelled');
            })
            ->leftJoin('domestic_bookings', function ($j) use ($from, $to) {
                $j->on('domestic_bookings.customer_id', '=', 'customers.id')
                  ->whereBetween('domestic_bookings.booking_date', [$from, $to])
                  ->where('domestic_bookings.status', '!=', 'cancelled');
            })
            ->selectRaw('customers.id, customers.code, customers.full_name, customers.phone, customers.city')
            ->selectRaw('COUNT(DISTINCT religious_bookings.id) as religious_count')
            ->selectRaw('COUNT(DISTINCT domestic_bookings.id)  as domestic_count')
            ->selectRaw('COALESCE(SUM(religious_bookings.selling_price), 0) + COALESCE(SUM(domestic_bookings.selling_price), 0) as total_revenue')
            ->groupBy('customers.id', 'customers.code', 'customers.full_name', 'customers.phone', 'customers.city')
            ->havingRaw('(religious_count + domestic_count) > 0')
            ->orderByDesc('total_revenue')
            ->limit(50)
            ->get();

        return view('admin.reports.analytics.top_customers', compact('rows', 'from', 'to'));
    }

    /**
     * 4. العملاء الجدد في الفترة.
     */
    public function newCustomers(Request $request)
    {
        [$from, $to] = $this->resolveDateRange($request);

        $rows = Customer::query()
            ->whereBetween('created_at', [$from, $to->copy()->endOfDay()])
            ->withCount(['religiousBookings', 'domesticBookings'])
            ->orderByDesc('created_at')
            ->get();

        $summary = [
            'total'         => $rows->count(),
            'with_booking'  => $rows->filter(fn ($c) => ($c->religious_bookings_count + $c->domestic_bookings_count) > 0)->count(),
            'no_booking'    => $rows->filter(fn ($c) => ($c->religious_bookings_count + $c->domestic_bookings_count) === 0)->count(),
        ];

        return view('admin.reports.analytics.new_customers', compact('rows', 'summary', 'from', 'to'));
    }

    /**
     * 5. أداء مبيعات الموظفين.
     */
    public function salesPerformance(Request $request)
    {
        [$from, $to] = $this->resolveDateRange($request);

        $rows = Employee::query()
            ->leftJoin('religious_bookings', function ($j) use ($from, $to) {
                $j->on('religious_bookings.sales_employee_id', '=', 'employees.id')
                  ->whereBetween('religious_bookings.booking_date', [$from, $to])
                  ->where('religious_bookings.status', '!=', 'cancelled');
            })
            ->leftJoin('domestic_bookings', function ($j) use ($from, $to) {
                $j->on('domestic_bookings.sales_employee_id', '=', 'employees.id')
                  ->whereBetween('domestic_bookings.booking_date', [$from, $to])
                  ->where('domestic_bookings.status', '!=', 'cancelled');
            })
            ->selectRaw('employees.id, employees.code, employees.full_name')
            ->selectRaw('COUNT(DISTINCT religious_bookings.id) as religious_count')
            ->selectRaw('COUNT(DISTINCT domestic_bookings.id)  as domestic_count')
            ->selectRaw('COALESCE(SUM(religious_bookings.selling_price), 0) + COALESCE(SUM(domestic_bookings.selling_price), 0) as total_revenue')
            ->selectRaw('COALESCE(SUM(religious_bookings.net_profit), 0)   + COALESCE(SUM(domestic_bookings.net_profit), 0)   as total_profit')
            ->groupBy('employees.id', 'employees.code', 'employees.full_name')
            ->havingRaw('(religious_count + domestic_count) > 0')
            ->orderByDesc('total_revenue')
            ->get();

        return view('admin.reports.analytics.sales_performance', compact('rows', 'from', 'to'));
    }

    /**
     * 6. كشف العمولات الفعلية المدفوعة (من payslip_lines line_type=commission).
     */
    public function commissions(Request $request)
    {
        [$from, $to] = $this->resolveDateRange($request);

        // فلترة على مستوى السنة + الشهر (المخزّن في payroll_runs كأعمدة منفصلة)
        // — تجنّب SQL مخصوص بمحرّك.
        $fromYM = $from->year * 100 + $from->month;
        $toYM   = $to->year   * 100 + $to->month;

        $rows = PayslipLine::query()
            ->join('payslips',     'payslips.id',     '=', 'payslip_lines.payslip_id')
            ->join('employees',    'employees.id',    '=', 'payslips.employee_id')
            ->join('payroll_runs', 'payroll_runs.id', '=', 'payslips.payroll_run_id')
            ->where('payslip_lines.line_type', PayslipLine::TYPE_COMMISSION)
            ->whereRaw('(payroll_runs.period_year * 100 + payroll_runs.period_month) BETWEEN ? AND ?', [$fromYM, $toYM])
            ->selectRaw('employees.id as employee_id, employees.code, employees.full_name')
            ->selectRaw('SUM(payslip_lines.amount) as total_commission')
            ->selectRaw('COUNT(payslip_lines.id) as lines_count')
            ->groupBy('employees.id', 'employees.code', 'employees.full_name')
            ->orderByDesc('total_commission')
            ->get();

        return view('admin.reports.analytics.commissions', compact('rows', 'from', 'to'));
    }

    /**
     * 7. الحجوزات بدفعات متأخرة (الرصيد المستحق > 0).
     */
    public function outstandingPayments(Request $request)
    {
        [$from, $to] = $this->resolveDateRange($request);

        $religious = ReligiousBooking::query()
            ->with('customer:id,full_name,phone')
            ->whereBetween('booking_date', [$from, $to])
            ->whereIn('status', ['confirmed', 'in_progress'])
            ->get()
            ->filter(fn ($b) => $b->outstanding_balance > 0)
            ->map(fn ($b) => (object) [
                'kind'           => 'religious',
                'booking_number' => $b->booking_number,
                'customer'       => $b->customer?->full_name,
                'phone'          => $b->customer?->phone,
                'trip_date'      => $b->trip_date,
                'selling_price'  => (float) $b->selling_price,
                'paid'           => (float) $b->total_paid,
                'outstanding'    => (float) $b->outstanding_balance,
                'days_to_trip'   => $b->trip_date ? Carbon::today()->diffInDays($b->trip_date, false) : null,
                'show_url'       => route('admin.religious.bookings.show', $b),
            ]);

        $domestic = DomesticBooking::query()
            ->with('customer:id,full_name,phone')
            ->whereBetween('booking_date', [$from, $to])
            ->whereIn('status', ['confirmed', 'in_progress'])
            ->get()
            ->filter(fn ($b) => $b->outstanding_balance > 0)
            ->map(fn ($b) => (object) [
                'kind'           => 'domestic',
                'booking_number' => $b->booking_number,
                'customer'       => $b->customer?->full_name,
                'phone'          => $b->customer?->phone,
                'trip_date'      => $b->trip_date,
                'selling_price'  => (float) $b->selling_price,
                'paid'           => (float) $b->total_paid,
                'outstanding'    => (float) $b->outstanding_balance,
                'days_to_trip'   => $b->trip_date ? Carbon::today()->diffInDays($b->trip_date, false) : null,
                'show_url'       => route('admin.domestic.bookings.show', $b),
            ]);

        $rows = $religious->merge($domestic)->sortBy('days_to_trip')->values();

        $summary = [
            'count'           => $rows->count(),
            'total_outstand'  => $rows->sum('outstanding'),
            'urgent_count'    => $rows->filter(fn ($r) => $r->days_to_trip !== null && $r->days_to_trip <= 7)->count(),
        ];

        return view('admin.reports.analytics.outstanding_payments', compact('rows', 'summary', 'from', 'to'));
    }

    /**
     * يحدد فترة التقرير من query string أو الافتراضي.
     */
    private function resolveDateRange(Request $request): array
    {
        $from = $request->filled('from')
            ? Carbon::parse($request->input('from'))->startOfDay()
            : Carbon::now()->startOfYear();

        $to = $request->filled('to')
            ? Carbon::parse($request->input('to'))->endOfDay()
            : Carbon::now()->endOfDay();

        if ($to->lt($from)) {
            [$from, $to] = [$to, $from];
        }

        return [$from, $to];
    }
}
