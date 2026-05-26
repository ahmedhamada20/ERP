<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Hotel extends Model
{
    use HasUlids, SoftDeletes;

    protected $fillable = [
        'code', 'name', 'name_en',
        'city', 'grade', 'distance_meters',
        'address', 'contact_phone', 'contact_email', 'website',
        'base_price_per_night', 'currency',
        'room_types', 'max_occupancy', 'amenities',
        'cover_image', 'notes',
        'is_active', 'created_by',
    ];

    protected $casts = [
        'distance_meters'      => 'integer',
        'max_occupancy'        => 'integer',
        'base_price_per_night' => 'decimal:2',
        'room_types'           => 'array',
        'amenities'            => 'array',
        'is_active'            => 'boolean',
    ];

    public const CITY_LABELS = [
        'mecca'         => 'مكة المكرمة',
        'medina'        => 'المدينة المنورة',
        'jeddah'        => 'جدة',
        'cairo'         => 'القاهرة',
        'dubai'         => 'دبي',
        'istanbul'      => 'إسطنبول',
        'kuala_lumpur'  => 'كوالالمبور',
        'other'         => 'أخرى',
    ];

    public const GRADE_LABELS = [
        'economy'  => 'اقتصادي',
        '3_stars'  => '3 نجوم',
        '4_stars'  => '4 نجوم',
        '5_stars'  => '5 نجوم',
        'luxury'   => 'فاخر',
    ];

    protected static function booted(): void
    {
        static::creating(function (Hotel $row) {
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
        $next = Sequence::next('hotel:' . $year);

        return 'HTL-' . $year . '-' . str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }

    // ── Relations ────────────────────────────────────────────────────────
    public function accommodations() { return $this->hasMany(BookingAccommodation::class); }
    public function creator()        { return $this->belongsTo(User::class, 'created_by'); }

    public function getCityLabelAttribute(): string
    {
        return self::CITY_LABELS[$this->city] ?? $this->city;
    }

    public function getGradeLabelAttribute(): string
    {
        return self::GRADE_LABELS[$this->grade] ?? $this->grade;
    }

    public function getGradeStarsAttribute(): string
    {
        return match ($this->grade) {
            'economy'  => '⭐',
            '3_stars'  => '⭐⭐⭐',
            '4_stars'  => '⭐⭐⭐⭐',
            '5_stars'  => '⭐⭐⭐⭐⭐',
            'luxury'   => '👑',
            default    => '',
        };
    }

    public function scopeActive($q) { return $q->where('is_active', true); }
}
