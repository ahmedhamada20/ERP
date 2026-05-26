<?php

namespace App\Services\Religious;

use App\Models\BookingPilgrim;
use App\Models\IntegrationLog;
use App\Models\ReligiousBooking;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Mock service for Safa visa integration — صفا.
 *
 * Replace the body of `pullForBooking()` with a real HTTP call once the
 * client provides API credentials & docs. The signature should stay the
 * same so the controller doesn't need to change.
 *
 * Every operation logs to integration_logs so the Integrations page can
 * show what ran, when, by whom, and whether it succeeded.
 */
class SafaService
{
    public function pullForBooking(ReligiousBooking $booking): array
    {
        $start = microtime(true);
        Log::info('SafaService.pullForBooking (MOCK)', ['booking_id' => $booking->id]);

        try {
            $groupVisa = 'SF-' . strtoupper(Str::random(2)) . '-' . date('Y') . '-' . str_pad(crc32($booking->id) % 1000000, 6, '0', STR_PAD_LEFT);
            $groupBar  = 'BC' . str_pad(crc32($booking->booking_number) % 10000000000, 10, '0', STR_PAD_LEFT);

            $pilgrimsUpdated = 0;
            $booking->pilgrims()->whereNull('safa_barcode')->get()->each(function (BookingPilgrim $p) use (&$pilgrimsUpdated) {
                $p->update([
                    'safa_barcode'     => 'BC' . str_pad(crc32($p->id) % 10000000000, 10, '0', STR_PAD_LEFT),
                    'visa_number'      => 'V-' . date('Y') . '-' . str_pad(crc32($p->id) % 1000000, 6, '0', STR_PAD_LEFT),
                    'visa_status'      => 'issued',
                    'visa_issued_date' => now()->toDateString(),
                    'visa_expiry_date' => now()->addMonths(3)->toDateString(),
                ]);
                $pilgrimsUpdated++;
            });

            $booking->update([
                'safa_barcode'           => $groupBar,
                'safa_visa_group_number' => $groupVisa,
                'safa_synced_at'         => now(),
            ]);

            $result = [
                'success'           => true,
                'group_visa'        => $groupVisa,
                'group_barcode'     => $groupBar,
                'pilgrims_updated'  => $pilgrimsUpdated,
            ];

            $this->log($booking, 'pull_visa', 'success', $result, null, $start);

            return $result;
        } catch (\Throwable $e) {
            $this->log($booking, 'pull_visa', 'failed', null, $e->getMessage(), $start);
            throw $e;
        }
    }

    /**
     * Mock connection test — verifies credentials would be valid.
     * Replace with a real ping/handshake when real API arrives.
     */
    public function testConnection(): array
    {
        $start = microtime(true);

        // Mock latency
        usleep(rand(50_000, 200_000));

        $result = [
            'success'        => true,
            'provider'       => 'safa',
            'endpoint'       => config('services.safa.url', 'https://api.safa.sa/v1 (MOCK)'),
            'mock'           => true,
            'tested_at'      => now()->toIso8601String(),
        ];

        $this->log(null, 'test_connection', 'success', $result, null, $start);

        return $result;
    }

    private function log(?ReligiousBooking $booking, string $action, string $status, ?array $response, ?string $error, float $startedAt): void
    {
        IntegrationLog::create([
            'provider'         => 'safa',
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
