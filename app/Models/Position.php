<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Position extends Model
{
    use HasUlids, HasFactory, SoftDeletes;

    public const COMMISSION_BASIS_LABELS = [
        'selling_price' => 'سعر البيع',
        'net_profit'    => 'صافي الربح',
    ];

    protected $fillable = [
        'code', 'title', 'title_en', 'department_id',
        'default_basic_salary', 'default_housing_allowance',
        'default_transport_allowance', 'default_other_allowances',
        'commission_rate', 'commission_basis',
        'description', 'is_active', 'created_by',
    ];

    protected $casts = [
        'default_basic_salary'        => 'decimal:2',
        'default_housing_allowance'   => 'decimal:2',
        'default_transport_allowance' => 'decimal:2',
        'default_other_allowances'    => 'decimal:2',
        'commission_rate'             => 'decimal:2',
        'is_active'                   => 'boolean',
    ];

    protected $attributes = [
        'commission_basis' => 'net_profit',
        'is_active'        => true,
    ];

    protected static function booted(): void
    {
        static::creating(function (Position $pos) {
            if (empty($pos->code)) {
                $pos->code = self::generateCode();
            }
            if (auth()->check() && empty($pos->created_by)) {
                $pos->created_by = auth()->id();
            }
        });
    }

    public static function generateCode(): string
    {
        $count = self::withTrashed()->count() + 1;
        return 'POS-' . str_pad((string) $count, 3, '0', STR_PAD_LEFT);
    }

    public function department() { return $this->belongsTo(Department::class); }
    public function employees()  { return $this->hasMany(Employee::class); }

    public function scopeActive($query) { return $query->where('is_active', true); }

    /** Total of all default allowance + basic — used as a salary-grade ranking. */
    public function getTotalDefaultSalaryAttribute(): float
    {
        return (float) $this->default_basic_salary
             + (float) $this->default_housing_allowance
             + (float) $this->default_transport_allowance
             + (float) $this->default_other_allowances;
    }

    public function getCommissionBasisLabelAttribute(): string
    {
        return self::COMMISSION_BASIS_LABELS[$this->commission_basis] ?? $this->commission_basis;
    }
}
