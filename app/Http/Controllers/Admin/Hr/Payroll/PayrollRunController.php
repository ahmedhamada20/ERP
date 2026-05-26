<?php

namespace App\Http\Controllers\Admin\Hr\Payroll;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PayrollRunRequest;
use App\Models\Branch;
use App\Models\PayrollRun;
use App\Services\Payroll\PayrollPostingService;
use App\Services\Payroll\PayrollService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Yajra\DataTables\Facades\DataTables;

class PayrollRunController extends Controller
{
    private const STATS_CACHE_KEY = 'payroll.runs.kpi_stats';
    private const STATS_TTL       = 600;

    public function __construct(
        private readonly PayrollService $payroll,
        private readonly PayrollPostingService $posting,
    ) {}

    public function index()
    {
        $stats = Cache::remember(self::STATS_CACHE_KEY, self::STATS_TTL, function () {
            return [
                'total'      => PayrollRun::count(),
                'draft'      => PayrollRun::where('status', PayrollRun::STATUS_DRAFT)->count(),
                'calculated' => PayrollRun::where('status', PayrollRun::STATUS_CALCULATED)->count(),
                'approved'   => PayrollRun::where('status', PayrollRun::STATUS_APPROVED)->count(),
                'posted'     => PayrollRun::where('status', PayrollRun::STATUS_POSTED)->count(),
                'total_paid' => PayrollRun::posted()->sum('total_net'),
            ];
        });

        $branches = Branch::active()->orderBy('name')->get(['id', 'name']);
        $years    = range((int) date('Y'), (int) date('Y') - 3);

        return view('admin.hr.payroll.runs.index', compact('stats', 'branches', 'years'));
    }

    public function data(Request $request)
    {
        $query = PayrollRun::query()
            ->select(['id', 'run_code', 'branch_id', 'period_year', 'period_month',
                      'payment_date', 'status', 'employees_count',
                      'total_earnings', 'total_net', 'created_at'])
            ->with(['branch:id,name']);

        if ($request->filled('status_filter')) {
            $query->where('status', $request->status_filter);
        }
        if ($request->filled('branch_filter')) {
            $query->where('branch_id', $request->branch_filter);
        }
        if ($request->filled('year_filter')) {
            $query->where('period_year', (int) $request->year_filter);
        }
        if ($request->filled('q')) {
            $term = trim((string) $request->q);
            $query->where('run_code', 'like', "%{$term}%");
        }

        return DataTables::eloquent($query)
            ->addColumn('run_info', function (PayrollRun $r) {
                return '<div>'
                    . '<div><strong>' . e($r->run_code) . '</strong></div>'
                    . '<div class="text-muted small">' . e($r->period_label) . '</div>'
                    . '</div>';
            })
            ->addColumn('branch_name', fn (PayrollRun $r) =>
                $r->branch
                    ? '<i class="bi bi-buildings text-primary"></i> ' . e($r->branch->name)
                    : '<span class="text-muted">—</span>'
            )
            ->editColumn('employees_count', fn (PayrollRun $r) =>
                '<span class="badge bg-light text-dark"><i class="bi bi-people"></i> '
                . number_format($r->employees_count) . '</span>'
            )
            ->editColumn('total_earnings', fn (PayrollRun $r) =>
                '<div class="text-end small"><strong>' . number_format((float) $r->total_earnings, 2)
                . '</strong> <span class="text-muted x-small">ج.م</span></div>'
            )
            ->editColumn('total_net', fn (PayrollRun $r) =>
                '<div class="text-end small"><strong class="text-success">'
                . number_format((float) $r->total_net, 2)
                . '</strong> <span class="text-muted x-small">ج.م</span></div>'
            )
            ->editColumn('status', fn (PayrollRun $r) =>
                '<span class="badge bg-' . $r->status_badge . '-soft">' . e($r->status_label) . '</span>'
            )
            ->editColumn('payment_date', fn (PayrollRun $r) =>
                $r->payment_date
                    ? '<div class="small">' . $r->payment_date->format('Y-m-d') . '</div>'
                    : '<span class="text-muted">—</span>'
            )
            ->addColumn('actions', function (PayrollRun $r) {
                $user = auth()->user();
                $buttons = '<a href="' . route('admin.hr.payroll.runs.show', $r) . '" class="btn btn-icon btn-sm btn-light-primary" title="عرض"><i class="bi bi-eye"></i></a> ';
                if ($user && $user->can('payroll.process') && $r->canCancel() && $r->isDraft()) {
                    $buttons .= '<button data-url="' . route('admin.hr.payroll.runs.destroy', $r) . '" class="btn btn-icon btn-sm btn-light-danger btn-delete" title="حذف"><i class="bi bi-trash"></i></button>';
                }
                return '<div class="table-actions">' . $buttons . '</div>';
            })
            ->rawColumns(['run_info', 'branch_name', 'employees_count', 'total_earnings', 'total_net', 'status', 'payment_date', 'actions'])
            ->toJson();
    }

