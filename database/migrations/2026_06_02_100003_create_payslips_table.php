<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Payslips (قسائم الرواتب) — one row per employee per payroll run.
 *
 * Every monetary field is a SNAPSHOT at calculation time. We don't read
 * `employees.basic_salary` at print time: the moment the run is calculated,
 * the values are copied here and frozen. This is critical because:
 *   - Employee salary changes mid-month shouldn't retroactively alter
 *     last month's payslip
 *   - Bank/IBAN snapshots ensure a reprinted payslip shows the bank the
 *     money actually went to
 *
 * Earnings / Deductions / Net are stored explicitly (not computed) so DB
 * sums match what was actually paid even if calculation logic later changes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payslips', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->foreignUlid('payroll_run_id')
                  ->constrained('payroll_runs')->cascadeOnDelete();

            $table->foreignUlid('employee_id')
                  ->constrained('employees')->restrictOnDelete();

            // Denormalized from employee at calculation time (for fast filtering)
            $table->foreignUlid('branch_id')->nullable()
                  ->constrained('branches')->nullOnDelete();

            // ── Earnings (snapshot) ──────────────────────────────────────
            $table->decimal('basic_salary',         12, 2)->default(0);
            $table->decimal('housing_allowance',    12, 2)->default(0);
            $table->decimal('transport_allowance',  12, 2)->default(0);
            $table->decimal('other_allowances',     12, 2)->default(0);
            $table->decimal('commission_amount',    12, 2)->default(0);
            $table->decimal('bonus',                12, 2)->default(0);
            $table->decimal('total_earnings',       14, 2)->default(0);

            // ── Deductions (snapshot) ────────────────────────────────────
            $table->decimal('social_insurance',     12, 2)->default(0);
            $table->decimal('income_tax',           12, 2)->default(0);
            $table->decimal('loan_deduction',       12, 2)->default(0);
            $table->decimal('absence_deduction',    12, 2)->default(0);
            $table->decimal('lateness_deduction',   12, 2)->default(0);
            $table->decimal('other_deductions',     12, 2)->default(0);
            $table->decimal('total_deductions',     14, 2)->default(0);

            // ── Result ───────────────────────────────────────────────────
            $table->decimal('net_pay',              14, 2)->default(0);

            // Attendance counters (filled manually in 5.1, automated later)
            $table->unsignedTinyInteger('absent_days')->default(0);
            $table->unsignedSmallInteger('lateness_minutes')->default(0);
            $table->unsignedTinyInteger('working_days')->default(30)
                  ->comment('عدد أيام العمل المعتمدة في الشهر');

            // Payment snapshot
            $table->enum('payment_method', ['cash', 'bank_transfer', 'cheque'])
                  ->default('bank_transfer');
            $table->string('bank_name')->nullable();
            $table->string('bank_account')->nullable();
            $table->string('iban')->nullable();

            $table->timestamp('paid_at')->nullable()->comment('وقت الصرف الفعلي');
            $table->text('notes')->nullable();

            $table->timestamps();

            // One payslip per (run, employee). Sprint 6 contract.
            $table->unique(['payroll_run_id', 'employee_id'], 'payslips_run_emp_uniq');

            // Queries: "all payslips for employee X" and "payslips by branch in run Y"
            $table->index(['employee_id', 'created_at'], 'payslips_emp_created_idx');
            $table->index(['branch_id', 'payroll_run_id'], 'payslips_branch_run_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payslips');
    }
};
