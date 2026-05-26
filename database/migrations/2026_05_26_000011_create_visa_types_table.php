<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Visa types catalog — كتالوج أنواع التأشيرات وأسعارها.
 *
 * Used by sales when quoting bookings to pick a visa product.
 * Religious tourism keeps its own visa_type enum on bookings,
 * this is for the wider tourism business (tourist/business/etc.).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visa_types', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('code')->unique()->comment('كود التأشيرة');
            $table->string('name')->comment('الاسم - تأشيرة سياحية الإمارات...');
            $table->string('country')->index()->comment('السعودية، الإمارات، ...');
            $table->enum('type', [
                'tourist', 'business', 'transit', 'work',
                'religious', 'student', 'medical', 'family_visit', 'other',
            ])->default('tourist')->index();

            $table->unsignedSmallInteger('duration_days')->comment('مدة الإقامة بالأيام');
            $table->boolean('multiple_entry')->default(false);
            $table->unsignedSmallInteger('processing_days')->default(7)->comment('أيام الإصدار');
            $table->unsignedSmallInteger('validity_months')->default(3)->comment('صلاحية التأشيرة من الإصدار');

            $table->decimal('base_fee', 12, 2)->default(0)->comment('رسوم القنصلية');
            $table->decimal('service_fee', 12, 2)->default(0)->comment('رسوم الخدمة - ربح المكتب');
            $table->enum('currency', ['EGP', 'SAR', 'USD'])->default('EGP');

            $table->string('supplier_name')->nullable()->comment('المورد/المكتب الموزع');
            $table->string('supplier_contact')->nullable();
            $table->json('requirements')->nullable()->comment('المستندات المطلوبة كـ array');
            $table->text('notes')->nullable();

            $table->boolean('is_active')->default(true)->index();
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['country', 'type']);
            $table->index(['is_active', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visa_types');
    }
};
