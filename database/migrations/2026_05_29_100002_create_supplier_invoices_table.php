<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Supplier Invoices (فواتير الموردين) — accounts payable subsidiary ledger.
 *
 * When posted, generates a balanced journal entry:
 *   DR expense_account           (pre-tax amount)
 *   DR VAT payable (2131)        (tax_amount, if > 0)
 *   CR supplier parent (2111-5)  (total = amount + tax)
 *
 * Money is denominated in the invoice's `currency`; `amount_egp` is the
 * EGP-converted total used for the journal lines.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_invoices', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('number')->unique()->comment('SI-YYYY-NNNNNN');

            $table->foreignUlid('supplier_id')->constrained('suppliers')->restrictOnDelete();
            $table->foreignUlid('expense_account_id')->constrained('accounts')->restrictOnDelete()
                  ->comment('الحساب المدين (تكلفة فنادق، طيران، مصروف عام...)');

            $table->date('invoice_date');
            $table->date('due_date')->nullable();
            $table->string('supplier_reference')->nullable()->comment('رقم فاتورة المورد');
            $table->text('description');

            // Money
            $table->enum('currency', ['EGP', 'SAR', 'USD'])->default('EGP');
            $table->decimal('amount', 14, 2)->comment('قبل الضريبة');
            $table->decimal('tax_amount', 14, 2)->default(0)->comment('قيمة الضريبة');
            $table->decimal('exchange_rate', 12, 4)->default(1);
            $table->decimal('amount_egp', 14, 2)->comment('الإجمالي بالجنيه (amount + tax) × rate');

            $table->string('attachment')->nullable()->comment('صورة الفاتورة');

            // Workflow
            $table->foreignUlid('journal_entry_id')->nullable()
                  ->constrained('journal_entries')->nullOnDelete();
            $table->enum('status', ['draft', 'posted', 'cancelled'])->default('draft');
            $table->timestamp('posted_at')->nullable();
            $table->foreignUlid('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignUlid('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('cancellation_reason')->nullable();

            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // Hot paths
            $table->index(['supplier_id', 'invoice_date']);   // supplier statement
            $table->index(['status', 'due_date']);            // aging / outstanding
            $table->index(['status', 'invoice_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_invoices');
    }
};
