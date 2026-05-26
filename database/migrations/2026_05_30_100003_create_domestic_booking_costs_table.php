<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cost line items for domestic bookings — بنود التكلفة لكل حجز داخلي.
 *
 * Categories tailored to domestic tourism: no visa/mutawif, but adds
 * activities (excursions/tickets) and meals. Mirrors religious cost
 * structure for service reuse (recalculateTotals, BalanceCalculator, …).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('domestic_booking_costs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('booking_id')->constrained('domestic_bookings')->cascadeOnDelete();

            $table->enum('category', [
                'hotel',           // الفندق
                'room',            // غرفة (لو منفصلة عن الفندق)
                'transport',       // نقل (باص/ميكروباص)
                'private_car',     // سيارة خاصة
                'flight',          // طيران داخلي
                'meals',           // وجبات إضافية
                'activities',      // أنشطة / رحلات بحرية / تذاكر
                'supervision',     // إشراف
                'tax',             // ضرائب
                'activation',      // تنشيط
                'profit',          // ربح (revenue marker)
                'gifts',           // هدايا
                'commission',      // عمولة موظف
                'bank_fee',        // رسوم بنكية
                'insurance',       // تأمين سفر
                'miscellaneous',   // نثريات
                'other',
            ])->comment('بند التكلفة');

            $table->string('description')->nullable();
            $table->enum('currency', ['EGP', 'USD', 'EUR'])->default('EGP');
            $table->decimal('amount', 14, 2)->default(0);
            $table->decimal('exchange_rate', 12, 4)->default(1);
            $table->decimal('amount_egp', 14, 2)->default(0)->comment('الموحد بالجنيه (للتقارير)');

            $table->unsignedSmallInteger('quantity')->default(1);
            $table->enum('per_unit', ['per_person', 'per_room', 'per_night', 'per_trip', 'total'])
                  ->default('total');

            $table->boolean('is_revenue')->default(false)->comment('true لبند الربح');
            $table->boolean('is_locked')->default(false)->comment('بنود مالية مقفولة بعد إقفال الحجز');

            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['booking_id', 'category']);
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('domestic_booking_costs');
    }
};
