<?php

namespace App\Services\Religious;

use App\Models\IntegrationLog;
use App\Models\ReligiousBooking;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Mock service for Umrah Portal — بوابة العمرة (الجهة الحكومية السعودية).
 *
 * Replace `syncBooking()` body with real HTTP integration when docs land.
 * Every operation writes to integration_logs.
 */
class UmrahPortalService
{
    public function syncBooking(ReligiousBooking $booking): array
    {
        $start = microtime(true);
        Log::info('UmrahPortalService.syncBooking (MOCK)', ['booking_id' => $booking->id]);

        try {
            $ref = 'UMR-PRTL-' . date('Y') . '-' . strtoupper(Str::random(8));

            $booking->update([
                'umrah_portal_ref'        => $ref,
                'umrah_portal_synced_at'  => now(),
            ]);

            $result = [
                'success'   => true,
                'reference' => $ref,
            ];

            $this->log($booking, 'sync_booking', 'success', $result, null, $start);

            return $result;
        } catch (\Throwable $e) {
            $this->log($booking, 'sync_booking', 'failed', null, $e->getMessage(), $start);
            throw $e;
        }
    }

    public function testConnection(): array
    {
        $start = microtime(true);
        usleep(rand(50_000, 200_000));

        $result = [
            'success'   => true,
            'provider'  => 'umrah_portal',
            'endpoint'  => config('services.umrah_portal.url', 'https://api.umrah.com.sa/v1 (MOCK)'),
            'mock'      => true,
            'tested_at' => now()->toIso8601String(),
        ];

        $this->log(null, 'test_connection', 'success', $result, null, $start);

        return $result;
    }

    private function log(?ReligiousBooking $booking, string $action, string $status, ?array $response, ?string $error, float $startedAt): void
    {
        IntegrationLog::create([
            'provider'         => 'umrah_portal',
            'action'           => $action,
            'status'           => $status,
            'booking_id'       => $booking?->id,
            'triggered_by'     => auth()->id(),
            'request_summary'  => $booking ? 'Booking ' . $booking->booking_number : 'Connection test',
            'response_payload' => $response,
            'error_message'    => $error,
            'duration_ms'      => (int) round((microtime(true) - $startedAt) * 1000),
        ]);
    }
}
