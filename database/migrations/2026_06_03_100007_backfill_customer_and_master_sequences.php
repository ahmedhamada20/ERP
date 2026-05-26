<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * تعبئة sequences للموديلات التي اكتُشف لها race condition في Sprint 11:
 * Customer (CUS-YYYY-XXXXX), Hotel (HTL-YYYY-XXXXX),
 * Airline (AIR-YYYY-XXXXX), TransportProvider (TRP-YYYY-XXXXX).
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->backfill('customers',           'code', 'CUS', 'customer');
        $this->backfill('hotels',              'code', 'HTL', 'hotel');
        $this->backfill('airlines',            'code', 'AIR', 'airline');
        $this->backfill('transport_providers', 'code', 'TRP', 'transport_provider');
    }

    public function down(): void {}

    private function backfill(string $table, string $column, string $prefix, string $sequenceKeyBase): void
    {
        if (! DB::getSchemaBuilder()->hasTable($table)) {
            return;
        }

        $rows = DB::table($table)
            ->where($column, 'like', $prefix . '-%')
            ->pluck($column);

        $maxByYear = [];
        foreach ($rows as $value) {
            if (preg_match('/^' . preg_quote($prefix, '/') . '-(\d{4})-(\d+)$/', $value, $m)) {
                $year   = $m[1];
                $number = (int) $m[2];
                if (! isset($maxByYear[$year]) || $number > $maxByYear[$year]) {
                    $maxByYear[$year] = $number;
                }
            }
        }

        foreach ($maxByYear as $year => $max) {
            DB::table('sequences')->updateOrInsert(
                ['key' => $sequenceKeyBase . ':' . $year],
                ['last_number' => $max, 'updated_at' => now()],
            );
        }
    }
};
