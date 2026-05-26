<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Employee documents — صور وعقود الموظفين.
 *
 * Types: contract (عقد), id_card (بطاقة), passport (جواز), driver_license,
 * cv, certificate (شهادة), other. Each row stores the file path plus
 * optional expiry — the alerts engine will surface expiring docs.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_documents', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('employee_id')->constrained('employees')->cascadeOnDelete();

            $table->enum('type', [
                'contract', 'id_card', 'passport', 'driver_license',
                'cv', 'certificate', 'medical', 'other',
            ])->index();

            $table->string('title');
            $table->string('file_path');
            $table->string('file_type', 20)->nullable();
            $table->unsignedInteger('file_size')->nullable()->comment('bytes');

            $table->date('issue_date')->nullable();
            $table->date('expiry_date')->nullable()->index()
                  ->comment('للوثائق اللي بتنتهي زي البطاقة والجواز');

            $table->text('notes')->nullable();
            $table->foreignUlid('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['employee_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_documents');
    }
};
