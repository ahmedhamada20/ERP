<?php

namespace App\Services\Payroll;

use App\Models\JournalEntry;
use App\Models\PayrollRun;
use App\Models\PayslipLine;
use App\Services\Accounting\AccountingMappings;
use App\Services\Accounting\JournalService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Posts a payroll run to the General Ledger.
 *
 * One journal entry per run. Posting is monolithic and atomic:
 *   build entry → validate → post → advance loans → flip run status
 * If any step fails, the transaction rolls back and the run stays approved.
 *
 * Journal direction (per run):
 *   DR  مرتبات الموظفين (521)            base + housing + transport + other
 *   DR  عمولات مبيعات    (518)            commissions (if any)
 *   CR  التأمينات المستحقة (2133)          social insurance withheld
 *   CR  ضريبة كسب العمل (2132)            income tax withheld
 *   CR  مرتبات مستحقة   (2122)            net pay to be disbursed
 *
 * Note: this credits "salaries payable" — not cash. Cash leaves later via
 * a payment voucher posting against 2122. That separation matters because
 * payday is often days after payroll close.
 *
 * Loan installments are advanced only AFTER successful posting. Calculation
 * alone never moves loan balances.
 */
class PayrollPostingService
{
    public function __construct(
        private readonly AccountingMappings $mappings,
        private readonly JournalService $journals,
    ) {}

