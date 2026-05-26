<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Wire bookings to a sales employee for commission calculation.
 *
 * Nullable on purpose: legacy rows + bookings made by admin/integration
 * have no salesperson. Commission engine simply skips rows where this is null.
 *
 * Indexed because commission reports query "all bookings for employee X
 * in period Y" — without the index that scan would walk the full table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('religious_bookings', function (Blueprint $table) {
            $table->foreignUlid('sales_employee_id')->nullable()->after('customer_id')
                  ->comment('موظف المبيعات المسؤول عن الحجز (للعمولات)')
                  ->constrained('employees')->nullOnDelete();

            $table->index(['sales_employee_id', 'created_at'], 'rb_sales_emp_created_idx');
        });

        Schema::table('domestic_bookings', function (Blueprint $table) {
            $table->foreignUlid('sales_employee_id')->nullable()->after('customer_id')
                  ->comment('موظف المبيعات المسؤول عن الحجز (للعمولات)')
                  ->constrained('employees')->nullOnDelete();

            $table->index(['sales_employee_id', 'created_at'], 'db_sales_emp_created_idx');
        });
    }

    public function down(): void
    {
        Schema::table('religious_bookings', function (Blueprint $table) {
            $table->dropIndex('rb_sales_emp_created_idx');
            $table->dropForeign(['sales_employee_id']);
            $table->dropColumn('sales_employee_id');
        });

        Schema::table('domestic_bookings', function (Blueprint $table) {
            $table->dropIndex('db_sales_emp_created_idx');
            $table->dropForeign(['sales_employee_id']);
            $table->dropColumn('sales_employee_id');
        });
    }
};
