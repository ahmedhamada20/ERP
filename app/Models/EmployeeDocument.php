<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class EmployeeDocument extends Model
{
    use HasUlids;

    public const TYPE_LABELS = [
        'contract'       => 'عقد العمل',
        'id_card'        => 'بطاقة شخصية',
        'passport'       => 'جواز سفر',
        'driver_license' => 'رخصة قيادة',
        'cv'             => 'سيرة ذاتية',
        'certificate'    => 'شهادة',
        'medical'        => 'كشف طبي',
        'other'          => 'أخرى',
    ];

    public const TYPE_ICONS = [
        'contract'       => 'file-earmark-text',
        'id_card'        => 'person-vcard',
        'passport'       => 'passport',
        'driver_license' => 'car-front',
        'cv'             => 'file-person',
        'certificate'    => 'award',
        'medical'        => 'heart-pulse',
        'other'          => 'file-earmark',
    ];

    protected $fillable = [
        'employee_id', 'type', 'title',
        'file_path', 'file_type', 'file_size',
        'issue_date', 'expiry_date',
        'notes', 'uploaded_by',
    ];

    protected $casts = [
        'issue_date'  => 'date',
        'expiry_date' => 'date',
        'file_size'   => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (EmployeeDocument $doc) {
            if (auth()->check() && empty($doc->uploaded_by)) {
                $doc->uploaded_by = auth()->id();
            }
        });
    }

    public function employee() { return $this->belongsTo(Employee::class); }
    public function uploader() { return $this->belongsTo(User::class, 'uploaded_by'); }

    public function getTypeLabelAttribute(): string { return self::TYPE_LABELS[$this->type] ?? $this->type; }
    public function getTypeIconAttribute(): string  { return self::TYPE_ICONS[$this->type] ?? 'file'; }
    public function getFileUrlAttribute(): string   { return asset('storage/' . $this->file_path); }

    public function isExpired(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    public function isExpiringSoon(int $days = 30): bool
    {
        if (! $this->expiry_date) return false;
        return $this->expiry_date->between(now(), now()->addDays($days));
    }

    public function scopeExpiringSoon($query, int $days = 30)
    {
        return $query->whereNotNull('expiry_date')
            ->whereBetween('expiry_date', [now(), now()->addDays($days)]);
    }

    public function scopeExpired($query)
    {
        return $query->whereNotNull('expiry_date')->where('expiry_date', '<', now());
    }
}
