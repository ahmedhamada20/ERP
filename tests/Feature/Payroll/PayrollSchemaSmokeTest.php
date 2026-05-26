<?php

namespace Tests\Feature\Payroll;

use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeLoan;
use App\Models\Payslip;
use App\Models\PayslipLine;
use App\Models\PayrollRun;
use App\Models\Position;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Step 5.1 smoke test: the 5 new tables + 4 new models work end-to-end.
 *
 * This is a SCHEMA test, not a business-logic test. The payroll calculation
 * engine is Step 5.2 — here we only prove rows can be created, relationships
 * resolve, and computed fields work.
 */
class PayrollSchemaSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_payroll_run_auto_generates_code_and_status_starts_draft(): void
    {
        $branch = $this->mainBranch();

        $run = PayrollRun::create([
            'branch_id'    => $branch->id,
            'period_year'  => 2026,
            'period_month' => 5,
            'payment_date' => '2026-05-28',
        ]);

        $this->assertSame('PAY-2026-05-001', $run->run_code);
        $this->assertSame(PayrollRun::STATUS_DRAFT, $run->status);
        $this->assertSame('مايو 2026', $run->period_label);
        $this->assertSame('مسودة', $run->status_label);
        $this->assertTrue($run->canCalculate());
        $this->assertFalse($run->canPost());
    }

    public function test_payroll_run_codes_are_sequential_per_month(): void
    {
        $branch = $this->mainBranch();

        $r1 = PayrollRun::create(['branch_id' => $branch->id, 'period_year' => 2026, 'period_month' => 5]);
        $r2 = PayrollRun::create(['branch_id' => $branch->id, 'period_year' => 2026, 'period_month' => 5]);
        $r3 = PayrollRun::create(['branch_id' => $branch->id, 'period_year' => 2026, 'period_month' => 6]);

        $this->assertSame('PAY-2026-05-001', $r1->run_code);
        $this->assertSame('PAY-2026-05-002', $r2->run_code);
        $this->assertSame('PAY-2026-06-001', $r3->run_code, 'June starts fresh sequence');
    }

    public function test_payslip_recompute_totals_sums_earnings_and_deductions(): void
    {
        $branch = $this->mainBranch();
        $emp    = $this->makeEmployee($branch);
        $run    = PayrollRun::create(['branch_id' => $branch->id, 'period_year' => 2026, 'period_month' => 5]);

        $slip = new Payslip([
            'payroll_run_id'      => $run->id,
            'employee_id'         => $emp->id,
            'branch_id'           => $branch->id,
            'basic_salary'        => 5000,
            'housing_allowance'   => 1000,
            'transport_allowance' => 500,
            'commission_amount'   => 750,
            'bonus'               => 200,
            'social_insurance'    => 660,    // 11% of gross
            'income_tax'          => 150,
            'loan_deduction'      => 500,
            'absence_deduction'   => 100,
        ]);
        $slip->recomputeTotals();
        $slip->save();

        $this->assertEquals(7450, (float) $slip->total_earnings);   // 5000+1000+500+750+200
        $this->assertEquals(1410, (float) $slip->total_deductions); // 660+150+500+100
        $this->assertEquals(6040, (float) $slip->net_pay);          // 7450-1410
    }

    public function test_payslip_unique_per_run_and_employee(): void
    {
        $branch = $this->mainBranch();
        $emp    = $this->makeEmployee($branch);
        $run    = PayrollRun::create(['branch_id' => $branch->id, 'period_year' => 2026, 'period_month' => 5]);

        Payslip::create(['payroll_run_id' => $run->id, 'employee_id' => $emp->id, 'branch_id' => $branch->id]);

        $this->expectException(\Illuminate\Database\QueryException::class);
        Payslip::create(['payroll_run_id' => $run->id, 'employee_id' => $emp->id, 'branch_id' => $branch->id]);
    }

    public function test_payslip_lines_classify_as_earning_or_deduction(): void
    {
        $branch = $this->mainBranch();
        $emp    = $this->makeEmployee($branch);
        $run    = PayrollRun::create(['branch_id' => $branch->id, 'period_year' => 2026, 'period_month' => 5]);
        $slip   = Payslip::create(['payroll_run_id' => $run->id, 'employee_id' => $emp->id, 'branch_id' => $branch->id]);

        $earning = PayslipLine::create([
            'payslip_id'  => $slip->id,
            'line_type'   => PayslipLine::TYPE_COMMISSION,
            'description' => 'عمولة حجز BK-2026-00123',
            'amount'      => 750,
            'rate_used'   => 3.0,
            'base_value'  => 25000,
        ]);

        $deduction = PayslipLine::create([
            'payslip_id'  => $slip->id,
            'line_type'   => PayslipLine::TYPE_LOAN_INSTALLMENT,
            'description' => 'قسط سلفة LOAN-2026-00001',
            'amount'      => 500,
        ]);

        $this->assertTrue($earning->isEarning());
        $this->assertFalse($earning->isDeduction());
        $this->assertTrue($deduction->isDeduction());
        $this->assertSame('عمولة', $earning->type_label);
        $this->assertCount(1, $slip->lines()->earnings()->get());
        $this->assertCount(1, $slip->lines()->deductions()->get());
    }

    public function test_employee_loan_auto_generates_code_and_initial_remaining(): void
    {
        $branch = $this->mainBranch();
        $emp    = $this->makeEmployee($branch);

        $loan = EmployeeLoan::create([
            'employee_id'       => $emp->id,
            'amount'            => 6000,
            'installments'      => 6,
            'monthly_deduction' => 1000,
            'start_date'        => '2026-05-01',
        ]);

        $this->assertMatchesRegularExpression('/^LOAN-\d{4}-\d{5}$/', $loan->loan_code);
        $this->assertEquals(6000, (float) $loan->remaining_amount, 'remaining initialized to full amount');
        $this->assertEquals(0,    (float) $loan->paid_amount);
        $this->assertSame(EmployeeLoan::STATUS_ACTIVE, $loan->status);
        $this->assertEquals(0, $loan->progress_percent);
    }

    public function test_employee_loan_installment_advances_status_to_completed(): void
    {
        $branch = $this->mainBranch();
        $emp    = $this->makeEmployee($branch);

        $loan = EmployeeLoan::create([
            'employee_id'       => $emp->id,
            'amount'            => 3000,
            'installments'      => 3,
            'monthly_deduction' => 1000,
            'start_date'        => '2026-05-01',
        ]);

        $loan->applyInstallment(1000);
        $this->assertEquals(1000, (float) $loan->paid_amount);
        $this->assertEquals(2000, (float) $loan->remaining_amount);
        $this->assertSame(EmployeeLoan::STATUS_ACTIVE, $loan->status);
        $this->assertEquals(33.33, $loan->progress_percent);

        $loan->applyInstallment(1000);
        $loan->applyInstallment(1000);

        $this->assertEquals(3000, (float) $loan->paid_amount);
        $this->assertEquals(0,    (float) $loan->remaining_amount);
        $this->assertSame(EmployeeLoan::STATUS_COMPLETED, $loan->status, 'auto-completed when fully paid');
    }

    public function test_employee_active_loans_relation_filters_correctly(): void
    {
        $branch = $this->mainBranch();
        $emp    = $this->makeEmployee($branch);

        EmployeeLoan::create([
            'employee_id'       => $emp->id,
            'amount'            => 1000, 'installments' => 1, 'monthly_deduction' => 1000,
            'start_date'        => '2026-05-01',
        ]);
        EmployeeLoan::create([
            'employee_id'       => $emp->id,
            'amount'            => 500, 'installments' => 1, 'monthly_deduction' => 500,
            'start_date'        => '2026-04-01',
            'status'            => EmployeeLoan::STATUS_COMPLETED,
        ]);

        $this->assertCount(1, $emp->fresh()->activeLoans);
        $this->assertCount(2, $emp->fresh()->loans);
    }

    public function test_booking_can_link_to_sales_employee(): void
    {
        // This proves the migration successfully added sales_employee_id
        // and the relation works. Verifies FK works on both booking tables.
        $branch = $this->mainBranch();
        $emp    = $this->makeEmployee($branch);

        // Create a customer using the model so auto-code generation fires
        $customer = \App\Models\Customer::create([
            'full_name'  => 'عميل تجريبي',
            'phone'      => '01111111111',
            'branch_id'  => $branch->id,
        ]);
        $customerId = $customer->id;

        $bookingId = (string) \Illuminate\Support\Str::ulid();
        \DB::table('religious_bookings')->insert([
            'id'                 => $bookingId,
            'branch_id'          => $branch->id,
            'booking_number'     => 'UM-2026-000001',
            'customer_id'        => $customerId,
            'sales_employee_id'  => $emp->id,
            'type'               => 'umrah',
            'booking_date'       => '2026-05-15',
            'trip_date'          => '2026-06-01',
            'duration_days'      => 7,
            'adults_count'       => 1,
            'children_count'     => 0,
            'infants_count'      => 0,
            'selling_price'      => 25000,
            'total_cost'         => 20000,
            'net_profit'         => 5000,
            'status'             => 'pending',
            'workflow_stage'     => 'new',
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);

        $booking = \App\Models\ReligiousBooking::find($bookingId);
        $this->assertNotNull($booking);
        $this->assertSame($emp->id, $booking->sales_employee_id);
        $this->assertSame($emp->id, $booking->salesEmployee->id);
    }

    private function mainBranch(): Branch
    {
        // The 2026_06_01_100006 migration creates BRN-001 automatically.
        return Branch::main() ?? Branch::firstOrCreate(
            ['code' => 'BRN-001'],
            ['name' => 'الفرع الرئيسي', 'is_main' => true]
        );
    }

    private function makeEmployee(Branch $branch): Employee
    {
        $dept = Department::create(['code' => 'DEP-001', 'name' => 'المبيعات', 'branch_id' => $branch->id]);
        $pos  = Position::create([
            'code'                       => 'POS-001',
            'title'                      => 'مندوب مبيعات',
            'department_id'              => $dept->id,
            'default_basic_salary'       => 4000,
            'default_housing_allowance'  => 800,
            'commission_rate'            => 3,
            'commission_basis'           => 'net_profit',
        ]);

        return Employee::create([
            'full_name'     => 'أحمد محمد',
            'phone'         => '01000000000',
            'branch_id'     => $branch->id,
            'department_id' => $dept->id,
            'position_id'   => $pos->id,
            'hire_date'     => '2025-01-01',
            'basic_salary'  => 5000,
        ]);
    }
}
