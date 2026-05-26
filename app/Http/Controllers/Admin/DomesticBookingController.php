<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DomesticBookingRequest;
use App\Models\Customer;
use App\Models\DomesticBooking;
use App\Models\DomesticProgram;
use App\Models\Hotel;
use App\Models\User;
use App\Services\Accounting\DomesticBookingCostJournalPoster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;
use Yajra\DataTables\Facades\DataTables;

class DomesticBookingController extends Controller
{
    private const STATS_CACHE_KEY = 'domestic_bookings.kpi_stats';
    private const STATS_TTL       = 180;

    public function index()
    {
        $stats = Cache::remember(self::STATS_CACHE_KEY, self::STATS_TTL, function () {
            $row = DB::table('domestic_bookings')
                ->selectRaw("
                    COUNT(*) AS total,
                    SUM(CASE WHEN status = 'pending'     THEN 1 ELSE 0 END) AS pending,
                    SUM(CASE WHEN status = 'confirmed'   THEN 1 ELSE 0 END) AS confirmed,
                    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) AS in_progress,
                    SUM(CASE WHEN status = 'completed'   THEN 1 ELSE 0 END) AS completed,
                    SUM(CASE WHEN status = 'cancelled'   THEN 1 ELSE 0 END) AS cancelled,
                    COALESCE(SUM(CASE WHEN status != 'cancelled' THEN selling_price ELSE 0 END), 0) AS revenue,
                    COALESCE(SUM(CASE WHEN status != 'cancelled' THEN net_profit    ELSE 0 END), 0) AS profit,
                    SUM(CASE WHEN trip_date BETWEEN ? AND ? AND status NOT IN ('cancelled','completed') THEN 1 ELSE 0 END) AS upcoming_30
                ", [now(), now()->addDays(30)])
                ->whereNull('deleted_at')
                ->first();

            return [
                'total'        => (int) ($row->total ?? 0),
                'pending'      => (int) ($row->pending ?? 0),
                'confirmed'    => (int) ($row->confirmed ?? 0),
                'in_progress'  => (int) ($row->in_progress ?? 0),
                'completed'    => (int) ($row->completed ?? 0),
                'cancelled'    => (int) ($row->cancelled ?? 0),
                'revenue'      => (float) ($row->revenue ?? 0),
                'profit'       => (float) ($row->profit ?? 0),
                'upcoming_30'  => (int) ($row->upcoming_30 ?? 0),
            ];
        });

        return view('admin.domestic.bookings.index', compact('stats'));
    }

