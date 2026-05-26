<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class BookingTransportation extends Model
{
    use HasUlids, LogsActivity;

    protected $table = 'booking_transportation';

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['booking_id', 'type', 'airline_id', 'transport_provider_id', 'carrier_name', 'departure_at', 'pax_count', 'total_cost_egp'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('booking_transportation');
    }

    protected $fillable = [
        'booking_id', 'type', 'direction', 'segment',
        'airline_id', 'transport_provider_id',
        'carrier_name', 'reference',
        'departure_location', 'arrival_location',
        'departure_at', 'arrival_at',
        'currency', 'cost_per_person', 'pax_count', 'total_cost',
        'exchange_rate', 'total_cost_egp', 'notes',
    ];

    protected $casts = [
        'departure_at'    => 'datetime',
        'arrival_at'      => 'datetime',
        'pax_count'       => 'integer',
        'cost_per_person' => 'decimal:2',
        'total_cost'      => 'decimal:2',
        'exchange_rate'   => 'decimal:4',
        'total_cost_egp'  => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saving(function (BookingTransportation $row) {
            $row->total_cost     = round((float) $row->cost_per_person * (int) $row->pax_count, 2);
            $rate                = $row->currency === 'EGP' ? 1 : (float) $row->exchange_rate;
            $row->total_cost_egp = round((float) $row->total_cost * $rate, 2);
        });
    }

    public function booking()          { return $this->belongsTo(ReligiousBooking::class, 'booking_id'); }
    public function airline()          { return $this->belongsTo(Airline::class); }
    public function transportProvider(){ return $this->belongsTo(TransportProvider::class); }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'flight' => 'طيران',
            'bus'    => 'باص',
            'train'  => 'قطار',
            'vip'    => 'VIP',
            default  => $this->type,
        };
    }
}
