<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayslipLine extends Model
{
    use HasUlids, HasFactory;

    public const TYPE_COMMISSION       = 'commission';
    public const TYPE_BONUS            = 'bonus';
    public const TYPE_LOAN_INSTALLMENT = 'loan_installment';
    public const TYPE_ABSENCE          = 'absence';
    public const TYPE_LATENESS         = 'lateness';
    public const TYPE_MANUAL_DEDUCTION = 'manual_deduction';
    public const TYPE_MANUAL_EARNING   = 'manual_earning';

    public const TYPE_LABELS = [
        self::TYPE_COMMISSION       => 'عمولة',
        self::TYPE_BONUS            => 'مكافأة',
        self::TYPE_LOAN_INSTALLMENT => 'قسط سلفة',
        self::TYPE_ABSENCE          => 'غياب',
        self::TYPE_LATENESS         => 'تأخير',
        self::TYPE_MANUAL_DEDUCTION => 'خصم يدوي',
        self::TYPE_MANUAL_EARNING   => 'استحقاق يدوي',
    ];

    public const EARNING_TYPES = [
        self::TYPE_COMMISSION,
        self::TYPE_BONUS,
        self::TYPE_MANUAL_EARNING,
    ];

    public const DEDUCTION_TYPES = [
        self::TYPE_LOAN_INSTALLMENT,
        self::TYPE_ABSENCE,
        self::TYPE_LATENESS,
        self::TYPE_MANUAL_DEDUCTION,
    ];

    protected $fillable = [
        'payslip_id', 'line_type',
        'reference_type', 'reference_id',
        'description', 'amount', 'rate_used', 'base_value',
    ];

    protected $casts = [
        'amount'     => 'decimal:2',
        'rate_used'  => 'decimal:2',
        'base_value' => 'decimal:2',
    ];

    // ── Relations ────────────────────────────────────────────────────────
    public function payslip()   { return $this->belongsTo(Payslip::class); }
    public function reference() { return $this->morphTo(); }

    // ── Helpers ──────────────────────────────────────────────────────────
    public function getTypeLabelAttribute(): string
    {
        return self::TYPE_LABELS[$this->line_type] ?? $this->line_type;
    }

    public function isEarning(): bool   { return in_array($this->line_type, self::EARNING_TYPES, true); }
    public function isDeduction(): bool { return in_array($this->line_type, self::DEDUCTION_TYPES, true); }

    // ── Scopes ───────────────────────────────────────────────────────────
    public function scopeEarnings($query)   { return $query->whereIn('line_type', self::EARNING_TYPES); }
    public function scopeDeductions($query) { return $query->whereIn('line_type', self::DEDUCTION_TYPES); }
    public function scopeOfType($query, string $type) { return $query->where('line_type', $type); }
}