    public function data(Request $request)
    {
        $cols = [
            'id', 'booking_number', 'contract_number', 'customer_id', 'program_id', 'hotel_id',
            'responsible_employee_id', 'responsible_manager_id',
            'type', 'destination_city', 'destination_area',
            'booking_date', 'trip_date', 'duration_days', 'duration_nights',
            'adults_count', 'children_count',
            'selling_price', 'total_cost', 'net_profit',
            'status', 'workflow_stage', 'created_at',
        ];

        $query = DomesticBooking::query()
            ->select($cols)
            ->with([
                'customer:id,full_name,phone,code',
                'program:id,name,code',
                'hotel:id,name',
                'employee:id,name',
                'manager:id,name',
            ]);

        if ($request->filled('type_filter'))   $query->where('type', $request->type_filter);
        if ($request->filled('status_filter')) $query->where('status', $request->status_filter);
        if ($request->filled('stage_filter'))  $query->where('workflow_stage', $request->stage_filter);
        if ($request->filled('city_filter'))   $query->where('destination_city', $request->city_filter);
        if ($request->filled('employee_id'))   $query->where('responsible_employee_id', $request->employee_id);
        if ($request->filled('manager_id'))    $query->where('responsible_manager_id', $request->manager_id);
        if ($request->filled('date_from'))     $query->whereDate('trip_date', '>=', $request->date_from);
        if ($request->filled('date_to'))       $query->whereDate('trip_date', '<=', $request->date_to);

        if ($request->filled('q')) {
            $term = trim((string) $request->q);
            $query->where(function ($q) use ($term) {
                $q->where('booking_number', 'like', "%{$term}%")
                  ->orWhere('contract_number', 'like', "%{$term}%")
                  ->orWhere('destination_city', 'like', "%{$term}%")
                  ->orWhereHas('customer', fn ($c) =>
                        $c->where('full_name', 'like', "%{$term}%")
                          ->orWhere('phone', 'like', "%{$term}%")
                          ->orWhere('code', 'like', "%{$term}%")
                  );
            });
        }

        return DataTables::eloquent($query)
            ->addColumn('booking_info', function (DomesticBooking $b) {
                $typeIcons = [
                    'hotel_only' => 'building', 'package' => 'bag-check', 'day_trip' => 'sun',
                    'cruise' => 'water', 'camp' => 'tree', 'event' => 'calendar-event',
                ];
                $typeColors = [
                    'hotel_only' => ['bg' => '#dbeafe', 'fg' => '#1d4ed8'],
                    'package'    => ['bg' => '#e0e7ff', 'fg' => '#4338ca'],
                    'day_trip'   => ['bg' => '#fef3c7', 'fg' => '#b45309'],
                    'cruise'     => ['bg' => '#dcfce7', 'fg' => '#15803d'],
                    'camp'       => ['bg' => '#f1f5f9', 'fg' => '#475569'],
                    'event'      => ['bg' => '#fee2e2', 'fg' => '#b91c1c'],
                ];
                $clr = $typeColors[$b->type] ?? ['bg' => '#f1f5f9', 'fg' => '#475569'];
                $ic  = $typeIcons[$b->type] ?? 'compass';
                return '<div class="booking-cell">'
                    . '<div class="booking-icon" style="background:' . $clr['bg'] . ';color:' . $clr['fg'] . '"><i class="bi bi-' . $ic . '"></i></div>'
                    . '<div class="booking-body">'
                    . '<div class="booking-num"><strong>' . e($b->booking_number) . '</strong></div>'
                    . ($b->contract_number ? '<div class="text-muted small">عقد: ' . e($b->contract_number) . '</div>' : '')
                    . ($b->program ? '<div class="text-muted small"><i class="bi bi-collection"></i> ' . e($b->program->name) . '</div>' : '')
                    . '</div></div>';
            })
            ->addColumn('customer_info', function (DomesticBooking $b) {
                if (!$b->customer) return '<span class="text-muted">—</span>';
                return '<div>'
                    . '<div><strong>' . e($b->customer->full_name) . '</strong></div>'
                    . '<div class="text-muted small"><i class="bi bi-telephone"></i> <span dir="ltr">' . e($b->customer->phone) . '</span></div>'
                    . '</div>';
            })
            ->addColumn('destination', fn (DomesticBooking $b) =>
                '<div><i class="bi bi-geo-alt text-danger"></i> <strong>' . e($b->destination_city) . '</strong>'
                . ($b->hotel ? '<div class="small text-muted"><i class="bi bi-building"></i> ' . e($b->hotel->name) . '</div>' : '')
                . '</div>'
            )
            ->addColumn('trip_info', function (DomesticBooking $b) {
                $daysLeft  = now()->startOfDay()->diffInDays($b->trip_date, false);
                $countdown = '';
                if ($b->status !== 'cancelled') {
                    if ($daysLeft < 0)       $countdown = '<span class="badge bg-secondary-soft small">انتهت</span>';
                    elseif ($daysLeft === 0) $countdown = '<span class="badge bg-danger-soft small">اليوم</span>';
                    elseif ($daysLeft <= 7)  $countdown = '<span class="badge bg-warning-soft small">' . (int)$daysLeft . ' أيام</span>';
                    elseif ($daysLeft <= 30) $countdown = '<span class="badge bg-info-soft small">' . (int)$daysLeft . ' يوم</span>';
                }
                return '<div>'
                    . '<div><i class="bi bi-calendar-event text-primary"></i> ' . $b->trip_date?->format('Y-m-d') . '</div>'
                    . '<div class="text-muted small">' . $b->duration_days . ' يوم'
                    . ($b->duration_nights ? ' / ' . $b->duration_nights . ' ليلة' : '')
                    . '</div>'
                    . $countdown
                    . '</div>';
            })
            ->addColumn('pax', fn (DomesticBooking $b) =>
                '<div><strong>' . ($b->adults_count + $b->children_count) . '</strong> فرد</div>'
                . ($b->children_count > 0 ? '<div class="text-muted small">' . $b->adults_count . ' بالغ + ' . $b->children_count . ' طفل</div>' : '')
            )
            ->addColumn('money', function (DomesticBooking $b) {
                $profitClass = $b->net_profit >= 0 ? 'text-success' : 'text-danger';
                return '<div>'
                    . '<div><strong>' . number_format($b->selling_price, 0) . '</strong> <small class="text-muted">ج.م</small></div>'
                    . '<div class="' . $profitClass . ' small"><i class="bi bi-graph-up-arrow"></i> ' . number_format($b->net_profit, 0) . '</div>'
                    . '</div>';
            })
            ->editColumn('status', fn (DomesticBooking $b) =>
                '<span class="badge bg-' . $b->status_badge . '-soft">' . $b->status_label . '</span>'
            )
            ->editColumn('workflow_stage', fn (DomesticBooking $b) =>
                '<span class="badge bg-light text-dark">' . $b->workflow_label . '</span>'
            )
            ->addColumn('actions', function (DomesticBooking $b) {
                $user = auth()->user();
                $buttons = '<a href="' . route('admin.domestic.bookings.show', $b) . '" class="btn btn-icon btn-sm btn-light-primary" title="عرض"><i class="bi bi-eye"></i></a> ';
                if ($user && $user->can('domestic_bookings.update') && $b->status !== 'cancelled') {
                    $buttons .= '<a href="' . route('admin.domestic.bookings.edit', $b) . '" class="btn btn-icon btn-sm btn-light-info" title="تعديل"><i class="bi bi-pencil"></i></a> ';
                }
                if ($user && $user->can('domestic_bookings.delete')) {
                    $buttons .= '<button data-url="' . route('admin.domestic.bookings.destroy', $b) . '" class="btn btn-icon btn-sm btn-light-danger btn-delete" title="حذف"><i class="bi bi-trash"></i></button>';
                }
                return '<div class="table-actions">' . $buttons . '</div>';
            })
            ->rawColumns(['booking_info', 'customer_info', 'destination', 'trip_info', 'pax', 'money', 'status', 'workflow_stage', 'actions'])
            ->make(true);
    }

