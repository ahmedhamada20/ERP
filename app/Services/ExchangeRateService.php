<?php

namespace App\Services;

use App\Models\ExchangeRate;
use App\Models\IntegrationLog;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Live exchange rate sync — جلب أسعار الصرف اللحظية.
 *
 * Talks to open.er-api.com (free, no API key needed) using EGP as the base
 * currency. The provider returns "1 EGP = X foreign", so we invert each
 * value to store the "1 foreign = N EGP" rate the rest of the system uses.
 *
 * Sample response (truncated):
 * {
 *   "result": "success",
 *   "base_code": "EGP",
 *   "rates": { "USD": 0.02, "SAR": 0.075, ... },
 *   "time_last_update_unix": 1747...
 * }
 *
 * Every sync is recorded in integration_logs so finance can see when rates
 * last refreshed, who triggered the run, and whether it succeeded.
 */
class ExchangeRateService
{
    /**
     * Pull today's rates and upsert into the exchange_rates table.
     * Returns a summary array; raises on hard failures.
     *
     * $triggeredBy is a User ULID (string) — null when run from scheduler.
     */
    public function syncToday(?string $triggeredBy = null): array
    {
        $config = (array) config('services.exchange_rates_api');
        $started = microtime(true);

        $log = IntegrationLog::create([
            'provider'        => 'exchange_rates_api',
            'action'          => 'sync_rates',
            'status'          => 'pending',
            'triggered_by'    => $triggeredBy,
            'request_summary' => 'سحب أسعار الصرف اللحظية',
            'request_payload' => ['base' => 'EGP', 'currencies' => $config['currencies']],
        ]);

        try {
            $response = Http::timeout((int) $config['timeout'])
                ->retry(2, 500, throw: false)
                ->get(rtrim($config['base_url'], '/') . '/latest/EGP');

            if (!$response->successful()) {
                throw new \RuntimeException('HTTP ' . $response->status() . ' — ' . $response->body());
            }

            $body = $response->json();

            if (($body['result'] ?? null) !== 'success' || empty($body['rates'])) {
                throw new \RuntimeException('Unexpected payload: ' . substr($response->body(), 0, 200));
            }

            $today   = now()->toDateString();
            $updated = [];

            foreach ($config['currencies'] as $currency) {
                if (!isset($body['rates'][$currency])) continue;

                $rateFromBase = (float) $body['rates'][$currency]; // 1 EGP → X foreign
                if ($rateFromBase <= 0) continue;

                $foreignToEgp = round(1 / $rateFromBase, 4);     // 1 foreign → N EGP

                ExchangeRate::updateOrCreate(
                    [
                        'from_currency'  => $currency,
                        'to_currency'    => 'EGP',
                        'effective_date' => $today,
                    ],
                    [
                        'rate'        => $foreignToEgp,
                        'is_active'   => true,
                        'notes'       => 'تحديث تلقائي من open.er-api.com',
                        'created_by'  => $triggeredBy,
                    ]
                );

                $updated[$currency] = $foreignToEgp;
            }

            // Deactivate same-pair older same-day duplicates if any sneaked in.
            // Keep historical records (yesterday and before) intact for audit.

            $log->update([
                'status'           => 'success',
                'response_payload' => $updated,
                'duration_ms'      => (int) ((microtime(true) - $started) * 1000),
            ]);

            return [
                'ok'        => true,
                'updated'   => $updated,
                'fetched_at'=> isset($body['time_last_update_unix'])
                    ? date('Y-m-d H:i:s', (int) $body['time_last_update_unix'])
                    : now()->toDateTimeString(),
            ];
        } catch (ConnectionException $e) {
            $log->update([
                'status'        => 'failed',
                'error_message' => 'Connection: ' . $e->getMessage(),
                'duration_ms'   => (int) ((microtime(true) - $started) * 1000),
            ]);
            Log::warning('ExchangeRateService connection error', ['error' => $e->getMessage()]);
            return ['ok' => false, 'error' => 'فشل الاتصال بالخادم — تحقق من الإنترنت'];
        } catch (\Throwable $e) {
            $log->update([
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
                'duration_ms'   => (int) ((microtime(true) - $started) * 1000),
            ]);
            Log::error('ExchangeRateService failed', ['error' => $e->getMessage()]);
            return ['ok' => false, 'error' => 'تعذّر جلب الأسعار: ' . $e->getMessage()];
        }
    }

    public function lastSyncedAt(): ?\Illuminate\Support\Carbon
    {
        return IntegrationLog::query()
            ->where('provider', 'exchange_rates_api')
            ->where('status', 'success')
            ->latest('created_at')
            ->value('created_at');
    }
}
