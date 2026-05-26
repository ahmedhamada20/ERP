<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hotel accommodation segments — سكن مكة والمدينة.
 *
 * Typical booking has two rows (Mecca + Medina), but the schema allows
 * any number of segments to cover edge cases (multi-hotel itineraries,
 * Jeddah transit nights, etc).
 *
 * Per-person price formula (from the brief):
 *   price_per_person = (room_price_per_night × nights) / pax_per_room × exchange_rate
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_accommodations', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('booking_id')->constrained('religious_bookings')->cascadeOnDelete();

            $table->enum('city', ['mecca', 'medina', 'jeddah', 'other'])->default('mecca');
            $table->string('hotel_name');
            $table->enum('hotel_grade', ['economy', '4_stars', '5_stars'])->default('economy');
            $table->string('hotel_distance_meters')->nullable()->comment('المسافة من الحرم بالأمتار');

            $table->date('check_in_date');
            $table->date('check_out_date');
            $table->unsignedSmallInteger('nights');
            $table->unsignedSmallInteger('rooms_count')->default(1);
            $table->enum('room_type', ['single', 'double', 'triple', 'quad', 'quintuple', 'sextuple'])
                  ->comment('نوع الغرفة: 1-6 أفراد');
            $table->unsignedSmallInteger('pax_per_room')->default(2)->comment('عدد الأفراد في الغرفة');
            $table->enum('meal_plan', ['ro', 'bb', 'hb', 'fb', 'pp', 'hp'])->default('hp')
                  ->comment('Room Only / Bed & Breakfast / Half Board / Full Board / P.P / H.P');

            // Money — kept in SAR (hotels invoice in SAR) and converted to EGP
            $table->decimal('room_price_per_night_sar', 12, 2)->default(0);
            $table->decimal('total_cost_sar', 14, 2)->default(0);
            $table->decimal('exchange_rate', 12, 4)->default(0);
            $table->decimal('total_cost_egp', 14, 2)->default(0);

            $table->string('confirmation_number')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['booking_id', 'city']);
            $table->index('check_in_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_accommodations');
    }
};
