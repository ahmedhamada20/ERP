<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LeadRequest;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class LeadController extends Controller
{
    private const STATS_CACHE_KEY = 'leads.kpi_stats';
    private const STATS_TTL       = 180;

    public function index()
    {
        $stats = Cache::remember(self::STATS_CACHE_KEY, self::STATS_TTL, function () {
            $row = DB::table('leads')
                ->selectRaw("
                    COUNT(*) AS total,
                    SUM(CASE WHEN status = 'new'       THEN 1 ELSE 0 END) AS new_count,
                    SUM(CASE WHEN status = 'contacted' THEN 1 ELSE 0 END) AS contacted,
                    SUM(CASE WHEN status = 'qualified' THEN 1 ELSE 0 END) AS qualified,
                    SUM(CASE WHEN status = 'proposal'  THEN 1 ELSE 0 END) AS proposal,
                    SUM(CASE WHEN status = 'won'       THEN 1 ELSE 0 END) AS won,
                    SUM(CASE WHEN status = 'lost'      THEN 1 ELSE 0 END) AS lost,
                    COALESCE(SUM(CASE WHEN status NOT IN ('won','lost') THEN estimated_value ELSE 0 END), 0) AS pipeline_value,
                    COALESCE(SUM(CASE WHEN status = 'won' THEN estimated_value ELSE 0 END), 0) AS won_value
                ")
                ->whereNull('deleted_at')
                ->first();

            $totalClosed = (int) ($row->won + $row->lost);
            $conversionRate = $totalClosed > 0 ? round(((int) $row->won / $totalClosed) * 100, 1) : 0;

            return [
                'total'           => (int) ($row->total ?? 0),
                'new'             => (int) ($row->new_count ?? 0),
                'contacted'       => (int) ($row->contacted ?? 0),
                'qualified'       => (int) ($row->qualified ?? 0),
                'proposal'        => (int) ($row->proposal ?? 0),
                'won'             => (int) ($row->won ?? 0),
                'lost'            => (int) ($row->lost ?? 0),
                'pipeline_value'  => (float) ($row->pipeline_value ?? 0),
                'won_value'       => (float) ($row->won_value ?? 0),
                'conversion_rate' => $conversionRate,
            ];
        });

        return view('admin.crm.leads.index', compact('stats'));
    }

    /** Kanban-style pipeline view — groups leads by status into 6 columns. */
    public function kanban(Request $request)
    {
        $query = Lead::query()
            ->with(['assignee:id,name'])
            ->select(['id', 'code', 'full_name', 'phone', 'whatsapp', 'source',
                      'status', 'interest_type', 'estimated_value',
                      'expected_close_date', 'assigned_to', 'created_at']);

        if ($request->filled('assignee_id')) {
            $query->where('assigned_to', $request->assignee_id);
        }
        if ($request->filled('interest')) {
            $query->where('interest_type', $request->interest);
        }

        $leads = $query->orderByDesc('created_at')->get()->groupBy('status');

        $assignees = User::orderBy('name')->select('id', 'name')->get();

        return view('admin.crm.leads.kanban', compact('leads', 'assignees'));
    }

    public function data(Request $request)
    {
        $cols = [
            'id', 'code', 'full_name', 'phone', 'whatsapp', 'email',
            'source', 'status', 'interest_type',
            'assigned_to', 'estimated_value', 'expected_close_date',
            'converted_to_customer_id', 'created_at',
        ];

        $query = Lead::query()->select($cols)->with(['assignee:id,name']);

        if ($request->filled('status_filter'))   $query->where('status', $request->status_filter);
        if ($request->filled('source_filter'))   $query->where('source', $request->source_filter);
        if ($request->filled('interest_filter')) $query->where('interest_type', $request->interest_filter);
        if ($request->filled('assignee_id'))     $query->where('assigned_to', $request->assignee_id);

        if ($request->filled('q')) {
            $term = trim((string) $request->q);
            $query->where(function ($q) use ($term) {
                $q->where('full_name', 'like', "%{$term}%")
                  ->orWhere('phone', 'like', "%{$term}%")
                  ->orWhere('whatsapp', 'like', "%{$term}%")
                  ->orWhere('email', 'like', "%{$term}%")
                  ->orWhere('code', 'like', "%{$term}%");
            });
        }

        return DataTables::eloquent($query)
            ->addColumn('lead_info', function (Lead $l) {
                return '<div>'
                    . '<div><strong>' . e($l->full_name) . '</strong></div>'
                    . '<div class="text-muted small"><i class="bi bi-hash"></i>' . e($l->code) . '</div>'
                    . '</div>';
            })
            ->addColumn('contact', function (Lead $l) {
                return '<div class="small">'
                    . '<div><i class="bi bi-telephone text-primary"></i> <span dir="ltr">' . e($l->phone) . '</span></div>'
                    . ($l->whatsapp && $l->whatsapp !== $l->phone
                        ? '<div><i class="bi bi-whatsapp text-success"></i> <span dir="ltr">' . e($l->whatsapp) . '</span></div>'
                        : '')
                    . '</div>';
            })
            ->editColumn('source', fn (Lead $l) =>
                '<span class="badge bg-light text-dark">' . e($l->source_label) . '</span>'
            )
            ->editColumn('interest_type', fn (Lead $l) =>
                '<span class="badge bg-info-soft">' . e($l->interest_label) . '</span>'
            )
            ->editColumn('status', fn (Lead $l) =>
                '<span class="badge bg-' . $l->status_badge . '-soft">' . $l->status_label . '</span>'
            )
            ->editColumn('estimated_value', fn (Lead $l) =>
                $l->estimated_value > 0
                    ? '<strong>' . number_format($l->estimated_value, 0) . '</strong> <small class="text-muted">ج.م</small>'
                    : '<span class="text-muted">—</span>'
            )
            ->addColumn('assignee_name', fn (Lead $l) =>
                $l->assignee?->name ?? '<span class="text-muted">غير مُسند</span>'
            )
            ->editColumn('created_at', fn (Lead $l) =>
                '<div class="small">' . $l->created_at?->diffForHumans() . '</div>'
            )
            ->addColumn('actions', function (Lead $l) {
                $user = auth()->user();
                $buttons = '<a href="' . route('admin.crm.leads.show', $l) . '" class="btn btn-icon btn-sm btn-light-primary" title="عرض"><i class="bi bi-eye"></i></a> ';
                if ($user && $user->can('leads.update')) {
                    $buttons .= '<a href="' . route('admin.crm.leads.edit', $l) . '" class="btn btn-icon btn-sm btn-light-info" title="تعديل"><i class="bi bi-pencil"></i></a> ';
                }
                if ($user && $user->can('leads.delete')) {
                    $buttons .= '<button data-url="' . route('admin.crm.leads.destroy', $l) . '" class="btn btn-icon btn-sm btn-light-danger btn-delete" title="حذف"><i class="bi bi-trash"></i></button>';
                }
                return '<div class="table-actions">' . $buttons . '</div>';
            })
            ->rawColumns(['lead_info', 'contact', 'source', 'interest_type', 'status', 'estimated_value', 'assignee_name', 'created_at', 'actions'])
            ->make(true);
    }

    public function create()
    {
        return view('admin.crm.leads.create', $this->formData());
    }

    public function store(LeadRequest $request)
    {
        $lead = DB::transaction(function () use ($request) {
            $data = $request->validated();
            // Default assignee to current user if not specified
            $data['assigned_to'] ??= auth()->id();
            return Lead::create($data);
        });

        Cache::forget(self::STATS_CACHE_KEY);

        return redirect()
            ->route('admin.crm.leads.show', $lead)
            ->with('success', 'تم إنشاء العميل المحتمل بنجاح');
    }

    public function show(Lead $lead)
    {
        $lead->load([
            'assignee', 'creator', 'customer',
            'activities.creator',
            'opportunities' => fn ($q) => $q->latest(),
        ]);

        return view('admin.crm.leads.show', compact('lead'));
    }

    public function edit(Lead $lead)
    {
        return view('admin.crm.leads.edit', array_merge(
            $this->formData(),
            ['lead' => $lead]
        ));
    }

    public function update(LeadRequest $request, Lead $lead)
    {
        $statusChanged = $request->status && $request->status !== $lead->status;
        $oldStatus = $lead->status;

        DB::transaction(function () use ($request, $lead, $statusChanged, $oldStatus) {
            $lead->update($request->validated());

            if ($statusChanged) {
                $lead->activities()->create([
                    'type'    => 'status_change',
                    'subject' => 'تغيير الحالة',
                    'body'    => 'من "' . (Lead::STATUS_LABELS[$oldStatus] ?? $oldStatus)
                              . '" إلى "' . $lead->status_label . '"',
                ]);
            }
        });

        Cache::forget(self::STATS_CACHE_KEY);

        return redirect()
            ->route('admin.crm.leads.show', $lead)
            ->with('success', 'تم تحديث بيانات العميل المحتمل');
    }

    public function destroy(Lead $lead)
    {
        if ($lead->isConverted()) {
            return response()->json([
                'message' => 'لا يمكن حذف عميل محتمل تم تحويله بالفعل',
            ], 422);
        }

        $lead->delete();
        Cache::forget(self::STATS_CACHE_KEY);

        return response()->json(['message' => 'تم حذف العميل المحتمل بنجاح']);
    }

    /** Quick status change (AJAX from Kanban drag-drop). */
    public function updateStatus(Request $request, Lead $lead)
    {
        $data = $request->validate([
            'status'      => ['required', 'in:new,contacted,qualified,proposal,won,lost'],
            'lost_reason' => ['nullable', 'string', 'max:200'],
        ]);

        if ($data['status'] === $lead->status) {
            return response()->json(['message' => 'لا يوجد تغيير'], 200);
        }

        $oldLabel = $lead->status_label;

        DB::transaction(function () use ($lead, $data, $oldLabel) {
            $lead->update($data);
            $lead->activities()->create([
                'type'    => 'status_change',
                'subject' => 'تغيير الحالة',
                'body'    => 'من "' . $oldLabel . '" إلى "' . $lead->status_label . '"',
            ]);
        });

        Cache::forget(self::STATS_CACHE_KEY);

        return response()->json([
            'message'      => 'تم تحديث الحالة',
            'status_label' => $lead->status_label,
            'status_badge' => $lead->status_badge,
        ]);
    }

    /**
     * Convert a lead to a Customer record. Doesn't delete the lead —
     * keeps history + links to the new customer.
     */
    public function convertToCustomer(Lead $lead)
    {
        if ($lead->isConverted()) {
            return back()->with('error', 'هذا العميل المحتمل تم تحويله بالفعل');
        }

        $customer = DB::transaction(function () use ($lead) {
            $customer = Customer::create([
                'full_name'  => $lead->full_name,
                'phone'      => $lead->phone,
                'mobile'     => $lead->phone,
                'whatsapp'   => $lead->whatsapp,
                'email'      => $lead->email,
                'city'       => $lead->city,
                'country'    => 'مصر',
                'type'       => 'individual',
                'status'     => 'active',
                'notes'      => "تم التحويل من lead {$lead->code}",
            ]);

            $lead->update([
                'converted_to_customer_id' => $customer->id,
                'converted_at'             => now(),
                'status'                   => 'won',
            ]);

            $lead->activities()->create([
                'type'    => 'status_change',
                'subject' => 'تحويل لعميل',
                'body'    => 'تم إنشاء عميل بكود ' . $customer->code,
            ]);

            return $customer;
        });

        Cache::forget(self::STATS_CACHE_KEY);

        return redirect()
            ->route('admin.crm.leads.show', $lead)
            ->with('success', "تم تحويل العميل المحتمل إلى عميل: {$customer->code}");
    }

    private function formData(): array
    {
        return [
            'employees' => User::orderBy('name')->select('id', 'name')->get(),
        ];
    }
}
