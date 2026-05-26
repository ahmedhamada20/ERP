<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\IntegrationLog;
use App\Models\ReligiousBooking;
use App\Services\Religious\SafaService;
use App\Services\Religious\UmrahPortalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Religious integrations admin page — صفحة التكاملات الخارجية.
 *
 * Single place to:
 *  - See status of each integration provider (Safa, Umrah Portal)
 *  - Run connection tests
 *  - Browse integration logs
 *  - Manually trigger bulk sync for pending bookings
 */
class ReligiousIntegrationsPageController extends Controller
{
    public function index(Request $request)
    {
        // Per-provider stats
        $providers = [
            'safa' => [
                'key'        => 'safa',
                'name'       => 'صفا',
                'name_en'    => 'Safa',
                'icon'       => 'bi-qr-code',
                'color'      => 'success',
                'description' => 'منصة سحب تأشيرات العمرة والباركودات الجماعية',
                'endpoint'   => config('services.safa.url', 'https://api.safa.sa/v1 (MOCK)'),
                'is_mock'    => true,
            ],
            'umrah_portal' => [
                'key'        => 'umrah_portal',
                'name'       => 'بوابة العمرة',
                'name_en'    => 'Umrah Portal',
                'icon'       => 'bi-globe-asia-australia',
                'color'      => 'primary',
                'description' => 'البوابة الحكومية السعودية لتسجيل المعتمرين',
                'endpoint'   => config('services.umrah_portal.url', 'https://api.umrah.com.sa/v1 (MOCK)'),
                'is_mock'    => true,
            ],
        ];

        foreach ($providers as $key => &$p) {
            $row = DB::table('integration_logs')
                ->selectRaw("
                    COUNT(*) AS total_calls,
                    SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) AS successful,
                    SUM(CASE WHEN status = 'failed'  THEN 1 ELSE 0 END) AS failed,
                    MAX(created_at) AS last_call_at,
                    AVG(duration_ms) AS avg_duration_ms
                ")
                ->where('provider', $key)
                ->first();

            $p['stats'] = [
                'total_calls'   => (int) ($row->total_calls ?? 0),
                'successful'    => (int) ($row->successful ?? 0),
                'failed'        => (int) ($row->failed ?? 0),
                'success_rate'  => $row->total_calls > 0 ? round(($row->successful / $row->total_calls) * 100, 1) : 0,
                'last_call_at'  => $row->last_call_at,
                'avg_duration'  => $row->avg_duration_ms ? (int) round($row->avg_duration_ms) : null,
            ];

            // Provider-specific booking sync stats
            if ($key === 'safa') {
                $p['sync_stats'] = [
                    'synced_bookings'   => ReligiousBooking::whereNotNull('safa_synced_at')->count(),
                    'pending_bookings'  => ReligiousBooking::whereNull('safa_synced_at')->whereNotIn('status', ['cancelled'])->count(),
                ];
            } else {
                $p['sync_stats'] = [
                    'synced_bookings'   => ReligiousBooking::whereNotNull('umrah_portal_synced_at')->count(),
                    'pending_bookings'  => ReligiousBooking::whereNull('umrah_portal_synced_at')->whereNotIn('status', ['cancelled'])->count(),
                ];
            }
        }
        unset($p);

        // Overall log stats
        $overallStats = DB::table('integration_logs')
            ->selectRaw("
                COUNT(*) AS total,
                SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) AS successful,
                SUM(CASE WHEN status = 'failed'  THEN 1 ELSE 0 END) AS failed,
                SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) AS today_calls
            ")
            ->first();

        // Recent logs
        $logsQuery = IntegrationLog::with(['booking:id,booking_number', 'trigger:id,name'])
            ->latest();

        if ($request->filled('provider')) {
            $logsQuery->where('provider', $request->provider);
        }
        if ($request->filled('status')) {
            $logsQuery->where('status', $request->status);
        }

        $logs = $logsQuery->paginate(20)->withQueryString();

        return view('admin.religious.integrations.index', compact('providers', 'overallStats', 'logs'));
    }

    /**
     * Test connection to a provider without touching any booking.
     */
    public function testConnection(Request $request, SafaService $safa, UmrahPortalService $portal)
    {
        $provider = $request->validate(['provider' => ['required', 'in:safa,umrah_portal']])['provider'];

        try {
            $result = $provider === 'safa'
                ? $safa->testConnection()
                : $portal->testConnection();

            return back()->with('success', sprintf(
                'اختبار الاتصال مع %s نجح ✓ — Endpoint: %s',
                $provider === 'safa' ? 'صفا' : 'بوابة العمرة',
                $result['endpoint']
            ));
        } catch (\Throwable $e) {
            return back()->with('error', 'فشل الاختبار: ' . $e->getMessage());
        }
    }

    /**
     * Bulk sync all unsynced bookings for a given provider.
     * Sequential — for hundreds of bookings, this should be moved to a queue job.
     */
    public function bulkSync(Request $request, SafaService $safa, UmrahPortalService $portal)
    {
        $provider = $request->validate(['provider' => ['required', 'in:safa,umrah_portal']])['provider'];

        $pending = ReligiousBooking::query()
            ->whereNotIn('status', ['cancelled'])
            ->when($provider === 'safa',         fn ($q) => $q->whereNull('safa_synced_at'))
            ->when($provider === 'umrah_portal', fn ($q) => $q->whereNull('umrah_portal_synced_at'))
            ->limit(50)
            ->get();

        $success = 0;
        $failed  = 0;

        foreach ($pending as $booking) {
            try {
                if ($provider === 'safa') {
                    $safa->pullForBooking($booking);
                } else {
                    $portal->syncBooking($booking);
                }
                $success++;
            } catch (\Throwable $e) {
                $failed++;
            }
        }

        return back()->with('success', sprintf(
            'تمت المزامنة الجماعية: %d حجز نجح، %d فشل (من أصل %d حجوزات معلقة)',
            $success,
            $failed,
            $pending->count()
        ));
    }

    /**
     * Show full payload of a single log entry — for debugging.
     */
    public function logDetail(IntegrationLog $log)
    {
        $log->load(['booking', 'trigger']);
        return view('admin.religious.integrations.log_detail', compact('log'));
    }
}
