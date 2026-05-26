<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Suppliers (الموردون) — subsidiary ledger for accounts payable.
 *
 * Each supplier references a parent GL account (2111-2115) based on type.
 * Per-supplier balance is tracked via supplier_invoices + supplier_payments,
 * and the sum across all suppliers of a type matches the GL parent balance.
 *
 * type → GL parent account mapping:
 *   hotel     → 2111 موردين فنادق
 *   airline   → 2112 موردين طيران
 *   transport → 2113 موردين نقل
 *   visa      → 2114 موردين تأشيرات
 *   other     → 2115 موردين متنوعون
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->string('code')->unique()->comment('SUP-YYYY-NNNN');
            $table->string('name')->comment('اسم المورد');
            $table->string('name_en')->nullable();

            $table->enum('type', ['hotel', 'airline', 'transport', 'visa', 'other'])
                  ->comment('يحدد حساب GL الأب الذي يجمع رصيد هذا المورد');

            // Contact
            $table->string('contact_person')->nullable();
            $table->string('phone')->nullable();
            $table->string('mobile')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->nullable()->default('السعودية');

            // Legal/financial
            $table->string('tax_number', 30)->nullable()->comment('الرقم الضريبي');
            $table->string('commercial_register', 30)->nullable()->comment('السجل التجاري');
            $table->enum('currency', ['EGP', 'SAR', 'USD'])->default('EGP');
            $table->decimal('opening_balance', 14, 2)->default(0)->comment('+ دائن (مستحق له) / - مدين (مستحق علينا)');
            $table->date('opening_balance_date')->nullable();
            $table->unsignedSmallInteger('payment_terms_days')->default(30)->comment('عدد أيام السداد المتفق عليها');

            // Status + meta
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->softDeletes();
            $table->timestamps();

            $table->index(['type', 'is_active']);
            $table->index('name');
            $table->index('phone');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
