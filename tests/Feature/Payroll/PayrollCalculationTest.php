<?php

namespace Tests\Feature\Payroll;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Department;
use App\Models\DomesticBooking;
use App\Models\Employee;
use App\Models\EmployeeLoan;
use App\Models\Payslip;
use App\Models\PayslipLine;
use App\Models\PayrollRun;
use App\Models\Position;
use App\Models\ReligiousBooking;
use App\Services\Payroll\CommissionCalculator;
use App\Services\Payroll\IncomeTaxCalculator;
use App\Services\Payroll\PayrollService;
use App\Services\Payroll\SocialInsuranceCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Step 5.2 — covers the four pieces that compose payroll:
 *   SocialInsuranceCalculator   (rate + cap)
 *   IncomeTaxCalculator         (annualize → brackets → /12)
 *   CommissionCalculator        (booking filter + basis)
 *   PayrollService::calculate   (orchestrates everything)
 */
class PayrollCalculationTest extends TestCase
{
    use RefreshDatabase;

    // ── SocialInsuranceCalculator ───────────────────────────────────────

    public function test_social_insurance_is_rate_of_gross_under_cap(): void
    {
        $calc = new SocialInsuranceCalculator(rate: 11.0, cap: 12600);

        $this->assertEquals(550.0,  $calc->calculate(5000));      // 11% of 5000
        $this->assertEquals(770.0,  $calc->calculate(7000));      // 11% of 7000
        $this->assertEquals(0,      $calc->calculate(0));
        $this->assertEquals(0,      $calc->calculate(-100));      // defensive
    }

    public function test_social_insurance_caps_earnings_above_threshold(): void
    {
        $calc = new SocialInsuranceCalculator(rate: 11.0, cap: 12600);

        // 20,000 gross → only 12,600 insurable → 11% of 12,600 = 1,386
        $this->assertEquals(1386.0, $calc->calculate(20000));
        $this->assertEquals(1386.0, $calc->calculate(12600));     // exactly at cap
    }

    // ── IncomeTaxCalculator ─────────────────────────────────────────────

    public function test_income_tax_returns_zero_below_exemption(): void
    {
        $calc = new IncomeTaxCalculator();

        // 2000/month = 24k/year < 29k exemption → 0
        $this->assertEquals(0, $calc->calculateMonthly(2000));
    }

    public function test_income_tax_zero_bracket_means_no_tax_in_first_slice(): void
    {
        $calc = new IncomeTaxCalculator();

        // 5,000/month = 60,000/year ; taxable = 60,000 - 29,000 = 31,000
        // 31,000 entirely in 0-40k bracket @ 0% → 0 tax
        $this->assertEquals(0, $calc->calculateMonthly(5000));
    }

    public function test_income_tax_progressive_brackets_apply_correctly(): void
    {
        $calc = new IncomeTaxCalculator();

        // 8,000/month = 96,000/year ; taxable = 67,000
        //   First  40,000 @ 0%    = 0
        //   Next   15,000 @ 10%   = 1,500
        //   Next   12,000 @ 15%   = 1,800
        // Annual tax = 3,300  →  monthly = 275
        $this->assertEquals(275.0, $calc->calculateMonthly(8000));
    }

    public function test_income_tax_handles_highest_bracket(): void
    {
        $calc = new IncomeTaxCalculator();

        // 200,000/month = 2,400,000/year ; taxable = 2,371,000
        //   0-40,000     @ 0%    = 0
        //   40-55k       @ 10%   = 1,500
        //   55-70k       @ 15%   = 2,250
        //   70-200k      @ 20%   = 26,000
        //   200-400k     @ 22.5% = 45,000
        //   400-1.2M     @ 25%   = 200,000
        //   1.2M-2.371M  @ 27.5% = 322,025
        // Annual tax = 596,775  →  monthly = 49,731.25
        $this->assertEquals(49731.25, $calc->calculateMonthly(200000));
    }

    // ── CommissionCalculator ────────────────────────────────────────────

