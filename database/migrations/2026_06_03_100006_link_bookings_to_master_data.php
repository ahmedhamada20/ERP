<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ربط بنود السكن والنقل بجداول الماستر (Hotels, Airlines, TransportProviders).
 *
 * المشكلة: booking_accommodations.hotel_name و booking_transportation.carrier_name
 * كانتا نصوص حرة، فلا يمكن تتبع عقود الأسعار مع الفندق/شركة الطيران، ولا
 * استرجاع بياناتها (تليفونات، أرقام تأكيد، إلخ).
 *
 * الحل: FK اختياري إلى الماستر. إذا تم تحديده، يمكن للنظام استخدام بيانات
 * الفندق/الطيران المخزنة وعمل تقارير per-supplier.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_accommodations', function (Blueprint $table) {
            $table->foreignUlid('hotel_id')->nullable()->after('booking_id')
                  ->constrained('hotels')->nullOnDelete()
                  ->comment('فندق من جدول الماستر (اختياري)');
            $table->index('hotel_id');
        });

        Schema::table('booking_transportation', function (Blueprint $table) {
            $table->foreignUlid('airline_id')->nullable()->after('booking_id')
                  ->constrained('airlines')->nullOnDelete()
                  ->comment('شركة طيران من الماستر (للرحلات الجوية فقط)');
            $table->foreignUlid('transport_provider_id')->nullable()->after('airline_id')
                  ->constrained('transport_providers')->nullOnDelete()
                  ->comment('مزود نقل أرضي من الماستر (للحافلات/VIP)');
            $table->index('airline_id');
            $table->index('transport_provider_id');
        });
    }

    public function down(): void
    {
        Schema::table('booking_accommodations', function (Blueprint $table) {
            $table->dropForeign(['hotel_id']);
            $table->dropIndex(['hotel_id']);
            $table->dropColumn('hotel_id');
        });

        Schema::table('booking_transportation', function (Blueprint $table) {
            $table->dropForeign(['airline_id']);
            $table->dropForeign(['transport_provider_id']);
            $table->dropIndex(['airline_id']);
            $table->dropIndex(['transport_provider_id']);
            $table->dropColumn(['airline_id', 'transport_provider_id']);
        });
    }
};
