<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class ReligiousAlert extends Model
{
    use HasUlids;

    protected $fillable = [
        'booking_id', 'pilgrim_id',
        'type', 'severity', 'title', 'message', 'context',
        'is_acknowledged', 'acknowledged_by', 'acknowledged_at', 'resolution_notes',
    ];

    protected $casts = [
        'context'         => 'array',
        'is_acknowledged' => 'boolean',
        'acknowledged_at' => 'datetime',
    ];

    public function booking()      { return $this->belongsTo(ReligiousBooking::class, 'booking_id'); }
    public function pilgrim()      { return $this->belongsTo(BookingPilgrim::class, 'pilgrim_id'); }
    public function acknowledger() { return $this->belongsTo(User::class, 'acknowledged_by'); }

    public function scopeActive($query)
    {
        return $query->where('is_acknowledged', false);
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'passport_expiring'   => 'جواز سفر يقارب الانتهاء',
            'visa_overdue'        => 'تأشيرة متأخرة',
            'payment_overdue'     => 'دفعة متأخرة',
            'profit_low'          => 'ربحية منخفضة',
            'trip_imminent'       => 'رحلة وشيكة',
            'safa_sync_failed'    => 'فشل المزامنة مع صفا',
            'umrah_portal_failed' => 'فشل بوابة العمرة',
            default               => $this->type,
        };
    }

    public function getSeverityBadgeAttribute(): string
    {
        return match ($this->severity) {
            'info'     => 'info',
            'warning'  => 'warning',
            'critical' => 'danger',
            default    => 'secondary',
        };
    }
}
