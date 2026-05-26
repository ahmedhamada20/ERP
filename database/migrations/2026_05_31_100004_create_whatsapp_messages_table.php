<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * WhatsApp messages log — كل رسائل واتساب الصادرة والواردة.
 *
 * Powered by Meta WhatsApp Business Cloud API. The `whatsapp_message_id`
 * returned by Meta is stored so delivery/read webhooks can update status
 * later. `related_*` is a polymorphic link to whatever business object
 * triggered the message (booking, payment, lead, customer).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_messages', function (Blueprint $table) {
            $table->ulid('id')->primary();

            // Addressing
            $table->string('to_phone')->index()->comment('رقم المستلم (E.164 format)');
            $table->string('from_phone')->nullable()->comment('رقم الإرسال (phone_number_id)');
            $table->enum('direction', ['outbound', 'inbound'])->default('outbound');

            // Content
            $table->enum('message_type', ['text', 'template', 'image', 'document', 'video', 'audio', 'interactive'])
                  ->default('text');
            $table->string('template_name')->nullable()->comment('اسم القالب المعتمد من Meta');
            $table->json('template_params')->nullable()->comment('متغيرات القالب');
            $table->text('body')->nullable()->comment('نص الرسالة (للنوع text أو نسخة عرض من template)');
            $table->string('media_url')->nullable();

            // Delivery state
            $table->enum('status', ['queued', 'sent', 'delivered', 'read', 'failed'])
                  ->default('queued')->index();
            $table->string('error_code')->nullable();
            $table->text('error_message')->nullable();
            $table->string('whatsapp_message_id')->nullable()->index()->comment('wamid من Meta API');

            // Polymorphic source
            $table->string('related_type', 60)->nullable();
            $table->ulid('related_id')->nullable();

            // Timestamps for delivery events (from webhooks)
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('failed_at')->nullable();

            // Audit
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'created_at'], 'idx_wa_status_created');
            $table->index(['to_phone', 'created_at'], 'idx_wa_phone_created');
            $table->index(['related_type', 'related_id'], 'idx_wa_related');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_messages');
    }
};
