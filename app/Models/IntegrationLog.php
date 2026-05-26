<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class IntegrationLog extends Model
{
    use HasUlids;

    protected $fillable = [
        'provider', 'action', 'status',
        'booking_id', 'triggered_by',
        'request_summary', 'request_payload', 'response_payload',
        'error_message', 'duration_ms',
    ];

    protected $casts = [
        'request_payload'  => 'array',
        'response_payload' => 'array',
        'duration_ms'      => 'integer',
    ];

    public function booking()    { return $this->belongsTo(ReligiousBooking::class, 'booking_id'); }
    public function trigger()    { return $this->belongsTo(User::class, 'triggered_by'); }

    public function getProviderLabelAttribute(): string
    {
        return match ($this->provider) {
            'safa'         => 'صفا',
            'umrah_portal' => 'بوابة العمرة',
            default        => $this->provider,
        };
    }

    public function getActionLabelAttribute(): string
    {
        return match ($this->action) {
            'pull_visa'        => 'سحب تأشيرة',
            'pull_barcode'     => 'سحب باركود',
            'sync_booking'     => 'مزامنة حجز',
            'test_connection'  => 'اختبار اتصال',
            default            => 'أخرى',
        };
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'success' => 'success',
            'failed'  => 'danger',
            'pending' => 'warning',
            default   => 'secondary',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'success' => 'ناجح',
            'failed'  => 'فشل',
            'pending' => 'معلق',
            default   => $this->status,
        };
    }
}
