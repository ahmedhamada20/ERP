<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class WhatsappMessage extends Model
{
    use HasUlids;

    public const STATUS_LABELS = [
        'queued'    => 'في الانتظار',
        'sent'      => 'مُرسلة',
        'delivered' => 'تم التسليم',
        'read'      => 'تمت القراءة',
        'failed'    => 'فشل',
    ];

    public const STATUS_BADGES = [
        'queued'    => 'secondary',
        'sent'      => 'info',
        'delivered' => 'primary',
        'read'      => 'success',
        'failed'    => 'danger',
    ];

    public const STATUS_ICONS = [
        'queued'    => 'hourglass',
        'sent'      => 'send',
        'delivered' => 'check-all',
        'read'      => 'check-all',
        'failed'    => 'x-octagon',
    ];

    public const TYPE_LABELS = [
        'text'        => 'نص',
        'template'    => 'قالب',
        'image'       => 'صورة',
        'document'    => 'مستند',
        'video'       => 'فيديو',
        'audio'       => 'صوت',
        'interactive' => 'تفاعلية',
    ];

    protected $fillable = [
        'to_phone', 'from_phone', 'direction',
        'message_type', 'template_name', 'template_params',
        'body', 'media_url',
        'status', 'error_code', 'error_message', 'whatsapp_message_id',
        'related_type', 'related_id',
        'sent_at', 'delivered_at', 'read_at', 'failed_at',
        'created_by',
    ];

    protected $casts = [
        'template_params' => 'array',
        'sent_at'         => 'datetime',
        'delivered_at'    => 'datetime',
        'read_at'         => 'datetime',
        'failed_at'       => 'datetime',
    ];

    /** Mirror DB defaults so label accessors don't crash on fresh models. */
    protected $attributes = [
        'direction'    => 'outbound',
        'message_type' => 'text',
        'status'       => 'queued',
    ];

    protected static function booted(): void
    {
        static::creating(function (WhatsappMessage $msg) {
            if (auth()->check() && empty($msg->created_by)) {
                $msg->created_by = auth()->id();
            }
        });
    }

    public function creator() { return $this->belongsTo(User::class, 'created_by'); }

    /** Polymorphic relation to the source (booking/lead/customer/etc). */
    public function related()
    {
        if (! $this->related_type || ! $this->related_id) {
            return null;
        }
        $modelClass = match ($this->related_type) {
            'religious_booking'         => ReligiousBooking::class,
            'domestic_booking'          => DomesticBooking::class,
            'lead'                      => Lead::class,
            'opportunity'               => Opportunity::class,
            'customer'                  => Customer::class,
            'booking_payment'           => BookingPayment::class,
            'domestic_booking_payment'  => DomesticBookingPayment::class,
            'supplier_invoice'          => SupplierInvoice::class,
            'employee_loan'             => EmployeeLoan::class,
            'payslip'                   => Payslip::class,
            'religious_alert'           => ReligiousAlert::class,
            default                     => null,
        };
        return $modelClass ? $modelClass::find($this->related_id) : null;
    }

    public function getStatusLabelAttribute(): string { return self::STATUS_LABELS[$this->status] ?? $this->status; }
    public function getStatusBadgeAttribute(): string { return self::STATUS_BADGES[$this->status] ?? 'secondary'; }
    public function getStatusIconAttribute(): string  { return self::STATUS_ICONS[$this->status] ?? 'circle'; }
    public function getTypeLabelAttribute(): string   { return self::TYPE_LABELS[$this->message_type] ?? $this->message_type; }

    public function scopeOutbound($query) { return $query->where('direction', 'outbound'); }
    public function scopeInbound($query)  { return $query->where('direction', 'inbound'); }
    public function scopeFailed($query)   { return $query->where('status', 'failed'); }
    public function scopePending($query)  { return $query->whereIn('status', ['queued', 'sent']); }
}
