<?php

namespace App\Http\Controllers\Admin\Accounting;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Accounting\ReceiptVoucherRequest;
use App\Models\Account;
use App\Models\ExchangeRate;
use App\Models\Voucher;
use App\Services\Accounting\VoucherService;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class ReceiptVoucherController extends Controller
{
    public function index()
    {
        return view('admin.accounting.vouchers.receipts.index');
    }

    public function data(Request $request)
    {
        $query = Voucher::query()
            ->receipts()
            ->with(['cashAccount:id,code,name', 'counterAccount:id,code,name'])
            ->select(['id', 'number', 'date', 'cash_account_id', 'counter_account_id',
                      'party_name', 'amount', 'currency', 'amount_egp', 'status']);

        if ($request->filled('status')) $query->where('status', $request->status);
        if ($request->filled('from'))   $query->whereDate('date', '>=', $request->from);
        if ($request->filled('to'))     $query->whereDate('date', '<=', $request->to);

        return DataTables::eloquent($query)
            ->editColumn('number', fn (Voucher $v) =>
                '<a href="' . route('admin.accounting.vouchers.receipts.show', $v) . '"><code>' . e($v->number) . '</code></a>'
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
                    'posted'    => 'bg-success',
                    'cancelled' => 'bg-secondary',
                };
                return '<span class="badge ' . $class . '">' . e($v->status_label) . '</span>';
            })
            ->addColumn('actions', function (Voucher $v) {
                $html  = '<a href="' . route('admin.accounting.vouchers.receipts.show', $v) . '" class="btn btn-icon btn-sm btn-light-info"><i class="bi bi-eye"></i></a> ';
                $html .= '<a href="' . route('admin.accounting.vouchers.receipts.print', $v) . '" target="_blank" class="btn btn-icon btn-sm btn-light-primary"><i class="bi bi-printer"></i></a>';
                return $html;
            })
            ->rawColumns(['number', 'amount', 'status', 'actions'])
            ->make(true);
    }

    public function create()
    {
        return view('admin.accounting.vouchers.receipts.create', [
            'cashAccounts'    => $this->cashAccounts(),
            'counterAccounts' => $this->counterAccounts(),
        ]);
    }

    public function store(ReceiptVoucherRequest $request, VoucherService $service)
    {
        $data = $request->validated();
        $data['type'] = 'receipt';

        // Auto-resolve FX rate if non-EGP and not provided
        if ($data['currency'] !== 'EGP' && empty($data['exchange_rate'])) {
            $data['exchange_rate'] = ExchangeRate::rateFor($data['currency'], 'EGP') ?: 1;
        }
        if ($data['currency'] === 'EGP') {
            $data['exchange_rate'] = 1;
        }

        try {
            $voucher = $service->create($data);
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.accounting.vouchers.receipts.show', $voucher)
            ->with('success', 'تم إنشاء سند القبض وترحيله بنجاح');
    }

    public function show(Voucher $voucher)
    {
        abort_unless($voucher->isReceipt(), 404);
        $voucher->load(['cashAccount', 'counterAccount', 'creator', 'poster', 'canceller', 'journalEntry.lines.account']);

        return view('admin.accounting.vouchers.receipts.show', compact('voucher'));
    }

    public function cancel(Request $request, Voucher $voucher, VoucherService $service)
    {
        abort_unless($voucher->isReceipt(), 404);

        $data = $request->validate([
            'cancellation_reason' => ['required', 'string', 'max:500'],
        ]);

        try {
            $service->cancel($voucher, $data['cancellation_reason']);
            return back()->with('success', 'تم إلغاء سند القبض');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function print(Voucher $voucher)
    {
        abort_unless($voucher->isReceipt(), 404);
        $voucher->load(['cashAccount', 'counterAccount']);

        return view('admin.accounting.vouchers.receipts.print', compact('voucher'));
    }

    private function cashAccounts()
    {
        return Account::query()
            ->whereIn('sub_type', ['cash', 'bank'])
            ->where('is_active', true)
            ->orderBy('sub_type')->orderBy('code')
            ->get(['id', 'code', 'name', 'sub_type', 'currency']);
    }

    private function counterAccounts()
    {
        return Account::query()
            ->where('is_group', false)
            ->where('is_active', true)
            ->whereNotIn('sub_type', ['cash', 'bank']) // cash/bank are for the other side
            ->orderBy('type')->orderBy('code')
            ->get(['id', 'code', 'name', 'type']);
    }
}
