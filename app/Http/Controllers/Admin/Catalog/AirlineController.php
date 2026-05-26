<?php

namespace App\Http\Controllers\Admin\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Catalog\AirlineRequest;
use App\Models\Airline;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class AirlineController extends Controller
{
    public function index() { return view('admin.catalog.airlines.index'); }

    public function data(Request $request)
    {
        $query = Airline::query()->select([
            'id', 'code', 'airline_name', 'airline_code', 'route',
            'cabin_class', 'base_price_per_pax', 'currency',
            'capacity', 'available_seats', 'is_active', 'created_at',
        ]);

        if ($request->filled('cabin')) $query->where('cabin_class', $request->cabin);
        if ($request->filled('status')) $query->where('is_active', $request->status === '1');

        return DataTables::eloquent($query)
            ->addColumn('airline_info', fn (Airline $a) =>
                '<div><strong>' . e($a->airline_name) . '</strong>'
                . ($a->airline_code ? ' <span class="badge bg-secondary">' . e($a->airline_code) . '</span>' : '')
                . '<div class="small text-muted">' . e($a->code) . '</div></div>'
            )
            ->addColumn('route_chip', fn (Airline $a) =>
                '<span class="route-chip" dir="ltr">' . e($a->route) . '</span>'
            )
            ->editColumn('cabin_class', fn (Airline $a) =>
                '<span class="badge bg-info-soft">' . $a->cabin_label . '</span>'
            )
            ->editColumn('base_price_per_pax', fn (Airline $a) =>
                '<strong>' . number_format($a->base_price_per_pax, 2) . '</strong> '
                . '<small class="text-muted">' . e($a->currency) . '</small>'
            )
            ->addColumn('seats', fn (Airline $a) =>
                '<small>' . $a->available_seats . ' / ' . $a->capacity . '</small>'
            )
            ->editColumn('is_active', fn (Airline $a) =>
                $a->is_active
                    ? '<span class="badge bg-success-soft">نشط</span>'
                    : '<span class="badge bg-secondary-soft">متوقف</span>'
            )
            ->addColumn('actions', function (Airline $a) {
                $user = auth()->user();
                $html = '';
                if ($user?->can('catalog.airlines.manage')) {
                    $html .= '<a href="' . route('admin.catalog.airlines.edit', $a) . '" class="btn btn-icon btn-sm btn-light-primary"><i class="bi bi-pencil"></i></a> ';
                    $html .= '<button class="btn btn-icon btn-sm btn-light-danger btn-delete" data-url="' . route('admin.catalog.airlines.destroy', $a) . '"><i class="bi bi-trash"></i></button>';
                }
                return $html;
            })
            ->rawColumns(['airline_info', 'route_chip', 'cabin_class', 'base_price_per_pax', 'seats', 'is_active', 'actions'])
            ->make(true);
    }

    public function create() { return view('admin.catalog.airlines.create'); }

    public function store(AirlineRequest $request)
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);
        Airline::create($data);
        return redirect()->route('admin.catalog.airlines.index')->with('success', 'تم إضافة شركة الطيران');
    }

    public function edit(Airline $airline) { return view('admin.catalog.airlines.edit', compact('airline')); }

    public function update(AirlineRequest $request, Airline $airline)
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', false);
        $airline->update($data);
        return redirect()->route('admin.catalog.airlines.index')->with('success', 'تم تحديث بيانات شركة الطيران');
    }

    public function destroy(Airline $airline)
    {
        $airline->delete();
        return response()->json(['message' => 'تم حذف شركة الطيران']);
    }
}
