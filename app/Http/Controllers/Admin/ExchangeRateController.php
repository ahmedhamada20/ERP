<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExchangeRate;
use App\Services\ExchangeRateService;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class ExchangeRateController extends Controller
{
    public function index(ExchangeRateService $service)
    {
        $lastSync = $service->lastSyncedAt();
        return view('admin.religious.exchange_rates.index', compact('lastSync'));
    }

    /**
     * Pull live rates from the configured provider and upsert today's row.
     * Permission: exchange_rates.manage.
     */
    public function sync(ExchangeRateService $service)
    {
        $result = $service->syncToday(auth()->id());

        if (!$result['ok']) {
            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], 502);
        }

        $lines = [];
        foreach ($result['updated'] as $currency => $rate) {
            $lines[] = "1 {$currency} = " . number_format($rate, 4) . ' EGP';
        }

        return response()->json([
            'success'    => true,
            'message'    => 'تم تحديث أسعار الصرف من المصدر الحي',
            'updated'    => $result['updated'],
            'lines'      => $lines,
            'fetched_at' => $result['fetched_at'],
        ]);
    }

    public function data(Request $request)
    {
        $query = ExchangeRate::query()->with('creator:id,name');

        if ($request->filled('currency_filter')) {
            $query->where('from_currency', $request->currency_filter);
        }

        return DataTables::eloquent($query)
            ->addColumn('pair', fn (ExchangeRate $r) =>
                '<strong>' . $r->from_currency . '</strong> → <strong>' . $r->to_currency . '</strong>'
            )
            ->editColumn('rate', fn (ExchangeRate $r) =>
                '<span class="badge bg-info-soft" style="font-size:.9rem;">' . number_format($r->rate, 4) . '</span>'
            )
            ->editColumn('effective_date', fn (ExchangeRate $r) => $r->effective_date?->format('Y-m-d'))
            ->editColumn('is_active', fn (ExchangeRate $r) =>
                $r->is_active
                    ? '<span class="badge bg-success-soft">نشط</span>'
                    : '<span class="badge bg-secondary-soft">متوقف</span>'
            )
            ->addColumn('actions', function (ExchangeRate $r) {
                if (!auth()->user()?->can('exchange_rates.manage')) return '';
                return '<button class="btn btn-icon btn-sm btn-light-danger btn-delete" data-url="'
                    . route('admin.religious.exchange_rates.destroy', $r) . '"><i class="bi bi-trash"></i></button>';
            })
            ->rawColumns(['pair', 'rate', 'is_active', 'actions'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'from_currency'  => ['required', 'in:SAR,USD,EUR'],
            'to_currency'    => ['required', 'in:EGP'],
            'rate'           => ['required', 'numeric', 'min:0', 'max:9999.9999'],
            'effective_date' => ['required', 'date'],
            'notes'          => ['nullable', 'string', 'max:200'],
        ]);

        $data['is_active']  = true;
        $data['created_by'] = auth()->id();

        ExchangeRate::updateOrCreate(
            [
                'from_currency'  => $data['from_currency'],
                'to_currency'    => $data['to_currency'],
                'effective_date' => $data['effective_date'],
            ],
            $data
        );

        return back()->with('success', 'تم حفظ سعر الصرف');
    }

    public function destroy(ExchangeRate $exchange_rate)
    {
        $exchange_rate->delete();
        return response()->json(['message' => 'تم حذف سعر الصرف']);
    }
}
