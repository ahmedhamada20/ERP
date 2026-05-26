<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class DomesticProgram extends Model
{
    use HasUlids, HasFactory, SoftDeletes, LogsActivity;

    public const TYPE_LABELS = [
        'hotel_only' => 'إقامة فندقية فقط',
        'package'    => 'باكدج كامل',
        'day_trip'   => 'رحلة يوم واحد',
        'cruise'     => 'رحلة نيلية / بحرية',
        'camp'       => 'مخيم',
        'event'      => 'فعالية (فرح / مؤتمر)',
    ];

    public const MEAL_PLAN_LABELS = [
        'ro' => 'بدون وجبات',
        'bb' => 'إفطار',
        'hb' => 'نصف إقامة',
        'fb' => 'إقامة كاملة',
        'ai' => 'شامل كل شيء',
    ];

    public const TRANSPORT_LABELS = [
        'none'        => 'بدون',
        'bus'         => 'أتوبيس',
        'minivan'     => 'ميكروباص',
        'private_car' => 'سيارة خاصة',
        'train'       => 'قطار',
        'flight'      => 'طيران داخلي',
    ];

    public const GRADE_LABELS = [
        'economy' => 'اقتصادي',
        '3_stars' => '3 نجوم',
        '4_stars' => '4 نجوم',
        '5_stars' => '5 نجوم',
        'resort'  => 'منتجع',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'code', 'name', 'type', 'season', 'destination_city',
                'duration_days', 'base_price_per_person',
                'is_active', 'is_published',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $event) => match ($event) {
                'created' => 'تم إنشاء برنامج داخلي',
                'updated' => 'تم تعديل برنامج داخلي',
                'deleted' => 'تم حذف برنامج داخلي',
                default   => $event,
            })
            ->useLogName('domestic_program');
    }

    protected $fillable = [
        'code', 'name', 'name_en', 'type', 'season',
        'destination_country', 'destination_city', 'destination_area',
        'start_date', 'end_date', 'duration_days', 'duration_nights',
        'default_accommodation_grade', 'default_transport_type', 'default_meal_plan',
        'base_price_per_person', 'min_guests', 'max_guests',
        'inclusions', 'exclusions', 'description', 'cover_image',
        'is_active', 'is_published', 'created_by',
    ];

    protected $casts = [
        'start_date'             => 'date',
        'end_date'               => 'date',
        'duration_days'          => 'integer',
        'duration_nights'        => 'integer',
        'min_guests'             => 'integer',
        'max_guests'             => 'integer',
        'base_price_per_person'  => 'decimal:2',
        'is_active'              => 'boolean',
        'is_published'           => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (DomesticProgram $program) {
            if (empty($program->code)) {
                $program->code = self::generateCode();
            }
            if (auth()->check() && empty($program->created_by)) {
                $program->created_by = auth()->id();
            }
        });
    }

    public static function generateCode(): string
    {
        $year = date('Y');
        $next = Sequence::next('domestic_program:' . $year);

        return 'DOM-' . $year . '-' . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    // ── Relations ─────────────────────────────────────────────────────
    public function bookings() { return $this->hasMany(DomesticBooking::class, 'program_id'); }
    public function creator()  { return $this->belongsTo(User::class, 'created_by'); }

    // ── Scopes ────────────────────────────────────────────────────────
    public function scopeActive($query)    { return $query->where('is_active', true); }
    public function scopePublished($query) { return $query->where('is_published', true); }
    public function scopeOfType($query, string $type) { return $query->where('type', $type); }
    public function scopeInCity($query, string $city) { return $query->where('destination_city', $city); }

    // ── Labels ────────────────────────────────────────────────────────
    public function getTypeLabelAttribute(): string
    {
        return self::TYPE_LABELS[$this->type] ?? $this->type;
    }

    public function getMealPlanLabelAttribute(): string
    {
        return self::MEAL_PLAN_LABELS[$this->default_meal_plan] ?? $this->default_meal_plan;
    }

    public function getTransportLabelAttribute(): string
    {
        return self::TRANSPORT_LABELS[$this->default_transport_type] ?? $this->default_transport_type;
    }

    public function getGradeLabelAttribute(): string
    {
        return self::GRADE_LABELS[$this->default_accommodation_grade] ?? $this->default_accommodation_grade;
    }

    public function getCoverUrlAttribute(): string
    {
        return $this->cover_image
            ? asset('storage/' . $this->cover_image)
            : asset('admin/img/program-placeholder.png');
    }
}
