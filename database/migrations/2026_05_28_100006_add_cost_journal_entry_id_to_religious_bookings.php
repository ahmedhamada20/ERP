<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Link each booking to its consolidated cost-closing journal entry.
 *
 * Unlike payments (one JE per payment), all booking costs collapse into
 * ONE journal entry posted when the booking is closed. This single JE
 * aggregates per (expense_account, supplier_account) pair:
 *   DR Σ costs by expense type   CR Σ costs by supplier type
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('religious_bookings', function (Blueprint $table) {
            $table->foreignUlid('cost_journal_entry_id')->nullable()
                  ->after('cancelled_by')
                  ->constrained('journal_entries')->nullOnDelete()
                  ->comment('قيد إقفال التكاليف عند close');

            $table->index('cost_journal_entry_id');
        });
    }

    public function down(): void
    {
        Schema::table('religious_bookings', function (Blueprint $table) {
            $table->dropIndex(['cost_journal_entry_id']);
            $table->dropForeign(['cost_journal_entry_id']);
            $table->dropColumn('cost_journal_entry_id');
        });
    }
};
