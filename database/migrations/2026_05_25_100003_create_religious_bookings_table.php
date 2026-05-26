<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Religious tourism bookings — حجوزات الحج والعمرة (السجل الرئيسي).
 *
 * One row per booking. Pilgrims, costs, accommodations, transport,
 * and payments hang off this via foreign keys.
 *
 * Money columns:
 *  - selling_price     : final price quoted to customer (EGP)
 *  - total_cost        : computed from booking_costs aggregated (EGP)
 *  - net_profit        : selling_price - total_cost
 *  - exchange_rate_sar : SAR→EGP rate snapshotted at booking creation,
 *                        ensures historical profit reports stay accurate
 *                        even when rates change later.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('religious_bookings', function (Blueprint $table) {
            $table->ulid('id')->primary();

            // Identifiers
            $table->string('booking_number')->unique()->comment('رقم الحجز');
            $table->string('contract_number')->nullable()->index()->comment('رقم العقد');
            $table->string('receipt_number')->nullable()->index()->comment('رقم الإيصال');

            // Relations
            $table->foreignUlid('customer_id')->constrained('customers')->restrictOnDelete();
            $table->foreignUlid('program_id')->nullable()->constrained('religious_programs')->nullOnDelete();
            $table->foreignUlid('responsible_manager_id')->nullable()->constrained('users')->nullOnDelete()->comment('المدير المسؤول');
            $table->foreignUlid('responsible_employee_id')->nullable()->constrained('users')->nullOnDelete()->comment('الموظف المسؤول (البائع)');

            // Trip details
            $table->enum('type', ['hajj', 'umrah'])->comment('نوع الرحلة');
            $table->date('booking_date')->comment('تاريخ الحجز');
            $table->date('trip_date')->comment('تاريخ السفر');
            $table->date('return_date')->nullable();
            $table->unsignedSmallInteger('duration_days')->comment('المدة');

            // Pilgrims summary (detailed rows in booking_pilgrims)
            $table->unsignedSmallInteger('adults_count')->default(1);
            $table->unsignedSmallInteger('children_count')->default(0);
            $table->unsignedSmallInteger('infants_count')->default(0);
            $table->json('children_data')->nullable()->comment('[{"name":"...","age":7}, ...]');

            // Trip configuration
            $table->enum('visa_type', ['standard', 'haram', 'kaaba'])->default('standard');
            $table->enum('accommodation_type', ['single', 'double', 'triple', 'quad', 'quintuple', 'sextuple'])
                  ->default('quad')->comment('نوع التسكين: 1-6 أفراد بالغرفة');
            $table->enum('meal_plan', ['pp', 'hp'])->default('hp');
            $table->enum('transport_type', ['bus', 'train', 'vip', 'flight'])->default('flight');
            $table->enum('mutawif_grade', ['economy', 'land', '5_stars'])->default('economy');

            // Money
            $table->decimal('selling_price', 14, 2)->default(0)->comment('سعر البيع الإجمالي EGP');
            $table->decimal('total_cost', 14, 2)->default(0)->comment('إجمالي التكلفة EGP (محسوبة)');
            $table->decimal('net_profit', 14, 2)->default(0)->comment('صافي الربح EGP (محسوب)');
            $table->decimal('exchange_rate_sar', 12, 4)->default(0)->comment('SAR→EGP وقت الحجز');

            // Status + workflow
            $table->enum('status', ['pending', 'confirmed', 'in_progress', 'completed', 'cancelled'])
                  ->default('pending');
            $table->enum('workflow_stage', ['sales', 'manager_review', 'operations', 'finance', 'closed'])
                  ->default('sales');

            // Safa integration
            $table->string('safa_barcode')->nullable()->index()->comment('باركود صفا للحجز');
            $table->string('safa_visa_group_number')->nullable()->comment('رقم التأشيرة الجماعي');
            $table->timestamp('safa_synced_at')->nullable();

            // Umrah portal (بوابة العمرة)
            $table->string('umrah_portal_ref')->nullable()->index();
            $table->timestamp('umrah_portal_synced_at')->nullable();

            // Cancellation
            $table->text('cancellation_reason')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignUlid('cancelled_by')->nullable()->constrained('users')->nullOnDelete();

            // Meta
            $table->text('notes')->nullable();
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            // Composite indexes for the most common query patterns
            $table->index(['type', 'status', 'trip_date'], 'idx_book_type_status_trip');
            $table->index(['status', 'workflow_stage'], 'idx_book_status_stage');
            $table->index(['trip_date', 'status'], 'idx_book_trip_status');
            $table->index(['responsible_employee_id', 'created_at'], 'idx_book_emp_created');
            $table->index(['responsible_manager_id', 'created_at'], 'idx_book_mgr_created');
            $table->index('booking_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('religious_bookings');
    }
};
