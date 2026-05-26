<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Chart of Accounts (دليل الحسابات).
 *
 * Hierarchical: each account may have a parent (forming a tree).
 * Group accounts (is_group=true) are containers and CANNOT receive journal lines.
 * Leaf accounts (is_group=false) are postable.
 *
 * Account code format: numeric, dot-free (e.g. "1110", "1131").
 * Convention by first digit:
 *   1 = Assets (الأصول)
 *   2 = Liabilities (الخصوم)
 *   3 = Equity (حقوق الملكية)
 *   4 = Revenue (الإيرادات)
 *   5 = Expenses (المصروفات)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->string('code', 20)->unique()->comment('كود الحساب (1110, 1131...)');
            $table->string('name')->comment('اسم الحساب بالعربي');
            $table->string('name_en')->nullable()->comment('اسم الحساب بالإنجليزي');

            $table->enum('type', ['asset', 'liability', 'equity', 'revenue', 'expense'])
                  ->comment('التصنيف الرئيسي');

            $table->enum('sub_type', [
                'current_asset', 'fixed_asset', 'other_asset',
                'current_liability', 'long_term_liability',
                'equity',
                'operating_revenue', 'other_revenue',
                'cost_of_services', 'operating_expense', 'other_expense',
                'cash', 'bank',  // special subtypes for voucher dropdowns
            ])->nullable();

            // Self-ref tree
            $table->foreignUlid('parent_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->boolean('is_group')->default(false)->comment('true = حساب رئيسي (مايقبلش حركات)');

            // Behaviour flags
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false)->comment('حسابات افتراضية ما تتمسحش');
            $table->enum('currency', ['EGP', 'SAR', 'USD'])->default('EGP');

            // Opening balance (for go-live migration)
            $table->decimal('opening_balance', 18, 2)->default(0)->comment('الرصيد الافتتاحي');
            $table->date('opening_balance_date')->nullable();

            $table->text('notes')->nullable();
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // Listing the chart by code (most common query)
            $table->index('code');
            $table->index(['type', 'is_active']);
            $table->index('parent_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
