<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class LeadActivity extends Model
{
    use HasUlids;

    public const TYPE_LABELS = [
        'call'          => 'مكالمة',
        'whatsapp'      => 'واتساب',
        'email'         => 'بريد إلكتروني',
        'sms'           => 'رسالة نصية',
        'meeting'       => 'اجتماع',
        'visit'         => 'زيارة',
        'note'          => 'ملاحظة',
        'status_change' => 'تغيير حالة',
    ];

    public const TYPE_ICONS = [
        'call'          => 'telephone',
        'whatsapp'      => 'whatsapp',
        'email'         => 'envelope',
        'sms'           => 'chat-dots',
        'meeting'       => 'people',
        'visit'         => 'door-open',
        'note'          => 'sticky',
        'status_change' => 'arrow-repeat',
    ];

    public const OUTCOME_LABELS = [
        'positive'  => 'إيجابي',
        'neutral'   => 'محايد',
        'negative'  => 'سلبي',
        'no_answer' => 'لم يرد',
        'follow_up' => 'متابعة لاحقة',
    ];

    protected $fillable = [
        'lead_id', 'type', 'subject', 'body', 'outcome',
        'next_action_date', 'next_action_done',
        'whatsapp_message_id', 'created_by',
    ];

    protected $casts = [
        'next_action_date' => 'date',
        'next_action_done' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (LeadActivity $activity) {
            if (auth()->check() && empty($activity->created_by)) {
                $activity->created_by = auth()->id();
            }
        });
    }

    public function lead()           { return $this->belongsTo(Lead::class); }
    public function creator()        { return $this->belongsTo(User::class, 'created_by'); }
    public function whatsappMessage(){ return $this->belongsTo(WhatsappMessage::class); }

    public function getTypeLabelAttribute(): string { return self::TYPE_LABELS[$this->type] ?? $this->type; }
    public function getTypeIconAttribute(): string  { return self::TYPE_ICONS[$this->type] ?? 'circle'; }
    public function getOutcomeLabelAttribute(): ?string { return $this->outcome ? (self::OUTCOME_LABELS[$this->outcome] ?? $this->outcome) : null; }

    public function isFollowUpDue(): bool
    {
        return $this->next_action_date
            && !$this->next_action_done
            && $this->next_action_date->isPast();
    }
}
