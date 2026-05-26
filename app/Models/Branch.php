<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Branch extends Model
{
    use HasUlids, HasFactory, SoftDeletes, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['code', 'name', 'city', 'manager_name', 'is_main', 'is_active'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('branch');
    }

    protected $fillable = [
        'code', 'name', 'name_en',
        'phone', 'email', 'manager_name',
        'country', 'city', 'governorate', 'address',
        'is_main', 'is_active', 'business_hours',
        'notes', 'created_by',
    ];

    protected $casts = [
        'is_main'        => 'boolean',
        'is_active'      => 'boolean',
        'business_hours' => 'array',
    ];

    protected $attributes = [
        'country'   => 'مصر',
        'is_active' => true,
        'is_main'   => false,
    ];

    protected static function booted(): void
    {
        static::creating(function (Branch $branch) {
            if (empty($branch->code)) {
                $branch->code = self::generateCode();
            }
            if (auth()->check() && empty($branch->created_by)) {
                $branch->created_by = auth()->id();
            }
        });

        // Ensure only one branch can be is_main=true
        static::saving(function (Branch $branch) {
            if ($branch->is_main && $branch->isDirty('is_main')) {
                self::where('id', '!=', $branch->id ?? '')->where('is_main', true)->update(['is_main' => false]);
            }
        });
    }

    public static function generateCode(): string
    {
        $count = self::withTrashed()->count() + 1;
        return 'BRN-' . str_pad((string) $count, 3, '0', STR_PAD_LEFT);
    }

    /** Returns the main branch, creating a sensible fallback if none flagged. */
    public static function main(): ?self
    {
        return self::where('is_main', true)->first()
            ?? self::where('is_active', true)->orderBy('created_at')->first();
    }

    // ── Relations ─────────────────────────────────────────────────────
    public function employees()   { return $this->hasMany(Employee::class); }
    public function departments() { return $this->hasMany(Department::class); }
    public function creator()     { return $this->belongsTo(User::class, 'created_by'); }

    // ── Scopes ────────────────────────────────────────────────────────
    public function scopeActive($query) { return $query->where('is_active', true); }
}
