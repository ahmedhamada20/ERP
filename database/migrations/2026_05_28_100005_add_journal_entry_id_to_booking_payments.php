<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Link each booking_payment to its auto-generated journal_entry.
 *
 * - Normal payments (deposit/installment/final) → JE posted at creation:
 *     DR cash/bank, CR revenue (umrah/hajj)
 * - Refund payments → JE posted ONLY when refund_status='paid':
 *     DR revenue, CR cash/bank (reverses the receipt)
 *
 * Nullable: a payment may exist without a JE if auto-posting failed
 * (chart of accounts not configured, etc). Admin can re-post manually.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_payments', function (Blueprint $table) {
            $table->foreignUlid('journal_entry_id')->nullable()
                  ->after('approval_notes')
                  ->constrained('journal_entries')->nullOnDelete()
                  ->comment('القيد المحاسبي المرتبط');

            $table->index('journal_entry_id');
        });
    }

    public function down(): void
    {
        Schema::table('booking_payments', function (Blueprint $table) {
            $table->dropIndex(['journal_entry_id']);
            $table->dropForeign(['journal_entry_id']);
            $table->dropColumn('journal_entry_id');
        });
    }
};
