<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Employee Loans (سلف الموظفين) — interest-free installment advances
 * deducted from monthly payroll.
 *
 * Lifecycle:
 *   active     — installments still being deducted
 *   completed  — paid_amount >= amount, no more deductions
 *   cancelled  — written off / forgiven before completion
 *
 * The payroll engine queries `active` loans for the employee being processed
 * and creates a payslip_lines row (line_type=loan_installment, amount=
 * monthly_deduction). After successful posting, `paid_amount` is bumped and
 * status flips to `completed` if fully paid.
 *
 * paid_amount + remaining_amount are denormalized so we don't have to sum
 * payslip_lines on every report query. They MUST be kept in sync with the
 * deductions actually posted (PayrollService is the only writer).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_loans', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('loan_code', 32)->unique()->comment('LOAN-2026-00001');

            $table->foreignUlid('employee_id')
                  ->constrained('employees')->restrictOnDelete();

            $table->decimal('amount',             12, 2)->comment('مبلغ السلفة الإجمالي');
            $table->unsignedTinyInteger('installments')->comment('عدد الأقساط');
            $table->decimal('monthly_deduction',  12, 2)->comment('قسط شهري ثابت');

            $table->decimal('paid_amount',        12, 2)->default(0);
            $table->decimal('remaining_amount',   12, 2)->default(0);

            $table->date('start_date')->comment('أول شهر يبدأ فيه الخصم');

            $table->enum('status', ['active', 'completed', 'cancelled'])
                  ->default('active')->index();

            $table->text('reason')->nullable();
            $table->text('notes')->nullable();

            $table->foreignUlid('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->softDeletes();
            $table->timestamps();

            // Query: "active loans for employee X" — used by payroll engine each run
            $table->index(['employee_id', 'status'], 'loans_employee_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_loans');
    }
};
