<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * جدول العدّادات الذرية — sequences.
 *
 * يحل مشكلة Race Condition في توليد الأرقام التسلسلية (إيصالات،
 * قيود يومية، فواتير، حجوزات). كل مفتاح (key) يمثل سلسلة منفصلة،
 * مثل: 'receipt:2026', 'JE:2026', 'HJ:2026', 'VR:2026'.
 *
 * الاستخدام: App\Models\Sequence::next($key) داخل DB::transaction
 * مع lockForUpdate تضمن أن طلبين متزامنين لن يحصلا على نفس الرقم.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sequences', function (Blueprint $table) {
            $table->string('key', 64)->primary()->comment('مفتاح السلسلة، مثل receipt:2026');
            $table->unsignedBigInteger('last_number')->default(0)->comment('آخر رقم تم توليده');
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sequences');
    }
};
