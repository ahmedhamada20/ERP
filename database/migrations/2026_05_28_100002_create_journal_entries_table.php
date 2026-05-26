<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Journal Entries (القيود اليومية) — the header table.
 *
 * Each entry represents one accounting transaction with N >= 2 balanced lines
 * (total debit == total credit). Lines live in `journal_lines`.
 *
 * Lifecycle:
 *   draft     → editable, doesn't affect ledger balances
 *   posted    → locked, included in trial balance / GL / reports
 *   cancelled → reversed (the lines stay but are excluded from balances)
 *
 * Source linkage:
 *   source_type + source_id together identify the originating record
 *   (e.g. source_type='booking_payment', source_id=<payment.id>).
 *   This lets us trace any journal entry back to its business source.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('number')->unique()->comment('JE-YYYY-NNNNNN');
            $table->date('date')->comment('تاريخ القيد المحاسبي');
            $table->text('description')->comment('بيان القيد');
            $table->string('reference')->nullable()->index()->comment('مرجع خارجي (رقم حجز، فاتورة...)');

            // Polymorphic-ish link to the originating record
            $table->string('source_type', 64)->default('manual')
                  ->comment('manual, booking_payment, booking_cost, voucher, opening_balance');
            $table->ulid('source_id')->nullable();

            $table->decimal('total_debit',  18, 2)->default(0);
            $table->decimal('total_credit', 18, 2)->default(0);

            $table->enum('status', ['draft', 'posted', 'cancelled'])->default('draft');
            $table->timestamp('posted_at')->nullable();
            $table->foreignUlid('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignUlid('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('cancellation_reason')->nullable();

            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['date', 'status']);
            $table->index(['status', 'date']);
            $table->index(['source_type', 'source_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_entries');
    }
};
