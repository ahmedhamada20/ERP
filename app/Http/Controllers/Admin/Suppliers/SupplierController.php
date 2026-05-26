<?php

namespace App\Http\Controllers\Admin\Suppliers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Suppliers\SupplierRequest;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class SupplierController extends Controller
{
    public function index()
    {
        $counts = [
            'total'    => Supplier::count(),
            'active'   => Supplier::where('is_active', true)->count(),
            'hotels'   => Supplier::where('type', 'hotel')->count(),
            'airlines' => Supplier::where('type', 'airline')->count(),
        ];
        return view('admin.suppliers.index', compact('counts'));
    }

    public function data(Request $request)
    {
        $query = Supplier::query()
            ->select(['id', 'code', 'name', 'type', 'phone', 'contact_person',
                      'currency', 'opening_balance', 'payment_terms_days', 'is_active']);

        if ($request->filled('type'))   $query->where('type', $request->type);
        if ($request->filled('status')) $query->where('is_active', $request->status === '1');

        return DataTables::eloquent($query)
            ->editColumn('code', fn (Supplier $s) =>
                '<a href="' . route('admin.suppliers.show', $s) . '"><code>' . e($s->code) . '</code></a>'
            )
            ->editColumn('name', fn (Supplier $s) =>
                '<strong>' . e($s->name) . '</strong>'
                . ($s->name_en ? '<div class="small text-muted" dir="ltr">' . e($s->name_en) . '</div>' : '')
            )
            ->editColumn('type', function (Supplier $s) {
                $class = match ($s->type) {
                    'hotel'     => 'bg-info text-white',
                    'airline'   => 'bg-primary',
                    'transport' => 'bg-warning text-dark',
                    'visa'      => 'bg-success',
                    default     => 'bg-secondary',
                };
                return '<span class="badge ' . $class . '">' . e($s->type_label) . '</span>';
            })
            ->addColumn('contact', fn (Supplier $s) =>
                ($s->phone   ? '<i class="bi bi-telephone"></i> ' . e($s->phone) : '')
                . ($s->contact_person ? '<div class="small text-muted">' . e($s->contact_person) . '</div>' : '')
            )
            ->editColumn('opening_balance', fn (Supplier $s) =>
                '<span dir="ltr">' . number_format($s->opening_balance, 2) . '</span> <small class="text-muted">' . e($s->currency) . '</small>'
            )
            ->editColumn('is_active', fn (Supplier $s) =>
                $s->is_active
                    ? '<span class="badge bg-success">نشط</span>'
                    : '<span class="badge bg-secondary">متوقف</span>'
            )
            ->addColumn('actions', function (Supplier $s) {
                $u = auth()->user();
                $html = '<a href="' . route('admin.suppliers.show', $s) . '" class="btn btn-icon btn-sm btn-light-info" title="عرض"><i class="bi bi-eye"></i></a> ';
                if ($u?->can('suppliers.update')) {
                    $html .= '<a href="' . route('admin.suppliers.edit', $s) . '" class="btn btn-icon btn-sm btn-light-primary" title="تعديل"><i class="bi bi-pencil"></i></a> ';
                }
                if ($u?->can('suppliers.delete')) {
                    $html .= '<button class="btn btn-icon btn-sm btn-light-danger btn-delete" data-url="' . route('admin.suppliers.destroy', $s) . '" title="حذف"><i class="bi bi-trash"></i></button>';
                }
                return $html;
            })
            ->rawColumns(['code', 'name', 'type', 'contact', 'opening_balance', 'is_active', 'actions'])
            ->make(true);
    }

    public function create()
    {
        return view('admin.suppliers.create');
    }

    public function store(SupplierRequest $request)
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);

        $supplier = Supplier::create($data);

        return redirect()
            ->route('admin.suppliers.show', $supplier)
            ->with('success', 'تم إضافة المورد بنجاح');
    }

    public function show(Supplier $supplier)
    {
        $supplier->load('creator');
        return view('admin.suppliers.show', compact('supplier'));
    }

    public function edit(Supplier $supplier)
    {
        return view('admin.suppliers.edit', compact('supplier'));
    }

    public function update(SupplierRequest $request, Supplier $supplier)
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);

        $supplier->update($data);

        return redirect()
            ->route('admin.suppliers.show', $supplier)
            ->with('success', 'تم تحديث بيانات المورد');
    }

    public function destroy(Supplier $supplier)
    {
        // Future: block delete if supplier has invoices or payments
        // (after Step 3-5 ship). For now, soft delete is always allowed.
        $supplier->delete();
        return response()->json(['message' => 'تم حذف المورد']);
    }
}
