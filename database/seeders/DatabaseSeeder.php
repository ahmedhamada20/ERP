<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ─── Essential seeders (always run) ───────────────────────────────
        $this->call([
            RolePermissionSeeder::class,
            AdminUserSeeder::class,
            SettingsSeeder::class,
            ExchangeRateSeeder::class,
            ReligiousProgramSeeder::class,
            ChartOfAccountsSeeder::class,
        ]);

        // ─── Demo seeders ──────────────────────────────────────────────────
        // Skip in production unless explicitly forced via SEED_DEMO=true.
        // Run individually with: php artisan db:seed --class=CustomerDemoSeeder
        if (app()->environment('production') && env('SEED_DEMO') !== true) {
            return;
        }

        $this->call([
            TeamUsersSeeder::class,
            CustomerDemoSeeder::class,
            ReligiousBookingDemoSeeder::class,
            SupplierDemoSeeder::class,
        ]);
    }
}
