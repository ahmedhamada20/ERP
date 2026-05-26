<?php

namespace App\Http\Controllers\Admin\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Catalog\VisaTypeRequest;
use App\Models\VisaType;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class VisaTypeController extends Controller
{
    public function index() { return view('admin.catalog.visas.index'); }

    public function data(Request $request)
    {
        $query = VisaType::query()->select([
            'id', 'code', 'name', 'country', 'type',
            'duration_days', 'multiple_entry', 'processing_days',
            'base_fee', 'service_fee', 'currency',
            'is_active', 'created_at',
        ]);

        if ($request->filled('type'))    $query->where('type', $request->type);
        if ($request->filled('country')) $query->where('country', $request->country);
        if ($request->filled('status'))  $query->where('is_active', $request->status === '1');

        return DataTables::eloquent($query)
            ->addColumn('visa_info', fn (VisaType $v) =>
                '<div><strong>' . e($v->name) . '</strong>'
                . '<div class="small text-muted">' . e($v->code) . ' • ' . e($v->country) . '</div></div>'
            )
            ->editColumn('type', fn (VisaType $v) =>
                '<span class="badge bg-info-soft">' . $v->type_label . '</span>'
            )
            ->addColumn('duration_info', fn (VisaType $v) =>
                '<div class="small"><i class="bi bi-clock"></i> ' . $v->duration_days . ' يوم إقامة</div>'
                . '<div class="small text-muted">إصدار: ' . $v->processing_days . ' يوم</div>'
            )
            ->addColumn('multiple_entry_label', fn (VisaType $v) =>
                $v->multiple_entry
                    ? '<span class="badge bg-success-soft">دخول متعدد</span>'
                    : '<span class="badge bg-secondary-soft">دخول واحد</span>'
            )
            ->editColumn('base_fee', fn (VisaType $v) =>
                '<div><strong>' . number_format($v->base_fee, 2) . '</strong> '
                . '<small class="text-muted">' . e($v->currency) . '</small></div>'
                . ($v->service_fee > 0
                    ? '<div class="small text-muted">+' . number_format($v->service_fee, 0) . ' خدمة</div>'
                    : '')
            )
            ->editColumn('is_active', fn (VisaType $v) =>
                $v->is_active
                    ? '<span class="badge bg-success-soft">نشط</span>'
                    : '<span class="badge bg-secondary-soft">متوقف</span>'
            )
            ->addColumn('actions', function (VisaType $v) {
                $user = auth()->user();
                $html = '';
                if ($user?->can('catalog.visas.manage')) {
                    $html .= '<a href="' . route('admin.catalog.visas.edit', $v) . '" class="btn btn-icon btn-sm btn-light-primary"><i class="bi bi-pencil"></i></a> ';
                    $html .= '<button class="btn btn-icon btn-sm btn-light-danger btn-delete" data-url="' . route('admin.catalog.visas.destroy', $v) . '"><i class="bi bi-trash"></i></button>';
                }
                return $html;
            })
            ->rawColumns(['visa_info', 'type', 'duration_info', 'multiple_entry_label', 'base_fee', 'is_active', 'actions'])
            ->make(true);
    }

    public function create() { return view('admin.catalog.visas.create'); }

    public function store(VisaTypeRequest $request)
    {
        $data = $request->validated();
        $data['multiple_entry'] = $request->boolean('multiple_entry');
        $data['is_active']      = $request->boolean('is_active', true);
        VisaType::create($data);
        return redirect()->route('admin.catalog.visas.index')->with('success', 'تم إضافة نوع التأشيرة');
    }

    public function edit(VisaType $visa) { return view('admin.catalog.visas.edit', compact('visa')); }

    public function update(VisaTypeRequest $request, VisaType $visa)
    {
        $data = $request->validated();
        $data['multiple_entry'] = $request->boolean('multiple_entry');
        $data['is_active']      = $request->boolean('is_active', false);
        $visa->update($data);
        return redirect()->route('admin.catalog.visas.index')->with('success', 'تم تحديث نوع التأشيرة');
    }

    public function destroy(VisaType $visa)
    {
        $visa->delete();
        return response()->json(['message' => 'تم حذف نوع التأشيرة']);
    }
}