    public function create()
    {
        $branches = Branch::active()->orderBy('name')->get(['id', 'name']);
        $userBranchId = auth()->user()?->employee?->branch_id;

        return view('admin.hr.payroll.runs.create', [
            'branches'     => $branches,
            'userBranchId' => $userBranchId,
            'currentYear'  => (int) date('Y'),
            'currentMonth' => (int) date('n'),
        ]);
    }

    public function store(PayrollRunRequest $request)
    {
        $run = PayrollRun::create($request->validated());

        Cache::forget(self::STATS_CACHE_KEY);

        return redirect()
            ->route('admin.hr.payroll.runs.show', $run)
            ->with('success', 'تم إنشاء دورة الرواتب ' . $run->run_code . ' بنجاح. اضغط "احسب الدورة" لبدء الاحتساب.');
    }

    public function show(PayrollRun $run)
    {
        $run->load([
            'branch',
            'payslips' => fn ($q) => $q->orderBy('created_at'),
            'payslips.employee:id,code,full_name,phone,photo',
            'payslips.lines',
            'journalEntry',
            'creator', 'calculator', 'approver', 'poster',
        ]);

        return view('admin.hr.payroll.runs.show', compact('run'));
    }

    public function destroy(PayrollRun $run)
    {
        if (! $run->isDraft()) {
            return back()->with('error', 'لا يمكن حذف دورة رواتب بعد البدء في احتسابها. استخدم الإلغاء بدلاً منها.');
        }

        $run->delete();
        Cache::forget(self::STATS_CACHE_KEY);

        return redirect()
            ->route('admin.hr.payroll.runs.index')
            ->with('success', 'تم حذف دورة الرواتب.');
    }

    // ── Workflow actions ────────────────────────────────────────────────

    public function calculate(PayrollRun $run)
    {
        try {
            $this->payroll->calculate($run);
            Cache::forget(self::STATS_CACHE_KEY);
            return back()->with('success', 'تم احتساب دورة الرواتب: ' . $run->fresh()->employees_count . ' موظف.');
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function approve(PayrollRun $run)
    {
        try {
            $this->payroll->approve($run);
            Cache::forget(self::STATS_CACHE_KEY);
            return back()->with('success', 'تم اعتماد دورة الرواتب — جاهزة للترحيل المحاسبي.');
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function post(PayrollRun $run)
    {
        try {
            $entry = $this->posting->post($run);
            Cache::forget(self::STATS_CACHE_KEY);
            return back()->with('success',
                'تم ترحيل دورة الرواتب إلى دفتر اليومية برقم القيد: ' . $entry->number);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function cancel(Request $request, PayrollRun $run)
    {
        $request->validate(['reason' => 'required|string|min:3|max:500']);

        try {
            $this->posting->cancel($run, $request->reason);
            Cache::forget(self::STATS_CACHE_KEY);
            return back()->with('success', 'تم إلغاء دورة الرواتب وعكس قيدها المحاسبي.');
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
