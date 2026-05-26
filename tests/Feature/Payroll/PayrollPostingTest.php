<?php

namespace Tests\Feature\Payroll;

use App\Models\Account;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeLoan;
use App\Models\JournalEntry;
use App\Models\PayrollRun;
use App\Models\Position;
use App\Models\ReligiousBooking;
use App\Services\Payroll\PayrollPostingService;
use App\Services\Payroll\PayrollService;
use Database\Seeders\ChartOfAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Step 5.3 — GL posting service:
 *   approve()     calculated → approved
 *   post()        approved   → posted + creates journal entry + advances loans
 *   cancel()      posted     → cancelled + reverses the JE
 */
class PayrollPostingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ChartOfAccountsSeeder::class);
    }

    public function test_approve_advances_status_and_records_approver(): void
    {
        $run = $this->calculatedRun();

        $approver = \App\Models\User::factory()->create();
        $this->actingAs($approver);

        app(PayrollService::class)->approve($run);

        $run = $run->fresh();
        $this->assertSame(PayrollRun::STATUS_APPROVED, $run->status);
        $this->assertSame($approver->id, $run->approved_by);
        $this->assertNotNull($run->approved_at);
    }

    public function test_approve_rejects_non_calculated_runs(): void
    {
        $run = PayrollRun::create([
            'branch_id' => $this->mainBranch()->id, 'period_year' => 2026, 'period_month' => 5,
        ]); // status=draft

        $this->expectException(\RuntimeException::class);
        app(PayrollService::class)->approve($run);
    }

    public function test_post_creates_balanced_journal_entry(): void
    {
        $run = $this->approvedRun(basicSalary: 5000, housing: 1000);
        // One employee: gross=6000, SI=11%*6000=660, taxable=5340, tax/year on (5340*12=64080-29000=35080) = 0 (entirely in 0% bracket since 35080<40000)
        // Wait: 35080 → first 35080 in 0-40k bracket @ 0% = 0
        // So tax=0; net = 6000 - 660 - 0 = 5340

        $entry = app(PayrollPostingService::class)->post($run);

        $entry->loadMissing('lines.account');

        $debit  = (float) $entry->lines->sum('debit');
        $credit = (float) $entry->lines->sum('credit');

        $this->assertEqualsWithDelta($debit, $credit, 0.01, 'journal entry must balance');
        $this->assertEquals(6000.0, $debit, 'total debit = gross');
        $this->assertEquals('posted', $entry->status);
        $this->assertSame('payroll_run', $entry->source_type);
        $this->assertSame($run->id, $entry->source_id);
    }

    public function test_post_journal_lines_use_correct_accounts(): void
    {
        $run = $this->approvedRun(basicSalary: 5000, housing: 1000);

        $entry = app(PayrollPostingService::class)->post($run);
        $entry->loadMissing('lines.account');

        $accountCodes = $entry->lines->pluck('account.code')->all();

        $this->assertContains('521',  $accountCodes, 'مرتبات الموظفين expense');
        $this->assertContains('2133', $accountCodes, 'تأمينات اجتماعية مستحقة');
        $this->assertContains('2122', $accountCodes, 'مرتبات مستحقة (net pay)');
    }

    public function test_post_includes_commission_line_when_commissions_exist(): void
    {
        $branch = $this->mainBranch();
        $emp = $this->makeEmployee($branch, basicSalary: 4000, commissionRate: 5.0);
        $this->makeBooking($branch, $emp, '2026-05-10', netProfit: 2000);

        $run = $this->buildRunForEmployee($emp, $branch);
        app(PayrollService::class)->calculate($run);
        app(PayrollService::class)->approve($run);

        $entry = app(PayrollPostingService::class)->post($run);
        $entry->loadMissing('lines.account');

        $commissionLine = $entry->lines->firstWhere('account.code', '518');
        $this->assertNotNull($commissionLine, 'عمولات مبيعات expense line expected');
        $this->assertEquals(100.0, (float) $commissionLine->debit, '2000 × 5% = 100');
    }

    public function test_post_advances_loan_balances(): void
    {
        $branch = $this->mainBranch();
        $emp = $this->makeEmployee($branch, basicSalary: 8000);

        $loan = EmployeeLoan::create([
            'employee_id'       => $emp->id,
            'amount'            => 3000,
            'installments'      => 3,
            'monthly_deduction' => 1000,
            'start_date'        => '2026-05-01',
        ]);

        $run = $this->buildRunForEmployee($emp, $branch);
        app(PayrollService::class)->calculate($run);
        app(PayrollService::class)->approve($run);
        app(PayrollPostingService::class)->post($run);

        $loan = $loan->fresh();
        $this->assertEquals(1000.0, (float) $loan->paid_amount, 'one installment posted');
        $this->assertEquals(2000.0, (float) $loan->remaining_amount);
        $this->assertSame(EmployeeLoan::STATUS_ACTIVE, $loan->status);
    }

    public function test_post_completes_loan_when_fully_paid(): void
    {
        $branch = $this->mainBranch();
        $emp = $this->makeEmployee($branch, basicSalary: 8000);

        $loan = EmployeeLoan::create([
            'employee_id'       => $emp->id,
            'amount'            => 1000,
            'installments'      => 1,
            'monthly_deduction' => 1000,
            'start_date'        => '2026-05-01',
        ]);

        $run = $this->buildRunForEmployee($emp, $branch);
        app(PayrollService::class)->calculate($run);
        app(PayrollService::class)->approve($run);
        app(PayrollPostingService::class)->post($run);

        $loan = $loan->fresh();
        $this->assertEquals(1000.0, (float) $loan->paid_amount);
        $this->assertEquals(0,      (float) $loan->remaining_amount);
        $this->assertSame(EmployeeLoan::STATUS_COMPLETED, $loan->status);
    }

    public function test_post_links_journal_entry_to_run_and_flips_status(): void
    {
        $run   = $this->approvedRun();
        $entry = app(PayrollPostingService::class)->post($run);

        $run = $run->fresh();
        $this->assertSame(PayrollRun::STATUS_POSTED, $run->status);
        $this->assertSame($entry->id, $run->journal_entry_id);
        $this->assertNotNull($run->posted_at);
    }

    public function test_post_rejects_non_approved_runs(): void
    {
        $run = $this->calculatedRun(); // status=calculated, not approved

        $this->expectException(\RuntimeException::class);
        app(PayrollPostingService::class)->post($run);
    }

    public function test_post_rejects_runs_without_payslips(): void
    {
        // Approved run with no payslips (edge case — shouldn't normally happen)
        $run = PayrollRun::create([
            'branch_id' => $this->mainBranch()->id, 'period_year' => 2026, 'period_month' => 5,
            'status' => PayrollRun::STATUS_APPROVED,
        ]);

        $this->expectException(\RuntimeException::class);
        app(PayrollPostingService::class)->post($run);
    }

    public function test_cancel_reverses_journal_and_marks_run_cancelled(): void
    {
        $run   = $this->approvedRun();
        $entry = app(PayrollPostingService::class)->post($run);

        app(PayrollPostingService::class)->cancel($run->fresh(), 'إعادة احتساب');

        $run   = $run->fresh();
        $entry = $entry->fresh();

        $this->assertSame(PayrollRun::STATUS_CANCELLED, $run->status);
        $this->assertSame('cancelled', $entry->status);
        $this->assertStringContainsString('إعادة احتساب', $run->notes);
    }

    public function test_cancel_rejects_non_posted_runs(): void
    {
        $run = $this->calculatedRun();

        $this->expectException(\RuntimeException::class);
        app(PayrollPostingService::class)->cancel($run, 'test');
    }

    // ── Helpers ─────────────────────────────────────────────────────────

    private function mainBranch(): Branch
    {
        return Branch::main() ?? Branch::firstOrCreate(
            ['code' => 'BRN-001'],
            ['name' => 'الفرع الرئيسي', 'is_main' => true]
        );
    }

    private function calculatedRun(float $basicSalary = 5000, float $housing = 0): PayrollRun
    {
        $branch = $this->mainBranch();
        $emp = $this->makeEmployee($branch, basicSalary: $basicSalary, housingAllowance: $housing);

        $run = $this->buildRunForEmployee($emp, $branch);
        app(PayrollService::class)->calculate($run);

        return $run->fresh();
    }

    private function approvedRun(float $basicSalary = 5000, float $housing = 0): PayrollRun
    {
        $run = $this->calculatedRun($basicSalary, $housing);
        return app(PayrollService::class)->approve($run);
    }

    private function buildRunForEmployee(Employee $emp, Branch $branch): PayrollRun
    {
        return PayrollRun::create([
            'branch_id'    => $branch->id,
            'period_year'  => 2026,
            'period_month' => 5,
            'payment_date' => '2026-05-28',
        ]);
    }

    private function makeEmployee(
        Branch $branch,
        float $basicSalary = 4000,
        float $housingAllowance = 0,
        float $commissionRate = 0,
    ): Employee {
        $dept = Department::firstOrCreate(
            ['code' => 'DEP-001'],
            ['name' => 'الإدارة', 'branch_id' => $branch->id]
        );
        $pos = Position::firstOrCreate(
            ['code' => 'POS-001'],
            ['title' => 'موظف', 'department_id' => $dept->id, 'default_basic_salary' => 0]
        );

        return Employee::create([
            'full_name'         => 'موظف ' . Str::random(4),
            'phone'             => '0100' . random_int(1000000, 9999999),
            'branch_id'         => $branch->id,
            'department_id'     => $dept->id,
            'position_id'       => $pos->id,
            'hire_date'         => '2025-01-01',
            'basic_salary'      => $basicSalary,
            'housing_allowance' => $housingAllowance,
            'commission_rate'   => $commissionRate > 0 ? $commissionRate : null,
            'commission_basis'  => 'net_profit',
        ]);
    }

    private function makeBooking(Branch $branch, Employee $emp, string $date, float $netProfit): ReligiousBooking
    {
        $customer = Customer::create([
            'full_name' => 'عميل ' . Str::random(4),
            'phone'     => '0102' . random_int(1000000, 9999999),
            'branch_id' => $branch->id,
        ]);

        return ReligiousBooking::create([
            'branch_id'         => $branch->id,
            'customer_id'       => $customer->id,
            'sales_employee_id' => $emp->id,
            'type'              => 'umrah',
            'booking_date'      => $date,
            'trip_date'         => date('Y-m-d', strtotime("$date +1 month")),
            'duration_days'     => 7,
            'adults_count'      => 1,
            'children_count'    => 0, 'infants_count' => 0,
            'selling_price'     => $netProfit + 10000,
            'total_cost'        => 10000,
            'net_profit'        => $netProfit,
            'status'            => 'confirmed',
            'workflow_stage'    => 'new',
        ]);
    }
}
