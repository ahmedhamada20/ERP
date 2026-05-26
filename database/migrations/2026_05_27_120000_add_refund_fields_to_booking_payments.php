<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Refund workflow fields for booking_payments.
 *
 * Refunds are no longer just negative payments — they require a reason,
 * optional link to the original payment, and an approval workflow
 * (pending → approved/rejected → paid).
 *
 * Only refunds with refund_status='paid' reduce the booking's paid balance.
 * Pending/approved refunds are "reserved" against the receivable amount
 * to prevent double-spend.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_payments', function (Blueprint $table) {
            $table->text('refund_reason')->nullable()->after('notes');
            $table->foreignUlid('refunded_payment_id')
                  ->nullable()
                  ->after('refund_reason')
                  ->constrained('booking_payments')
                  ->nullOnDelete()
                  ->comment('الدفعة الأصلية اللي بنسترد منها');
            $table->enum('refund_status', ['pending', 'approved', 'rejected', 'paid'])
                  ->nullable()
                  ->after('refunded_payment_id')
                  ->comment('حالة الاسترداد — فقط للدفعات من نوع refund');
            $table->foreignUlid('approved_by')
                  ->nullable()
                  ->after('refund_status')
                  ->constrained('users')
                  ->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->text('approval_notes')->nullable()->after('approved_at');

            $table->index(['booking_id', 'refund_status']);
        });
    }

    public function down(): void
    {
        Schema::table('booking_payments', function (Blueprint $table) {
            $table->dropIndex(['booking_id', 'refund_status']);
            $table->dropForeign(['refunded_payment_id']);
            $table->dropForeign(['approved_by']);
            $table->dropColumn([
                'refund_reason',
                'refunded_payment_id',
                'refund_status',
                'approved_by',
                'approved_at',
                'approval_notes',
            ]);
        });
    }
};
