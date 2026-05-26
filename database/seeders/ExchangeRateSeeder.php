<?php

namespace Database\Seeders;

use App\Models\ExchangeRate;
use Illuminate\Database\Seeder;

class ExchangeRateSeeder extends Seeder
{
    public function run(): void
    {
        // Seed only realistic recent rates so booking creation has something to read.
        // Operations team should update these via the Exchange Rates admin screen.
        $rates = [
            ['from_currency' => 'SAR', 'to_currency' => 'EGP', 'rate' => 13.5000, 'effective_date' => now()->subDays(30)->toDateString()],
            ['from_currency' => 'SAR', 'to_currency' => 'EGP', 'rate' => 13.8000, 'effective_date' => now()->subDays(7)->toDateString()],
            ['from_currency' => 'SAR', 'to_currency' => 'EGP', 'rate' => 14.0000, 'effective_date' => now()->toDateString()],

            ['from_currency' => 'USD', 'to_currency' => 'EGP', 'rate' => 52.0000, 'effective_date' => now()->subDays(7)->toDateString()],
            ['from_currency' => 'USD', 'to_currency' => 'EGP', 'rate' => 52.5000, 'effective_date' => now()->toDateString()],
        ];

        foreach ($rates as $row) {
            ExchangeRate::updateOrCreate(
                [
                    'from_currency'  => $row['from_currency'],
                    'to_currency'    => $row['to_currency'],
                    'effective_date' => $row['effective_date'],
                ],
                array_merge($row, ['is_active' => true])
            );
        }
    }
}
