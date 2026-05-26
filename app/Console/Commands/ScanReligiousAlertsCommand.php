<?php

namespace App\Console\Commands;

use App\Services\Religious\ReligiousAlertScanner;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class ScanReligiousAlertsCommand extends Command
{
    protected $signature = 'religious:alerts-scan';

    protected $description = 'Scan religious bookings/pilgrims and create alerts for issues that need attention';

    public function handle(ReligiousAlertScanner $scanner): int
    {
        $startedAt = now();
        $this->info("[{$startedAt->toDateTimeString()}] Scanning religious alerts...");

        try {
            $created = $scanner->scan();
        } catch (Throwable $e) {
            $this->error('Scan failed: ' . $e->getMessage());
            Log::channel('single')->error('religious:alerts-scan failed', [
                'exception' => $e->getMessage(),
                'trace'     => $e->getTraceAsString(),
            ]);

            return self::FAILURE;
        }

        $duration = $startedAt->diffInMilliseconds(now());

        $this->info(sprintf('Done — created %d new alert(s) in %d ms', $created, $duration));
        Log::channel('single')->info('religious:alerts-scan completed', [
            'created_alerts' => $created,
            'duration_ms'    => $duration,
        ]);

        return self::SUCCESS;
    }
}
