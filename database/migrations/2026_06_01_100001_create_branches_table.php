<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Branches (الفروع) — physical company offices.
 *
 * Every transaction (bookings, vouchers, journal entries, etc.) will carry
 * a branch_id so we can produce per-branch P&L reports and isolate workflows.
 *
 * One branch must be marked is_main=true; this is the fallback for legacy
 * records and the default for transactions created without a branch context.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('code')->unique()->comment('كود الفرع (BRN-001, BRN-002, ...)');
            $table->string('name');
            $table->string('name_en')->nullable();

            // Contact
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('manager_name')->nullable()->comment('اسم مدير الفرع');

            // Address
            $table->string('country')->default('مصر');
            $table->string('city')->nullable();
            $table->string('governorate')->nullable();
            $table->text('address')->nullable();

            // Operational flags
            $table->boolean('is_main')->default(false)->index()
                  ->comment('فرع رئيسي — واحد فقط');
            $table->boolean('is_active')->default(true)->index();

            // Business hours (simple JSON: {"sunday": "09:00-17:00", ...})
            $table->json('business_hours')->nullable();

            $table->text('notes')->nullable();
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['is_active', 'is_main'], 'idx_branches_active_main');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
