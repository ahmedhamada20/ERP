<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Employee extends Model
{
    use HasUlids, HasFactory, SoftDeletes, LogsActivity;

    public const STATUS_LABELS = [
        'active'      => 'نشط',
        'on_leave'    => 'في إجازة',
        'terminated'  => 'منتهى الخدمة',
        'suspended'   => 'موقوف',
    ];

    public const STATUS_BADGES = [
        'active'     => 'success',
        'on_leave'   => 'info',
        'terminated' => 'danger',
        'suspended'  => 'warning',
    ];

    public const EMPLOYMENT_TYPE_LABELS = [
        'full_time' => 'دوام كامل',
        'part_time' => 'دوام جزئي',
        'contract'  => 'عقد محدد',
        'intern'    => 'تدريب',
    ];

    public const PAYMENT_METHOD_LABELS = [
        'cash'          => 'نقدي',
        'bank_transfer' => 'تحويل بنكي',
        'cheque'        => 'شيك',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'code', 'full_name', 'national_id', 'phone',
                'branch_id', 'department_id', 'position_id',
                'hire_date', 'termination_date', 'employment_type', 'status',
                'basic_salary', 'commission_rate',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $event) => match ($event) {
                'created' => 'تم إضافة موظف',
                'updated' => 'تم تعديل بيانات موظف',
                'deleted' => 'تم حذف موظف',
                default   => $event,
            })
            ->useLogName('employee');
    }

    protected $fillable = [
        'code', 'user_id',
        'full_name', 'full_name_en', 'national_id', 'passport_number',
        'birth_date', 'gender', 'marital_status', 'nationality', 'religion',
        'phone', 'whatsapp', 'email',
        'emergency_contact_name', 'emergency_contact_phone',
        'address', 'city',
        'branch_id', 'department_id', 'position_id', 'reports_to',
        'hire_date', 'termination_date', 'employment_type', 'status',
        'basic_salary', 'housing_allowance', 'transport_allowance', 'other_allowances',
        'commission_rate', 'commission_basis',
        'payment_method', 'bank_name', 'bank_account', 'iban',
        'photo', 'id_image',
        'notes', 'created_by',
    ];

    protected $casts = [
        'birth_date'          => 'date',
        'hire_date'           => 'date',
        'termination_date'    => 'date',
        'basic_salary'        => 'decimal:2',
        'housing_allowance'   => 'decimal:2',
        'transport_allowance' => 'decimal:2',
        'other_allowances'    => 'decimal:2',
        'commission_rate'     => 'decimal:2',
    ];

    protected $attributes = [
        'status'          => 'active',
        'employment_type' => 'full_time',
        'payment_method'  => 'bank_transfer',
        'nationality'     => 'مصري',
    ];

    protected static function booted(): void
    {
        static::creating(function (Employee $emp) {
            if (empty($emp->code)) {
                $emp->code = self::generateCode();
            }
            if (auth()->check() && empty($emp->created_by)) {
                $emp->created_by = auth()->id();
            }
            // Default whatsapp to phone
            if (empty($emp->whatsapp) && !empty($emp->phone)) {
                $emp->whatsapp = $emp->phone;
            }
        });
    }

    public static function generateCode(): string
    {
        $year = date('Y');
        $next = Sequence::next('employee:' . $year);

        return 'EMP-' . $year . '-' . str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }

    // ── Relations ─────────────────────────────────────────────────────
    public function user()       { return $this->belongsTo(User::class); }
    public function branch()     { return $this->belongsTo(Branch::class); }
    public function department() { return $this->belongsTo(Department::class); }
    public function position()   { return $this->belongsTo(Position::class); }
    public function manager()    { return $this->belongsTo(self::class, 'reports_to'); }
    public function subordinates() { return $this->hasMany(self::class, 'reports_to'); }
    public function creator()    { return $this->belongsTo(User::class, 'created_by'); }
    public function documents()  { return $this->hasMany(EmployeeDocument::class); }

    // Payroll relations (Sprint 6 Step 5)
    public function payslips()         { return $this->hasMany(Payslip::class); }
    public function loans()            { return $this->hasMany(EmployeeLoan::class); }
    public function activeLoans()      { return $this->hasMany(EmployeeLoan::class)->where('status', EmployeeLoan::STATUS_ACTIVE); }
    public function religiousSales()   { return $this->hasMany(ReligiousBooking::class, 'sales_employee_id'); }
    public function domesticSales()    { return $this->hasMany(DomesticBooking::class, 'sales_employee_id'); }

    // ── Salary helpers ────────────────────────────────────────────────

    /**
     * Resolve effective salary value, preferring employee override over
     * position default. Used by payroll engine.
     */
    public function effectiveBasicSalary(): float
    {
        return (float) $this->basic_salary > 0
            ? (float) $this->basic_salary
            : (float) ($this->position?->default_basic_salary ?? 0);
    }

    public function effectiveHousingAllowance(): float
    {
        return (float) $this->housing_allowance > 0
            ? (float) $this->housing_allowance
            : (float) ($this->position?->default_housing_allowance ?? 0);
    }

    public function effectiveTransportAllowance(): float
    {
        return (float) $this->transport_allowance > 0
            ? (float) $this->transport_allowance
            : (float) ($this->position?->default_transport_allowance ?? 0);
    }

    public function effectiveOtherAllowances(): float
    {
        return (float) $this->other_allowances > 0
            ? (float) $this->other_allowances
            : (float) ($this->position?->default_other_allowances ?? 0);
    }

    public function effectiveGrossSalary(): float
    {
        return $this->effectiveBasicSalary()
             + $this->effectiveHousingAllowance()
             + $this->effectiveTransportAllowance()
             + $this->effectiveOtherAllowances();
    }

    /** Commission rate falls back to position if the employee's column is NULL. */
    public function effectiveCommissionRate(): float
    {
        return ! is_null($this->commission_rate)
            ? (float) $this->commission_rate
            : (float) ($this->position?->commission_rate ?? 0);
    }

    public function effectiveCommissionBasis(): string
    {
        return $this->commission_basis ?: ($this->position?->commission_basis ?? 'net_profit');
    }

    // ── Labels ────────────────────────────────────────────────────────
    public function getStatusLabelAttribute(): string { return self::STATUS_LABELS[$this->status] ?? $this->status; }
    public function getStatusBadgeAttribute(): string { return self::STATUS_BADGES[$this->status] ?? 'secondary'; }
    public function getEmploymentTypeLabelAttribute(): string { return self::EMPLOYMENT_TYPE_LABELS[$this->employment_type] ?? $this->employment_type; }
    public function getPaymentMethodLabelAttribute(): string  { return self::PAYMENT_METHOD_LABELS[$this->payment_method] ?? $this->payment_method; }

    public function getYearsOfServiceAttribute(): float
    {
        if (! $this->hire_date) return 0;
        $end = $this->termination_date ?? now();
        return round($this->hire_date->floatDiffInYears($end), 1);
    }

    public function getPhotoUrlAttribute(): string
    {
        return $this->photo ? asset('storage/' . $this->photo) : asset('admin/img/user-placeholder.png');
    }

    // ── Scopes ────────────────────────────────────────────────────────
    public function scopeActive($query)     { return $query->where('status', 'active'); }
    public function scopeInBranch($query, string $branchId) { return $query->where('branch_id', $branchId); }
    public function scopeOfPosition($query, string $positionId) { return $query->where('position_id', $positionId); }
}
