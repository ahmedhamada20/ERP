<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Departments (الأقسام) — functional groupings inside a branch.
 *
 * Common examples: Sales, Operations, Accounting, Customer Service.
 * Departments can be branch-specific or shared across branches via the
 * nullable branch_id (null = global department).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('code')->unique()->comment('كود القسم (DEP-001, ...)');
            $table->string('name');
            $table->string('name_en')->nullable();

            $table->foreignUlid('branch_id')->nullable()
                  ->constrained('branches')->nullOnDelete()
                  ->comment('null = قسم عام لكل الفروع');

            $table->foreignUlid('manager_employee_id')->nullable()
                  ->comment('FK يضاف لاحقاً بعد employees');

            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);

            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['branch_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('departments');
    }
};
