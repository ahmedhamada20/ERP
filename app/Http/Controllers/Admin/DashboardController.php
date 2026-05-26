<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\ReligiousAlert;
use App\Models\ReligiousBooking;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class DashboardController extends Controller
{
    public function index()
    {
        // ── Real counts ─────────────────────────────────────────
        $usersCount     = User::count();
        $rolesCount     = Role::count();
        $customersCount = Customer::count();
        $todayCustomers = Customer::whereDate('created_at', today())->count();

        // ── Religious tourism aggregates (cached 3 min) ─────────
        $religious = Cache::remember('dashboard.religious_stats', 180, function () {
            $row = DB::table('religious_bookings')
                ->selectRaw("
                    COUNT(*) AS total,
                    SUM(CASE WHEN type = 'hajj'  THEN 1 ELSE 0 END) AS hajj_total,
                    SUM(CASE WHEN type = 'umrah' THEN 1 ELSE 0 END) AS umrah_total,
                    SUM(adults_count + children_count) AS pax,
                    COALESCE(SUM(CASE WHEN status != 'cancelled' THEN selling_price END), 0) AS revenue,
                    COALESCE(SUM(CASE WHEN status != 'cancelled' THEN total_cost    END), 0) AS cost,
                    COALESCE(SUM(CASE WHEN status != 'cancelled' THEN net_profit    END), 0) AS profit,
                    SUM(CASE WHEN status = 'pending'     THEN 1 ELSE 0 END) AS pending,
                    SUM(CASE WHEN status = 'confirmed'   THEN 1 ELSE 0 END) AS confirmed,
                    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) AS in_progress,
                    SUM(CASE WHEN status = 'completed'   THEN 1 ELSE 0 END) AS completed,
                    SUM(CASE WHEN status = 'cancelled'   THEN 1 ELSE 0 END) AS cancelled,
                    SUM(CASE WHEN MONTH(booking_date) = MONTH(NOW()) AND YEAR(booking_date) = YEAR(NOW()) AND status != 'cancelled' THEN selling_price ELSE 0 END) AS revenue_this_month
                ")
                ->whereNull('deleted_at')
                ->first();

            // Issued visas + barcodes total
            $visasIssued = DB::table('booking_pilgrims')->where('visa_status', 'issued')->count();
            $flightsTotal = DB::table('booking_transportation')->where('type', 'flight')->sum('pax_count');

            // 12 months trend (bookings + revenue)
            $trend = DB::table('religious_bookings')
                ->selectRaw("DATE_FORMAT(booking_date, '%Y-%m') AS ym,
                            COUNT(*) AS count_b,
                            COALESCE(SUM(selling_price), 0) AS revenue")
                ->whereNull('deleted_at')
                ->where('status', '!=', 'cancelled')
                ->whereDate('booking_date', '>=', now()->subMonths(11)->startOfMonth())
                ->groupBy('ym')
                ->orderBy('ym')
                ->get()
                ->keyBy('ym');

            return ['row' => $row, 'visas' => $visasIssued, 'flights' => (int) $flightsTotal, 'trend' => $trend];
        });

        $rRow = $religious['row'];

        // ── KPI top cards ───────────────────────────────────────
        $kpis = [
            ['label' => 'إيرادات هذا الشهر', 'value' => number_format($rRow->revenue_this_month, 0),
                'trend' => '', 'note' => 'جنيه مصري', 'icon' => 'bi-cash-stack', 'color' => 'gold'],
            ['label' => 'إجمالي التأشيرات الصادرة', 'value' => number_format($religious['visas']),
                'trend' => '', 'note' => 'تأشيرة', 'icon' => 'bi-passport', 'color' => 'orange'],
            ['label' => 'إجمالي الحجوزات', 'value' => number_format($rRow->total),
                'trend' => '', 'note' => 'حج: ' . $rRow->hajj_total . ' • عمرة: ' . $rRow->umrah_total, 'icon' => 'bi-journal-bookmark-fill', 'color' => 'indigo'],
            ['label' => 'تذاكر الطيران', 'value' => number_format($religious['flights']),
                'trend' => '', 'note' => 'تذكرة طيران', 'icon' => 'bi-airplane-fill', 'color' => 'blue'],
            ['label' => 'العملاء النشطون', 'value' => number_format($customersCount),
                'trend' => '+' . $todayCustomers, 'note' => 'عميل جديد اليوم', 'icon' => 'bi-people-fill', 'color' => 'teal'],
            ['label' => 'صافي الأرباح', 'value' => number_format($rRow->profit, 0),
                'trend' => '', 'note' => 'جنيه مصري', 'icon' => 'bi-graph-up-arrow', 'color' => 'green'],
        ];

        // ── Bookings trend (real - last 12 months) ──────────────
        $months = [];
        $trend  = [];
        $revenueTrend = [];
        $arabicMonths = ['يناير','فبراير','مارس','أبريل','مايو','يونيو','يوليو','أغسطس','سبتمبر','أكتوبر','نوفمبر','ديسمبر'];
        for ($i = 11; $i >= 0; $i--) {
            $d = now()->subMonths($i);
            $key = $d->format('Y-m');
            $months[]       = $arabicMonths[$d->month - 1];
            $trend[]        = (int) ($religious['trend'][$key]->count_b ?? 0);
            $revenueTrend[] = (float) ($religious['trend'][$key]->revenue ?? 0);
        }

        // ── Bookings by status (donut) ──────────────────────────
        $totalForDonut = max(1, $rRow->confirmed + $rRow->pending + $rRow->cancelled + $rRow->completed + $rRow->in_progress);
        $statusBreakdown = [
            ['label' => 'مؤكد',         'value' => (int) $rRow->confirmed,   'pct' => round($rRow->confirmed   * 100 / $totalForDonut), 'color' => '#1d4ed8'],
            ['label' => 'قيد الانتظار', 'value' => (int) $rRow->pending,     'pct' => round($rRow->pending     * 100 / $totalForDonut), 'color' => '#f59e0b'],
            ['label' => 'جارية',         'value' => (int) $rRow->in_progress, 'pct' => round($rRow->in_progress * 100 / $totalForDonut), 'color' => '#06b6d4'],
            ['label' => 'مكتمل',         'value' => (int) $rRow->completed,   'pct' => round($rRow->completed   * 100 / $totalForDonut), 'color' => '#10b981'],
            ['label' => 'ملغي',          'value' => (int) $rRow->cancelled,   'pct' => round($rRow->cancelled   * 100 / $totalForDonut), 'color' => '#ef4444'],
        ];

        // ── Upcoming trips (real) ───────────────────────────────
        $upcomingTrips = ReligiousBooking::query()
            ->with(['customer:id,full_name'])
            ->upcoming()
            ->orderBy('trip_date')
            ->limit(5)
            ->get()
            ->map(function (ReligiousBooking $b) {
                $days = (int) now()->startOfDay()->diffInDays($b->trip_date, false);
                return [
                    'destination' => $b->type === 'hajj' ? 'الحج' : 'العمرة',
                    'customer'    => $b->customer?->full_name ?? '—',
                    'booking_no'  => $b->booking_number,
                    'date'        => $b->trip_date?->translatedFormat('d M Y'),
                    'when'        => $days === 0 ? 'اليوم' : ($days === 1 ? 'غداً' : "بعد {$days} يوم"),
                    'urgent'      => $days <= 7,
                    'url'         => route('admin.religious.bookings.show', $b),
                    'icon'        => $b->type === 'hajj' ? 'mosque' : 'moon-stars',
                ];
            });

        // ── Active alerts (real) ────────────────────────────────
        $alerts = ReligiousAlert::active()
            ->with('booking:id,booking_number')
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn (ReligiousAlert $a) => [
                'title' => $a->title,
                'note'  => $a->message,
                'level' => match ($a->severity) {
                    'critical' => 'danger',
                    'warning'  => 'warning',
                    default    => 'success',
                },
                'booking_url' => $a->booking ? route('admin.religious.bookings.show', $a->booking) : null,
                'booking_no'  => $a->booking?->booking_number,
            ]);

        // ── Hotel occupancy (real - from accommodations) ────────
        $hotelOccupancy = DB::table('booking_accommodations')
            ->join('religious_bookings', 'booking_accommodations.booking_id', '=', 'religious_bookings.id')
            ->selectRaw("city, COUNT(*) AS bookings_count, SUM(rooms_count) AS rooms_total")
            ->where('religious_bookings.status', '!=', 'cancelled')
            ->whereNull('religious_bookings.deleted_at')
            ->groupBy('city')
            ->get()
            ->map(fn ($r) => [
                'city' => match ($r->city) {
                    'mecca'  => 'مكة المكرمة',
                    'medina' => 'المدينة المنورة',
                    'jeddah' => 'جدة',
                    default  => $r->city,
                },
                'pct' => min(100, $r->bookings_count * 5), // proxy metric until real occupancy data
                'rooms' => (int) $r->rooms_total,
            ])
            ->toArray();

        $avgOccupancy = count($hotelOccupancy) > 0 ? (int) round(collect($hotelOccupancy)->avg('pct')) : 0;

        // ── Top programs (real) ─────────────────────────────────
        $topDestinations = DB::table('religious_bookings')
            ->join('religious_programs', 'religious_bookings.program_id', '=', 'religious_programs.id')
            ->selectRaw("religious_programs.id, religious_programs.name, religious_programs.type, COUNT(*) AS bookings, COALESCE(SUM(religious_bookings.selling_price), 0) AS revenue")
            ->where('religious_bookings.status', '!=', 'cancelled')
            ->whereNull('religious_bookings.deleted_at')
            ->groupBy('religious_programs.id', 'religious_programs.name', 'religious_programs.type')
            ->orderByDesc('bookings')
            ->limit(5)
            ->get();

        // ── Payments summary (real) ─────────────────────────────
        $paymentRow = DB::table('booking_payments')
            ->join('religious_bookings', 'booking_payments.booking_id', '=', 'religious_bookings.id')
            ->selectRaw("COALESCE(SUM(booking_payments.amount_egp), 0) AS total_paid")
            ->whereNull('religious_bookings.deleted_at')
            ->where('religious_bookings.status', '!=', 'cancelled')
            ->first();

        $totalRevenue   = (float) $rRow->revenue;
        $totalPaid      = (float) $paymentRow->total_paid;
        $outstanding    = max(0, $totalRevenue - $totalPaid);
        $overduePayments = DB::table('religious_bookings')
            ->whereBetween('trip_date', [now(), now()->addDays(30)])
            ->where('status', '!=', 'cancelled')
            ->whereNull('deleted_at')
            ->get()
            ->sum(function ($b) {
                $paid = DB::table('booking_payments')->where('booking_id', $b->id)->sum('amount_egp');
                return max(0, $b->selling_price - $paid);
            });

        $payments = [
            ['label' => 'إجمالي الإيرادات',    'value' => number_format($totalRevenue, 0),    'icon' => 'bi-wallet2',             'color' => 'primary'],
            ['label' => 'المدفوعات المكتملة', 'value' => number_format($totalPaid, 0),       'icon' => 'bi-check-circle',        'color' => 'success'],
            ['label' => 'قيد التحصيل',         'value' => number_format($outstanding, 0),     'icon' => 'bi-hourglass-split',     'color' => 'warning'],
            ['label' => 'مدفوعات قريبة',       'value' => number_format($overduePayments, 0), 'icon' => 'bi-exclamation-circle', 'color' => 'danger'],
        ];

        // ── Latest bookings (real) ──────────────────────────────
        $latestBookings = ReligiousBooking::query()
            ->with(['customer:id,full_name', 'program:id,name'])
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn (ReligiousBooking $b) => [
                'ref'         => $b->booking_number,
                'customer'    => $b->customer?->full_name ?? '—',
                'destination' => $b->type === 'hajj' ? 'حج' : 'عمرة',
                'service'     => $b->program?->name ?? '—',
                'date'        => $b->trip_date?->format('Y-m-d'),
                'status'      => $b->status_label,
                'badge'       => $b->status_badge,
                'amount'      => number_format($b->selling_price, 0),
                'url'         => route('admin.religious.bookings.show', $b),
            ]);

        // ── Top sellers (last 30 days) ──────────────────────────
        $topSellers = DB::table('religious_bookings')
            ->join('users', 'religious_bookings.responsible_employee_id', '=', 'users.id')
            ->selectRaw("users.id, users.name, COUNT(*) AS bookings_count, COALESCE(SUM(religious_bookings.selling_price), 0) AS revenue")
            ->where('religious_bookings.status', '!=', 'cancelled')
            ->whereNull('religious_bookings.deleted_at')
            ->whereDate('religious_bookings.created_at', '>=', now()->subDays(30))
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('revenue')
            ->limit(5)
            ->get();

        // ── Cross-module pulse (cached 5 min) ───────────────────
        // Each subsystem reports a single count + secondary metric so the
        // dashboard can show one tile per module without N extra queries.
        $modulePulse = Cache::remember('dashboard.module_pulse', 300, function () {
            $hasTable = fn (string $t) => \Illuminate\Support\Facades\Schema::hasTable($t);

            $domesticCount = $hasTable('domestic_bookings')
                ? DB::table('domestic_bookings')->whereNull('deleted_at')->count() : 0;
            $domesticActive = $hasTable('domestic_bookings')
                ? DB::table('domestic_bookings')->whereNull('deleted_at')->whereIn('status', ['confirmed','in_progress'])->count() : 0;

            $leadsOpen = $hasTable('leads')
                ? DB::table('leads')->whereNotIn('status', ['won','lost','converted'])->count() : 0;
            $oppsOpen  = $hasTable('opportunities')
                ? DB::table('opportunities')->whereNotIn('stage', ['closed_won','closed_lost'])->count() : 0;

            $employeesCount = $hasTable('employees')
                ? DB::table('employees')->whereNull('deleted_at')->where('status', 'active')->count() : 0;

            $suppliersCount = $hasTable('suppliers')
                ? DB::table('suppliers')->where('is_active', true)->count() : 0;
            $apOverdue = $hasTable('supplier_invoices')
                ? DB::table('supplier_invoices')
                    ->where('status', 'posted')
                    ->whereDate('due_date', '<', now())
                    ->count() : 0;
            $apOutstanding = $hasTable('supplier_invoices')
                ? (float) DB::table('supplier_invoices')
                    ->where('status', 'posted')
                    ->selectRaw('COALESCE(SUM(amount_egp), 0) AS total')
                    ->value('total') : 0;

            $journalThisMonth = $hasTable('journal_entries')
                ? DB::table('journal_entries')
                    ->whereYear('date', now()->year)
                    ->whereMonth('date', now()->month)
                    ->where('status', 'posted')
                    ->count() : 0;

            // Cash/bank balance = sum(opening + posted debits - posted credits)
            // for accounts with sub_type IN ('cash','bank'). One aggregate query.
            $cashOnHand = 0.0;
            if ($hasTable('accounts') && $hasTable('journal_lines') && $hasTable('journal_entries')) {
                $cashOnHand = (float) DB::table('accounts')
                    ->leftJoin('journal_lines', 'journal_lines.account_id', '=', 'accounts.id')
                    ->leftJoin('journal_entries', function ($j) {
                        $j->on('journal_entries.id', '=', 'journal_lines.journal_entry_id')
                          ->where('journal_entries.status', '=', 'posted');
                    })
                    ->whereIn('accounts.sub_type', ['cash','bank'])
                    ->where('accounts.is_active', true)
                    ->selectRaw('COALESCE(SUM(DISTINCT accounts.opening_balance), 0)
                              + COALESCE(SUM(journal_lines.debit), 0)
                              - COALESCE(SUM(journal_lines.credit), 0) AS balance')
                    ->value('balance');
            }

            return compact(
                'domesticCount','domesticActive','leadsOpen','oppsOpen',
                'employeesCount','suppliersCount','apOutstanding','apOverdue',
                'journalThisMonth','cashOnHand'
            );
        });

        return view('admin.dashboard.index', compact(
            'kpis', 'months', 'trend', 'revenueTrend', 'statusBreakdown',
            'upcomingTrips', 'alerts',
            'hotelOccupancy', 'avgOccupancy', 'topDestinations',
            'payments', 'latestBookings', 'topSellers', 'modulePulse',
            'usersCount', 'rolesCount', 'customersCount', 'todayCustomers'
        ));
    }
}
