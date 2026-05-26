<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Airline extends Model
{
    use HasUlids, SoftDeletes;

    protected $fillable = [
        'code', 'airline_name', 'airline_code', 'route',
        'cabin_class', 'aircraft_type',
        'base_price_per_pax', 'currency',
        'departure_time', 'arrival_time', 'flight_duration_minutes',
        'capacity', 'available_seats',
        'contact_phone', 'contact_email',
        'notes', 'is_active', 'created_by',
    ];

    protected $casts = [
        'base_price_per_pax'      => 'decimal:2',
        'capacity'                => 'integer',
        'available_seats'         => 'integer',
        'flight_duration_minutes' => 'integer',
        'is_active'               => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (Airline $row) {
            if (empty($row->code)) {
                $row->code = self::generateCode();
            }
            if (auth()->check() && empty($row->created_by)) {
                $row->created_by = auth()->id();
            }
        });
    }

    public static function generateCode(): string
    {
        $year = date('Y');
        $next = Sequence::next('airline:' . $year);

        return 'AIR-' . $year . '-' . str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }

    // ── Relations ────────────────────────────────────────────────────────
    public function transportation() { return $this->hasMany(BookingTransportation::class); }
    public function creator()        { return $this->belongsTo(User::class, 'created_by'); }

    public function getCabinLabelAttribute(): string
    {
        return match ($this->cabin_class) {
            'economy'  => 'اقتصادي',
            'business' => 'رجال أعمال',
            'first'    => 'أولى',
            default    => $this->cabin_class,
        };
    }

    public function scopeActive($q) { return $q->where('is_active', true); }
}
