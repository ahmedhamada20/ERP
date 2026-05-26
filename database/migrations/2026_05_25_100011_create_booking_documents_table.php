<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Booking documents — مستودع الوثائق لكل حجز.
 *
 * يستوعب وثائق على مستوى الحجز كله (تأمين سفر، تذاكر جماعية...)
 * أو على مستوى معتمر معين (جواز، شهادة تطعيم...).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_documents', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('booking_id')->constrained('religious_bookings')->cascadeOnDelete();
            $table->foreignUlid('pilgrim_id')->nullable()->constrained('booking_pilgrims')->cascadeOnDelete()
                  ->comment('null = وثيقة للحجز كله، set = وثيقة لمعتمر معين');

            $table->enum('category', [
                'passport',       // جواز سفر
                'national_id',    // بطاقة قومية
                'visa',           // تأشيرة
                'vaccination',    // شهادة تطعيم
                'medical',        // تقرير طبي
                'insurance',      // وثيقة تأمين
                'ticket',         // تذكرة
                'contract',       // عقد موقع
                'receipt',        // إيصال
                'photo',          // صورة شخصية
                'mahram',         // وثيقة محرم
                'other',          // أخرى
            ])->index();

            $table->string('title');
            $table->text('description')->nullable();
            $table->string('file_path')->comment('storage/app/public/...');
            $table->string('file_name');
            $table->string('mime_type', 80)->nullable();
            $table->unsignedInteger('file_size_bytes')->nullable();

            $table->date('issue_date')->nullable();
            $table->date('expiry_date')->nullable();

            $table->foreignUlid('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['booking_id', 'category']);
            $table->index(['pilgrim_id', 'category']);
            $table->index('expiry_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_documents');
    }
};