    public function post(PayrollRun $run): JournalEntry
    {
        if (! $run->canPost()) {
            throw new RuntimeException(
                "لا يمكن ترحيل دورة في حالة '{$run->status_label}' — يجب أن تكون معتمدة."
            );
        }

        $run->loadMissing('payslips.lines', 'branch');

        if ($run->payslips->isEmpty()) {
            throw new RuntimeException('دورة الرواتب لا تحتوي على قسائم رواتب — لا شيء للترحيل.');
        }

        // Aggregate payslip columns. Working on the run's own payslips
        // collection (already eagerloaded) keeps this side-effect-free until
        // we hit the transaction.
        $totals = [
            'base_salary' => 0.0,   // basic + 3 allowances + bonus (NOT commissions)
            'commissions' => 0.0,
            'si'          => 0.0,
            'tax'         => 0.0,
            'loans'       => 0.0,
            'net'         => 0.0,
        ];
        foreach ($run->payslips as $slip) {
            $totals['base_salary'] += (float) $slip->basic_salary
                                    + (float) $slip->housing_allowance
                                    + (float) $slip->transport_allowance
                                    + (float) $slip->other_allowances
                                    + (float) $slip->bonus;
            $totals['commissions'] += (float) $slip->commission_amount;
            $totals['si']          += (float) $slip->social_insurance;
            $totals['tax']         += (float) $slip->income_tax;
            $totals['loans']       += (float) $slip->loan_deduction;
            $totals['net']         += (float) $slip->net_pay;
        }

        $salaryAcc       = $this->mappings->salaryExpenseAccount();
        $commissionAcc   = $this->mappings->commissionExpenseAccount();
        $siPayableAcc    = $this->mappings->socialInsurancePayableAccount();
        $taxPayableAcc   = $this->mappings->incomeTaxPayableAccount();
        $loanReceivableAcc = $this->mappings->employeeLoansReceivableAccount();
        $netPayableAcc   = $this->mappings->salariesPayableAccount();

        return DB::transaction(function () use ($run, $totals, $salaryAcc, $commissionAcc, $siPayableAcc, $taxPayableAcc, $loanReceivableAcc, $netPayableAcc) {

            // ── Build the journal entry ──────────────────────────────────
            $entry = JournalEntry::create([
                'date'        => $run->payment_date ?? now()->toDateString(),
                'description' => sprintf(
                    'رواتب %s — فرع %s — %d موظف',
                    $run->period_label,
                    $run->branch?->name ?? '',
                    $run->employees_count,
                ),
                'reference'   => $run->run_code,
                'source_type' => 'payroll_run',
                'source_id'   => $run->id,
            ]);

            $line = 1;

            // DR base salary expense (always present)
            $entry->lines()->create([
                'account_id'  => $salaryAcc->id,
                'debit'       => round($totals['base_salary'], 2),
                'credit'      => 0,
                'description' => 'مرتبات وبدلات — ' . $run->run_code,
                'line_number' => $line++,
            ]);

            // DR commissions (only if non-zero — avoids null-impact lines)
            if ($totals['commissions'] > 0) {
                $entry->lines()->create([
                    'account_id'  => $commissionAcc->id,
                    'debit'       => round($totals['commissions'], 2),
                    'credit'      => 0,
                    'description' => 'عمولات مبيعات — ' . $run->run_code,
                    'line_number' => $line++,
                ]);
            }

            // CR social insurance payable
            if ($totals['si'] > 0) {
                $entry->lines()->create([
                    'account_id'  => $siPayableAcc->id,
                    'debit'       => 0,
                    'credit'      => round($totals['si'], 2),
                    'description' => 'تأمينات اجتماعية مستحقة — ' . $run->run_code,
                    'line_number' => $line++,
                ]);
            }

            // CR income tax payable
            if ($totals['tax'] > 0) {
                $entry->lines()->create([
                    'account_id'  => $taxPayableAcc->id,
                    'debit'       => 0,
                    'credit'      => round($totals['tax'], 2),
                    'description' => 'ضريبة كسب عمل مستحقة — ' . $run->run_code,
                    'line_number' => $line++,
                ]);
            }

            // CR employee loans receivable (asset reduces — loan is being paid back)
            if ($totals['loans'] > 0) {
                $entry->lines()->create([
                    'account_id'  => $loanReceivableAcc->id,
                    'debit'       => 0,
                    'credit'      => round($totals['loans'], 2),
                    'description' => 'استرداد أقساط سلف — ' . $run->run_code,
                    'line_number' => $line++,
                ]);
            }

            // CR net pay (salaries payable)
            $entry->lines()->create([
                'account_id'  => $netPayableAcc->id,
                'debit'       => 0,
                'credit'      => round($totals['net'], 2),
                'description' => 'صافي مرتبات مستحقة الصرف — ' . $run->run_code,
                'line_number' => $line++,
            ]);

            // JournalService validates balance + posts (sets status=posted)
            $entry = $this->journals->post($entry->fresh());

            // ── Advance loan balances using payslip_lines as source of truth ─
            // Aggregate loan deductions per loan across all payslips in this run.
            // (Same loan can't appear twice on one payslip but could across
            // payslips in different runs — not relevant here, single run.)
            $loanLines = PayslipLine::query()
                ->whereIn('payslip_id', $run->payslips->pluck('id'))
                ->where('line_type', PayslipLine::TYPE_LOAN_INSTALLMENT)
                ->where('reference_type', \App\Models\EmployeeLoan::class)
                ->get();

            foreach ($loanLines as $line) {
                $loan = \App\Models\EmployeeLoan::find($line->reference_id);
                if (! $loan) continue;
                $loan->applyInstallment((float) $line->amount);
            }

            // ── Flip run status ──────────────────────────────────────────
            $run->update([
                'status'            => PayrollRun::STATUS_POSTED,
                'journal_entry_id'  => $entry->id,
                'posted_by'         => auth()->id(),
                'posted_at'         => now(),
            ]);

            return $entry;
        });
    }

    /**
     * Cancel a posted payroll run.
     *
     * Strategy: cancel the linked journal entry, set run.status = cancelled,
     * detach journal_entry_id. We do NOT roll back loan installments
     * automatically — the user should manually adjust loans if needed,
     * because in practice a cancelled payroll usually means re-issuing
     * a corrected one rather than undoing payments.
     */
    public function cancel(PayrollRun $run, string $reason): void
    {
        if (! $run->isPosted()) {
            throw new RuntimeException('لا يمكن إلغاء دورة غير مرحّلة.');
        }

        DB::transaction(function () use ($run, $reason) {
            if ($run->journal_entry_id) {
                $entry = JournalEntry::find($run->journal_entry_id);
                if ($entry && $entry->status === 'posted') {
                    $this->journals->cancel($entry, $reason);
                }
            }

            $run->update([
                'status' => PayrollRun::STATUS_CANCELLED,
                'notes'  => trim(($run->notes ?? '') . "\nسبب الإلغاء: " . $reason),
            ]);
        });
    }
}
