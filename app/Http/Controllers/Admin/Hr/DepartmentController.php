<?php

namespace App\Http\Controllers\Admin\Hr;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DepartmentRequest;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class DepartmentController extends Controller
{
    private const STATS_CACHE_KEY = 'departments.kpi_stats';
    private const STATS_TTL       = 600;

    public function index()
    {
        $stats = Cache::remember(self::STATS_CACHE_KEY, self::STATS_TTL, function () {
            return [
                'total'    => Department::count(),
                'active'   => Department::where('is_active', true)->count(),
                'inactive' => Department::where('is_active', false)->count(),
                'global'   => Department::whereNull('branch_id')->count(),
            ];
        });

        $branches = Branch::active()->orderBy('name')->get(['id', 'name']);

        return view('admin.hr.departments.index', compact('stats', 'branches'));
    }

    public function data(Request $request)
    {
        $cols = ['id', 'code', 'name', 'name_en', 'branch_id',
                 'manager_employee_id', 'is_active', 'created_at'];

        $query = Department::query()->select($cols)
            ->with([
                'branch:id,name',
                'manager:id,code,full_name',
            ])
            ->withCount(['positions', 'employees']);

        if ($request->filled('status_filter')) {
            $query->where('is_active', $request->status_filter === 'active' ? 1 : 0);
        }

        if ($request->filled('branch_filter')) {
            if ($request->branch_filter === 'global') {
                $query->whereNull('branch_id');
            } else {
                $query->where('branch_id', $request->branch_filter);
            }
        }

        if ($request->filled('q')) {
            $term = trim((string) $request->q);
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                  ->orWhere('code', 'like', "%{$term}%")
                  ->orWhere('name_en', 'like', "%{$term}%");
            });
        }

        return DataTables::eloquent($query)
            ->addColumn('dept_info', function (Department $d) {
                return '<div>'
                    . '<div><strong>' . e($d->name) . '</strong></div>'
                    . '<div class="text-muted small"><i class="bi bi-hash"></i>' . e($d->code) . '</div>'
                    . '</div>';
            })
            ->addColumn('branch_label', fn (Department $d) =>
                $d->branch
                    ? '<span class="badge bg-info-soft"><i class="bi bi-buildings"></i> ' . e($d->branch->name) . '</span>'
                    : '<span class="badge bg-secondary-soft"><i class="bi bi-globe"></i> قسم عام</span>'
            )
            ->addColumn('manager_label', function (Department $d) {
                if (!$d->manager) {
                    return '<span class="text-muted">—</span>';
                }
                return '<i class="bi bi-person-badge text-primary"></i> ' . e($d->manager->full_name)
                    . '<div class="small text-muted">' . e($d->manager->code) . '</div>';
            })
            ->addColumn('stats', fn (Department $d) =>
                '<div class="small">'
                . '<div><i class="bi bi-people"></i> ' . $d->employees_count . ' موظف</div>'
                . '<div class="text-muted"><i class="bi bi-briefcase"></i> ' . $d->positions_count . ' وظيفة</div>'
                . '</div>'
            )
            ->editColumn('is_active', fn (Department $d) =>
                $d->is_active
                    ? '<span class="badge bg-success-soft"><i class="bi bi-check-circle"></i> نشط</span>'
                    : '<span class="badge bg-secondary-soft"><i class="bi bi-pause-circle"></i> متوقف</span>'
            )
            ->addColumn('actions', function (Department $d) {
                $user = auth()->user();
                $buttons = '<a href="' . route('admin.hr.departments.show', $d) . '" class="btn btn-icon btn-sm btn-light-primary" title="عرض"><i class="bi bi-eye"></i></a> ';
                if ($user && $user->can('departments.update')) {
                    $buttons .= '<a href="' . route('admin.hr.departments.edit', $d) . '" class="btn btn-icon btn-sm btn-light-info" title="تعديل"><i class="bi bi-pencil"></i></a> ';
                }
                if ($user && $user->can('departments.delete')) {
                    $buttons .= '<button data-url="' . route('admin.hr.departments.destroy', $d) . '" class="btn btn-icon btn-sm btn-light-danger btn-delete" title="حذف"><i class="bi bi-trash"></i></button>';
                }
                return '<div class="table-actions">' . $buttons . '</div>';
            })
            ->rawColumns(['dept_info', 'branch_label', 'manager_label', 'stats', 'is_active', 'actions'])
            ->make(true);
    }

    public function create()
    {
        $branches  = Branch::active()->orderBy('name')->get(['id', 'name']);
        $employees = Employee::query()
            ->where('status', 'active')
            ->orderBy('full_name')
            ->get(['id', 'code', 'full_name']);

        return view('admin.hr.departments.create', compact('branches', 'employees'));
    }

    public function store(DepartmentRequest $request)
    {
        $department = DB::transaction(fn () => Department::create($request->validated()));

        Cache::forget(self::STATS_CACHE_KEY);

        return redirect()
            ->route('admin.hr.departments.show', $department)
            ->with('success', 'تم إنشاء القسم بنجاح');
    }

    public function show(Department $department)
    {
        $department->load(['branch', 'manager'])
                   ->loadCount(['positions', 'employees']);

        return view('admin.hr.departments.show', compact('department'));
    }

    public function edit(Department $department)
    {
        $branches  = Branch::active()->orderBy('name')->get(['id', 'name']);
        $employees = Employee::query()
            ->where('status', 'active')
            ->orderBy('full_name')
            ->get(['id', 'code', 'full_name']);

        return view('admin.hr.departments.edit', compact('department', 'branches', 'employees'));
    }

    public function update(DepartmentRequest $request, Department $department)
    {
        DB::transaction(fn () => $department->update($request->validated()));

        Cache::forget(self::STATS_CACHE_KEY);

        return redirect()
            ->route('admin.hr.departments.show', $department)
            ->with('success', 'تم تحديث القسم');
    }

    public function destroy(Department $department)
    {
        if ($department->employees()->exists() || $department->positions()->exists()) {
            return response()->json([
                'message' => 'لا يمكن حذف القسم — مرتبط بموظفين أو وظائف. عطّله بدلاً من الحذف.',
            ], 422);
        }

        $department->delete();
        Cache::forget(self::STATS_CACHE_KEY);

        return response()->json(['message' => 'تم حذف القسم']);
    }
}