    public function test_commission_filters_by_employee_and_period(): void
    {
        $branch = $this->mainBranch();
        $emp    = $this->makeEmployee($branch, commissionRate: 3.0);

        // Sale in May 2026 → counted
        $this->makeReligiousBooking($branch, $emp, '2026-05-15', sellPrice: 25000, netProfit: 5000, status: 'confirmed');

        // Sale in April → NOT counted
        $this->makeReligiousBooking($branch, $emp, '2026-04-15', sellPrice: 30000, netProfit: 6000, status: 'confirmed');

        // Cancelled sale → NOT counted
        $this->makeReligiousBooking($branch, $emp, '2026-05-20', sellPrice: 40000, netProfit: 8000, status: 'cancelled');

        $calc = new CommissionCalculator();
        $result = $calc->calculateForEmployee($emp, 2026, 5);

        // Only the May confirmed booking: 5000 net_profit × 3% = 150
        $this->assertEquals(150.0, $result['total']);
        $this->assertCount(1, $result['lines']);
    }

    public function test_commission_zero_rate_returns_empty(): void
    {
        $branch = $this->mainBranch();
        $emp    = $this->makeEmployee($branch, commissionRate: 0.0);

        $this->makeReligiousBooking($branch, $emp, '2026-05-15', sellPrice: 25000, netProfit: 5000, status: 'confirmed');

        $calc = new CommissionCalculator();
        $result = $calc->calculateForEmployee($emp, 2026, 5);

        $this->assertEquals(0, $result['total']);
        $this->assertEmpty($result['lines']);
    }

    public function test_commission_selling_price_basis_uses_sale_price(): void
    {
        $branch = $this->mainBranch();
        $emp    = $this->makeEmployee($branch, commissionRate: 2.0, commissionBasis: 'selling_price');

        $this->makeReligiousBooking($branch, $emp, '2026-05-15', sellPrice: 25000, netProfit: 5000, status: 'confirmed');

        $calc = new CommissionCalculator();
        $result = $calc->calculateForEmployee($emp, 2026, 5);

        // 25,000 selling × 2% = 500 (not 5,000 × 2% = 100)
        $this->assertEquals(500.0, $result['total']);
    }

    public function test_commission_aggregates_religious_and_domestic_bookings(): void
    {
        $branch = $this->mainBranch();
        $emp    = $this->makeEmployee($branch, commissionRate: 3.0);

        $this->makeReligiousBooking($branch, $emp, '2026-05-10', sellPrice: 20000, netProfit: 4000, status: 'confirmed');
        $this->makeDomesticBooking ($branch, $emp, '2026-05-20', sellPrice: 10000, netProfit: 2000, status: 'completed');

        $calc = new CommissionCalculator();
        $result = $calc->calculateForEmployee($emp, 2026, 5);

        // (4000 + 2000) × 3% = 180
        $this->assertEquals(180.0, $result['total']);
        $this->assertCount(2, $result['lines']);
    }

    // ── PayrollService::calculate (orchestrator) ────────────────────────

    public function test_calculate_generates_one_payslip_per_active_employee(): void
    {
        $branch = $this->mainBranch();
        $active1 = $this->makeEmployee($branch, fullName: 'موظف 1');
        $active2 = $this->makeEmployee($branch, fullName: 'موظف 2');
        $terminated = $this->makeEmployee($branch, fullName: 'مفصول');
        $terminated->update(['status' => 'terminated']);

        $run = PayrollRun::create([
            'branch_id'    => $branch->id,
            'period_year'  => 2026,
            'period_month' => 5,
        ]);

        $service = app(PayrollService::class);
        $service->calculate($run);

        $this->assertSame(2, $run->fresh()->payslips()->count());
        $this->assertSame(2, $run->fresh()->employees_count);
        $this->assertSame(PayrollRun::STATUS_CALCULATED, $run->fresh()->status);
    }

    public function test_calculate_freezes_salary_snapshot_into_payslip(): void
    {
        $branch = $this->mainBranch();
        $emp = $this->makeEmployee(
            $branch,
            basicSalary: 5000,
            housingAllowance: 1000,
            transportAllowance: 500,
        );

        $run = PayrollRun::create([
            'branch_id' => $branch->id, 'period_year' => 2026, 'period_month' => 5,
        ]);
        app(PayrollService::class)->calculate($run);

        $slip = $run->fresh()->payslips()->first();
        $this->assertEquals(5000, (float) $slip->basic_salary);
        $this->assertEquals(1000, (float) $slip->housing_allowance);
        $this->assertEquals(500,  (float) $slip->transport_allowance);
        $this->assertEquals(6500, (float) $slip->total_earnings);

        // Salary change after calculation must NOT affect payslip
        $emp->update(['basic_salary' => 9999]);
        $this->assertEquals(5000, (float) $slip->fresh()->basic_salary);
    }

