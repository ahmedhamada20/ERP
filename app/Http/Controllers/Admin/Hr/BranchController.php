<?php

namespace App\Http\Controllers\Admin\Hr;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BranchRequest;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class BranchController extends Controller
{
    private const STATS_CACHE_KEY = 'branches.kpi_stats';
    private const STATS_TTL       = 600;

    public function index()
    {
        $stats = Cache::remember(self::STATS_CACHE_KEY, self::STATS_TTL, function () {
            return [
                'total'    => Branch::count(),
                'active'   => Branch::where('is_active', true)->count(),
                'inactive' => Branch::where('is_active', false)->count(),
                'main'     => Branch::where('is_main', true)->value('name'),
            ];
        });

        return view('admin.hr.branches.index', compact('stats'));
    }

    public function data(Request $request)
    {
        $cols = ['id', 'code', 'name', 'name_en', 'city', 'governorate',
                 'phone', 'manager_name', 'is_main', 'is_active', 'created_at'];

        $query = Branch::query()->select($cols)
            ->withCount(['employees', 'departments']);

        if ($request->filled('status_filter')) {
            $query->where('is_active', $request->status_filter === 'active' ? 1 : 0);
        }

        if ($request->filled('q')) {
            $term = trim((string) $request->q);
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                  ->orWhere('code', 'like', "%{$term}%")
                  ->orWhere('city', 'like', "%{$term}%")
                  ->orWhere('manager_name', 'like', "%{$term}%");
            });
        }

        return DataTables::eloquent($query)
            ->addColumn('branch_info', function (Branch $b) {
                $mainBadge = $b->is_main
                    ? ' <span class="badge bg-warning text-dark x-small"><i class="bi bi-star-fill"></i> رئيسي</span>'
                    : '';
                return '<div>'
                    . '<div><strong>' . e($b->name) . '</strong>' . $mainBadge . '</div>'
                    . '<div class="text-muted small"><i class="bi bi-hash"></i>' . e($b->code) . '</div>'
                    . '</div>';
            })
            ->addColumn('location', fn (Branch $b) =>
                $b->city
                    ? '<i class="bi bi-geo-alt text-danger"></i> <strong>' . e($b->city) . '</strong>'
                      . ($b->governorate ? '<div class="small text-muted">' . e($b->governorate) . '</div>' : '')
                    : '<span class="text-muted">—</span>'
            )
            ->addColumn('contact', function (Branch $b) {
                $html = '<div class="small">';
                if ($b->phone) {
                    $html .= '<div><i class="bi bi-telephone text-primary"></i> <span dir="ltr">' . e($b->phone) . '</span></div>';
                }
                if ($b->manager_name) {
                    $html .= '<div><i class="bi bi-person"></i> ' . e($b->manager_name) . '</div>';
                }
                $html .= '</div>';
                return $html ?: '<span class="text-muted">—</span>';
            })
            ->addColumn('stats', fn (Branch $b) =>
                '<div class="small">'
                . '<div><i class="bi bi-people"></i> ' . $b->employees_count . ' موظف</div>'
                . '<div class="text-muted"><i class="bi bi-diagram-3"></i> ' . $b->departments_count . ' قسم</div>'
                . '</div>'
            )
            ->editColumn('is_active', fn (Branch $b) =>
                $b->is_active
                    ? '<span class="badge bg-success-soft"><i class="bi bi-check-circle"></i> نشط</span>'
                    : '<span class="badge bg-secondary-soft"><i class="bi bi-pause-circle"></i> متوقف</span>'
            )
            ->addColumn('actions', function (Branch $b) {
                $user = auth()->user();
                $buttons = '<a href="' . route('admin.hr.branches.show', $b) . '" class="btn btn-icon btn-sm btn-light-primary" title="عرض"><i class="bi bi-eye"></i></a> ';
                if ($user && $user->can('branches.update')) {
                    $buttons .= '<a href="' . route('admin.hr.branches.edit', $b) . '" class="btn btn-icon btn-sm btn-light-info" title="تعديل"><i class="bi bi-pencil"></i></a> ';
                }
                if ($user && $user->can('branches.update') && !$b->is_main) {
                    $buttons .= '<form action="' . route('admin.hr.branches.set_main', $b) . '" method="POST" class="d-inline" onsubmit="return confirm(\'تعيين هذا الفرع كرئيسي؟ ستفقد الحالة الحالية للفرع الرئيسي الحالي.\')">'
                              . csrf_field()
                              . '<button class="btn btn-icon btn-sm btn-warning" title="جعله رئيسي"><i class="bi bi-star"></i></button>'
                              . '</form> ';
                }
                if ($user && $user->can('branches.delete') && !$b->is_main) {
                    $buttons .= '<button data-url="' . route('admin.hr.branches.destroy', $b) . '" class="btn btn-icon btn-sm btn-light-danger btn-delete" title="حذف"><i class="bi bi-trash"></i></button>';
                }
                return '<div class="table-actions">' . $buttons . '</div>';
            })
            ->rawColumns(['branch_info', 'location', 'contact', 'stats', 'is_active', 'actions'])
            ->make(true);
    }

    public function create()
    {
        return view('admin.hr.branches.create');
    }

    public function store(BranchRequest $request)
    {
        $branch = DB::transaction(fn () => Branch::create($request->validated()));

        Cache::forget(self::STATS_CACHE_KEY);

        return redirect()
            ->route('admin.hr.branches.show', $branch)
            ->with('success', 'تم إنشاء الفرع بنجاح');
    }

    public function show(Branch $branch)
    {
        $branch->loadCount(['employees', 'departments']);

        // Per-table transaction counts for this branch
        $txnCounts = [
            'religious_bookings' => DB::table('religious_bookings')->where('branch_id', $branch->id)->count(),
            'domestic_bookings'  => DB::table('domestic_bookings')->where('branch_id', $branch->id)->count(),
            'customers'          => DB::table('customers')->where('branch_id', $branch->id)->count(),
            'suppliers'          => DB::table('suppliers')->where('branch_id', $branch->id)->count(),
            'vouchers'           => DB::table('vouchers')->where('branch_id', $branch->id)->count(),
        ];

        return view('admin.hr.branches.show', compact('branch', 'txnCounts'));
    }

    public function edit(Branch $branch)
    {
        return view('admin.hr.branches.edit', compact('branch'));
    }

    public function update(BranchRequest $request, Branch $branch)
    {
        DB::transaction(fn () => $branch->update($request->validated()));

        Cache::forget(self::STATS_CACHE_KEY);

        return redirect()
            ->route('admin.hr.branches.show', $branch)
            ->with('success', 'تم تحديث الفرع');
    }

    public function destroy(Branch $branch)
    {
        if ($branch->is_main) {
            return response()->json([
                'message' => 'لا يمكن حذف الفرع الرئيسي. عيّن فرعاً آخر كرئيسي أولاً.',
            ], 422);
        }

        $hasEmployees = $branch->employees()->exists();
        $hasTxns = DB::table('religious_bookings')->where('branch_id', $branch->id)->exists()
                 || DB::table('domestic_bookings')->where('branch_id', $branch->id)->exists()
                 || DB::table('vouchers')->where('branch_id', $branch->id)->exists();

        if ($hasEmployees || $hasTxns) {
            return response()->json([
                'message' => 'لا يمكن حذف الفرع — مرتبط بموظفين أو حجوزات. عطّله بدلاً من الحذف.',
            ], 422);
        }

        $branch->delete();
        Cache::forget(self::STATS_CACHE_KEY);

        return response()->json(['message' => 'تم حذف الفرع']);
    }

    /** Promote a branch to main — demotes the current main automatically. */
    public function setMain(Branch $branch)
    {
        if ($branch->is_main) {
            return back()->with('info', 'هذا الفرع رئيسي بالفعل');
        }

        $branch->update(['is_main' => true]);
        Cache::forget(self::STATS_CACHE_KEY);

        return back()->with('success', "تم تعيين الفرع \"{$branch->name}\" كرئيسي");
    }
}
