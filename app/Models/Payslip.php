<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payslip extends Model
{
    use HasUlids, HasFactory;

    public const PAYMENT_METHOD_LABELS = [
        'cash'          => 'نقدي',
        'bank_transfer' => 'تحويل بنكي',
        'cheque'        => 'شيك',
    ];

    protected $fillable = [
        'payroll_run_id', 'employee_id', 'branch_id',
        // Earnings
        'basic_salary', 'housing_allowance', 'transport_allowance', 'other_allowances',
        'commission_amount', 'bonus', 'total_earnings',
        // Deductions
        'social_insurance', 'income_tax', 'loan_deduction',
        'absence_deduction', 'lateness_deduction', 'other_deductions',
        'total_deductions',
        // Result
        'net_pay',
        // Attendance
        'absent_days', 'lateness_minutes', 'working_days',
        // Payment snapshot
        'payment_method', 'bank_name', 'bank_account', 'iban',
        'paid_at', 'notes',
    ];

    protected $casts = [
        'basic_salary'        => 'decimal:2',
        'housing_allowance'   => 'decimal:2',
        'transport_allowance' => 'decimal:2',
        'other_allowances'    => 'decimal:2',
        'commission_amount'   => 'decimal:2',
        'bonus'               => 'decimal:2',
        'total_earnings'      => 'decimal:2',
        'social_insurance'    => 'decimal:2',
        'income_tax'          => 'decimal:2',
        'loan_deduction'      => 'decimal:2',
        'absence_deduction'   => 'decimal:2',
        'lateness_deduction'  => 'decimal:2',
        'other_deductions'    => 'decimal:2',
        'total_deductions'    => 'decimal:2',
        'net_pay'             => 'decimal:2',
        'absent_days'         => 'integer',
        'lateness_minutes'    => 'integer',
        'working_days'        => 'integer',
        'paid_at'             => 'datetime',
    ];

    protected $attributes = [
        'working_days'    => 30,
        'payment_method'  => 'bank_transfer',
    ];

    // ── Relations ────────────────────────────────────────────────────────
    public function payrollRun()  { return $this->belongsTo(PayrollRun::class); }
    public function employee()    { return $this->belongsTo(Employee::class); }
    public function branch()      { return $this->belongsTo(Branch::class); }
    public function lines()       { return $this->hasMany(PayslipLine::class); }

    // ── Helpers ──────────────────────────────────────────────────────────

    /**
     * Recompute totals from the individual columns. Call after mutating any
     * earning/deduction field; the engine does this automatically.
     */
    public function recomputeTotals(): void
    {
        $this->total_earnings = (float) $this->basic_salary
                              + (float) $this->housing_allowance
                              + (float) $this->transport_allowance
                              + (float) $this->other_allowances
                              + (float) $this->commission_amount
                              + (float) $this->bonus;

        $this->total_deductions = (float) $this->social_insurance
                                + (float) $this->income_tax
                                + (float) $this->loan_deduction
                                + (float) $this->absence_deduction
                                + (float) $this->lateness_deduction
                                + (float) $this->other_deductions;

        $this->net_pay = $this->total_earnings - $this->total_deductions;
    }

    public function getPaymentMethodLabelAttribute(): string
    {
        return self::PAYMENT_METHOD_LABELS[$this->payment_method] ?? $this->payment_method;
    }

    public function isPaid(): bool { return ! is_null($this->paid_at); }

    // ── Scopes ───────────────────────────────────────────────────────────
    public function scopeUnpaid($query)        { return $query->whereNull('paid_at'); }
    public function scopePaid($query)          { return $query->whereNotNull('paid_at'); }
    public function scopeForEmployee($query, string $empId) { return $query->where('employee_id', $empId); }
}
