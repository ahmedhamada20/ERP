<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ربط الدفعات بحساب الخزينة/البنك المحدد — cash_account_id.
 *
 * المشكلة الأصلية: booking_payments و domestic_booking_payments كانت
 * تخزن method (cash/bank_transfer/...) + bank_name كنص حر. الترحيل
 * المحاسبي كان يختار "أول" خزينة/بنك من دليل الحسابات بشكل عشوائي،
 * مما يجعل المطابقة البنكية مستحيلة.
 *
 * الحل: إضافة FK لحساب محدد. nullable للتوافق مع البيانات القديمة،
 * لكن required في الـ validation للدفعات الجديدة.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_payments', function (Blueprint $table) {
            $table->foreignUlid('cash_account_id')->nullable()->after('method')
                  ->constrained('accounts')->restrictOnDelete()
                  ->comment('حساب الخزينة/البنك المحدد المستقبل للدفعة');
            $table->index('cash_account_id');
        });

        Schema::table('domestic_booking_payments', function (Blueprint $table) {
            $table->foreignUlid('cash_account_id')->nullable()->after('method')
                  ->constrained('accounts')->restrictOnDelete()
                  ->comment('حساب الخزينة/البنك المحدد المستقبل للدفعة');
            $table->index('cash_account_id');
        });
    }

    public function down(): void
    {
        Schema::table('booking_payments', function (Blueprint $table) {
            $table->dropForeign(['cash_account_id']);
            $table->dropIndex(['cash_account_id']);
            $table->dropColumn('cash_account_id');
        });

        Schema::table('domestic_booking_payments', function (Blueprint $table) {
            $table->dropForeign(['cash_account_id']);
            $table->dropIndex(['cash_account_id']);
            $table->dropColumn('cash_account_id');
        });
    }
};
