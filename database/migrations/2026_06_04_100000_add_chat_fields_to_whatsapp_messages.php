<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * يحوّل سجل WhatsApp إلى واجهة محادثة (مثل تطبيق واتساب).
 *
 * - contact_phone: رقم الطرف الآخر (counterparty) — مفتاح تجميع المحادثة.
 *     صادر → to_phone، وارد → from_phone. يُفهرس للقائمة الجانبية السريعة.
 * - agent_read_at: متى شاهد الموظف الرسالة الواردة (لعدّاد غير المقروء).
 * - media_*: بيانات الوسائط (صورة/صوت/فيديو/مستند) صادرة وواردة.
 *     media_id هو معرّف Meta المؤقت للوسائط الواردة (يُنزَّل لاحقاً).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_messages', function (Blueprint $table) {
            $table->string('contact_phone')->nullable()->after('from_phone');
            $table->string('media_mime')->nullable()->after('media_url');
            $table->string('media_filename')->nullable()->after('media_mime');
            $table->string('media_id')->nullable()->after('media_filename')
                  ->comment('معرّف الوسائط من Meta (للوارد قبل التنزيل)');
            $table->timestamp('agent_read_at')->nullable()->after('read_at')
                  ->comment('متى قرأ الموظف الرسالة الواردة');

            $table->index(['contact_phone', 'created_at'], 'idx_wa_contact_created');
        });

        // Backfill contact_phone from existing rows so old messages group correctly.
        DB::table('whatsapp_messages')->where('direction', 'outbound')
            ->update(['contact_phone' => DB::raw('to_phone')]);
        DB::table('whatsapp_messages')->where('direction', 'inbound')
            ->update(['contact_phone' => DB::raw('from_phone')]);
    }

    public function down(): void
    {
        Schema::table('whatsapp_messages', function (Blueprint $table) {
            $table->dropIndex('idx_wa_contact_created');
            $table->dropColumn(['contact_phone', 'media_mime', 'media_filename', 'media_id', 'agent_read_at']);
        });
    }
};
