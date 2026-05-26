<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class DomesticBookingCost extends Model
{
    use HasUlids, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['booking_id', 'category', 'supplier_id', 'description', 'currency', 'amount', 'amount_egp', 'is_revenue', 'is_locked'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('domestic_booking_cost');
    }

    public const CATEGORY_LABELS = [
        'hotel'         => 'فندق',
        'room'          => 'غرفة',
        'transport'     => 'نقل',
        'private_car'   => 'سيارة خاصة',
        'flight'        => 'طيران داخلي',
        'meals'         => 'وجبات',
        'activities'    => 'أنشطة',
        'supervision'   => 'إشراف',
        'tax'           => 'ضرائب',
        'activation'    => 'تنشيط',
        'profit'        => 'ربح',
        'gifts'         => 'هدايا',
        'commission'    => 'عمولة',
        'bank_fee'      => 'رسوم بنكية',
        'insurance'     => 'تأمين',
        'miscellaneous' => 'نثريات',
        'other'         => 'أخرى',
    ];

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

    protected static function booted(): void
    {
        static::saving(function (DomesticBookingCost $cost) {
            $rate = $cost->currency === 'EGP' ? 1 : (float) $cost->exchange_rate;
            $cost->amount_egp = round((float) $cost->amount * (int) $cost->quantity * $rate, 2);

            if (auth()->check() && empty($cost->created_by)) {
                $cost->created_by = auth()->id();
            }
        });

        static::saved(fn (DomesticBookingCost $cost)   => optional($cost->booking)->recalculateTotals());
        static::deleted(fn (DomesticBookingCost $cost) => optional($cost->booking)->recalculateTotals());
    }

    public function booking()  { return $this->belongsTo(DomesticBooking::class, 'booking_id'); }
    public function creator()  { return $this->belongsTo(User::class, 'created_by'); }
    public function supplier() { return $this->belongsTo(Supplier::class); }

    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORY_LABELS[$this->category] ?? $this->category;
    }
}
