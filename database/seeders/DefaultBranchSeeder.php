<?php

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Seeder;

/**
 * Seeds a single main branch — required for multi-branch fallback.
 *
 * Idempotent: if any main branch already exists, does nothing.
 * Run during initial install AND before backfilling legacy transactions
 * with branch_id.
 */
class DefaultBranchSeeder extends Seeder
{
    public function run(): void
    {
        if (Branch::where('is_main', true)->exists()) {
            return;
        }

        Branch::create([
            'code'         => 'BRN-001',
            'name'         => 'الفرع الرئيسي',
            'name_en'      => 'Main Branch',
            'country'      => 'مصر',
            'city'         => 'القاهرة',
            'is_main'      => true,
            'is_active'    => true,
        ]);
    }
}
