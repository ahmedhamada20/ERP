<?php

namespace App\Http\Controllers\Admin\Accounting;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Accounting\PaymentVoucherRequest;
use App\Models\Account;
use App\Models\ExchangeRate;
use App\Models\Supplier;
use App\Models\SupplierInvoice;
use App\Models\Voucher;
use App\Services\Accounting\VoucherService;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class PaymentVoucherController extends Controller
{
    public function index()
    {
        return view('admin.accounting.vouchers.payments.index');
    }

    public function data(Request $request)
    {
        $query = Voucher::query()
            ->payments()
            ->with(['cashAccount:id,code,name', 'counterAccount:id,code,name'])
            ->select(['id', 'number', 'date', 'cash_account_id', 'counter_account_id',
                      'party_name', 'amount', 'currency', 'amount_egp', 'status']);

        if ($request->filled('status')) $query->where('status', $request->status);
        if ($request->filled('from'))   $query->whereDate('date', '>=', $request->from);
        if ($request->filled('to'))     $query->whereDate('date', '<=', $request->to);

        return DataTables::eloquent($query)
            ->editColumn('number', fn (Voucher $v) =>
                '<a href="' . route('admin.accounting.vouchers.payments.show', $v) . '"><code>' . e($v->number) . '</code></a>'
            )
            ->editColumn('date', fn (Voucher $v) => $v->date?->format('Y-m-d'))
            ->addColumn('cash_label',    fn (Voucher $v) => $v->cashAccount ? e($v->cashAccount->code . ' — ' . $v->cashAccount->name) : '—')
            ->addColumn('counter_label', fn (Voucher $v) => $v->counterAccount ? e($v->counterAccount->code . ' — ' . $v->counterAccount->name) : '—')
            ->editColumn('amount', fn (Voucher $v) =>
                '<strong>' . number_format($v->amount, 2) . '</strong> <small class="text-muted">' . e($v->currency) . '</small>'
                . ($v->currency !== 'EGP' ? '<div class="small text-muted">' . number_format($v->amount_egp, 2) . ' ج.م</div>' : '')
            )
            ->editColumn('status', function (Voucher $v) {
                $class = match ($v->status) {
                    'draft'     => 'bg-warning text-dark',
                    'posted'    => 'bg-danger',  // payments shown in red (money out)
                    'cancelled' => 'bg-secondary',
                };
                return '<span class="badge ' . $class . '">' . e($v->status_label) . '</span>';
            })
            ->addColumn('actions', function (Voucher $v) {
                $html  = '<a href="' . route('admin.accounting.vouchers.payments.show', $v) . '" class="btn btn-icon btn-sm btn-light-info"><i class="bi bi-eye"></i></a> ';
                $html .= '<a href="' . route('admin.accounting.vouchers.payments.print', $v) . '" target="_blank" class="btn btn-icon btn-sm btn-light-primary"><i class="bi bi-printer"></i></a>';
                return $html;
            })
            ->rawColumns(['number', 'amount', 'status', 'actions'])
            ->make(true);
    }

    public function create(Request $request)
    {
        return view('admin.accounting.vouchers.payments.create', [
            'cashAccounts'      => $this->cashAccounts(),
            'counterAccounts'   => $this->counterAccounts(),
            'suppliers'         => Supplier::active()->orderBy('name')->get(['id', 'code', 'name', 'type', 'currency']),
            'openInvoices'      => $this->openInvoicesBySupplier(),
            'presetSupplierId'  => $request->query('supplier_id'),
            'presetInvoiceId'   => $request->query('invoice_id'),
        ]);
    }

    public function store(PaymentVoucherRequest $request, VoucherService $service)
    {
        $data = $request->validated();
        $data['type'] = 'payment';

        if ($data['currency'] !== 'EGP' && empty($data['exchange_rate'])) {
            $data['exchange_rate'] = ExchangeRate::rateFor($data['currency'], 'EGP') ?: 1;
        }
        if ($data['currency'] === 'EGP') {
            $data['exchange_rate'] = 1;
        }

        // If a supplier was selected, force counter_account = supplier's parent
        // and tag the voucher metadata.
        if (! empty($data['supplier_id'])) {
            $supplier = Supplier::find($data['supplier_id']);
            if ($supplier) {
                $parent = $supplier->parentAccountModel();
                if ($parent) {
                    $data['counter_account_id'] = $parent->id;
                }
                $data['party_type'] = 'supplier';
                $data['party_id']   = $supplier->id;
                if (empty($data['party_name'])) {
                    $data['party_name'] = $supplier->name;
                }
            }
        }

        try {
            $voucher = $service->create($data);
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.accounting.vouchers.payments.show', $voucher)
            ->with('success', 'تم إنشاء سند الصرف وترحيله بنجاح');
    }

    public function show(Voucher $voucher)
    {
        abort_unless($voucher->isPayment(), 404);
        $voucher->load(['cashAccount', 'counterAccount', 'supplier', 'supplierInvoice',
                        'creator', 'poster', 'canceller', 'journalEntry.lines.account']);

        return view('admin.accounting.vouchers.payments.show', compact('voucher'));
    }

    public function cancel(Request $request, Voucher $voucher, VoucherService $service)
    {
        abort_unless($voucher->isPayment(), 404);

        $data = $request->validate([
            'cancellation_reason' => ['required', 'string', 'max:500'],
        ]);

        try {
            $service->cancel($voucher, $data['cancellation_reason']);
            return back()->with('success', 'تم إلغاء سند الصرف');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function print(Voucher $voucher)
    {
        abort_unless($voucher->isPayment(), 404);
        $voucher->load(['cashAccount', 'counterAccount']);

        return view('admin.accounting.vouchers.payments.print', compact('voucher'));
    }

    private function cashAccounts()
    {
        return Account::query()
            ->whereIn('sub_type', ['cash', 'bank'])
            ->where('is_active', true)
            ->orderBy('sub_type')->orderBy('code')
            ->get(['id', 'code', 'name', 'sub_type', 'currency']);
    }

    /**
     * Posted supplier invoices grouped by supplier_id — used by the create
     * form to filter the invoice dropdown after a supplier is picked.
     */
    private function openInvoicesBySupplier()
    {
        return SupplierInvoice::query()
            ->posted()
            ->with('supplier:id,name')
            ->orderBy('invoice_date', 'desc')
            ->get(['id', 'number', 'supplier_id', 'invoice_date', 'due_date',
                   'currency', 'amount', 'tax_amount', 'amount_egp'])
            ->groupBy('supplier_id');
    }

    /**
     * For payments, prioritize expense + liability accounts in the dropdown
     * (paying a supplier or recording an expense are the common cases).
     * DB-agnostic ordering via PHP.
     */
    private function counterAccounts()
    {
        $priority = ['expense' => 1, 'liability' => 2, 'asset' => 3, 'equity' => 4, 'revenue' => 5];

        return Account::query()
            ->where('is_group', false)
            ->where('is_active', true)
            ->whereNotIn('sub_type', ['cash', 'bank'])
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'type'])
            ->sortBy(fn ($a) => ($priority[$a->type] ?? 9) . '|' . $a->code)
            ->values();
    }
}
