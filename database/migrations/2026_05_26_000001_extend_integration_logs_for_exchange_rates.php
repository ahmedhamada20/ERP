<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Extend integration_logs.provider + action enums to also cover the live
 * exchange-rate API sync. Original enums covered only Safa/Umrah-Portal.
 */
return new class extends Migration
{
    public function up(): void
    {
        // MySQL-only: SQLite/other DBs don't support MODIFY ENUM and don't
        // enforce ENUM constraints anyway (Laravel emulates via CHECK).
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }
        DB::statement("ALTER TABLE integration_logs MODIFY provider ENUM('safa','umrah_portal','exchange_rates_api') NOT NULL");
        DB::statement("ALTER TABLE integration_logs MODIFY action ENUM('pull_visa','pull_barcode','sync_booking','test_connection','sync_rates','other') NOT NULL");
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }
        DB::statement("DELETE FROM integration_logs WHERE provider = 'exchange_rates_api'");
        DB::statement("ALTER TABLE integration_logs MODIFY provider ENUM('safa','umrah_portal') NOT NULL");
        DB::statement("ALTER TABLE integration_logs MODIFY action ENUM('pull_visa','pull_barcode','sync_booking','test_connection','other') NOT NULL");
    }
};
