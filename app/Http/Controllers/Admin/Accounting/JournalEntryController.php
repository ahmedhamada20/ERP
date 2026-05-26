<?php

namespace App\Http\Controllers\Admin\Accounting;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Accounting\JournalEntryRequest;
use App\Models\Account;
use App\Models\JournalEntry;
use App\Services\Accounting\JournalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class JournalEntryController extends Controller
{
    public function index()
    {
        return view('admin.accounting.journal.index');
    }

    public function data(Request $request)
    {
        $query = JournalEntry::query()
            ->select(['id', 'number', 'date', 'description', 'reference',
                      'source_type', 'total_debit', 'total_credit', 'status',
                      'posted_at', 'created_at'])
            ->withCount('lines');

        if ($request->filled('status')) $query->where('status', $request->status);
        if ($request->filled('from'))   $query->whereDate('date', '>=', $request->from);
        if ($request->filled('to'))     $query->whereDate('date', '<=', $request->to);

        return DataTables::eloquent($query)
            ->editColumn('number', fn (JournalEntry $e) =>
                '<a href="' . route('admin.accounting.journal.show', $e) . '"><code>' . e($e->number) . '</code></a>'
            )
            ->editColumn('date', fn (JournalEntry $e) => $e->date?->format('Y-m-d'))
            ->editColumn('total_debit', fn (JournalEntry $e) =>
                '<strong>' . number_format($e->total_debit, 2) . '</strong>'
            )
            ->editColumn('total_credit', fn (JournalEntry $e) =>
                '<strong>' . number_format($e->total_credit, 2) . '</strong>'
            )
            ->editColumn('status', function (JournalEntry $e) {
                $class = match ($e->status) {
                    'draft'     => 'bg-warning text-dark',
                    'posted'    => 'bg-success',
                    'cancelled' => 'bg-secondary',
                };
                return '<span class="badge ' . $class . '">' . e($e->status_label) . '</span>';
            })
            ->addColumn('source_label', fn (JournalEntry $e) => $this->sourceLabel($e->source_type))
            ->addColumn('actions', function (JournalEntry $e) {
                $u    = auth()->user();
                $html = '<a href="' . route('admin.accounting.journal.show', $e) . '" class="btn btn-icon btn-sm btn-light-info" title="عرض"><i class="bi bi-eye"></i></a> ';
                if ($e->isDraft() && $u?->can('accounting.journal.create')) {
                    $html .= '<a href="' . route('admin.accounting.journal.edit', $e) . '" class="btn btn-icon btn-sm btn-light-primary" title="تعديل"><i class="bi bi-pencil"></i></a> ';
                }
                return $html;
            })
            ->rawColumns(['number', 'total_debit', 'total_credit', 'status', 'actions'])
            ->make(true);
    }

    public function create()
    {
        return view('admin.accounting.journal.create', [
            'entry'    => null,
            'accounts' => $this->postableAccounts(),
        ]);
    }

    public function store(JournalEntryRequest $request, JournalService $service)
    {
        $entry = DB::transaction(function () use ($request) {
            $entry = JournalEntry::create([
                'date'        => $request->input('date'),
                'description' => $request->input('description'),
                'reference'   => $request->input('reference'),
                'source_type' => 'manual',
            ]);

            foreach ($request->input('lines', []) as $idx => $line) {
                $entry->lines()->create([
                    'account_id'  => $line['account_id'],
                    'debit'       => $line['debit']  ?: 0,
                    'credit'      => $line['credit'] ?: 0,
                    'description' => $line['description'] ?? null,
                    'line_number' => $idx + 1,
                ]);
            }

            return $entry->fresh('lines');
        });

        // Auto-post if requested
        if ($request->boolean('post_immediately')) {
            try {
                $service->post($entry);
                return redirect()->route('admin.accounting.journal.show', $entry)
                    ->with('success', 'تم إنشاء القيد وترحيله بنجاح');
            } catch (\Throwable $e) {
                return redirect()->route('admin.accounting.journal.show', $entry)
                    ->with('error', 'تم إنشاء القيد كمسودة — فشل الترحيل: ' . $e->getMessage());
            }
        }

        return redirect()->route('admin.accounting.journal.show', $entry)
            ->with('success', 'تم حفظ القيد كمسودة');
    }

    public function show(JournalEntry $entry)
    {
        $entry->load(['lines.account', 'creator', 'poster', 'canceller']);
        return view('admin.accounting.journal.show', compact('entry'));
    }

    public function edit(JournalEntry $entry)
    {
        if (! $entry->isDraft()) {
            return redirect()->route('admin.accounting.journal.show', $entry)
                ->with('error', 'لا يمكن تعديل قيد مرحّل أو ملغي');
        }

        $entry->load('lines');
        return view('admin.accounting.journal.edit', [
            'entry'    => $entry,
            'accounts' => $this->postableAccounts(),
        ]);
    }

    public function update(JournalEntryRequest $request, JournalEntry $entry)
    {
        if (! $entry->isDraft()) {
            abort(422, 'لا يمكن تعديل قيد مرحّل أو ملغي');
        }

        DB::transaction(function () use ($request, $entry) {
            $entry->update([
                'date'        => $request->input('date'),
                'description' => $request->input('description'),
                'reference'   => $request->input('reference'),
            ]);

            // Replace lines wholesale (simpler than diffing for manual entries)
            $entry->lines()->delete();
            foreach ($request->input('lines', []) as $idx => $line) {
                $entry->lines()->create([
                    'account_id'  => $line['account_id'],
                    'debit'       => $line['debit']  ?: 0,
                    'credit'      => $line['credit'] ?: 0,
                    'description' => $line['description'] ?? null,
                    'line_number' => $idx + 1,
                ]);
            }
        });

        return redirect()->route('admin.accounting.journal.show', $entry)
            ->with('success', 'تم تحديث القيد');
    }

    public function destroy(JournalEntry $entry)
    {
        if (! $entry->isDraft()) {
            return response()->json(['message' => 'لا يمكن حذف قيد مرحّل أو ملغي'], 422);
        }
        $entry->delete();
        return response()->json(['message' => 'تم حذف المسودة']);
    }

    public function post(JournalEntry $entry, JournalService $service)
    {
        try {
            $service->post($entry);
            return back()->with('success', 'تم ترحيل القيد بنجاح');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function cancel(Request $request, JournalEntry $entry, JournalService $service)
    {
        $data = $request->validate([
            'cancellation_reason' => ['required', 'string', 'max:500'],
        ]);

        try {
            $service->cancel($entry, $data['cancellation_reason']);
            return back()->with('success', 'تم إلغاء القيد');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    private function postableAccounts()
    {
        return Account::query()
            ->where('is_group', false)
            ->where('is_active', true)
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'type']);
    }

    private function sourceLabel(string $source): string
    {
        return match ($source) {
            'manual'           => 'يدوي',
            'booking_payment'  => 'دفعة حجز',
            'booking_cost'     => 'تكلفة حجز',
            'voucher'          => 'سند',
            'opening_balance'  => 'رصيد افتتاحي',
            default            => $source,
        };
    }
}
