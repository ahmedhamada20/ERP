<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TransportProvider extends Model
{
    use HasUlids, SoftDeletes;

    protected $fillable = [
        'code', 'name', 'type', 'country',
        'vehicle_count', 'capacity_per_vehicle',
        'base_price_per_pax', 'base_price_per_vehicle', 'currency',
        'routes',
        'contact_phone', 'contact_email', 'contact_person',
        'notes', 'is_active', 'created_by',
    ];

    protected $casts = [
        'vehicle_count'          => 'integer',
        'capacity_per_vehicle'   => 'integer',
        'base_price_per_pax'     => 'decimal:2',
        'base_price_per_vehicle' => 'decimal:2',
        'routes'                 => 'array',
        'is_active'              => 'boolean',
    ];

    public const TYPE_LABELS = [
        'bus'        => 'باص',
        'train'      => 'قطار',
        'vip'        => 'VIP',
        'limousine'  => 'ليموزين',
        'minivan'    => 'فان صغير',
    ];

    public const COUNTRY_LABELS = [
        'SA'    => 'السعودية',
        'EG'    => 'مصر',
        'AE'    => 'الإمارات',
        'TR'    => 'تركيا',
        'other' => 'أخرى',
    ];

    protected static function booted(): void
    {
        static::creating(function (TransportProvider $row) {
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
        $next = Sequence::next('transport_provider:' . $year);

        return 'TRP-' . $year . '-' . str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }

    // ── Relations ────────────────────────────────────────────────────────
    public function transportation() { return $this->hasMany(BookingTransportation::class); }
    public function creator()        { return $this->belongsTo(User::class, 'created_by'); }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPE_LABELS[$this->type] ?? $this->type;
    }

    public function getCountryLabelAttribute(): string
    {
        return self::COUNTRY_LABELS[$this->country] ?? $this->country;
    }

    public function getTotalCapacityAttribute(): int
    {
        return (int) $this->vehicle_count * (int) $this->capacity_per_vehicle;
    }

    public function scopeActive($q) { return $q->where('is_active', true); }
}
