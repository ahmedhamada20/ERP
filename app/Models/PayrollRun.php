<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class PayrollRun extends Model
{
    use HasUlids, HasFactory, SoftDeletes, LogsActivity;

    public const STATUS_DRAFT      = 'draft';
    public const STATUS_CALCULATED = 'calculated';
    public const STATUS_APPROVED   = 'approved';
    public const STATUS_POSTED     = 'posted';
    public const STATUS_CANCELLED  = 'cancelled';

    public const STATUS_LABELS = [
        self::STATUS_DRAFT      => 'مسودة',
        self::STATUS_CALCULATED => 'تم الحساب',
        self::STATUS_APPROVED   => 'معتمدة',
        self::STATUS_POSTED     => 'مرحّلة للمحاسبة',
        self::STATUS_CANCELLED  => 'ملغاة',
    ];

    public const STATUS_BADGES = [
        self::STATUS_DRAFT      => 'secondary',
        self::STATUS_CALCULATED => 'info',
        self::STATUS_APPROVED   => 'primary',
        self::STATUS_POSTED     => 'success',
        self::STATUS_CANCELLED  => 'danger',
    ];

    public const MONTH_LABELS = [
        1 => 'يناير', 2 => 'فبراير', 3 => 'مارس', 4 => 'أبريل',
        5 => 'مايو', 6 => 'يونيو', 7 => 'يوليو', 8 => 'أغسطس',
        9 => 'سبتمبر', 10 => 'أكتوبر', 11 => 'نوفمبر', 12 => 'ديسمبر',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['run_code', 'branch_id', 'period_year', 'period_month',
                       'status', 'total_net', 'journal_entry_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $event) => match ($event) {
                'created' => 'تم إنشاء دورة رواتب',
                'updated' => 'تم تعديل دورة رواتب',
                'deleted' => 'تم حذف دورة رواتب',
                default   => $event,
            })
            ->useLogName('payroll_run');
    }

    protected $fillable = [
        'run_code', 'branch_id', 'period_year', 'period_month', 'payment_date',
        'status',
        'employees_count', 'total_earnings', 'total_commissions',
        'total_deductions', 'total_net',
        'journal_entry_id', 'notes',
        'created_by', 'calculated_by', 'calculated_at',
        'approved_by', 'approved_at', 'posted_by', 'posted_at',
    ];

    protected $casts = [
        'period_year'       => 'integer',
        'period_month'      => 'integer',
        'employees_count'   => 'integer',
        'payment_date'      => 'date',
        'total_earnings'    => 'decimal:2',
        'total_commissions' => 'decimal:2',
        'total_deductions'  => 'decimal:2',
        'total_net'         => 'decimal:2',
        'calculated_at'     => 'datetime',
        'approved_at'       => 'datetime',
        'posted_at'         => 'datetime',
    ];

    protected $attributes = [
        'status' => self::STATUS_DRAFT,
    ];

    protected static function booted(): void
    {
        static::creating(function (PayrollRun $run) {
            if (empty($run->run_code)) {
                $run->run_code = self::generateCode($run->period_year, $run->period_month);
            }
            if (auth()->check() && empty($run->created_by)) {
                $run->created_by = auth()->id();
            }
        });
    }

    public static function generateCode(int $year, int $month): string
    {
        $monthStr = str_pad((string) $month, 2, '0', STR_PAD_LEFT);
        $next     = Sequence::next('payroll_run:' . $year . ':' . $monthStr);

        return 'PAY-' . $year . '-' . $monthStr . '-' . str_pad((string) $next, 3, '0', STR_PAD_LEFT);
    }

    // ── Relations ────────────────────────────────────────────────────────
    public function branch()         { return $this->belongsTo(Branch::class); }
    public function payslips()       { return $this->hasMany(Payslip::class); }
    public function journalEntry()   { return $this->belongsTo(JournalEntry::class); }
    public function creator()        { return $this->belongsTo(User::class, 'created_by'); }
    public function calculator()     { return $this->belongsTo(User::class, 'calculated_by'); }
    public function approver()       { return $this->belongsTo(User::class, 'approved_by'); }
    public function poster()         { return $this->belongsTo(User::class, 'posted_by'); }

    // ── Labels ───────────────────────────────────────────────────────────
    public function getStatusLabelAttribute(): string { return self::STATUS_LABELS[$this->status] ?? $this->status; }
    public function getStatusBadgeAttribute(): string { return self::STATUS_BADGES[$this->status] ?? 'secondary'; }

    public function getPeriodLabelAttribute(): string
    {
        return (self::MONTH_LABELS[$this->period_month] ?? $this->period_month) . ' ' . $this->period_year;
    }

    // ── State guards ─────────────────────────────────────────────────────
    public function isDraft(): bool      { return $this->status === self::STATUS_DRAFT; }
    public function isCalculated(): bool { return $this->status === self::STATUS_CALCULATED; }
    public function isApproved(): bool   { return $this->status === self::STATUS_APPROVED; }
    public function isPosted(): bool     { return $this->status === self::STATUS_POSTED; }
    public function isCancelled(): bool  { return $this->status === self::STATUS_CANCELLED; }

    public function canCalculate(): bool { return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_CALCULATED], true); }
    public function canApprove(): bool   { return $this->status === self::STATUS_CALCULATED; }
    public function canPost(): bool      { return $this->status === self::STATUS_APPROVED; }
    public function canCancel(): bool    { return ! in_array($this->status, [self::STATUS_POSTED, self::STATUS_CANCELLED], true); }
    public function canEdit(): bool      { return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_CALCULATED], true); }

    // ── Scopes ───────────────────────────────────────────────────────────
    public function scopeForPeriod($query, int $year, int $month)
    {
        return $query->where('period_year', $year)->where('period_month', $month);
    }

    public function scopeForBranch($query, string $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopePosted($query) { return $query->where('status', self::STATUS_POSTED); }
}
