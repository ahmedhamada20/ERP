<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Historical exchange rates — أسعار الصرف التاريخية.
 *
 * Every religious booking snapshots the rate effective at the moment
 * it was created (stored on religious_bookings.exchange_rate_sar).
 * This table is the source of truth for "what was the rate on day X?".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('from_currency', 3)->comment('SAR/USD/EUR');
            $table->string('to_currency', 3)->default('EGP');
            $table->decimal('rate', 12, 4)->comment('سعر الصرف من from_currency إلى to_currency');
            $table->date('effective_date')->comment('تاريخ السريان');
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['from_currency', 'to_currency', 'effective_date'], 'uq_exch_pair_date');
            $table->index(['from_currency', 'to_currency', 'effective_date'], 'idx_exch_lookup');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exchange_rates');
    }
};
