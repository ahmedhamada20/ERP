<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Journal Lines (سطور القيد).
 *
 * Each line carries a debit OR a credit amount (exactly one is > 0, the
 * other is 0). This is enforced at the model level via boot() and via a
 * CHECK constraint on MySQL where the syntax is supported.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_lines', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('journal_entry_id')->constrained('journal_entries')->cascadeOnDelete();
            $table->foreignUlid('account_id')->constrained('accounts')->restrictOnDelete();

            $table->decimal('debit',  18, 2)->default(0);
            $table->decimal('credit', 18, 2)->default(0);
            $table->string('description')->nullable();
            $table->unsignedSmallInteger('line_number')->default(1);

            $table->timestamps();

            $table->index(['account_id', 'created_at']);
            $table->index(['journal_entry_id', 'line_number']);
        });

        // MySQL CHECK constraint: exactly one of debit/credit is > 0, both >= 0.
        // SQLite supports CHECK too but Laravel doesn't expose it via the builder.
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE journal_lines ADD CONSTRAINT chk_jl_debit_xor_credit
                CHECK (
                    (debit > 0 AND credit = 0)
                    OR (credit > 0 AND debit = 0)
                )');
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE journal_lines DROP CHECK chk_jl_debit_xor_credit');
        }

        Schema::dropIfExists('journal_lines');
    }
};
