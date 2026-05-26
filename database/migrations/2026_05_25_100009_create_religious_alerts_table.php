<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Smart alerts table — التنبيهات الذكية للسياحة الدينية.
 *
 * Populated by scheduled jobs that scan bookings and surface issues:
 *   - passport_expiring : جواز سفر معتمر يقارب الانتهاء
 *   - visa_overdue      : تأشيرة لم تصدر قبل الرحلة
 *   - payment_overdue   : دفعة متأخرة
 *   - profit_low        : ربحية أقل من الحد المعتمد
 *   - trip_imminent     : رحلة قريبة + بيانات ناقصة
 *
 * Acknowledged alerts stay in the table for audit but are hidden from
 * the active alerts widget.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('religious_alerts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('booking_id')->nullable()->constrained('religious_bookings')->cascadeOnDelete();
            $table->foreignUlid('pilgrim_id')->nullable()->constrained('booking_pilgrims')->cascadeOnDelete();

            $table->enum('type', [
                'passport_expiring',
                'visa_overdue',
                'payment_overdue',
                'profit_low',
                'trip_imminent',
                'safa_sync_failed',
                'umrah_portal_failed',
                'other',
            ]);
            $table->enum('severity', ['info', 'warning', 'critical'])->default('warning');
            $table->string('title');
            $table->text('message');
            $table->json('context')->nullable()->comment('بيانات إضافية حسب نوع التنبيه');

            $table->boolean('is_acknowledged')->default(false);
            $table->foreignUlid('acknowledged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('acknowledged_at')->nullable();
            $table->text('resolution_notes')->nullable();

            $table->timestamps();

            $table->index(['type', 'is_acknowledged', 'severity'], 'idx_alert_type_ack_sev');
            $table->index(['booking_id', 'is_acknowledged']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('religious_alerts');
    }
};
