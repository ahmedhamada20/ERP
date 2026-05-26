<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VisaType extends Model
{
    use HasUlids, SoftDeletes;

    protected $fillable = [
        'code', 'name', 'country', 'type',
        'duration_days', 'multiple_entry', 'processing_days', 'validity_months',
        'base_fee', 'service_fee', 'currency',
        'supplier_name', 'supplier_contact', 'requirements',
        'notes', 'is_active', 'created_by',
    ];

    protected $casts = [
        'duration_days'   => 'integer',
        'processing_days' => 'integer',
        'validity_months' => 'integer',
        'multiple_entry'  => 'boolean',
        'base_fee'        => 'decimal:2',
        'service_fee'     => 'decimal:2',
        'requirements'    => 'array',
        'is_active'       => 'boolean',
    ];

    public const TYPE_LABELS = [
        'tourist'      => 'سياحية',
        'business'     => 'عمل / تجارية',
        'transit'      => 'عبور',
        'work'         => 'إقامة عمل',
        'religious'    => 'دينية',
        'student'      => 'طلابية',
        'medical'      => 'علاج',
        'family_visit' => 'زيارة عائلية',
        'other'        => 'أخرى',
    ];

    protected static function booted(): void
    {
        static::creating(function (VisaType $row) {
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
        $year   = date('Y');
        $prefix = 'VIS-' . $year . '-';
        $startPos = strlen($prefix) + 1;
        $max = (int) (self::withTrashed()
            ->where('code', 'like', $prefix . '%')
            ->selectRaw("MAX(CAST(SUBSTRING(code, {$startPos}) AS UNSIGNED)) as m")
            ->value('m') ?? 0);
        return $prefix . str_pad((string) ($max + 1), 5, '0', STR_PAD_LEFT);
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPE_LABELS[$this->type] ?? $this->type;
    }

    public function getTotalPriceAttribute(): float
    {
        return (float) $this->base_fee + (float) $this->service_fee;
    }

    public function scopeActive($q) { return $q->where('is_active', true); }
}
