<?php

use App\Models\Branch;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adds `branch_id` to every transaction-bearing table for per-branch reporting.
 *
 * Strategy:
 *   1. Ensure a main branch exists (DefaultBranchSeeder runs first or we create one inline)
 *   2. Add nullable branch_id column with FK to branches
 *   3. Backfill existing rows with the main branch id (so historical reports work)
 *
 * Tables touched:
 *   religious_bookings, domestic_bookings, customers, suppliers,
 *   vouchers, journal_entries
 *
 * branch_id stays nullable forever — supports records that genuinely have
 * no branch (e.g. central reports, system journals).
 */
return new class extends Migration
{
    private const TABLES = [
        'religious_bookings',
        'domestic_bookings',
        'customers',
        'suppliers',
        'vouchers',
        'journal_entries',
    ];

    public function up(): void
    {
        // 1. Ensure a main branch exists (inline — seeder may not have run yet)
        $mainBranch = Branch::where('is_main', true)->first()
            ?? Branch::create([
                'code'      => 'BRN-001',
                'name'      => 'الفرع الرئيسي',
                'name_en'   => 'Main Branch',
                'country'   => 'مصر',
                'city'      => 'القاهرة',
                'is_main'   => true,
                'is_active' => true,
            ]);

        // 2. Add branch_id columns
        foreach (self::TABLES as $table) {
            if (! Schema::hasTable($table) || Schema::hasColumn($table, 'branch_id')) {
                continue;
            }

            Schema::table($table, function (Blueprint $t) use ($table) {
                $t->foreignUlid('branch_id')->nullable()
                  ->after('id')
                  ->constrained('branches')->nullOnDelete();
                $t->index(['branch_id', 'created_at'], "idx_{$table}_branch_created");
            });
        }

        // 3. Backfill existing rows with the main branch
        foreach (self::TABLES as $table) {
            if (! Schema::hasTable($table)) continue;

            DB::table($table)
                ->whereNull('branch_id')
                ->update(['branch_id' => $mainBranch->id]);
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'branch_id')) {
                continue;
            }

            Schema::table($table, function (Blueprint $t) use ($table) {
                $t->dropIndex("idx_{$table}_branch_created");
                $t->dropForeign(['branch_id']);
                $t->dropColumn('branch_id');
            });
        }
    }
};
