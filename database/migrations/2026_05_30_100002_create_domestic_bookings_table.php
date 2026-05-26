<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Domestic tourism bookings — حجوزات السياحة الداخلية (السجل الرئيسي).
 *
 * Mirrors religious_bookings but drops visa/Safa/Umrah-portal/mutawif
 * fields and adds destination + hotel link. All money in EGP — no
 * exchange rate column needed for domestic-only trips.
 *
 * Money columns:
 *  - selling_price : final price quoted to customer (EGP)
 *  - total_cost    : aggregated from domestic_booking_costs (EGP)
 *  - net_profit    : selling_price - total_cost
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('domestic_bookings', function (Blueprint $table) {
            $table->ulid('id')->primary();

            // Identifiers
            $table->string('booking_number')->unique()->comment('رقم الحجز');
            $table->string('contract_number')->nullable()->index()->comment('رقم العقد');
            $table->string('receipt_number')->nullable()->index()->comment('رقم الإيصال');

            // Relations
            $table->foreignUlid('customer_id')->constrained('customers')->restrictOnDelete();
            $table->foreignUlid('program_id')->nullable()->constrained('domestic_programs')->nullOnDelete();
            $table->foreignUlid('hotel_id')->nullable()->constrained('hotels')->nullOnDelete()->comment('الفندق المختار');
            $table->foreignUlid('responsible_manager_id')->nullable()->constrained('users')->nullOnDelete()->comment('المدير المسؤول');
            $table->foreignUlid('responsible_employee_id')->nullable()->constrained('users')->nullOnDelete()->comment('الموظف المسؤول (البائع)');

            // Trip details
            $table->enum('type', ['hotel_only', 'package', 'day_trip', 'cruise', 'camp', 'event'])
                  ->default('package')->comment('نوع الرحلة');
            $table->string('destination_city')->comment('المدينة');
            $table->string('destination_area')->nullable()->comment('المنطقة');

            $table->date('booking_date')->comment('تاريخ الحجز');
            $table->date('trip_date')->comment('تاريخ السفر / الوصول');
            $table->date('return_date')->nullable()->comment('تاريخ المغادرة');
            $table->unsignedSmallInteger('duration_days')->default(1);
            $table->unsignedSmallInteger('duration_nights')->default(0);

            // Guests summary
            $table->unsignedSmallInteger('adults_count')->default(1);
            $table->unsignedSmallInteger('children_count')->default(0);
            $table->unsignedSmallInteger('infants_count')->default(0);
            $table->json('children_data')->nullable()->comment('[{"name":"...","age":7}, ...]');
            $table->json('guests_data')->nullable()->comment('بيانات الضيوف الكبار اختياري');

            // Trip configuration
            $table->enum('accommodation_type', ['single', 'double', 'triple', 'quad', 'family_room', 'suite'])
                  ->default('double')->comment('نوع الغرفة');
            $table->unsignedSmallInteger('rooms_count')->default(1)->comment('عدد الغرف');
            $table->enum('accommodation_grade', ['economy', '3_stars', '4_stars', '5_stars', 'resort'])
                  ->default('4_stars');
            $table->enum('meal_plan', ['ro', 'bb', 'hb', 'fb', 'ai'])->default('bb');
            $table->enum('transport_type', ['none', 'bus', 'minivan', 'private_car', 'train', 'flight'])
                  ->default('bus');

            // Money (all EGP for domestic)
            $table->decimal('selling_price', 14, 2)->default(0)->comment('سعر البيع الإجمالي EGP');
            $table->decimal('total_cost', 14, 2)->default(0)->comment('إجمالي التكلفة EGP (محسوبة)');
            $table->decimal('net_profit', 14, 2)->default(0)->comment('صافي الربح EGP (محسوب)');

            // Status + workflow (same as religious for unified UX)
            $table->enum('status', ['pending', 'confirmed', 'in_progress', 'completed', 'cancelled'])
                  ->default('pending');
            $table->enum('workflow_stage', ['sales', 'manager_review', 'operations', 'finance', 'closed'])
                  ->default('sales');

            // GL link for the consolidated cost JE on close
            $table->foreignUlid('cost_journal_entry_id')->nullable()
                  ->constrained('journal_entries')->nullOnDelete();

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
            $table->index(['type', 'status', 'trip_date'], 'idx_dom_book_type_status_trip');
            $table->index(['status', 'workflow_stage'], 'idx_dom_book_status_stage');
            $table->index(['trip_date', 'status'], 'idx_dom_book_trip_status');
            $table->index(['destination_city', 'trip_date'], 'idx_dom_book_dest_trip');
            $table->index(['responsible_employee_id', 'created_at'], 'idx_dom_book_emp_created');
            $table->index(['responsible_manager_id', 'created_at'], 'idx_dom_book_mgr_created');
            $table->index('booking_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('domestic_bookings');
    }
};
