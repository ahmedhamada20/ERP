<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Positions (الوظائف) — job titles with default salary + commission rules.
 *
 * Setting commission_rate here gives every employee in this position a
 * default. Individual employees can override via employees.commission_rate.
 *
 * commission_basis decides what the % applies to:
 *   - selling_price → % of the booking's selling_price
 *   - net_profit    → % of net_profit (selling - cost)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('positions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('code')->unique()->comment('كود الوظيفة (POS-001, ...)');
            $table->string('title')->comment('المسمى الوظيفي');
            $table->string('title_en')->nullable();

            $table->foreignUlid('department_id')->nullable()
                  ->constrained('departments')->nullOnDelete();

            // Salary defaults
            $table->decimal('default_basic_salary', 12, 2)->default(0);
            $table->decimal('default_housing_allowance', 12, 2)->default(0);
            $table->decimal('default_transport_allowance', 12, 2)->default(0);
            $table->decimal('default_other_allowances', 12, 2)->default(0);

            // Commission defaults
            $table->decimal('commission_rate', 6, 2)->default(0)
                  ->comment('% الافتراضي للعمولة من الحجوزات');
            $table->enum('commission_basis', ['selling_price', 'net_profit'])
                  ->default('net_profit')
                  ->comment('selling_price = نسبة من سعر البيع | net_profit = نسبة من صافي الربح');

            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);

            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['department_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('positions');
    }
};
