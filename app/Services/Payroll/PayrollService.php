<?php

namespace App\Services\Payroll;

use App\Models\Employee;
use App\Models\EmployeeLoan;
use App\Models\Payslip;
use App\Models\PayslipLine;
use App\Models\PayrollRun;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Orchestrates payroll calculation for a single run.
 *
 * Public surface:
 *   - calculate(PayrollRun $run): PayrollRun  → recomputes all payslips
 *
 * Why is recalculate destructive? Because we don't want partial state. If
 * the user fixes someone's salary mid-draft and re-clicks "calculate", we
 * blow away the previous payslips and rebuild from scratch. The run status
 * stays in `calculated` so the user knows it needs re-approval.
 *
 * Posting (Step 5.3) is a SEPARATE concern — it reads frozen payslips and
 * writes a journal entry. Calculations never touch the GL.
 */
class PayrollService
{
    public function __construct(
        private CommissionCalculator $commission,
        private SocialInsuranceCalculator $insurance,
        private IncomeTaxCalculator $tax,
    ) {}

    /**
     * (Re)calculate every payslip for the run.
     *
     * Side-effects (all inside one DB transaction):
     *   - Deletes existing payslips for this run (cascade kills lines too)
     *   - Creates one payslip per active employee in the branch
     *   - Creates one payslip_line per commission booking + per loan installment
     *   - Updates the run's denormalized totals + status → calculated
     */
    public function calculate(PayrollRun $run): PayrollRun
    {
        if (! $run->canCalculate()) {
            throw new RuntimeException(
                "لا يمكن حساب دورة في حالة '{$run->status_label}' — يجب أن تكون مسودة أو محسوبة."
            );
        }

        return DB::transaction(function () use ($run) {
            // Wipe old payslips — recalc is always full rebuild
            $run->payslips()->delete();

            $employees = Employee::query()
                ->where('branch_id', $run->branch_id)
                ->where('status', 'active')
                ->with(['position', 'activeLoans'])
                ->get();

            $totals = ['earnings' => 0.0, 'commissions' => 0.0, 'deductions' => 0.0, 'net' => 0.0];

            foreach ($employees as $employee) {
                $payslip = $this->calculateForEmployee($run, $employee);
                $totals['earnings']    += (float) $payslip->total_earnings;
                $totals['commissions'] += (float) $payslip->commission_amount;
                $totals['deductions']  += (float) $payslip->total_deductions;
                $totals['net']         += (float) $payslip->net_pay;
            }

            $run->update([
                'status'            => PayrollRun::STATUS_CALCULATED,
                'employees_count'   => $employees->count(),
                'total_earnings'    => round($totals['earnings'], 2),
                'total_commissions' => round($totals['commissions'], 2),
                'total_deductions'  => round($totals['deductions'], 2),
                'total_net'         => round($totals['net'], 2),
                'calculated_by'     => auth()->id(),
                'calculated_at'     => now(),
            ]);

            return $run->fresh();
        });
    }

    /**
     * Move a calculated run to approved state. Step before posting.
     * No data changes — just locks the calculation and records the approver.
     */
    public function approve(PayrollRun $run): PayrollRun
    {
        if (! $run->canApprove()) {
            throw new RuntimeException(
                "لا يمكن اعتماد دورة في حالة '{$run->status_label}' — يجب أن تكون محسوبة."
            );
        }

        $run->update([
            'status'      => PayrollRun::STATUS_APPROVED,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return $run->fresh();
    }

    /**
     * Calculate ONE employee's payslip. Public so the caller can recompute
     * a single payslip after a manual edit without redoing the whole run
     * (used by Step 5.4 UI).
     */
    public function calculateForEmployee(PayrollRun $run, Employee $employee): Payslip
    {
        // ── Earnings (snapshot from Employee → falls back to Position) ───
        $basic     = $employee->effectiveBasicSalary();
        $housing   = $employee->effectiveHousingAllowance();
        $transport = $employee->effectiveTransportAllowance();
        $other     = $employee->effectiveOtherAllowances();

        // ── Commission ────────────────────────────────────────────────────
        $commissionResult = $this->commission->calculateForEmployee(
            $employee, $run->period_year, $run->period_month
        );
        $commissionAmount = $commissionResult['total'];

        $grossEarnings = $basic + $housing + $transport + $other + $commissionAmount;

        // ── Deductions ────────────────────────────────────────────────────
        $socialInsurance = $this->insurance->calculate($grossEarnings);
        $taxableEarnings = max(0, $grossEarnings - $socialInsurance);
        $incomeTax       = $this->tax->calculateMonthly($taxableEarnings);

        // Loan installments — sum of monthly_deduction across active loans
        $loanLines = collect();
        $loanTotal = 0.0;
        foreach ($employee->activeLoans as $loan) {
            $installment = min(
                (float) $loan->monthly_deduction,
                (float) $loan->remaining_amount
            );
            if ($installment <= 0) continue;

            $loanLines->push([
                'loan'        => $loan,
                'amount'      => round($installment, 2),
                'description' => 'قسط سلفة ' . $loan->loan_code,
            ]);
            $loanTotal += $installment;
        }

        $totalDeductions = $socialInsurance + $incomeTax + round($loanTotal, 2);

        // ── Persist ───────────────────────────────────────────────────────
        $payslip = Payslip::create([
            'payroll_run_id'      => $run->id,
            'employee_id'         => $employee->id,
            'branch_id'           => $employee->branch_id,

            'basic_salary'        => $basic,
            'housing_allowance'   => $housing,
            'transport_allowance' => $transport,
            'other_allowances'    => $other,
            'commission_amount'   => $commissionAmount,
            'bonus'               => 0,
            'total_earnings'      => round($grossEarnings, 2),

            'social_insurance'    => $socialInsurance,
            'income_tax'          => $incomeTax,
            'loan_deduction'      => round($loanTotal, 2),
            'absence_deduction'   => 0,
            'lateness_deduction'  => 0,
            'other_deductions'    => 0,
            'total_deductions'    => round($totalDeductions, 2),

            'net_pay'             => round($grossEarnings - $totalDeductions, 2),

            // Bank snapshot
            'payment_method'      => $employee->payment_method,
            'bank_name'           => $employee->bank_name,
            'bank_account'        => $employee->bank_account,
            'iban'                => $employee->iban,
        ]);

        // ── Line items (audit trail) ──────────────────────────────────────
        foreach ($commissionResult['lines'] as $line) {
            PayslipLine::create([
                'payslip_id'     => $payslip->id,
                'line_type'      => PayslipLine::TYPE_COMMISSION,
                'reference_type' => $line['booking']::class,
                'reference_id'   => $line['booking']->id,
                'description'    => $line['description'],
                'amount'         => $line['amount'],
                'rate_used'      => $line['rate'],
                'base_value'     => $line['base'],
            ]);
        }

        foreach ($loanLines as $line) {
            PayslipLine::create([
                'payslip_id'     => $payslip->id,
                'line_type'      => PayslipLine::TYPE_LOAN_INSTALLMENT,
                'reference_type' => EmployeeLoan::class,
                'reference_id'   => $line['loan']->id,
                'description'    => $line['description'],
                'amount'         => $line['amount'],
            ]);
        }

        return $payslip;
    }
}
