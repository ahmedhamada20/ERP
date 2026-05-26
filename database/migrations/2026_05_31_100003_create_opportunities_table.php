<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Opportunities — الصفقات قيد التفاوض.
 *
 * Created when a lead reaches "qualified" status (manual or auto).
 * Tracks probability + expected value for the sales forecast.
 * Converts polymorphically to either a ReligiousBooking or a DomesticBooking.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('opportunities', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('code')->unique()->comment('كود الصفقة (e.g. OPP-2026-000001)');
            $table->string('title')->comment('عنوان الصفقة');

            // Source — usually from a lead, but can be created standalone
            $table->foreignUlid('lead_id')->nullable()
                  ->constrained('leads')->nullOnDelete();
            $table->foreignUlid('customer_id')->nullable()
                  ->constrained('customers')->nullOnDelete()->comment('بعد إنشاء العميل');

            // Booking specification
            $table->enum('booking_type', ['religious', 'domestic']);
            $table->string('sub_type', 40)->nullable()->comment('hajj/umrah أو package/hotel_only/cruise/...');
            $table->string('destination')->nullable()->comment('المدينة أو الوجهة');
            $table->date('expected_trip_date')->nullable();

            $table->unsignedSmallInteger('pax_count')->default(1);
            $table->decimal('estimated_value', 14, 2)->default(0)->comment('قيمة الصفقة المتوقعة EGP');
            $table->unsignedTinyInteger('probability')->default(50)->comment('احتمال الفوز %');

            // Pipeline stage
            $table->enum('stage', [
                'prospecting', 'qualification', 'proposal',
                'negotiation', 'closed_won', 'closed_lost',
            ])->default('prospecting')->index();

            $table->date('expected_close_date')->nullable();
            $table->date('actual_close_date')->nullable();
            $table->string('lost_reason')->nullable();

            // Conversion link — polymorphic to either religious_bookings or domestic_bookings
            $table->string('converted_booking_type', 40)->nullable()->comment('religious|domestic');
            $table->ulid('converted_booking_id')->nullable();

            $table->foreignUlid('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['stage', 'assigned_to'], 'idx_opp_stage_assigned');
            $table->index(['booking_type', 'stage'], 'idx_opp_type_stage');
            $table->index(['expected_close_date', 'stage'], 'idx_opp_close_stage');
            $table->index(['converted_booking_type', 'converted_booking_id'], 'idx_opp_converted');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('opportunities');
    }
};
