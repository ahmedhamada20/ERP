<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class BookingAccommodation extends Model
{
    use HasUlids, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['booking_id', 'hotel_id', 'city', 'hotel_name', 'check_in_date', 'check_out_date', 'rooms_count', 'total_cost_sar', 'total_cost_egp'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('booking_accommodation');
    }

    protected $fillable = [
        'booking_id', 'hotel_id', 'city', 'hotel_name', 'hotel_grade', 'hotel_distance_meters',
        'check_in_date', 'check_out_date', 'nights',
        'rooms_count', 'room_type', 'pax_per_room', 'meal_plan',
        'room_price_per_night_sar', 'total_cost_sar',
        'exchange_rate', 'total_cost_egp',
        'confirmation_number', 'notes',
    ];

    protected $casts = [
        'check_in_date'            => 'date',
        'check_out_date'           => 'date',
        'nights'                   => 'integer',
        'rooms_count'              => 'integer',
        'pax_per_room'             => 'integer',
        'room_price_per_night_sar' => 'decimal:2',
        'total_cost_sar'           => 'decimal:2',
        'exchange_rate'            => 'decimal:4',
        'total_cost_egp'           => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saving(function (BookingAccommodation $row) {
            $row->total_cost_sar = round(
                (float) $row->room_price_per_night_sar
                * (int) $row->nights
                * (int) $row->rooms_count, 2
            );
            $row->total_cost_egp = round((float) $row->total_cost_sar * (float) $row->exchange_rate, 2);
        });
    }

    public function booking() { return $this->belongsTo(ReligiousBooking::class, 'booking_id'); }
    public function hotel()   { return $this->belongsTo(Hotel::class); }

    public function getCityLabelAttribute(): string
    {
        return match ($this->city) {
            'mecca'  => 'مكة',
            'medina' => 'المدينة',
            'jeddah' => 'جدة',
            default  => $this->city,
        };
    }
}
