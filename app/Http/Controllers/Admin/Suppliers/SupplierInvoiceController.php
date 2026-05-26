<?php

namespace App\Http\Controllers\Admin\Suppliers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Suppliers\SupplierInvoiceRequest;
use App\Models\Account;
use App\Models\ExchangeRate;
use App\Models\Supplier;
use App\Models\SupplierInvoice;
use App\Services\Suppliers\SupplierInvoiceService;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class SupplierInvoiceController extends Controller
{
    public function index()
    {
        return view('admin.suppliers.invoices.index');
    }

    public function data(Request $request)
    {
        $query = SupplierInvoice::query()
            ->with(['supplier:id,code,name,type', 'expenseAccount:id,code,name'])
            ->select(['id', 'number', 'supplier_id', 'expense_account_id',
                      'invoice_date', 'due_date', 'currency', 'amount',
                      'tax_amount', 'amount_egp', 'status']);

        if ($request->filled('status'))      $query->where('status', $request->status);
        if ($request->filled('supplier_id')) $query->where('supplier_id', $request->supplier_id);
        if ($request->filled('from'))        $query->whereDate('invoice_date', '>=', $request->from);
        if ($request->filled('to'))          $query->whereDate('invoice_date', '<=', $request->to);
        if ($request->boolean('overdue_only')) {
            $query->where('status', 'posted')->whereDate('due_date', '<', now());
        }

        return DataTables::eloquent($query)
            ->editColumn('number', fn (SupplierInvoice $i) =>
                '<a href="' . route('admin.supplier_invoices.show', $i) . '"><code>' . e($i->number) . '</code></a>'
            )
            ->editColumn('invoice_date', fn (SupplierInvoice $i) => $i->invoice_date?->format('Y-m-d'))
            ->editColumn('due_date', function (SupplierInvoice $i) {
                if (! $i->due_date) return '—';
                $overdue = $i->status === 'posted' && $i->due_date->isPast();
                return '<span class="' . ($overdue ? 'text-danger fw-bold' : '') . '">'
                       . $i->due_date->format('Y-m-d')
                       . ($overdue ? ' <i class="bi bi-exclamation-triangle"></i>' : '')
                       . '</span>';
            })
            ->addColumn('supplier_label', fn (SupplierInvoice $i) =>
                $i->supplier
                    ? '<strong>' . e($i->supplier->name) . '</strong><div class="small text-muted">' . e($i->supplier->code) . '</div>'
                    : '—'
            )
            ->addColumn('expense_label', fn (SupplierInvoice $i) =>
                $i->expenseAccount ? e($i->expenseAccount->code . ' — ' . $i->expenseAccount->name) : '—'
            )
            ->editColumn('amount', fn (SupplierInvoice $i) =>
                '<div><strong>' . number_format($i->amount + $i->tax_amount, 2) . '</strong> <small class="text-muted">' . e($i->currency) . '</small></div>'
                . ($i->tax_amount > 0 ? '<div class="small text-muted">منها ضريبة ' . number_format($i->tax_amount, 2) . '</div>' : '')
                . ($i->currency !== 'EGP' ? '<div class="small text-muted">' . number_format($i->amount_egp, 2) . ' ج.م</div>' : '')
            )
            ->editColumn('status', function (SupplierInvoice $i) {
                $class = match ($i->status) {
                    'draft'     => 'bg-warning text-dark',
                    'posted'    => 'bg-success',
                    'cancelled' => 'bg-secondary',
                };
                return '<span class="badge ' . $class . '">' . e($i->status_label) . '</span>';
            })
            ->addColumn('actions', function (SupplierInvoice $i) {
                return '<a href="' . route('admin.supplier_invoices.show', $i) . '" class="btn btn-icon btn-sm btn-light-info" title="عرض"><i class="bi bi-eye"></i></a>';
            })
            ->rawColumns(['number', 'due_date', 'supplier_label', 'amount', 'status', 'actions'])
            ->make(true);
    }

    public function create(Request $request)
    {
        return view('admin.suppliers.invoices.create', [
            'suppliers'       => Supplier::active()->orderBy('name')->get(['id', 'code', 'name', 'type', 'currency']),
            'expenseAccounts' => $this->expenseAccounts(),
            'presetSupplier'  => $request->query('supplier_id'),
        ]);
    }

    public function store(SupplierInvoiceRequest $request, SupplierInvoiceService $service)
    {
        $data = $request->validated();
        $data['tax_amount']    = $data['tax_amount']    ?? 0;
        $data['exchange_rate'] = $data['exchange_rate'] ?? 1;

        // Auto-resolve FX for non-EGP if not provided
        if ($data['currency'] !== 'EGP' && empty($data['exchange_rate'])) {
            $data['exchange_rate'] = ExchangeRate::rateFor($data['currency'], 'EGP') ?: 1;
        }
        if ($data['currency'] === 'EGP') {
            $data['exchange_rate'] = 1;
        }

        // Refresh to pull DB defaults (e.g. status='draft') into the model
        $invoice = SupplierInvoice::create($data)->fresh();

        // Optionally auto-post if checkbox set
        if ($request->boolean('post_immediately')) {
            try {
                $service->post($invoice);
                return redirect()->route('admin.supplier_invoices.show', $invoice)
                    ->with('success', 'تم إنشاء الفاتورة وترحيلها بنجاح');
            } catch (\Throwable $e) {
                return redirect()->route('admin.supplier_invoices.show', $invoice)
                    ->with('error', 'تم حفظ الفاتورة كمسودة — فشل الترحيل: ' . $e->getMessage());
            }
        }

        return redirect()->route('admin.supplier_invoices.show', $invoice)
            ->with('success', 'تم حفظ الفاتورة كمسودة');
    }

    public function show(SupplierInvoice $invoice)
    {
        $invoice->load(['supplier', 'expenseAccount', 'journalEntry.lines.account',
                        'creator', 'poster', 'canceller']);
        return view('admin.suppliers.invoices.show', compact('invoice'));
    }

    public function post(SupplierInvoice $invoice, SupplierInvoiceService $service)
    {
        try {
            $service->post($invoice);
            return back()->with('success', 'تم ترحيل الفاتورة بنجاح');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function cancel(Request $request, SupplierInvoice $invoice, SupplierInvoiceService $service)
    {
        $data = $request->validate([
            'cancellation_reason' => ['required', 'string', 'max:500'],
        ]);

        try {
            $service->cancel($invoice, $data['cancellation_reason']);
            return back()->with('success', 'تم إلغاء الفاتورة');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function destroy(SupplierInvoice $invoice)
    {
        if (! $invoice->isDraft()) {
            return response()->json(['message' => 'لا يمكن حذف فاتورة مرحّلة — يجب إلغاؤها أولاً'], 422);
        }
        $invoice->delete();
        return response()->json(['message' => 'تم حذف المسودة']);
    }

    /**
     * Postable expense + asset accounts (suppliers can be paid for buying
     * inventory or fixed assets, not just expenses).
     */
    private function expenseAccounts()
    {
        return Account::query()
            ->where('is_group', false)
            ->where('is_active', true)
            ->whereIn('type', ['expense', 'asset'])
            ->whereNotIn('sub_type', ['cash', 'bank'])
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'type']);
    }
}
