<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cost line items — بنود التكلفة لكل حجز.
 *
 * Each row is a single cost (or revenue) entry. The category enum mirrors
 * exactly the items listed in the client's brief:
 *   تأشيرة / مصاريف غرفة / نقل / طيران / نثريات / إشراف / ضرائب /
 *   تنشيط / ربح / هدايا / مطوف
 *
 * Plus my recommended additions (commission, bank fees, insurance).
 *
 * All amounts are denormalized to EGP via amount_egp so reports can
 * aggregate without recomputing FX. The original currency + rate are
 * kept for audit and edits.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_costs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('booking_id')->constrained('religious_bookings')->cascadeOnDelete();

            $table->enum('category', [
                'visa',            // تأشيرة
                'room',            // مصاريف الغرفة
                'transport',       // نقل (باص/قطار/VIP)
                'flight',          // طيران
                'miscellaneous',   // نثريات
                'supervision',     // إشراف
                'tax',             // ضرائب
                'activation',      // تنشيط
                'profit',          // ربح (revenue marker)
                'gifts',           // هدايا
                'mutawif',         // المطوف
                'commission',      // عمولة موظف
                'bank_fee',        // رسوم بنكية
                'insurance',       // تأمين سفر
                'other',
            ])->comment('بند التكلفة');

            $table->string('description')->nullable();
            $table->enum('currency', ['EGP', 'SAR', 'USD'])->default('EGP');
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
        Schema::dropIfExists('booking_costs');
    }
};
