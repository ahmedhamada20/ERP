<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Transportation segments — النقل (طيران / باص / قطار / VIP).
 *
 * Covers both international flights and internal Saudi transport
 * (Mecca ↔ Medina, airport transfers, etc).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_transportation', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('booking_id')->constrained('religious_bookings')->cascadeOnDelete();

            $table->enum('type', ['flight', 'bus', 'train', 'vip'])->comment('نوع النقل');
            $table->enum('direction', ['outbound', 'inbound', 'internal'])
                  ->comment('ذهاب / عودة / داخلي');
            $table->enum('segment', ['cai_jed', 'jed_cai', 'jed_mec', 'mec_med', 'med_jed', 'other'])
                  ->default('other')->comment('قطاع السفر');

            $table->string('carrier_name')->nullable()->comment('اسم شركة الطيران/الناقل');
            $table->string('reference')->nullable()->comment('PNR / رقم التذكرة');
            $table->string('departure_location')->nullable();
            $table->string('arrival_location')->nullable();
            $table->dateTime('departure_at')->nullable();
            $table->dateTime('arrival_at')->nullable();

            $table->enum('currency', ['EGP', 'SAR', 'USD'])->default('EGP');
            $table->decimal('cost_per_person', 12, 2)->default(0);
            $table->unsignedSmallInteger('pax_count')->default(1);
            $table->decimal('total_cost', 14, 2)->default(0);
            $table->decimal('exchange_rate', 12, 4)->default(0);
            $table->decimal('total_cost_egp', 14, 2)->default(0);

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['booking_id', 'type']);
            $table->index('departure_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_transportation');
    }
};
