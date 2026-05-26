<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Transport providers catalog — شركات النقل البري.
 *
 * Buses, trains, VIP limos. Used by sales when adding transport
 * segments to bookings; complements booking_transportation which
 * stores per-booking inline transport rows.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transport_providers', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('code')->unique();
            $table->string('name')->comment('شركة سابتكو، شركة الفائز...');
            $table->enum('type', ['bus', 'train', 'vip', 'limousine', 'minivan'])->default('bus')->index();
            $table->enum('country', ['SA', 'EG', 'AE', 'TR', 'other'])->default('SA');

            $table->unsignedSmallInteger('vehicle_count')->default(1);
            $table->unsignedSmallInteger('capacity_per_vehicle')->default(45);

            $table->decimal('base_price_per_pax', 12, 2)->default(0);
            $table->decimal('base_price_per_vehicle', 12, 2)->default(0)->comment('لو حجز سيارة كاملة');
            $table->enum('currency', ['EGP', 'SAR', 'USD'])->default('SAR');

            $table->json('routes')->nullable()->comment('["MEC-MED","JED-MEC",...]');

            $table->string('contact_phone', 30)->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_person')->nullable()->comment('المسؤول للتواصل');
            $table->text('notes')->nullable();

            $table->boolean('is_active')->default(true)->index();
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['type', 'is_active']);
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transport_providers');
    }
};