    public function create()
    {
        return view('admin.domestic.bookings.create', $this->formData());
    }

    public function store(DomesticBookingRequest $request)
    {
        $booking = DB::transaction(function () use ($request) {
            return DomesticBooking::create($request->validated());
        });

        Cache::forget(self::STATS_CACHE_KEY);

        return redirect()
            ->route('admin.domestic.bookings.show', $booking)
            ->with('success', 'تم إنشاء الحجز بنجاح. أكمل البيانات التفصيلية الآن.');
    }

    public function show(DomesticBooking $booking)
    {
        $booking->load([
            'customer', 'program', 'hotel', 'employee', 'manager', 'creator', 'canceller',
            'costs.creator', 'costs.supplier:id,name,type',
            'payments.receiver', 'payments.cashAccount',
        ]);

        $totals = [
            'paid'         => $booking->total_paid,
            'outstanding'  => $booking->outstanding_balance,
            'profit_pct'   => $booking->profit_margin,
            'costs_count'  => $booking->costs->count(),
        ];

        $cashAccounts = \App\Models\Account::query()
            ->whereIn('sub_type', ['cash', 'bank'])
            ->where('is_active', true)
            ->where('is_group', false)
            ->orderBy('sub_type')
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'sub_type']);

        $suppliers = \App\Models\Supplier::query()
            ->where('is_active', true)
            ->orderBy('type')
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'type']);

        return view('admin.domestic.bookings.show', compact('booking', 'totals', 'cashAccounts', 'suppliers'));
    }

    public function edit(DomesticBooking $booking)
    {
        if ($booking->status === 'cancelled') {
            return redirect()->route('admin.domestic.bookings.show', $booking)
                ->with('error', 'لا يمكن تعديل حجز ملغي');
        }
        return view('admin.domestic.bookings.edit', array_merge(
            $this->formData(),
            ['booking' => $booking]
        ));
    }

    public function update(DomesticBookingRequest $request, DomesticBooking $booking)
    {
        if ($booking->status === 'cancelled') {
            return back()->with('error', 'لا يمكن تعديل حجز ملغي');
        }

        DB::transaction(function () use ($request, $booking) {
            $booking->update($request->validated());
            $booking->recalculateTotals();
        });

        Cache::forget(self::STATS_CACHE_KEY);

        return redirect()
            ->route('admin.domestic.bookings.show', $booking)
            ->with('success', 'تم تحديث بيانات الحجز');
    }

    public function destroy(DomesticBooking $booking)
    {
        $booking->delete();
        Cache::forget(self::STATS_CACHE_KEY);
        return response()->json(['message' => 'تم حذف الحجز بنجاح']);
    }

    /**
     * Duplicate a booking — copies the main row + cost lines. Payments
     * and cancellation state are NOT copied. Trip date shifts to +30 days.
     */
    public function duplicate(DomesticBooking $booking)
    {
        $new = DB::transaction(function () use ($booking) {
            $copy = $booking->replicate([
                'booking_number', 'contract_number', 'receipt_number',
                'cancellation_reason', 'cancelled_at', 'cancelled_by',
                'cost_journal_entry_id', 'total_cost', 'net_profit',
                'created_at', 'updated_at',
            ]);
            $copy->status         = 'pending';
            $copy->workflow_stage = 'sales';
            $copy->booking_date   = now()->toDateString();
            $copy->trip_date      = now()->addDays(30)->toDateString();
            $copy->return_date    = $booking->return_date
                ? now()->addDays(30 + $booking->trip_date->diffInDays($booking->return_date))->toDateString()
                : null;
            $copy->save();

            foreach ($booking->costs as $c) {
                $copy->costs()->create($c->only([
                    'category', 'description', 'currency', 'amount',
                    'exchange_rate', 'quantity', 'per_unit', 'is_revenue', 'notes',
                ]));
            }

            return $copy;
        });

        Cache::forget(self::STATS_CACHE_KEY);

        return redirect()
            ->route('admin.domestic.bookings.edit', $new)
            ->with('success', 'تم نسخ الحجز. عدّل التواريخ قبل الحفظ. رقم الحجز الجديد: ' . $new->booking_number);
    }

    /**
     * Workflow transition — same state machine as religious bookings.
     * GL auto-posting on close is wired in Step 5 (BookingCostJournalPoster
     * for domestic comes later).
     */
    public function transition(Request $request, DomesticBooking $booking, DomesticBookingCostJournalPoster $costPoster)
    {
        $action     = $request->input('action');
        $wasClosed  = $booking->workflow_stage === 'closed';
        $postingErr = null;

        DB::transaction(function () use ($action, $booking, $request, $costPoster, $wasClosed, &$postingErr) {
            switch ($action) {
                case 'approve':
                    $booking->update([
                        'status'         => 'confirmed',
                        'workflow_stage' => 'operations',
                    ]);
                    break;

                case 'start_operations':
                    $booking->update([
                        'status'         => 'in_progress',
                        'workflow_stage' => 'operations',
                    ]);
                    break;

                case 'send_to_finance':
                    $booking->update(['workflow_stage' => 'finance']);
                    break;

                case 'close':
                    $booking->update([
                        'status'         => 'completed',
                        'workflow_stage' => 'closed',
                    ]);
                    $booking->costs()->update(['is_locked' => true]);

                    // Auto-post consolidated cost JE (graceful failure)
                    try {
                        if (! $booking->cost_journal_entry_id && $booking->costs()->where('is_revenue', false)->exists()) {
                            $costPoster->postClosingJournal($booking->fresh());
                        }
                    } catch (Throwable $e) {
                        $postingErr = $e->getMessage();
                        Log::channel('single')->warning('Domestic booking close: cost JE post failed', [
                            'booking_id' => $booking->id,
                            'booking_no' => $booking->booking_number,
                            'error'      => $e->getMessage(),
                        ]);
                    }
                    break;

                case 'cancel':
                    $booking->update([
                        'status'              => 'cancelled',
                        'cancellation_reason' => $request->input('reason'),
                        'cancelled_at'        => now(),
                        'cancelled_by'        => auth()->id(),
                    ]);

                    // If already closed, reverse the cost JE
                    if ($wasClosed && $booking->cost_journal_entry_id) {
                        try {
                            $costPoster->cancelClosingJournal(
                                $booking->fresh(),
                                "إلغاء الحجز: " . ($request->input('reason') ?: 'غير محدد'),
                            );
                        } catch (Throwable $e) {
                            Log::channel('single')->warning('Domestic booking cancel: cost JE cancel failed', [
                                'booking_id' => $booking->id,
                                'error'      => $e->getMessage(),
                            ]);
                        }
                    }
                    break;
            }
        });

        Cache::forget(self::STATS_CACHE_KEY);

        if ($postingErr) {
            return back()->with('warning',
                "تم تحديث حالة الحجز، لكن لم يُسجّل قيد إقفال التكاليف: {$postingErr}"
            );
        }
        return back()->with('success', 'تم تحديث حالة الحجز');
    }

    /** Shared data for create/edit forms. */
    private function formData(): array
    {
        return [
            'customers' => Customer::active()
                ->select('id', 'code', 'full_name', 'phone')
                ->orderBy('full_name')
                ->limit(500)
                ->get(),
            'programs'  => DomesticProgram::active()
                ->select('id', 'code', 'name', 'type', 'destination_city',
                         'base_price_per_person', 'duration_days', 'duration_nights',
                         'default_accommodation_grade', 'default_transport_type', 'default_meal_plan')
                ->orderByDesc('created_at')
                ->get(),
            'hotels'    => Hotel::query()
                ->where('is_active', true)
                ->select('id', 'name', 'city')
                ->orderBy('name')
                ->get(),
            'employees' => User::orderBy('name')->select('id', 'name')->get(),
        ];
    }
}
