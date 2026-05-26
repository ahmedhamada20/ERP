<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Airlines catalog — قائمة شركات الطيران وعقود الأسعار.
 *
 * Master record for each airline route the office contracts with.
 * Used by sales to quote flight prices on bookings.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('airlines', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('code')->unique()->comment('كود داخلي');
            $table->string('airline_name')->comment('اسم الشركة - مصر للطيران، السعودية، ...');
            $table->string('airline_code', 10)->nullable()->comment('IATA code - MS, SV, ...');
            $table->string('route', 30)->index()->comment('CAI-JED, JED-CAI, ...');
            $table->enum('cabin_class', ['economy', 'business', 'first'])->default('economy');
            $table->string('aircraft_type')->nullable()->comment('Boeing 777, Airbus A320, ...');

            $table->decimal('base_price_per_pax', 12, 2)->default(0);
            $table->enum('currency', ['EGP', 'SAR', 'USD'])->default('EGP');

            $table->time('departure_time')->nullable();
            $table->time('arrival_time')->nullable();
            $table->unsignedSmallInteger('flight_duration_minutes')->nullable();

            $table->unsignedSmallInteger('capacity')->default(0)->comment('سعة الطائرة');
            $table->unsignedSmallInteger('available_seats')->default(0);

            $table->string('contact_phone', 30)->nullable();
            $table->string('contact_email')->nullable();
            $table->text('notes')->nullable();

            $table->boolean('is_active')->default(true)->index();
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['airline_name', 'route']);
            $table->index(['is_active', 'cabin_class']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('airlines');
    }
};
