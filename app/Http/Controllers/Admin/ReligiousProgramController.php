<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReligiousProgramRequest;
use App\Models\ReligiousProgram;
use App\Traits\HandlesImageUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class ReligiousProgramController extends Controller
{
    use HandlesImageUpload;

    private const STATS_CACHE_KEY = 'religious_programs.kpi_stats';
    private const STATS_TTL       = 300;

    public function index()
    {
        $stats = Cache::remember(self::STATS_CACHE_KEY, self::STATS_TTL, function () {
            $row = DB::table('religious_programs')
                ->selectRaw("
                    COUNT(*) AS total,
                    SUM(CASE WHEN type = 'hajj'  THEN 1 ELSE 0 END) AS hajj_total,
                    SUM(CASE WHEN type = 'umrah' THEN 1 ELSE 0 END) AS umrah_total,
                    SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) AS active,
                    SUM(CASE WHEN is_published = 1 THEN 1 ELSE 0 END) AS published
                ")
                ->whereNull('deleted_at')
                ->first();

            return [
                'total'       => (int) ($row->total ?? 0),
                'hajj_total'  => (int) ($row->hajj_total ?? 0),
                'umrah_total' => (int) ($row->umrah_total ?? 0),
                'active'      => (int) ($row->active ?? 0),
                'published'   => (int) ($row->published ?? 0),
            ];
        });

        return view('admin.religious.programs.index', compact('stats'));
    }

    public function data(Request $request)
    {
        $cols = [
            'id', 'code', 'name', 'name_en', 'type', 'season',
            'start_date', 'end_date', 'duration_days',
            'base_price_per_person', 'min_pilgrims', 'max_pilgrims',
            'cover_image', 'is_active', 'is_published', 'created_at',
        ];

        $query = ReligiousProgram::query()->select($cols);

        if ($request->filled('type_filter')) {
            $query->where('type', $request->type_filter);
        }
        if ($request->filled('status_filter')) {
            $query->where('is_active', $request->status_filter === 'active' ? 1 : 0);
        }
        if ($request->filled('q')) {
            $term = trim((string) $request->q);
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                  ->orWhere('name_en', 'like', "%{$term}%")
                  ->orWhere('code', 'like', "%{$term}%")
                  ->orWhere('season', 'like', "%{$term}%");
            });
        }

        return DataTables::eloquent($query)
            ->addColumn('program_info', function (ReligiousProgram $p) {
                $sub = $p->season ? '<div class="cust-sub small">' . e($p->season) . '</div>' : '';
                return '<div class="cust-cell">'
                    . '<img src="' . $p->cover_url . '" class="cust-avatar" loading="lazy">'
                    . '<div class="cust-body">'
                    . '<div class="cust-name">' . e($p->name) . '</div>'
                    . '<div class="cust-code"><i class="bi bi-hash"></i> ' . e($p->code) . '</div>'
                    . $sub
                    . '</div></div>';
            })
            ->editColumn('type', fn (ReligiousProgram $p) =>
                $p->type === 'hajj'
                    ? '<span class="badge bg-success-soft"><i class="bi bi-mosque"></i> حج</span>'
                    : '<span class="badge bg-info-soft"><i class="bi bi-moon-stars"></i> عمرة</span>'
            )
            ->editColumn('duration_days', fn (ReligiousProgram $p) =>
                '<span class="badge bg-light text-dark">' . $p->duration_days . ' يوم</span>'
            )
            ->editColumn('base_price_per_person', fn (ReligiousProgram $p) =>
                '<div><strong>' . number_format($p->base_price_per_person, 0) . '</strong> <small class="text-muted">ج.م</small></div>'
            )
            ->addColumn('capacity', fn (ReligiousProgram $p) =>
                '<small>' . $p->min_pilgrims . ' - ' . $p->max_pilgrims . ' فرد</small>'
            )
            ->editColumn('is_active', fn (ReligiousProgram $p) =>
                $p->is_active
                    ? '<span class="badge bg-success-soft"><i class="bi bi-check-circle"></i> نشط</span>'
                    : '<span class="badge bg-secondary-soft"><i class="bi bi-pause-circle"></i> متوقف</span>'
            )
            ->editColumn('created_at', fn (ReligiousProgram $p) =>
                '<div class="small">' . $p->created_at?->format('Y-m-d') . '</div>'
            )
            ->addColumn('actions', function (ReligiousProgram $p) {
                $user = auth()->user();
                $buttons = '<a href="' . route('admin.religious.programs.show', $p) . '" class="btn btn-icon btn-sm btn-light-primary" title="عرض"><i class="bi bi-eye"></i></a> ';
                if ($user && $user->can('religious_programs.update')) {
                    $buttons .= '<a href="' . route('admin.religious.programs.edit', $p) . '" class="btn btn-icon btn-sm btn-light-info" title="تعديل"><i class="bi bi-pencil"></i></a> ';
                }
                if ($user && $user->can('religious_programs.delete')) {
                    $buttons .= '<button data-url="' . route('admin.religious.programs.destroy', $p) . '" class="btn btn-icon btn-sm btn-light-danger btn-delete" title="حذف"><i class="bi bi-trash"></i></button>';
                }
                return '<div class="table-actions">' . $buttons . '</div>';
            })
            ->rawColumns(['program_info', 'type', 'duration_days', 'base_price_per_person', 'capacity', 'is_active', 'created_at', 'actions'])
            ->make(true);
    }

    public function create()
    {
        return view('admin.religious.programs.create');
    }

    public function store(ReligiousProgramRequest $request)
    {
        DB::transaction(function () use ($request) {
            $data = $request->validated();

            if ($request->hasFile('cover_image')) {
                $data['cover_image'] = $this->uploadImage($request->file('cover_image'), 'religious/programs');
            }

            ReligiousProgram::create($data);
        });

        Cache::forget(self::STATS_CACHE_KEY);

        return redirect()
            ->route('admin.religious.programs.index')
            ->with('success', 'تم إنشاء البرنامج بنجاح');
    }

    public function show(ReligiousProgram $program)
    {
        $program->load('creator');
        $bookingsCount = $program->bookings()->count();
        return view('admin.religious.programs.show', compact('program', 'bookingsCount'));
    }

    public function edit(ReligiousProgram $program)
    {
        return view('admin.religious.programs.edit', compact('program'));
    }

    public function update(ReligiousProgramRequest $request, ReligiousProgram $program)
    {
        DB::transaction(function () use ($request, $program) {
            $data = $request->validated();

            if ($request->hasFile('cover_image')) {
                $data['cover_image'] = $this->uploadImage(
                    $request->file('cover_image'),
                    'religious/programs',
                    $program->cover_image
                );
            }

            $program->update($data);
        });

        Cache::forget(self::STATS_CACHE_KEY);

        return redirect()
            ->route('admin.religious.programs.index')
            ->with('success', 'تم تعديل البرنامج بنجاح');
    }

    public function destroy(ReligiousProgram $program)
    {
        if ($program->bookings()->exists()) {
            return response()->json([
                'message' => 'لا يمكن حذف البرنامج لأنه مرتبط بحجوزات نشطة',
            ], 422);
        }

        $this->deleteImage($program->cover_image);
        $program->delete();

        Cache::forget(self::STATS_CACHE_KEY);

        return response()->json(['message' => 'تم حذف البرنامج بنجاح']);
    }
}
