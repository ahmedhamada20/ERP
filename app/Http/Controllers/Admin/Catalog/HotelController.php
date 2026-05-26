<?php

namespace App\Http\Controllers\Admin\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Catalog\HotelRequest;
use App\Models\Hotel;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class HotelController extends Controller
{
    public function index() { return view('admin.catalog.hotels.index'); }

    public function data(Request $request)
    {
        $query = Hotel::query()->select([
            'id', 'code', 'name', 'name_en', 'city', 'grade',
            'distance_meters', 'base_price_per_night', 'currency',
            'max_occupancy', 'cover_image', 'is_active', 'created_at',
        ]);

        if ($request->filled('city'))   $query->where('city', $request->city);
        if ($request->filled('grade'))  $query->where('grade', $request->grade);
        if ($request->filled('status')) $query->where('is_active', $request->status === '1');

        return DataTables::eloquent($query)
            ->addColumn('hotel_info', function (Hotel $h) {
                $cover = $h->cover_image
                    ? '<img src="' . asset('storage/' . $h->cover_image) . '" class="hotel-thumb">'
                    : '<div class="hotel-thumb hotel-thumb-empty"><i class="bi bi-building"></i></div>';
                $name_en = $h->name_en ? '<div class="small text-muted" dir="ltr">' . e($h->name_en) . '</div>' : '';
                return '<div class="d-flex align-items-center gap-2">'
                    . $cover
                    . '<div><strong>' . e($h->name) . '</strong>' . $name_en
                    . '<div class="small text-muted">' . e($h->code) . '</div></div></div>';
            })
            ->editColumn('city', fn (Hotel $h) =>
                '<span class="badge bg-primary-soft">' . $h->city_label . '</span>'
            )
            ->editColumn('grade', fn (Hotel $h) =>
                '<span style="font-size:.95rem;">' . $h->grade_stars . '</span>'
            )
            ->addColumn('distance', fn (Hotel $h) =>
                $h->distance_meters
                    ? '<small><i class="bi bi-geo-alt"></i> ' . number_format($h->distance_meters) . ' م</small>'
                    : '<small class="text-muted">—</small>'
            )
            ->editColumn('base_price_per_night', fn (Hotel $h) =>
                '<strong>' . number_format($h->base_price_per_night, 2) . '</strong> '
                . '<small class="text-muted">' . e($h->currency) . '</small>'
            )
            ->editColumn('is_active', fn (Hotel $h) =>
                $h->is_active
                    ? '<span class="badge bg-success-soft">نشط</span>'
                    : '<span class="badge bg-secondary-soft">متوقف</span>'
            )
            ->addColumn('actions', function (Hotel $h) {
                $user = auth()->user();
                $html = '';
                if ($user?->can('catalog.hotels.manage')) {
                    $html .= '<a href="' . route('admin.catalog.hotels.edit', $h) . '" class="btn btn-icon btn-sm btn-light-primary"><i class="bi bi-pencil"></i></a> ';
                    $html .= '<button class="btn btn-icon btn-sm btn-light-danger btn-delete" data-url="' . route('admin.catalog.hotels.destroy', $h) . '"><i class="bi bi-trash"></i></button>';
                }
                return $html;
            })
            ->rawColumns(['hotel_info', 'city', 'grade', 'distance', 'base_price_per_night', 'is_active', 'actions'])
            ->make(true);
    }

    public function create() { return view('admin.catalog.hotels.create'); }

    public function store(HotelRequest $request)
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);
        if ($request->hasFile('cover_image')) {
            $data['cover_image'] = $request->file('cover_image')->store('hotels', 'public');
        }
        Hotel::create($data);
        return redirect()->route('admin.catalog.hotels.index')->with('success', 'تم إضافة الفندق');
    }

    public function edit(Hotel $hotel) { return view('admin.catalog.hotels.edit', compact('hotel')); }

    public function update(HotelRequest $request, Hotel $hotel)
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', false);
        if ($request->hasFile('cover_image')) {
            $data['cover_image'] = $request->file('cover_image')->store('hotels', 'public');
        }
        $hotel->update($data);
        return redirect()->route('admin.catalog.hotels.index')->with('success', 'تم تحديث الفندق');
    }

    public function destroy(Hotel $hotel)
    {
        $hotel->delete();
        return response()->json(['message' => 'تم حذف الفندق']);
    }
}
