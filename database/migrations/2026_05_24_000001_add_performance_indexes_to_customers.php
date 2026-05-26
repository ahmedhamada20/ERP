<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Performance indexes for high-scale tourism ERP.
 *
 * Targets: 1M+ customers, 1000+ concurrent users.
 * Covers all common query patterns: filtering by status+type, listing by
 * created_at, passport expiry alerts, fast wildcard search.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            // Composite for the most common filter combo + ordering
            $table->index(['status', 'type', 'created_at'], 'idx_cust_status_type_created');

            // Hot path: passport expiry alerts (e.g. "expiring in 6 months")
            $table->index(['passport_expiry_date', 'status'], 'idx_cust_pass_expiry_status');

            // Listing by creator (manager dashboards)
            $table->index(['created_by', 'created_at'], 'idx_cust_creator_created');

            // Nationality + city filters used in advanced filters
            $table->index('nationality', 'idx_cust_nationality');
            $table->index('city', 'idx_cust_city');
            $table->index('country', 'idx_cust_country');

            // Birth date for age-based reports
            $table->index('birth_date', 'idx_cust_birth_date');
        });

        // MySQL FULLTEXT index for fast wildcard search across 1M+ rows.
        // Falls back gracefully — only added if not present and engine supports it.
        if (DB::connection()->getDriverName() === 'mysql') {
            $exists = collect(DB::select("SHOW INDEX FROM customers WHERE Key_name = 'ft_cust_search'"))->isNotEmpty();
            if (!$exists) {
                DB::statement("ALTER TABLE customers ADD FULLTEXT ft_cust_search (full_name, full_name_en, email, phone, national_id, passport_number)");
            }
        }
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex('idx_cust_status_type_created');
            $table->dropIndex('idx_cust_pass_expiry_status');
            $table->dropIndex('idx_cust_creator_created');
            $table->dropIndex('idx_cust_nationality');
            $table->dropIndex('idx_cust_city');
            $table->dropIndex('idx_cust_country');
            $table->dropIndex('idx_cust_birth_date');
        });

        if (DB::connection()->getDriverName() === 'mysql') {
            $exists = collect(DB::select("SHOW INDEX FROM customers WHERE Key_name = 'ft_cust_search'"))->isNotEmpty();
            if ($exists) {
                DB::statement('ALTER TABLE customers DROP INDEX ft_cust_search');
            }
        }
    }
};
