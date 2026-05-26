<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ربط بنود تكاليف الحجوزات (دينية + داخلية) بالموردين.
 *
 * كان البند يحفظ التكلفة كمبلغ مع category فقط، بدون أي ربط بمورد فعلي
 * من جدول suppliers. النتيجة: إقفال الحجز ينشئ JE على حساب أب عام
 * (مثل "موردين فنادق") بدون تتبع لكل مورد على حدة → كشف حساب المورد
 * لا يعكس التكاليف الحقيقية من الحجوزات.
 *
 * الحل: FK اختياري إلى suppliers — إذا تم تحديده، الـ Poster سيستخدم
 * الحساب الأب الخاص بنوع المورد، ويمكن لاحقاً إنشاء supplier_invoices
 * تلقائياً عند الإقفال.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_costs', function (Blueprint $table) {
            $table->foreignUlid('supplier_id')->nullable()->after('category')
                  ->constrained('suppliers')->nullOnDelete()
                  ->comment('المورد المرتبط بهذا البند (اختياري)');
            $table->index('supplier_id');
        });

        Schema::table('domestic_booking_costs', function (Blueprint $table) {
            $table->foreignUlid('supplier_id')->nullable()->after('category')
                  ->constrained('suppliers')->nullOnDelete()
                  ->comment('المورد المرتبط بهذا البند (اختياري)');
            $table->index('supplier_id');
        });
    }

    public function down(): void
    {
        Schema::table('booking_costs', function (Blueprint $table) {
            $table->dropForeign(['supplier_id']);
            $table->dropIndex(['supplier_id']);
            $table->dropColumn('supplier_id');
        });

        Schema::table('domestic_booking_costs', function (Blueprint $table) {
            $table->dropForeign(['supplier_id']);
            $table->dropIndex(['supplier_id']);
            $table->dropColumn('supplier_id');
        });
    }
};
