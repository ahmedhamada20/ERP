<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sales leads — العملاء المحتملون.
 *
 * Top of the funnel: contact info + interest signal. Each lead may
 * convert into an Opportunity (deal in progress), which itself converts
 * into a Booking (religious or domestic). Activities log every touchpoint.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('code')->unique()->comment('كود الـ lead (e.g. LEAD-2026-000001)');

            // Contact info
            $table->string('full_name');
            $table->string('phone')->index();
            $table->string('whatsapp')->nullable()->index();
            $table->string('email')->nullable();
            $table->string('city')->nullable();

            // Funnel data
            $table->enum('source', [
                'facebook', 'instagram', 'whatsapp', 'website',
                'walk_in', 'referral', 'phone', 'tiktok', 'other',
            ])->default('other')->comment('مصدر الـ lead');

            $table->enum('status', [
                'new', 'contacted', 'qualified', 'proposal', 'won', 'lost',
            ])->default('new')->index()->comment('حالة الـ lead');

            $table->enum('interest_type', [
                'hajj', 'umrah', 'domestic', 'international', 'other',
            ])->default('other')->comment('نوع الاهتمام');

            $table->foreignUlid('assigned_to')->nullable()
                  ->constrained('users')->nullOnDelete()->comment('الموظف المسؤول');

            $table->decimal('estimated_value', 14, 2)->default(0)->comment('قيمة الصفقة المتوقعة EGP');
            $table->date('expected_close_date')->nullable();

            // Lost tracking
            $table->string('lost_reason')->nullable()->comment('سبب الخسارة لو status=lost');
            $table->timestamp('lost_at')->nullable();

            // Conversion tracking
            $table->foreignUlid('converted_to_customer_id')->nullable()
                  ->constrained('customers')->nullOnDelete()->comment('بعد التحويل لعميل');
            $table->timestamp('converted_at')->nullable();

            $table->text('notes')->nullable();
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            // Composite indexes for common queries
            $table->index(['status', 'assigned_to'], 'idx_leads_status_assigned');
            $table->index(['source', 'status'], 'idx_leads_source_status');
            $table->index(['interest_type', 'status'], 'idx_leads_interest_status');
            $table->index(['assigned_to', 'created_at'], 'idx_leads_assigned_created');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
