<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Vouchers (السندات) — receipt + payment vouchers in one table.
 *
 * - receipt (سند قبض): money IN. Debits cash/bank, credits counter (revenue/customer/etc).
 * - payment (سند صرف): money OUT. Credits cash/bank, debits counter (expense/supplier/etc).
 *
 * Each posted voucher has a 1:1 link to an auto-created journal_entry
 * (source_type='voucher', source_id=voucher.id) that's also posted.
 * Cancelling a voucher cancels its journal entry too.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vouchers', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('number')->unique()->comment('VR-YYYY-NNNNNN (receipt) / VP-YYYY-NNNNNN (payment)');
            $table->enum('type', ['receipt', 'payment']);
            $table->date('date');

            // The cash/bank account (always sub_type cash or bank)
            $table->foreignUlid('cash_account_id')->constrained('accounts')->restrictOnDelete();
            // The opposing account (revenue/expense/customer/supplier — anything postable)
            $table->foreignUlid('counter_account_id')->constrained('accounts')->restrictOnDelete();

            // Party (the "from whom" or "to whom") — free-text + optional polymorphic link
            $table->string('party_type', 32)->nullable()->comment('customer, supplier, employee, other');
            $table->ulid('party_id')->nullable();
            $table->string('party_name')->comment('اسم المستلم / الدافع');

            // Money
            $table->enum('currency', ['EGP', 'SAR', 'USD'])->default('EGP');
            $table->decimal('amount', 14, 2);
            $table->decimal('exchange_rate', 12, 4)->default(1);
            $table->decimal('amount_egp', 14, 2);

            $table->text('description');
            $table->string('reference')->nullable()->comment('رقم شيك / تحويل / مرجع خارجي');
            $table->string('attachment')->nullable();

            // Link to the auto-created journal entry
            $table->foreignUlid('journal_entry_id')->nullable()
                  ->constrained('journal_entries')->nullOnDelete();

            // Workflow
            $table->enum('status', ['draft', 'posted', 'cancelled'])->default('draft');
            $table->timestamp('posted_at')->nullable();
            $table->foreignUlid('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignUlid('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('cancellation_reason')->nullable();

            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['type', 'date']);
            $table->index(['type', 'status', 'date']);
            $table->index(['party_type', 'party_id']);
            $table->index('cash_account_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vouchers');
    }
};
