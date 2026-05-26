<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Employees (الموظفين) — staff records, separate from users.
 *
 * Design decision (Sprint 6): a separate table. Why not just extend users?
 *   - Some employees never log into the system (drivers, cleaners)
 *   - Some users aren't employees (admins, integrations)
 *   - HR data (salary, contract) is sensitive — separating it lets us
 *     keep the auth-facing users table light
 *
 * The optional `user_id` links the employee to a login account when one exists.
 *
 * Salary fields here are the EMPLOYEE-LEVEL overrides; if any column is 0/null
 * the payroll engine falls back to the position's defaults. This way HR can
 * raise an individual's salary without changing the whole position.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('code')->unique()->comment('كود الموظف (EMP-2026-00001)');

            // Optional link to a login user
            $table->foreignUlid('user_id')->nullable()
                  ->unique()
                  ->constrained('users')->nullOnDelete();

            // Identity
            $table->string('full_name');
            $table->string('full_name_en')->nullable();
            $table->string('national_id', 20)->nullable()->unique();
            $table->string('passport_number', 30)->nullable();
            $table->date('birth_date')->nullable();
            $table->enum('gender', ['male', 'female'])->nullable();
            $table->enum('marital_status', ['single', 'married', 'divorced', 'widowed'])->nullable();
            $table->string('nationality')->default('مصري');
            $table->string('religion')->nullable();

            // Contact
            $table->string('phone')->index();
            $table->string('whatsapp')->nullable();
            $table->string('email')->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();

            // Organizational
            $table->foreignUlid('branch_id')->nullable()
                  ->constrained('branches')->nullOnDelete();
            $table->foreignUlid('department_id')->nullable()
                  ->constrained('departments')->nullOnDelete();
            $table->foreignUlid('position_id')->nullable()
                  ->constrained('positions')->nullOnDelete();
            $table->foreignUlid('reports_to')->nullable()
                  ->comment('FK to employees.id — added after table creation to avoid self-FK issues');

            // Employment
            $table->date('hire_date');
            $table->date('termination_date')->nullable();
            $table->enum('employment_type', ['full_time', 'part_time', 'contract', 'intern'])
                  ->default('full_time');
            $table->enum('status', ['active', 'on_leave', 'terminated', 'suspended'])
                  ->default('active')->index();

            // Salary (overrides position defaults — 0 = use position default)
            $table->decimal('basic_salary', 12, 2)->default(0);
            $table->decimal('housing_allowance', 12, 2)->default(0);
            $table->decimal('transport_allowance', 12, 2)->default(0);
            $table->decimal('other_allowances', 12, 2)->default(0);

            // Commission override (NULL = use position default)
            $table->decimal('commission_rate', 6, 2)->nullable()
                  ->comment('NULL = استخدم نسبة الوظيفة');
            $table->enum('commission_basis', ['selling_price', 'net_profit'])->nullable();

            // Payment
            $table->enum('payment_method', ['cash', 'bank_transfer', 'cheque'])
                  ->default('bank_transfer');
            $table->string('bank_name')->nullable();
            $table->string('bank_account')->nullable();
            $table->string('iban')->nullable();

            // Documents shortcut
            $table->string('photo')->nullable();
            $table->string('id_image')->nullable();

            $table->text('notes')->nullable();
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['branch_id', 'status']);
            $table->index(['department_id', 'status']);
            $table->index(['status', 'hire_date']);
        });

        // Self-FK for reports_to (manager hierarchy)
        Schema::table('employees', function (Blueprint $table) {
            $table->foreign('reports_to')->references('id')->on('employees')->nullOnDelete();
        });

        // Now we can add the manager_employee_id FK on departments
        Schema::table('departments', function (Blueprint $table) {
            $table->foreign('manager_employee_id')->references('id')->on('employees')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropForeign(['manager_employee_id']);
        });
        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['reports_to']);
        });
        Schema::dropIfExists('employees');
    }
};