    public function test_calculate_creates_commission_lines_for_each_booking(): void
    {
        $branch = $this->mainBranch();
        $emp = $this->makeEmployee($branch, basicSalary: 4000, commissionRate: 3.0);

        $this->makeReligiousBooking($branch, $emp, '2026-05-10', sellPrice: 20000, netProfit: 5000, status: 'confirmed');
        $this->makeReligiousBooking($branch, $emp, '2026-05-20', sellPrice: 15000, netProfit: 3000, status: 'confirmed');

        $run = PayrollRun::create([
            'branch_id' => $branch->id, 'period_year' => 2026, 'period_month' => 5,
        ]);
        app(PayrollService::class)->calculate($run);

        $slip = $run->fresh()->payslips()->first();

        // 8000 net × 3% = 240
        $this->assertEquals(240.0, (float) $slip->commission_amount);

        $commissionLines = $slip->lines()->ofType(PayslipLine::TYPE_COMMISSION)->get();
        $this->assertCount(2, $commissionLines);
        $this->assertEquals(150.0, (float) $commissionLines[0]->amount); // 5000×3%
        $this->assertEquals(3.0,   (float) $commissionLines[0]->rate_used);
    }

    public function test_calculate_creates_loan_installment_lines(): void
    {
        $branch = $this->mainBranch();
        $emp = $this->makeEmployee($branch, basicSalary: 8000);

        EmployeeLoan::create([
            'employee_id'       => $emp->id,
            'amount'            => 3000,
            'installments'      => 3,
            'monthly_deduction' => 1000,
            'start_date'        => '2026-05-01',
        ]);

        $run = PayrollRun::create([
            'branch_id' => $branch->id, 'period_year' => 2026, 'period_month' => 5,
        ]);
        app(PayrollService::class)->calculate($run);

        $slip = $run->fresh()->payslips()->first();
        $this->assertEquals(1000.0, (float) $slip->loan_deduction);
        $this->assertCount(1, $slip->lines()->ofType(PayslipLine::TYPE_LOAN_INSTALLMENT)->get());
    }

    public function test_calculate_loan_installment_caps_at_remaining_balance(): void
    {
        // Last installment should be the leftover, not the full monthly_deduction.
        $branch = $this->mainBranch();
        $emp = $this->makeEmployee($branch, basicSalary: 8000);

        EmployeeLoan::create([
            'employee_id'       => $emp->id,
            'amount'            => 3000,
            'installments'      => 3,
            'monthly_deduction' => 1000,
            'paid_amount'       => 2500,
            'remaining_amount'  => 500,
            'start_date'        => '2026-03-01',
        ]);

        $run = PayrollRun::create([
            'branch_id' => $branch->id, 'period_year' => 2026, 'period_month' => 5,
        ]);
        app(PayrollService::class)->calculate($run);

        $slip = $run->fresh()->payslips()->first();
        $this->assertEquals(500.0, (float) $slip->loan_deduction,
            'final installment capped at remaining 500, not 1000');
    }

    public function test_calculate_updates_run_totals(): void
    {
        $branch = $this->mainBranch();
        $this->makeEmployee($branch, basicSalary: 5000, housingAllowance: 1000);
        $this->makeEmployee($branch, basicSalary: 4000);

        $run = PayrollRun::create([
            'branch_id' => $branch->id, 'period_year' => 2026, 'period_month' => 5,
        ]);
        app(PayrollService::class)->calculate($run);

        $run = $run->fresh();
        $this->assertEquals(2, $run->employees_count);
        $this->assertEquals(10000.0, (float) $run->total_earnings, 'sum of two payslips');
        $this->assertNotNull($run->calculated_at);
    }

    public function test_recalculate_replaces_existing_payslips(): void
    {
        $branch = $this->mainBranch();
        $emp = $this->makeEmployee($branch, basicSalary: 4000);

        $run = PayrollRun::create([
            'branch_id' => $branch->id, 'period_year' => 2026, 'period_month' => 5,
        ]);
        app(PayrollService::class)->calculate($run);

        $firstSlipId = $run->fresh()->payslips()->first()->id;
        $emp->update(['basic_salary' => 9000]);

        app(PayrollService::class)->calculate($run);

        $this->assertSame(1, $run->fresh()->payslips()->count());
        $newSlip = $run->fresh()->payslips()->first();
        $this->assertNotSame($firstSlipId, $newSlip->id, 'old payslip should have been deleted');
        $this->assertEquals(9000.0, (float) $newSlip->basic_salary);
    }

