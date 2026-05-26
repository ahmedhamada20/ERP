<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Timeline of interactions with a lead — calls, WhatsApp messages,
 * emails, meetings, internal notes. Powers the activity feed on the
 * lead's show page and the "follow-up due" dashboard.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_activities', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('lead_id')->constrained('leads')->cascadeOnDelete();

            $table->enum('type', [
                'call', 'whatsapp', 'email', 'sms',
                'meeting', 'visit', 'note', 'status_change',
            ])->index();

            $table->string('subject')->nullable();
            $table->text('body')->nullable();

            $table->enum('outcome', [
                'positive', 'neutral', 'negative', 'no_answer', 'follow_up',
            ])->nullable()->comment('نتيجة المكالمة/الاتصال');

            $table->date('next_action_date')->nullable()->index()->comment('متى المتابعة القادمة');
            $table->boolean('next_action_done')->default(false);

            // Link to a sent WhatsApp message (filled in by Step 4)
            $table->ulid('whatsapp_message_id')->nullable();

            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['lead_id', 'created_at'], 'idx_lead_act_lead_created');
            $table->index(['next_action_date', 'next_action_done'], 'idx_lead_act_followup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_activities');
    }
};
