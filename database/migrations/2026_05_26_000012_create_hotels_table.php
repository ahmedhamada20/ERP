<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hotels master catalog — كتالوج الفنادق.
 *
 * The religious-tourism module embeds hotel data inline on booking_accommodations,
 * this is the master record sales can pick from when quoting future bookings.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hotels', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('code')->unique();
            $table->string('name')->comment('الاسم بالعربي');
            $table->string('name_en')->nullable();
            $table->enum('city', ['mecca', 'medina', 'jeddah', 'cairo', 'dubai', 'istanbul', 'kuala_lumpur', 'other'])->index();
            $table->enum('grade', ['economy', '3_stars', '4_stars', '5_stars', 'luxury'])->default('economy')->index();
            $table->unsignedInteger('distance_meters')->nullable()->comment('المسافة من الحرم/المعلم - متر');

            $table->string('address')->nullable();
            $table->string('contact_phone', 30)->nullable();
            $table->string('contact_email')->nullable();
            $table->string('website')->nullable();

            $table->decimal('base_price_per_night', 12, 2)->default(0);
            $table->enum('currency', ['EGP', 'SAR', 'USD', 'AED', 'TRY'])->default('SAR');
            $table->json('room_types')->nullable()->comment('["double","triple","quad",...]');
            $table->unsignedSmallInteger('max_occupancy')->default(4);

            $table->json('amenities')->nullable()->comment('[wifi,pool,gym,prayer_room,...]');
            $table->string('cover_image')->nullable();
            $table->text('notes')->nullable();

            $table->boolean('is_active')->default(true)->index();
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['city', 'grade']);
            $table->index(['is_active', 'city']);
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hotels');
    }
};
