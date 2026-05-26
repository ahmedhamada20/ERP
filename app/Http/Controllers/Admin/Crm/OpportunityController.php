<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\OpportunityRequest;
use App\Models\Customer;
use App\Models\DomesticBooking;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\ReligiousBooking;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class OpportunityController extends Controller
{
    private const STATS_CACHE_KEY = 'opportunities.kpi_stats';
    private const STATS_TTL       = 180;

    public function index()
    {
        $stats = Cache::remember(self::STATS_CACHE_KEY, self::STATS_TTL, function () {
            $row = DB::table('opportunities')
                ->selectRaw("
                    COUNT(*) AS total,
                    SUM(CASE WHEN stage = 'prospecting'   THEN 1 ELSE 0 END) AS prospecting,
                    SUM(CASE WHEN stage = 'qualification' THEN 1 ELSE 0 END) AS qualification,
                    SUM(CASE WHEN stage = 'proposal'      THEN 1 ELSE 0 END) AS proposal,
                    SUM(CASE WHEN stage = 'negotiation'   THEN 1 ELSE 0 END) AS negotiation,
                    SUM(CASE WHEN stage = 'closed_won'    THEN 1 ELSE 0 END) AS won,
                    SUM(CASE WHEN stage = 'closed_lost'   THEN 1 ELSE 0 END) AS lost,
                    COALESCE(SUM(CASE WHEN stage NOT IN ('closed_won','closed_lost') THEN estimated_value ELSE 0 END), 0) AS pipeline_value,
                    COALESCE(SUM(CASE WHEN stage NOT IN ('closed_won','closed_lost') THEN estimated_value * probability / 100 ELSE 0 END), 0) AS weighted_pipeline,
                    COALESCE(SUM(CASE WHEN stage = 'closed_won' THEN estimated_value ELSE 0 END), 0) AS won_value
                ")
                ->whereNull('deleted_at')
                ->first();

            $totalClosed = (int) ($row->won + $row->lost);
            $winRate = $totalClosed > 0 ? round(((int) $row->won / $totalClosed) * 100, 1) : 0;

            return [
                'total'             => (int) ($row->total ?? 0),
                'prospecting'       => (int) ($row->prospecting ?? 0),
                'qualification'     => (int) ($row->qualification ?? 0),
                'proposal'          => (int) ($row->proposal ?? 0),
                'negotiation'       => (int) ($row->negotiation ?? 0),
                'won'               => (int) ($row->won ?? 0),
                'lost'              => (int) ($row->lost ?? 0),
                'pipeline_value'    => (float) ($row->pipeline_value ?? 0),
                'weighted_pipeline' => (float) ($row->weighted_pipeline ?? 0),
                'won_value'         => (float) ($row->won_value ?? 0),
                'win_rate'          => $winRate,
            ];
        });

        return view('admin.crm.opportunities.index', compact('stats'));
    }

    /** Pipeline (Kanban) view — groups opportunities by stage. */
    public function pipeline(Request $request)
    {
        $query = Opportunity::query()
            ->with(['assignee:id,name', 'lead:id,full_name', 'customer:id,full_name'])
            ->select(['id', 'code', 'title', 'lead_id', 'customer_id',
                      'booking_type', 'sub_type', 'destination',
                      'estimated_value', 'probability', 'pax_count',
                      'expected_close_date', 'stage', 'assigned_to',
                      'converted_booking_type', 'converted_booking_id', 'created_at']);

        if ($request->filled('assignee_id'))   $query->where('assigned_to', $request->assignee_id);
        if ($request->filled('booking_type'))  $query->where('booking_type', $request->booking_type);

        $opportunities = $query->orderByDesc('created_at')->get()->groupBy('stage');

        $assignees = User::orderBy('name')->select('id', 'name')->get();

        return view('admin.crm.opportunities.pipeline', compact('opportunities', 'assignees'));
    }

    public function data(Request $request)
    {
        $cols = [
            'id', 'code', 'title', 'lead_id', 'customer_id',
            'booking_type', 'sub_type', 'destination',
            'pax_count', 'estimated_value', 'probability',
            'stage', 'expected_close_date',
            'assigned_to', 'converted_booking_type', 'converted_booking_id',
            'created_at',
        ];

        $query = Opportunity::query()->select($cols)->with([
            'assignee:id,name',
            'lead:id,full_name,code',
            'customer:id,full_name,code',
        ]);

        if ($request->filled('stage_filter'))        $query->where('stage', $request->stage_filter);
        if ($request->filled('booking_type_filter')) $query->where('booking_type', $request->booking_type_filter);
        if ($request->filled('assignee_id'))         $query->where('assigned_to', $request->assignee_id);

        if ($request->filled('q')) {
            $term = trim((string) $request->q);
            $query->where(function ($q) use ($term) {
                $q->where('title', 'like', "%{$term}%")
                  ->orWhere('code', 'like', "%{$term}%")
                  ->orWhere('destination', 'like', "%{$term}%")
                  ->orWhereHas('lead', fn ($l) => $l->where('full_name', 'like', "%{$term}%"))
                  ->orWhereHas('customer', fn ($c) => $c->where('full_name', 'like', "%{$term}%"));
            });
        }

        return DataTables::eloquent($query)
            ->addColumn('opp_info', function (Opportunity $o) {
                return '<div>'
                    . '<div><strong>' . e($o->title) . '</strong></div>'
                    . '<div class="text-muted small"><i class="bi bi-hash"></i>' . e($o->code) . '</div>'
                    . '</div>';
            })
            ->addColumn('source_party', function (Opportunity $o) {
                if ($o->customer) {
                    return '<div class="small"><i class="bi bi-person-check text-success"></i> ' . e($o->customer->full_name) . '</div>';
                }
                if ($o->lead) {
                    return '<div class="small"><i class="bi bi-person-plus text-info"></i> ' . e($o->lead->full_name) . ' <span class="badge bg-info-soft x-small">Lead</span></div>';
                }
                return '<span class="text-muted">—</span>';
            })
            ->editColumn('booking_type', fn (Opportunity $o) =>
                '<span class="badge bg-' . ($o->booking_type === 'religious' ? 'success' : 'primary') . '-soft">'
                . e($o->booking_type_label)
                . '</span>'
            )
            ->editColumn('destination', fn (Opportunity $o) =>
                $o->destination
                    ? '<i class="bi bi-geo-alt text-danger"></i> ' . e($o->destination)
                    : '<span class="text-muted">—</span>'
            )
            ->editColumn('stage', fn (Opportunity $o) =>
                '<span class="badge bg-' . $o->stage_badge . '-soft">' . $o->stage_label . '</span>'
            )
            ->editColumn('estimated_value', fn (Opportunity $o) =>
                '<div><strong>' . number_format($o->estimated_value, 0) . '</strong> <small class="text-muted">ج.م</small>'
                . '<div class="text-muted x-small">مرجح: ' . number_format($o->weighted_value, 0) . ' (' . $o->probability . '%)</div>'
                . '</div>'
            )
            ->addColumn('pax', fn (Opportunity $o) => $o->pax_count . ' فرد')
            ->addColumn('assignee_name', fn (Opportunity $o) =>
                $o->assignee?->name ?? '<span class="text-muted">—</span>'
            )
            ->editColumn('created_at', fn (Opportunity $o) =>
                '<div class="small">' . $o->created_at?->diffForHumans() . '</div>'
            )
            ->addColumn('actions', function (Opportunity $o) {
                $user = auth()->user();
                $buttons = '<a href="' . route('admin.crm.opportunities.show', $o) . '" class="btn btn-icon btn-sm btn-light-primary" title="عرض"><i class="bi bi-eye"></i></a> ';
                if ($user && $user->can('opportunities.update') && !$o->isConverted()) {
                    $buttons .= '<a href="' . route('admin.crm.opportunities.edit', $o) . '" class="btn btn-icon btn-sm btn-light-info" title="تعديل"><i class="bi bi-pencil"></i></a> ';
                }
                if ($user && $user->can('opportunities.delete')) {
                    $buttons .= '<button data-url="' . route('admin.crm.opportunities.destroy', $o) . '" class="btn btn-icon btn-sm btn-light-danger btn-delete" title="حذف"><i class="bi bi-trash"></i></button>';
                }
                return '<div class="table-actions">' . $buttons . '</div>';
            })
            ->rawColumns(['opp_info', 'source_party', 'booking_type', 'destination', 'stage', 'estimated_value', 'pax', 'assignee_name', 'created_at', 'actions'])
            ->make(true);
    }

    public function create(Request $request)
    {
        return view('admin.crm.opportunities.create', array_merge(
            $this->formData(),
            ['preselected_lead' => $request->filled('lead_id') ? Lead::find($request->lead_id) : null],
        ));
    }

    public function store(OpportunityRequest $request)
    {
        $opp = DB::transaction(function () use ($request) {
            $data = $request->validated();
            $data['assigned_to'] ??= auth()->id();
            return Opportunity::create($data);
        });

        Cache::forget(self::STATS_CACHE_KEY);

        return redirect()
            ->route('admin.crm.opportunities.show', $opp)
            ->with('success', 'تم إنشاء الصفقة بنجاح');
    }

    public function show(Opportunity $opportunity)
    {
        $opportunity->load([
            'lead', 'customer', 'assignee', 'creator',
        ]);

        return view('admin.crm.opportunities.show', [
            'opp'              => $opportunity,
            'convertedBooking' => $opportunity->convertedBooking(),
        ]);
    }

    public function edit(Opportunity $opportunity)
    {
        if ($opportunity->isConverted()) {
            return redirect()->route('admin.crm.opportunities.show', $opportunity)
                ->with('error', 'لا يمكن تعديل صفقة تم تحويلها إلى حجز');
        }

        return view('admin.crm.opportunities.edit', array_merge(
            $this->formData(),
            ['opp' => $opportunity],
        ));
    }

    public function update(OpportunityRequest $request, Opportunity $opportunity)
    {
        if ($opportunity->isConverted()) {
            return back()->with('error', 'لا يمكن تعديل صفقة محوّلة');
        }

        DB::transaction(function () use ($request, $opportunity) {
            $opportunity->update($request->validated());
        });

        Cache::forget(self::STATS_CACHE_KEY);

        return redirect()
            ->route('admin.crm.opportunities.show', $opportunity)
            ->with('success', 'تم تحديث الصفقة');
    }

    public function destroy(Opportunity $opportunity)
    {
        if ($opportunity->isConverted()) {
            return response()->json([
                'message' => 'لا يمكن حذف صفقة تم تحويلها بالفعل',
            ], 422);
        }

        $opportunity->delete();
        Cache::forget(self::STATS_CACHE_KEY);

        return response()->json(['message' => 'تم حذف الصفقة بنجاح']);
    }

    /** Quick stage change (AJAX from Kanban drag-drop). */
    public function updateStage(Request $request, Opportunity $opportunity)
    {
        if ($opportunity->isConverted()) {
            return response()->json(['message' => 'الصفقة محوّلة، لا يمكن تغيير مرحلتها'], 422);
        }

        $data = $request->validate([
            'stage'       => ['required', 'in:prospecting,qualification,proposal,negotiation,closed_won,closed_lost'],
            'lost_reason' => ['nullable', 'string', 'max:200'],
        ]);

        if ($data['stage'] === $opportunity->stage) {
            return response()->json(['message' => 'لا يوجد تغيير']);
        }

        // Auto-update probability when moving stages
        $data['probability'] = Opportunity::STAGE_PROBABILITY[$data['stage']] ?? $opportunity->probability;

        $opportunity->update($data);
        Cache::forget(self::STATS_CACHE_KEY);

        return response()->json([
            'message'      => 'تم تحديث المرحلة',
            'stage_label'  => $opportunity->fresh()->stage_label,
            'probability'  => $opportunity->probability,
        ]);
    }

    /**
     * Show the convert-to-booking form. Pre-fills as much as we can from
     * the opportunity. The user fills the rest (customer if missing, exact
     * booking config) and submits.
     */
    public function convertForm(Opportunity $opportunity)
    {
        if ($opportunity->isConverted()) {
            return redirect()->route('admin.crm.opportunities.show', $opportunity)
                ->with('error', 'هذه الصفقة محوّلة بالفعل');
        }
        if ($opportunity->stage === 'closed_lost') {
            return redirect()->route('admin.crm.opportunities.show', $opportunity)
                ->with('error', 'لا يمكن تحويل صفقة خاسرة');
        }

        // If lead exists but not converted to customer yet, we'll need to create one
        $needsCustomer = !$opportunity->customer_id;

        return view('admin.crm.opportunities.convert', [
            'opp'           => $opportunity,
            'needsCustomer' => $needsCustomer,
            'customers'     => Customer::active()->select('id', 'code', 'full_name', 'phone')->orderBy('full_name')->limit(500)->get(),
        ]);
    }

    public function convert(Request $request, Opportunity $opportunity)
    {
        if ($opportunity->isConverted()) {
            return back()->with('error', 'الصفقة محوّلة بالفعل');
        }

        $data = $request->validate([
            'customer_id'   => ['required_without:create_customer', 'nullable', 'exists:customers,id'],
            'create_customer' => ['nullable', 'boolean'],
            'trip_date'     => ['required', 'date'],
            'selling_price' => ['required', 'numeric', 'min:0'],
        ]);

        $booking = DB::transaction(function () use ($data, $opportunity, $request) {
            // 1. Resolve customer
            $customerId = $data['customer_id'] ?? null;
            if ($request->boolean('create_customer') && $opportunity->lead && !$customerId) {
                $lead = $opportunity->lead;
                $customer = Customer::create([
                    'full_name' => $lead->full_name,
                    'phone'     => $lead->phone,
                    'mobile'    => $lead->phone,
                    'whatsapp'  => $lead->whatsapp,
                    'email'     => $lead->email,
                    'city'      => $lead->city,
                    'country'   => 'مصر',
                    'type'      => 'individual',
                    'status'    => 'active',
                    'notes'     => "تم إنشاؤه من الصفقة {$opportunity->code}",
                ]);
                $lead->update([
                    'converted_to_customer_id' => $customer->id,
                    'converted_at'             => now(),
                    'status'                   => 'won',
                ]);
                $customerId = $customer->id;
            }

            // 2. Create the matching booking (religious or domestic)
            $booking = $opportunity->booking_type === 'religious'
                ? $this->createReligiousBooking($opportunity, $customerId, $data)
                : $this->createDomesticBooking($opportunity, $customerId, $data);

            // 3. Mark the opportunity as converted + won
            $opportunity->update([
                'customer_id'            => $customerId,
                'stage'                  => 'closed_won',
                'actual_close_date'      => now()->toDateString(),
                'probability'            => 100,
                'converted_booking_type' => $opportunity->booking_type,
                'converted_booking_id'   => $booking->id,
            ]);

            return $booking;
        });

        Cache::forget(self::STATS_CACHE_KEY);

        $route = $opportunity->booking_type === 'religious'
            ? 'admin.religious.bookings.show'
            : 'admin.domestic.bookings.show';

        return redirect()
            ->route($route, $booking)
            ->with('success', "تم تحويل الصفقة لحجز رقم: {$booking->booking_number}");
    }

    private function createReligiousBooking(Opportunity $opp, string $customerId, array $data): ReligiousBooking
    {
        return ReligiousBooking::create([
            'customer_id'        => $customerId,
            'type'               => $opp->sub_type ?: 'umrah',
            'booking_date'       => now()->toDateString(),
            'trip_date'          => $data['trip_date'],
            'duration_days'      => 10, // default — user edits in booking
            'adults_count'       => $opp->pax_count,
            'visa_type'          => 'standard',
            'accommodation_type' => 'quad',
            'meal_plan'          => 'hp',
            'transport_type'     => 'flight',
            'mutawif_grade'      => 'economy',
            'selling_price'      => $data['selling_price'],
            'notes'              => "محوّلة من صفقة {$opp->code} — {$opp->title}",
        ]);
    }

    private function createDomesticBooking(Opportunity $opp, string $customerId, array $data): DomesticBooking
    {
        return DomesticBooking::create([
            'customer_id'         => $customerId,
            'type'                => $opp->sub_type ?: 'package',
            'destination_city'    => $opp->destination ?: 'الغردقة',
            'booking_date'        => now()->toDateString(),
            'trip_date'           => $data['trip_date'],
            'duration_days'       => 3,  // default
            'duration_nights'     => 2,
            'adults_count'        => $opp->pax_count,
            'accommodation_type'  => 'double',
            'rooms_count'         => max(1, (int) ceil($opp->pax_count / 2)),
            'accommodation_grade' => '4_stars',
            'meal_plan'           => 'bb',
            'transport_type'      => 'bus',
            'selling_price'       => $data['selling_price'],
            'notes'               => "محوّلة من صفقة {$opp->code} — {$opp->title}",
        ]);
    }

    private function formData(): array
    {
        return [
            'leads'     => Lead::open()->select('id', 'code', 'full_name', 'phone')->orderByDesc('created_at')->limit(200)->get(),
            'customers' => Customer::active()->select('id', 'code', 'full_name', 'phone')->orderBy('full_name')->limit(500)->get(),
            'employees' => User::orderBy('name')->select('id', 'name')->get(),
        ];
    }
}
