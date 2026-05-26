<?php

namespace App\Http\Controllers\Admin\Hr;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PositionRequest;
use App\Models\Department;
use App\Models\Position;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class PositionController extends Controller
{
    private const STATS_CACHE_KEY = 'positions.kpi_stats';
    private const STATS_TTL       = 600;

    public function index()
    {
        $stats = Cache::remember(self::STATS_CACHE_KEY, self::STATS_TTL, function () {
            return [
                'total'           => Position::count(),
                'active'          => Position::where('is_active', true)->count(),
                'inactive'        => Position::where('is_active', false)->count(),
                'with_commission' => Position::where('commission_rate', '>', 0)->count(),
            ];
        });

        $departments = Department::active()->orderBy('name')->get(['id', 'name']);

        return view('admin.hr.positions.index', compact('stats', 'departments'));
    }

    public function data(Request $request)
    {
        $cols = ['id', 'code', 'title', 'title_en', 'department_id',
                 'default_basic_salary', 'default_housing_allowance',
                 'default_transport_allowance', 'default_other_allowances',
                 'commission_rate', 'commission_basis',
                 'is_active', 'created_at'];

        $query = Position::query()->select($cols)
            ->with(['department:id,name,code'])
            ->withCount('employees');

        if ($request->filled('status_filter')) {
            $query->where('is_active', $request->status_filter === 'active' ? 1 : 0);
        }

        if ($request->filled('department_filter')) {
            if ($request->department_filter === 'unassigned') {
                $query->whereNull('department_id');
            } else {
                $query->where('department_id', $request->department_filter);
            }
        }

        if ($request->filled('q')) {
            $term = trim((string) $request->q);
            $query->where(function ($q) use ($term) {
                $q->where('title', 'like', "%{$term}%")
                  ->orWhere('code', 'like', "%{$term}%")
                  ->orWhere('title_en', 'like', "%{$term}%");
            });
        }

        return DataTables::eloquent($query)
            ->addColumn('position_info', function (Position $p) {
                return '<div>'
                    . '<div><strong>' . e($p->title) . '</strong></div>'
                    . '<div class="text-muted small"><i class="bi bi-hash"></i>' . e($p->code) . '</div>'
                    . '</div>';
            })
            ->addColumn('department_label', fn (Position $p) =>
                $p->department
                    ? '<span class="badge bg-info-soft"><i class="bi bi-diagram-3"></i> ' . e($p->department->name) . '</span>'
                    : '<span class="text-muted small">— غير مرتبطة بقسم —</span>'
            )
            ->addColumn('salary_breakdown', function (Position $p) {
                $total = $p->total_default_salary;
                if ($total <= 0) {
                    return '<span class="text-muted small">لم يُحدَّد</span>';
                }
                return '<div class="small">'
                    . '<div><strong class="text-success">' . number_format($total, 2) . '</strong> ج.م</div>'
                    . '<div class="text-muted x-small">أساسي: ' . number_format((float) $p->default_basic_salary, 0) . '</div>'
                    . '</div>';
            })
            ->addColumn('commission_info', function (Position $p) {
                if ((float) $p->commission_rate <= 0) {
                    return '<span class="text-muted small">—</span>';
                }
                $basisLabel = $p->commission_basis_label;
                return '<div class="small">'
                    . '<div><strong class="text-warning">' . number_format((float) $p->commission_rate, 2) . '%</strong></div>'
                    . '<div class="text-muted x-small">من ' . e($basisLabel) . '</div>'
                    . '</div>';
            })
            ->addColumn('employees_count_col', fn (Position $p) =>
                '<div class="text-center"><span class="badge bg-secondary-soft">'
                . '<i class="bi bi-people"></i> ' . $p->employees_count . '</span></div>'
            )
            ->editColumn('is_active', fn (Position $p) =>
                $p->is_active
                    ? '<span class="badge bg-success-soft"><i class="bi bi-check-circle"></i> نشطة</span>'
                    : '<span class="badge bg-secondary-soft"><i class="bi bi-pause-circle"></i> متوقفة</span>'
            )
            ->addColumn('actions', function (Position $p) {
                $user = auth()->user();
                $buttons = '<a href="' . route('admin.hr.positions.show', $p) . '" class="btn btn-icon btn-sm btn-light-primary" title="عرض"><i class="bi bi-eye"></i></a> ';
                if ($user && $user->can('positions.update')) {
                    $buttons .= '<a href="' . route('admin.hr.positions.edit', $p) . '" class="btn btn-icon btn-sm btn-light-info" title="تعديل"><i class="bi bi-pencil"></i></a> ';
                }
                if ($user && $user->can('positions.delete')) {
                    $buttons .= '<button data-url="' . route('admin.hr.positions.destroy', $p) . '" class="btn btn-icon btn-sm btn-light-danger btn-delete" title="حذف"><i class="bi bi-trash"></i></button>';
                }
                return '<div class="table-actions">' . $buttons . '</div>';
            })
            ->rawColumns(['position_info', 'department_label', 'salary_breakdown',
                          'commission_info', 'employees_count_col', 'is_active', 'actions'])
            ->make(true);
    }

    public function create()
    {
        $departments = Department::active()->orderBy('name')->get(['id', 'name']);

        return view('admin.hr.positions.create', compact('departments'));
    }

    public function store(PositionRequest $request)
    {
        $position = DB::transaction(fn () => Position::create($request->validated()));

        Cache::forget(self::STATS_CACHE_KEY);

        return redirect()
            ->route('admin.hr.positions.show', $position)
            ->with('success', 'تم إنشاء الوظيفة بنجاح');
    }

    public function show(Position $position)
    {
        $position->load('department')->loadCount('employees');

        return view('admin.hr.positions.show', compact('position'));
    }

    public function edit(Position $position)
    {
        $departments = Department::active()->orderBy('name')->get(['id', 'name']);

        return view('admin.hr.positions.edit', compact('position', 'departments'));
    }

    public function update(PositionRequest $request, Position $position)
    {
        DB::transaction(fn () => $position->update($request->validated()));

        Cache::forget(self::STATS_CACHE_KEY);

        return redirect()
            ->route('admin.hr.positions.show', $position)
            ->with('success', 'تم تحديث الوظيفة');
    }

    public function destroy(Position $position)
    {
        if ($position->employees()->exists()) {
            return response()->json([
                'message' => 'لا يمكن حذف الوظيفة — مرتبطة بموظفين. عطّلها بدلاً من الحذف.',
            ], 422);
        }

        $position->delete();
        Cache::forget(self::STATS_CACHE_KEY);

        return response()->json(['message' => 'تم حذف الوظيفة']);
    }
}
