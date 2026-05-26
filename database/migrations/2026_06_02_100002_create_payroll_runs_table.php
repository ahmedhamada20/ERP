<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Payroll Runs (دورات الرواتب) — one batch per branch per month.
 *
 * Lifecycle:
 *   draft       → just created, no calculation yet
 *   calculated  → payslips generated, awaiting review
 *   approved    → reviewed and locked, ready to post
 *   posted      → journal entry created in GL (journal_entry_id set)
 *   cancelled   → voided before posting (no GL impact)
 *
 * Posted runs MUST NOT be edited — the only reversal path is a manual
 * reversing journal entry. This mirrors how Suppliers/Bookings handle posting.
 *
 * Totals are denormalized snapshots: payslips can be recomputed but the totals
 * on this row are frozen at calculation time. Eliminates SUM() queries on
 * payroll history reports and matches what was approved.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_runs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('run_code', 32)->unique()->comment('PAY-2026-05-001');

            // Scope: a payroll run belongs to ONE branch
            $table->foreignUlid('branch_id')->constrained('branches')->restrictOnDelete();

            // Period (monthly)
            $table->unsignedSmallInteger('period_year');
            $table->unsignedTinyInteger('period_month');     // 1..12

            $table->date('payment_date')->nullable()->comment('تاريخ صرف الرواتب الفعلي');

            // Lifecycle
            $table->enum('status', ['draft', 'calculated', 'approved', 'posted', 'cancelled'])
                  ->default('draft')->index();

            // Snapshots (calculated rollups — recomputed on each calculate() pass)
            $table->unsignedInteger('employees_count')->default(0);
            $table->decimal('total_earnings',    14, 2)->default(0)->comment('إجمالي المستحق');
            $table->decimal('total_commissions', 14, 2)->default(0);
            $table->decimal('total_deductions',  14, 2)->default(0);
            $table->decimal('total_net',         14, 2)->default(0)->comment('صافي الرواتب');

            // GL link (set when posted, null otherwise)
            $table->foreignUlid('journal_entry_id')->nullable()
                  ->constrained('journal_entries')->nullOnDelete();

            $table->text('notes')->nullable();

            // Audit trail
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('calculated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('calculated_at')->nullable();
            $table->foreignUlid('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignUlid('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();

            $table->softDeletes();
            $table->timestamps();

            // One active (non-cancelled) run per branch per month —
            // enforced in app logic since MySQL can't partial-index on enum.
            $table->index(['branch_id', 'period_year', 'period_month'], 'payroll_runs_period_idx');
            $table->index(['status', 'period_year', 'period_month'], 'payroll_runs_status_period_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_runs');
    }
};
