<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DomesticProgramRequest;
use App\Models\DomesticProgram;
use App\Traits\HandlesImageUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class DomesticProgramController extends Controller
{
    use HandlesImageUpload;

    private const STATS_CACHE_KEY = 'domestic_programs.kpi_stats';
    private const STATS_TTL       = 300;

    public function index()
    {
        $stats = Cache::remember(self::STATS_CACHE_KEY, self::STATS_TTL, function () {
            $row = DB::table('domestic_programs')
                ->selectRaw("
                    COUNT(*) AS total,
                    SUM(CASE WHEN type = 'package'    THEN 1 ELSE 0 END) AS package_total,
                    SUM(CASE WHEN type = 'hotel_only' THEN 1 ELSE 0 END) AS hotel_total,
                    SUM(CASE WHEN type = 'cruise'     THEN 1 ELSE 0 END) AS cruise_total,
                    SUM(CASE WHEN is_active = 1    THEN 1 ELSE 0 END) AS active,
                    SUM(CASE WHEN is_published = 1 THEN 1 ELSE 0 END) AS published
                ")
                ->whereNull('deleted_at')
                ->first();

            return [
                'total'         => (int) ($row->total ?? 0),
                'package_total' => (int) ($row->package_total ?? 0),
                'hotel_total'   => (int) ($row->hotel_total ?? 0),
                'cruise_total'  => (int) ($row->cruise_total ?? 0),
                'active'        => (int) ($row->active ?? 0),
                'published'     => (int) ($row->published ?? 0),
            ];
        });

        return view('admin.domestic.programs.index', compact('stats'));
    }

    public function data(Request $request)
    {
        $cols = [
            'id', 'code', 'name', 'name_en', 'type', 'season',
            'destination_city', 'destination_area',
            'start_date', 'end_date', 'duration_days', 'duration_nights',
            'base_price_per_person', 'min_guests', 'max_guests',
            'cover_image', 'is_active', 'is_published', 'created_at',
        ];

        $query = DomesticProgram::query()->select($cols);

        if ($request->filled('type_filter')) {
            $query->where('type', $request->type_filter);
        }
        if ($request->filled('status_filter')) {
            $query->where('is_active', $request->status_filter === 'active' ? 1 : 0);
        }
        if ($request->filled('city_filter')) {
            $query->where('destination_city', $request->city_filter);
        }
        if ($request->filled('q')) {
            $term = trim((string) $request->q);
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                  ->orWhere('name_en', 'like', "%{$term}%")
                  ->orWhere('code', 'like', "%{$term}%")
                  ->orWhere('season', 'like', "%{$term}%")
                  ->orWhere('destination_city', 'like', "%{$term}%");
            });
        }

        return DataTables::eloquent($query)
            ->addColumn('program_info', function (DomesticProgram $p) {
                $sub = $p->season ? '<div class="cust-sub small">' . e($p->season) . '</div>' : '';
                return '<div class="cust-cell">'
                    . '<img src="' . $p->cover_url . '" class="cust-avatar" loading="lazy">'
                    . '<div class="cust-body">'
                    . '<div class="cust-name">' . e($p->name) . '</div>'
                    . '<div class="cust-code"><i class="bi bi-hash"></i> ' . e($p->code) . '</div>'
                    . $sub
                    . '</div></div>';
            })
            ->editColumn('type', function (DomesticProgram $p) {
                $icons = [
                    'hotel_only' => 'building',
                    'package'    => 'bag-check',
                    'day_trip'   => 'sun',
                    'cruise'     => 'water',
                    'camp'       => 'tree',
                    'event'      => 'calendar-event',
                ];
                $colors = [
                    'hotel_only' => 'info',
                    'package'    => 'primary',
                    'day_trip'   => 'warning',
                    'cruise'     => 'success',
                    'camp'       => 'secondary',
                    'event'      => 'danger',
                ];
                $ic    = $icons[$p->type] ?? 'compass';
                $color = $colors[$p->type] ?? 'secondary';
                return '<span class="badge bg-' . $color . '-soft"><i class="bi bi-' . $ic . '"></i> ' . e($p->type_label) . '</span>';
            })
            ->addColumn('destination', fn (DomesticProgram $p) =>
                '<div><i class="bi bi-geo-alt text-danger"></i> <strong>' . e($p->destination_city) . '</strong>'
                . ($p->destination_area ? '<div class="small text-muted">' . e($p->destination_area) . '</div>' : '')
                . '</div>'
            )
            ->editColumn('duration_days', fn (DomesticProgram $p) =>
                '<span class="badge bg-light text-dark">' . $p->duration_days . ' يوم'
                . ($p->duration_nights ? ' / ' . $p->duration_nights . ' ليلة' : '')
                . '</span>'
            )
            ->editColumn('base_price_per_person', fn (DomesticProgram $p) =>
                '<div><strong>' . number_format($p->base_price_per_person, 0) . '</strong> <small class="text-muted">ج.م</small></div>'
            )
            ->addColumn('capacity', fn (DomesticProgram $p) =>
                '<small>' . $p->min_guests . ' - ' . $p->max_guests . ' فرد</small>'
            )
            ->editColumn('is_active', fn (DomesticProgram $p) =>
                $p->is_active
                    ? '<span class="badge bg-success-soft"><i class="bi bi-check-circle"></i> نشط</span>'
                    : '<span class="badge bg-secondary-soft"><i class="bi bi-pause-circle"></i> متوقف</span>'
            )
            ->editColumn('created_at', fn (DomesticProgram $p) =>
                '<div class="small">' . $p->created_at?->format('Y-m-d') . '</div>'
            )
            ->addColumn('actions', function (DomesticProgram $p) {
                $user = auth()->user();
                $buttons = '<a href="' . route('admin.domestic.programs.show', $p) . '" class="btn btn-icon btn-sm btn-light-primary" title="عرض"><i class="bi bi-eye"></i></a> ';
                if ($user && $user->can('domestic_programs.update')) {
                    $buttons .= '<a href="' . route('admin.domestic.programs.edit', $p) . '" class="btn btn-icon btn-sm btn-light-info" title="تعديل"><i class="bi bi-pencil"></i></a> ';
                }
                if ($user && $user->can('domestic_programs.delete')) {
                    $buttons .= '<button data-url="' . route('admin.domestic.programs.destroy', $p) . '" class="btn btn-icon btn-sm btn-light-danger btn-delete" title="حذف"><i class="bi bi-trash"></i></button>';
                }
                return '<div class="table-actions">' . $buttons . '</div>';
            })
            ->rawColumns(['program_info', 'type', 'destination', 'duration_days', 'base_price_per_person', 'capacity', 'is_active', 'created_at', 'actions'])
            ->make(true);
    }

    public function create()
    {
        return view('admin.domestic.programs.create');
    }

    public function store(DomesticProgramRequest $request)
    {
        DB::transaction(function () use ($request) {
            $data = $request->validated();

            if ($request->hasFile('cover_image')) {
                $data['cover_image'] = $this->uploadImage($request->file('cover_image'), 'domestic/programs');
            }

            DomesticProgram::create($data);
        });

        Cache::forget(self::STATS_CACHE_KEY);

        return redirect()
            ->route('admin.domestic.programs.index')
            ->with('success', 'تم إنشاء البرنامج بنجاح');
    }

    public function show(DomesticProgram $program)
    {
        $program->load('creator');
        $bookingsCount = $program->bookings()->count();
        return view('admin.domestic.programs.show', compact('program', 'bookingsCount'));
    }

    public function edit(DomesticProgram $program)
    {
        return view('admin.domestic.programs.edit', compact('program'));
    }

    public function update(DomesticProgramRequest $request, DomesticProgram $program)
    {
        DB::transaction(function () use ($request, $program) {
            $data = $request->validated();

            if ($request->hasFile('cover_image')) {
                $data['cover_image'] = $this->uploadImage(
                    $request->file('cover_image'),
                    'domestic/programs',
                    $program->cover_image
                );
            }

            $program->update($data);
        });

        Cache::forget(self::STATS_CACHE_KEY);

        return redirect()
            ->route('admin.domestic.programs.index')
            ->with('success', 'تم تعديل البرنامج بنجاح');
    }

    public function destroy(DomesticProgram $program)
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
