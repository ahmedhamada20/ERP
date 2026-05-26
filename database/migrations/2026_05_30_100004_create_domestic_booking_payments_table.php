<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Customer payments for domestic bookings — مدفوعات السياحة الداخلية.
 *
 * One booking may have many payments (deposit, installments, final).
 * Refund workflow built-in from the start (pending → approved/rejected →
 * paid). Only refunds with refund_status='paid' reduce the paid balance.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('domestic_booking_payments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('booking_id')->constrained('domestic_bookings')->cascadeOnDelete();

            $table->string('receipt_number')->unique()->comment('رقم الإيصال');
            $table->date('payment_date');
            $table->enum('payment_type', ['deposit', 'installment', 'final', 'refund'])->default('installment');

            $table->enum('currency', ['EGP', 'USD', 'EUR'])->default('EGP');
            $table->decimal('amount', 14, 2);
            $table->decimal('exchange_rate', 12, 4)->default(1);
            $table->decimal('amount_egp', 14, 2)->comment('المبلغ بعد التحويل للجنيه');

            $table->enum('method', ['cash', 'bank_transfer', 'credit_card', 'cheque', 'instapay', 'vodafone_cash'])
                  ->default('cash');
            $table->string('bank_name')->nullable();
            $table->string('transaction_reference')->nullable();
            $table->string('cheque_number')->nullable();
            $table->date('cheque_due_date')->nullable();

            $table->foreignUlid('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->string('attachment')->nullable()->comment('صورة الإيصال');

            // Refund workflow
            $table->text('refund_reason')->nullable();
            $table->foreignUlid('refunded_payment_id')->nullable()
                  ->constrained('domestic_booking_payments')->nullOnDelete()
                  ->comment('الدفعة الأصلية اللي بنسترد منها');
            $table->enum('refund_status', ['pending', 'approved', 'rejected', 'paid'])
                  ->nullable()->comment('حالة الاسترداد — فقط للدفعات من نوع refund');
            $table->foreignUlid('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_notes')->nullable();

            // GL link
            $table->foreignUlid('journal_entry_id')->nullable()
                  ->constrained('journal_entries')->nullOnDelete();

            $table->timestamps();

            $table->index(['booking_id', 'payment_date']);
            $table->index(['booking_id', 'refund_status']);
            $table->index('payment_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('domestic_booking_payments');
    }
};
