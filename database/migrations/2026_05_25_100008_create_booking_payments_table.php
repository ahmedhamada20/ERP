<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Payments received from the customer — المدفوعات والإيصالات.
 *
 * One booking may have many payments (deposit, installments, final).
 * Sum(amount) compared against religious_bookings.selling_price tells us
 * outstanding balance.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_payments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('booking_id')->constrained('religious_bookings')->cascadeOnDelete();

            $table->string('receipt_number')->unique()->comment('رقم الإيصال');
            $table->date('payment_date');
            $table->enum('payment_type', ['deposit', 'installment', 'final', 'refund'])->default('installment');

            $table->enum('currency', ['EGP', 'SAR', 'USD'])->default('EGP');
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
            $table->timestamps();

            $table->index(['booking_id', 'payment_date']);
            $table->index('payment_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_payments');
    }
};
