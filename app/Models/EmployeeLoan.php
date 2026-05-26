<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class EmployeeLoan extends Model
{
    use HasUlids, HasFactory, SoftDeletes, LogsActivity;

    public const STATUS_ACTIVE    = 'active';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_LABELS = [
        self::STATUS_ACTIVE    => 'نشطة',
        self::STATUS_COMPLETED => 'مسددة',
        self::STATUS_CANCELLED => 'ملغاة',
    ];

    public const STATUS_BADGES = [
        self::STATUS_ACTIVE    => 'warning',
        self::STATUS_COMPLETED => 'success',
        self::STATUS_CANCELLED => 'secondary',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['loan_code', 'employee_id', 'amount', 'monthly_deduction',
                       'paid_amount', 'status'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $event) => match ($event) {
                'created' => 'تم إنشاء سلفة',
                'updated' => 'تم تعديل سلفة',
                'deleted' => 'تم حذف سلفة',
                default   => $event,
            })
            ->useLogName('employee_loan');
    }

    protected $fillable = [
        'loan_code', 'employee_id',
        'amount', 'installments', 'monthly_deduction',
        'paid_amount', 'remaining_amount',
        'start_date', 'status',
        'reason', 'notes',
        'approved_by', 'approved_at', 'created_by',
    ];

    protected $casts = [
        'amount'            => 'decimal:2',
        'installments'      => 'integer',
        'monthly_deduction' => 'decimal:2',
        'paid_amount'       => 'decimal:2',
        'remaining_amount'  => 'decimal:2',
        'start_date'        => 'date',
        'approved_at'       => 'datetime',
    ];

    protected $attributes = [
        'status' => self::STATUS_ACTIVE,
    ];

    protected static function booted(): void
    {
        static::creating(function (EmployeeLoan $loan) {
            if (empty($loan->loan_code)) {
                $loan->loan_code = self::generateCode();
            }
            if (auth()->check() && empty($loan->created_by)) {
                $loan->created_by = auth()->id();
            }
            // Initialize remaining = amount on first save
            if (is_null($loan->remaining_amount)) {
                $loan->remaining_amount = $loan->amount;
            }
        });
    }

    public static function generateCode(): string
    {
        $year = date('Y');
        $next = Sequence::next('employee_loan:' . $year);

        return 'LOAN-' . $year . '-' . str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }

    // ── Relations ────────────────────────────────────────────────────────
    public function employee() { return $this->belongsTo(Employee::class); }
    public function approver() { return $this->belongsTo(User::class, 'approved_by'); }
    public function creator()  { return $this->belongsTo(User::class, 'created_by'); }

    // ── Helpers ──────────────────────────────────────────────────────────

    /**
     * Apply an installment deduction and flip status to completed if fully paid.
     * Called by the payroll engine after posting succeeds.
     */
    public function applyInstallment(float $amount): void
    {
        $this->paid_amount      = (float) $this->paid_amount + $amount;
        $this->remaining_amount = max(0, (float) $this->amount - (float) $this->paid_amount);

        if ($this->remaining_amount <= 0.005) {
            $this->status = self::STATUS_COMPLETED;
        }

        $this->save();
    }

    public function getProgressPercentAttribute(): float
    {
        if ((float) $this->amount <= 0) return 0;
        return round(((float) $this->paid_amount / (float) $this->amount) * 100, 2);
    }

    public function getInstallmentsRemainingAttribute(): int
    {
        if ((float) $this->monthly_deduction <= 0) return 0;
        return (int) ceil((float) $this->remaining_amount / (float) $this->monthly_deduction);
    }

    public function getStatusLabelAttribute(): string { return self::STATUS_LABELS[$this->status] ?? $this->status; }
    public function getStatusBadgeAttribute(): string { return self::STATUS_BADGES[$this->status] ?? 'secondary'; }

    public function isActive(): bool { return $this->status === self::STATUS_ACTIVE; }

    // ── Scopes ───────────────────────────────────────────────────────────
    public function scopeActive($query)              { return $query->where('status', self::STATUS_ACTIVE); }
    public function scopeForEmployee($query, string $empId) { return $query->where('employee_id', $empId); }
}
