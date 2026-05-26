<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Integration logs — سجل كل عمليات المزامنة مع APIs الخارجية.
 *
 * Every call to SafaService / UmrahPortalService writes a row here so the
 * integrations page can show what ran, when, by whom, and whether it
 * succeeded. Also useful for debugging when real APIs return errors.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integration_logs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->enum('provider', ['safa', 'umrah_portal'])->index();
            $table->enum('action', ['pull_visa', 'pull_barcode', 'sync_booking', 'test_connection', 'other']);
            $table->enum('status', ['success', 'failed', 'pending']);

            $table->foreignUlid('booking_id')->nullable()->constrained('religious_bookings')->cascadeOnDelete();
            $table->foreignUlid('triggered_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('request_summary', 500)->nullable()->comment('ملخص الطلب');
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->text('error_message')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();

            $table->timestamps();

            $table->index(['provider', 'status', 'created_at'], 'idx_intlog_prov_status');
            $table->index(['booking_id', 'created_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_logs');
    }
};
