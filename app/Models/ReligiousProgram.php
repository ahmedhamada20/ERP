<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ReligiousProgram extends Model
{
    use HasUlids, HasFactory, SoftDeletes, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'code', 'name', 'type', 'season', 'duration_days',
                'base_price_per_person', 'is_active', 'is_published',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $event) => match ($event) {
                'created' => 'تم إنشاء برنامج ديني',
                'updated' => 'تم تعديل برنامج ديني',
                'deleted' => 'تم حذف برنامج ديني',
                default   => $event,
            })
            ->useLogName('religious_program');
    }

    protected $fillable = [
        'code', 'name', 'name_en', 'type', 'season',
        'start_date', 'end_date', 'duration_days',
        'default_visa_type', 'default_accommodation_grade',
        'default_transport_type', 'default_meal_plan', 'default_mutawif_grade',
        'base_price_per_person', 'min_pilgrims', 'max_pilgrims',
        'inclusions', 'exclusions', 'description', 'cover_image',
        'is_active', 'is_published', 'created_by',
    ];

    protected $casts = [
        'start_date'             => 'date',
        'end_date'               => 'date',
        'duration_days'          => 'integer',
        'min_pilgrims'           => 'integer',
        'max_pilgrims'           => 'integer',
        'base_price_per_person'  => 'decimal:2',
        'is_active'              => 'boolean',
        'is_published'           => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (ReligiousProgram $program) {
            if (empty($program->code)) {
                $program->code = self::generateCode($program->type);
            }
            if (auth()->check() && empty($program->created_by)) {
                $program->created_by = auth()->id();
            }
        });
    }

    public static function generateCode(string $type): string
    {
        $year   = date('Y');
        $prefix = $type === 'hajj' ? 'HAJ' : 'UMR';
        $count  = self::withTrashed()->where('type', $type)->whereYear('created_at', $year)->count();
        return $prefix . '-' . $year . '-' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
    }

    public function bookings()
    {
        return $this->hasMany(ReligiousBooking::class, 'program_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function getTypeLabelAttribute(): string
    {
        return $this->type === 'hajj' ? 'حج' : 'عمرة';
    }

    public function getCoverUrlAttribute(): string
    {
        return $this->cover_image
            ? asset('storage/' . $this->cover_image)
            : asset('admin/img/program-placeholder.png');
    }
}
