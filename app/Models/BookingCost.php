<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class BookingCost extends Model
{
    use HasUlids, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['booking_id', 'category', 'supplier_id', 'description', 'currency', 'amount', 'amount_egp', 'is_revenue', 'is_locked'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('booking_cost');
    }

    protected $fillable = [
        'booking_id', 'category', 'supplier_id', 'description',
        'currency', 'amount', 'exchange_rate', 'amount_egp',
        'quantity', 'per_unit',
        'is_revenue', 'is_locked',
        'created_by', 'notes',
    ];

    protected $casts = [
        'amount'        => 'decimal:2',
        'exchange_rate' => 'decimal:4',
        'amount_egp'    => 'decimal:2',
        'quantity'      => 'integer',
        'is_revenue'    => 'boolean',
        'is_locked'     => 'boolean',
    ];

    /** Map category → Arabic label (mirrors the brief). */
    public const CATEGORY_LABELS = [
        'visa'          => 'تأشيرة',
        'room'          => 'مصاريف الغرفة',
        'transport'     => 'نقل',
        'flight'        => 'طيران',
        'miscellaneous' => 'نثريات',
        'supervision'   => 'إشراف',
        'tax'           => 'ضرائب',
        'activation'    => 'تنشيط',
        'profit'        => 'ربح',
        'gifts'         => 'هدايا',
        'mutawif'       => 'مطوف',
        'commission'    => 'عمولة',
        'bank_fee'      => 'رسوم بنكية',
        'insurance'     => 'تأمين',
        'other'         => 'أخرى',
    ];

    protected static function booted(): void
    {
        static::saving(function (BookingCost $cost) {
            $rate = $cost->currency === 'EGP' ? 1 : (float) $cost->exchange_rate;
            $cost->amount_egp = round((float) $cost->amount * (int) $cost->quantity * $rate, 2);

            if (auth()->check() && empty($cost->created_by)) {
                $cost->created_by = auth()->id();
            }
        });

        static::saved(fn (BookingCost $cost)   => optional($cost->booking)->recalculateTotals());
        static::deleted(fn (BookingCost $cost) => optional($cost->booking)->recalculateTotals());
    }

    public function booking()  { return $this->belongsTo(ReligiousBooking::class, 'booking_id'); }
    public function creator()  { return $this->belongsTo(User::class, 'created_by'); }
    public function supplier() { return $this->belongsTo(Supplier::class); }

    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORY_LABELS[$this->category] ?? $this->category;
    }
}
