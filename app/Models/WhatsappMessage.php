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
        'to_phone', 'from_phone', 'contact_phone', 'direction',
        'message_type', 'template_name', 'template_params',
        'body', 'media_url', 'media_mime', 'media_filename', 'media_id',
        'status', 'error_code', 'error_message', 'whatsapp_message_id',
        'related_type', 'related_id',
        'sent_at', 'delivered_at', 'read_at', 'failed_at', 'agent_read_at',
        'created_by',
    ];

    protected $casts = [
        'template_params' => 'array',
        'sent_at'         => 'datetime',
        'delivered_at'    => 'datetime',
        'read_at'         => 'datetime',
        'failed_at'       => 'datetime',
        'agent_read_at'   => 'datetime',
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
            // Group key for the chat view: the OTHER party's number.
            if (empty($msg->contact_phone)) {
                $msg->contact_phone = $msg->direction === 'inbound'
                    ? $msg->from_phone
                    : $msg->to_phone;
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

    /** Messages exchanged with one phone number, oldest first (a conversation). */
    public function scopeConversation($query, string $phone)
    {
        return $query->where('contact_phone', $phone)->orderBy('created_at');
    }

    /** True if this message carries downloadable/displayable media. */
    public function hasMedia(): bool
    {
        return in_array($this->message_type, ['image', 'audio', 'video', 'document'], true)
            && ! empty($this->media_url);
    }

    /**
     * Meta's 24h Customer Service Window: free-form text/media may only be
     * sent within 24h of the customer's last INBOUND message. Outside it you
     * must use an approved template. Returns true if the window is open.
     */
    public static function windowOpenFor(string $phone): bool
    {
        $lastInbound = static::where('contact_phone', $phone)
            ->where('direction', 'inbound')
            ->latest('created_at')
            ->value('created_at');

        return $lastInbound !== null && $lastInbound->gt(now()->subDay());
    }
}
