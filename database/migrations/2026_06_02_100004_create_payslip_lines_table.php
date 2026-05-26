<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Payslip Lines — itemized breakdown showing WHERE the snapshot totals came from.
 *
 * The Payslip row stores aggregate columns (commission_amount = 1500). This
 * table stores the WHY behind that 1500: e.g.
 *   - 750 from booking BK-2026-00123 (3% of 25000)
 *   - 500 from booking BK-2026-00124 (2.5% of 20000)
 *   - 250 from booking BK-2026-00125 (manual override)
 *
 * Same for loan deductions (which loan? which installment number?).
 *
 * Lines are polymorphic via reference_type + reference_id (nullable for
 * lines without a source row, like a manual bonus).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payslip_lines', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->foreignUlid('payslip_id')
                  ->constrained('payslips')->cascadeOnDelete();

            // What kind of line is this? Controls which payslip column it rolls up to.
            $table->enum('line_type', [
                'commission',
                'bonus',
                'loan_installment',
                'absence',
                'lateness',
                'manual_deduction',
                'manual_earning',
            ])->index();

            // Polymorphic source (nullable — manual lines have no source)
            $table->string('reference_type', 64)->nullable();
            $table->ulid('reference_id')->nullable();

            $table->string('description', 255);
            $table->decimal('amount', 12, 2);    // always positive; line_type controls sign

            // For commission lines: capture the rate so the user can audit later
            $table->decimal('rate_used',  6, 2)->nullable();
            $table->decimal('base_value', 14, 2)->nullable()->comment('قيمة الأساس (selling_price أو net_profit)');

            $table->timestamps();

            $table->index(['reference_type', 'reference_id'], 'payslip_lines_morph_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payslip_lines');
    }
};
