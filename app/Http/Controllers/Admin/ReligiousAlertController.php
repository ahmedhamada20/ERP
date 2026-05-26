<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReligiousAlert;
use App\Services\Religious\ReligiousAlertScanner;
use Illuminate\Http\Request;

class ReligiousAlertController extends Controller
{
    public function index(Request $request)
    {
        $query = ReligiousAlert::query()
            ->with(['booking:id,booking_number,type,customer_id,trip_date', 'booking.customer:id,full_name', 'pilgrim:id,full_name', 'acknowledger:id,name'])
            ->latest();

        if ($request->filled('severity'))   $query->where('severity', $request->severity);
        if ($request->filled('type'))       $query->where('type', $request->type);
        if ($request->boolean('active', true)) $query->where('is_acknowledged', false);

        $alerts = $query->paginate(30);

        $counts = [
            'critical' => ReligiousAlert::active()->where('severity', 'critical')->count(),
            'warning'  => ReligiousAlert::active()->where('severity', 'warning')->count(),
            'info'     => ReligiousAlert::active()->where('severity', 'info')->count(),
        ];

        return view('admin.religious.alerts.index', compact('alerts', 'counts'));
    }

    public function acknowledge(Request $request, ReligiousAlert $alert)
    {
        $data = $request->validate([
            'resolution_notes' => ['nullable', 'string', 'max:500'],
        ]);

        $alert->update([
            'is_acknowledged'  => true,
            'acknowledged_by'  => auth()->id(),
            'acknowledged_at'  => now(),
            'resolution_notes' => $data['resolution_notes'] ?? null,
        ]);

        return back()->with('success', 'تم استلام التنبيه');
    }

    /**
     * Manual scan trigger — delegates to ReligiousAlertScanner.
     * Same scanner runs hourly via the religious:alerts-scan command.
     */
    public function scan(ReligiousAlertScanner $scanner)
    {
        $created = $scanner->scan();

        return back()->with('success', sprintf('تم فحص التنبيهات. تم إنشاء %d تنبيه جديد', $created));
    }
}
