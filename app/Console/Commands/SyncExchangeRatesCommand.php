<?php

namespace App\Console\Commands;

use App\Services\ExchangeRateService;
use Illuminate\Console\Command;

class SyncExchangeRatesCommand extends Command
{
    protected $signature = 'exchange-rates:sync';

    protected $description = 'سحب أسعار صرف العملات اللحظية وتخزينها في قاعدة البيانات';

    public function handle(ExchangeRateService $service): int
    {
        $this->info('Fetching live exchange rates...');

        $result = $service->syncToday();

        if (!$result['ok']) {
            $this->error('❌ ' . $result['error']);
            return self::FAILURE;
        }

        $this->info('✔ Rates updated:');
        foreach ($result['updated'] as $currency => $rate) {
            $this->line("   1 {$currency} = " . number_format($rate, 4) . ' EGP');
        }
        $this->line('Source timestamp: ' . $result['fetched_at']);

        return self::SUCCESS;
    }
}
