<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pilgrims attached to a booking — المعتمرون / الحجاج.
 *
 * Each row represents one person traveling. Either references an existing
 * customer (customer_id) or carries the personal data inline (for accompanying
 * family members who may not be in the customers master file).
 *
 * The per-person safa_barcode field supports the "total barcodes" report
 * (إجمالي الباركودات) listed in the brief.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_pilgrims', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('booking_id')->constrained('religious_bookings')->cascadeOnDelete();
            $table->foreignUlid('customer_id')->nullable()->constrained('customers')->nullOnDelete()
                  ->comment('مرجع لقاعدة العملاء لو موجود');

            // Identity (inline fallback if not in customers table)
            $table->string('full_name');
            $table->string('full_name_en')->nullable();
            $table->string('national_id', 20)->nullable();
            $table->string('passport_number', 30)->nullable()->index();
            $table->date('passport_issue_date')->nullable();
            $table->date('passport_expiry_date')->nullable();
            $table->enum('gender', ['male', 'female'])->default('male');
            $table->date('birth_date')->nullable();
            $table->enum('age_group', ['adult', 'child', 'infant'])->default('adult');
            $table->string('nationality')->default('مصري');
            $table->enum('relationship_to_main', ['self', 'spouse', 'parent', 'child', 'sibling', 'other'])
                  ->default('self')->comment('صلة القرابة بصاحب الحجز');

            // Trip-specific
            $table->string('room_assignment')->nullable()->comment('رقم الغرفة المسكن فيها');
            $table->unsignedTinyInteger('bed_number')->nullable();

            // Safa per-person
            $table->string('safa_barcode')->nullable()->unique();
            $table->string('visa_number')->nullable();
            $table->enum('visa_status', ['pending', 'requested', 'issued', 'rejected', 'cancelled'])
                  ->default('pending');
            $table->date('visa_issued_date')->nullable();
            $table->date('visa_expiry_date')->nullable();

            // Documents
            $table->string('passport_image')->nullable();
            $table->string('photo')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['booking_id', 'visa_status'], 'idx_pilg_book_visa');
            $table->index('national_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_pilgrims');
    }
};
