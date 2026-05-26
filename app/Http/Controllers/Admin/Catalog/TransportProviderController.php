<?php

namespace App\Http\Controllers\Admin\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Catalog\TransportProviderRequest;
use App\Models\TransportProvider;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class TransportProviderController extends Controller
{
    public function index() { return view('admin.catalog.transport.index'); }

    public function data(Request $request)
    {
        $query = TransportProvider::query()->select([
            'id', 'code', 'name', 'type', 'country',
            'vehicle_count', 'capacity_per_vehicle',
            'base_price_per_pax', 'currency',
            'is_active', 'created_at',
        ]);

        if ($request->filled('type'))    $query->where('type', $request->type);
        if ($request->filled('country')) $query->where('country', $request->country);
        if ($request->filled('status'))  $query->where('is_active', $request->status === '1');

        return DataTables::eloquent($query)
            ->addColumn('provider_info', fn (TransportProvider $t) =>
                '<div><strong>' . e($t->name) . '</strong>'
                . '<div class="small text-muted">' . e($t->code) . ' • ' . e($t->country_label) . '</div></div>'
            )
            ->editColumn('type', function (TransportProvider $t) {
                $icon = match ($t->type) {
                    'bus'       => 'bus-front',
                    'train'     => 'train-front',
                    'vip'       => 'car-front',
                    'limousine' => 'car-front-fill',
                    'minivan'   => 'truck-front',
                    default     => 'truck',
                };
                return '<span class="badge bg-warning-soft"><i class="bi bi-' . $icon . '"></i> ' . $t->type_label . '</span>';
            })
            ->addColumn('fleet', fn (TransportProvider $t) =>
                '<div class="small"><strong>' . $t->vehicle_count . '</strong> سيارة × <strong>' . $t->capacity_per_vehicle . '</strong> راكب</div>'
                . '<div class="small text-muted">إجمالي: ' . $t->total_capacity . ' راكب</div>'
            )
            ->editColumn('base_price_per_pax', fn (TransportProvider $t) =>
                '<strong>' . number_format($t->base_price_per_pax, 2) . '</strong> '
                . '<small class="text-muted">' . e($t->currency) . '/راكب</small>'
            )
            ->editColumn('is_active', fn (TransportProvider $t) =>
                $t->is_active
                    ? '<span class="badge bg-success-soft">نشط</span>'
                    : '<span class="badge bg-secondary-soft">متوقف</span>'
            )
            ->addColumn('actions', function (TransportProvider $t) {
                $user = auth()->user();
                $html = '';
                if ($user?->can('catalog.transport.manage')) {
                    $html .= '<a href="' . route('admin.catalog.transport.edit', $t) . '" class="btn btn-icon btn-sm btn-light-primary"><i class="bi bi-pencil"></i></a> ';
                    $html .= '<button class="btn btn-icon btn-sm btn-light-danger btn-delete" data-url="' . route('admin.catalog.transport.destroy', $t) . '"><i class="bi bi-trash"></i></button>';
                }
                return $html;
            })
            ->rawColumns(['provider_info', 'type', 'fleet', 'base_price_per_pax', 'is_active', 'actions'])
            ->make(true);
    }

    public function create() { return view('admin.catalog.transport.create'); }

    public function store(TransportProviderRequest $request)
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);
        TransportProvider::create($data);
        return redirect()->route('admin.catalog.transport.index')->with('success', 'تم إضافة شركة النقل');
    }

    public function edit(TransportProvider $transport) { return view('admin.catalog.transport.edit', compact('transport')); }

    public function update(TransportProviderRequest $request, TransportProvider $transport)
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', false);
        $transport->update($data);
        return redirect()->route('admin.catalog.transport.index')->with('success', 'تم تحديث شركة النقل');
    }

    public function destroy(TransportProvider $transport)
    {
        $transport->delete();
        return response()->json(['message' => 'تم حذف شركة النقل']);
    }
}