    public function test_calculate_rejects_run_in_posted_status(): void
    {
        $branch = $this->mainBranch();
        $run = PayrollRun::create([
            'branch_id'   => $branch->id, 'period_year' => 2026, 'period_month' => 5,
            'status'      => PayrollRun::STATUS_POSTED,
        ]);

        $this->expectException(\RuntimeException::class);
        app(PayrollService::class)->calculate($run);
    }

    // ── Helpers ─────────────────────────────────────────────────────────

    private function mainBranch(): Branch
    {
        return Branch::main() ?? Branch::firstOrCreate(
            ['code' => 'BRN-001'],
            ['name' => 'الفرع الرئيسي', 'is_main' => true]
        );
    }

    private function makeEmployee(
        Branch $branch,
        string $fullName = 'موظف افتراضي',
        float $basicSalary = 4000,
        float $housingAllowance = 0,
        float $transportAllowance = 0,
        float $commissionRate = 0,
        string $commissionBasis = 'net_profit',
    ): Employee {
        $dept = Department::firstOrCreate(
            ['code' => 'DEP-001'],
            ['name' => 'المبيعات', 'branch_id' => $branch->id]
        );
        $pos = Position::firstOrCreate(
            ['code' => 'POS-001'],
            [
                'title'                       => 'مندوب',
                'department_id'               => $dept->id,
                'default_basic_salary'        => 0,
                'commission_rate'             => 0,
                'commission_basis'            => 'net_profit',
            ]
        );

        return Employee::create([
            'full_name'           => $fullName,
            'phone'               => '0100' . random_int(1000000, 9999999),
            'branch_id'           => $branch->id,
            'department_id'       => $dept->id,
            'position_id'         => $pos->id,
            'hire_date'           => '2025-01-01',
            'basic_salary'        => $basicSalary,
            'housing_allowance'   => $housingAllowance,
            'transport_allowance' => $transportAllowance,
            'commission_rate'     => $commissionRate > 0 ? $commissionRate : null,
            'commission_basis'    => $commissionBasis,
        ]);
    }

    private function makeReligiousBooking(
        Branch $branch, Employee $emp, string $bookingDate,
        float $sellPrice, float $netProfit, string $status
    ): ReligiousBooking {
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
            'booking_date'      => $bookingDate,
            'trip_date'         => date('Y-m-d', strtotime("$bookingDate +1 month")),
            'duration_days'     => 7,
            'adults_count'      => 1,
            'children_count'    => 0,
            'infants_count'     => 0,
            'selling_price'     => $sellPrice,
            'total_cost'        => $sellPrice - $netProfit,
            'net_profit'        => $netProfit,
            'status'            => $status,
            'workflow_stage'    => 'new',
        ]);
    }

    private function makeDomesticBooking(
        Branch $branch, Employee $emp, string $bookingDate,
        float $sellPrice, float $netProfit, string $status
    ): DomesticBooking {
        $customer = Customer::create([
            'full_name' => 'عميل ' . Str::random(4),
            'phone'     => '0103' . random_int(1000000, 9999999),
            'branch_id' => $branch->id,
        ]);

        return DomesticBooking::create([
            'branch_id'         => $branch->id,
            'customer_id'       => $customer->id,
            'sales_employee_id' => $emp->id,
            'type'              => 'leisure',
            'destination_city'  => 'الغردقة',
            'booking_date'      => $bookingDate,
            'trip_date'         => date('Y-m-d', strtotime("$bookingDate +1 month")),
            'duration_days'     => 5,
            'duration_nights'   => 4,
            'adults_count'      => 2,
            'children_count'    => 0,
            'infants_count'     => 0,
            'rooms_count'       => 1,
            'selling_price'     => $sellPrice,
            'total_cost'        => $sellPrice - $netProfit,
            'net_profit'        => $netProfit,
            'status'            => $status,
            'workflow_stage'    => 'new',
        ]);
    }
}
