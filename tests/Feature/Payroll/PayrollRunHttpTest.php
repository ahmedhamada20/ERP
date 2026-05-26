<?php

namespace Tests\Feature\Payroll;

use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use App\Models\PayrollRun;
use App\Models\Position;
use Database\Seeders\ChartOfAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpRolesForTesting;
use Tests\TestCase;

/**
 * HTTP-level coverage for PayrollRunController:
 *   - GET pages render with 200
 *   - POST workflow endpoints advance status
 *   - permission gating works
 *   - duplicate runs are blocked at validation time
 */
class PayrollRunHttpTest extends TestCase
{
    use RefreshDatabase, SetsUpRolesForTesting;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        $this->seed(ChartOfAccountsSeeder::class);
    }

    public function test_index_page_renders_for_accountant(): void
    {
        $this->actingAs($this->userWithRole('accountant'));

        $this->get(route('admin.hr.payroll.runs.index'))
            ->assertOk()
            ->assertSee('إدارة الرواتب');
    }

    public function test_index_page_blocked_without_payroll_view_permission(): void
    {
        // booking_staff doesn't have payroll permissions
        $this->actingAs($this->userWithRole('booking-staff'));

        $this->get(route('admin.hr.payroll.runs.index'))
            ->assertForbidden();
    }

    public function test_create_page_renders(): void
    {
        $this->actingAs($this->userWithRole('accountant'));

        $this->get(route('admin.hr.payroll.runs.create'))
            ->assertOk()
            ->assertSee('إنشاء دورة رواتب');
    }

    public function test_store_creates_draft_run(): void
    {
        $this->actingAs($this->userWithRole('accountant'));
        $branch = $this->mainBranch();

        $this->post(route('admin.hr.payroll.runs.store'), [
            'branch_id'    => $branch->id,
            'period_year'  => 2026,
            'period_month' => 5,
            'payment_date' => '2026-05-28',
        ])->assertRedirect();

        $this->assertDatabaseHas('payroll_runs', [
            'branch_id'    => $branch->id,
            'period_year'  => 2026,
            'period_month' => 5,
            'status'       => PayrollRun::STATUS_DRAFT,
        ]);
    }

    public function test_store_rejects_duplicate_run_for_same_branch_and_period(): void
    {
        $this->actingAs($this->userWithRole('accountant'));
        $branch = $this->mainBranch();

        PayrollRun::create([
            'branch_id' => $branch->id, 'period_year' => 2026, 'period_month' => 5,
        ]);

        $this->post(route('admin.hr.payroll.runs.store'), [
            'branch_id'    => $branch->id,
            'period_year'  => 2026,
            'period_month' => 5,
        ])->assertSessionHasErrors('period_month');

        $this->assertSame(1, PayrollRun::query()->count(), 'no second row created');
    }

    public function test_store_allows_new_run_when_previous_is_cancelled(): void
    {
        $this->actingAs($this->userWithRole('accountant'));
        $branch = $this->mainBranch();

        PayrollRun::create([
            'branch_id' => $branch->id, 'period_year' => 2026, 'period_month' => 5,
            'status'    => PayrollRun::STATUS_CANCELLED,
        ]);

        $this->post(route('admin.hr.payroll.runs.store'), [
            'branch_id'    => $branch->id,
            'period_year'  => 2026,
            'period_month' => 5,
        ])->assertSessionHasNoErrors();

        $this->assertSame(2, PayrollRun::query()->count());
    }

    public function test_show_page_renders_for_a_run(): void
    {
        $this->actingAs($this->userWithRole('accountant'));
        $branch = $this->mainBranch();
        $run = PayrollRun::create([
            'branch_id' => $branch->id, 'period_year' => 2026, 'period_month' => 5,
        ]);

        $this->get(route('admin.hr.payroll.runs.show', $run))
            ->assertOk()
            ->assertSee($run->run_code)
            ->assertSee('مايو 2026');
    }

    public function test_calculate_endpoint_advances_status(): void
    {
        $this->actingAs($this->userWithRole('accountant'));
        $branch = $this->mainBranch();
        $this->makeEmployee($branch, basicSalary: 5000);

        $run = PayrollRun::create([
            'branch_id' => $branch->id, 'period_year' => 2026, 'period_month' => 5,
        ]);

        $this->post(route('admin.hr.payroll.runs.calculate', $run))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(PayrollRun::STATUS_CALCULATED, $run->fresh()->status);
        $this->assertSame(1, $run->fresh()->employees_count);
    }

    public function test_approve_endpoint_advances_status(): void
    {
        $this->actingAs($this->userWithRole('accountant'));
        $branch = $this->mainBranch();
        $this->makeEmployee($branch, basicSalary: 5000);

        $run = PayrollRun::create([
            'branch_id' => $branch->id, 'period_year' => 2026, 'period_month' => 5,
        ]);
        app(\App\Services\Payroll\PayrollService::class)->calculate($run);

        $this->post(route('admin.hr.payroll.runs.approve', $run))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(PayrollRun::STATUS_APPROVED, $run->fresh()->status);
    }

    public function test_post_endpoint_creates_journal_entry(): void
    {
        $this->actingAs($this->userWithRole('accountant'));
        $branch = $this->mainBranch();
        $this->makeEmployee($branch, basicSalary: 5000);

        $run = PayrollRun::create([
            'branch_id' => $branch->id, 'period_year' => 2026, 'period_month' => 5,
        ]);
        app(\App\Services\Payroll\PayrollService::class)->calculate($run);
        app(\App\Services\Payroll\PayrollService::class)->approve($run);

        $this->post(route('admin.hr.payroll.runs.post', $run))
            ->assertRedirect()
            ->assertSessionHas('success');

        $run = $run->fresh();
        $this->assertSame(PayrollRun::STATUS_POSTED, $run->status);
        $this->assertNotNull($run->journal_entry_id);
    }

    public function test_destroy_works_only_for_draft_runs(): void
    {
        $this->actingAs($this->userWithRole('accountant'));
        $branch = $this->mainBranch();

        $draft = PayrollRun::create([
            'branch_id' => $branch->id, 'period_year' => 2026, 'period_month' => 5,
        ]);

        $this->delete(route('admin.hr.payroll.runs.destroy', $draft))
            ->assertRedirect();

        $this->assertSoftDeleted('payroll_runs', ['id' => $draft->id]);
    }

    public function test_destroy_blocked_after_calculation(): void
    {
        $this->actingAs($this->userWithRole('accountant'));
        $branch = $this->mainBranch();
        $this->makeEmployee($branch, basicSalary: 5000);

        $run = PayrollRun::create([
            'branch_id' => $branch->id, 'period_year' => 2026, 'period_month' => 5,
        ]);
        app(\App\Services\Payroll\PayrollService::class)->calculate($run);

        $this->delete(route('admin.hr.payroll.runs.destroy', $run))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('payroll_runs', ['id' => $run->id, 'deleted_at' => null]);
    }

    public function test_cancel_requires_reason(): void
    {
        $this->actingAs($this->userWithRole('accountant'));
        $branch = $this->mainBranch();
        $this->makeEmployee($branch, basicSalary: 5000);

        $run = PayrollRun::create([
            'branch_id' => $branch->id, 'period_year' => 2026, 'period_month' => 5,
        ]);
        app(\App\Services\Payroll\PayrollService::class)->calculate($run);
        app(\App\Services\Payroll\PayrollService::class)->approve($run);
        app(\App\Services\Payroll\PayrollPostingService::class)->post($run);

        // No reason
        $this->post(route('admin.hr.payroll.runs.cancel', $run))
            ->assertSessionHasErrors('reason');

        // With reason
        $this->post(route('admin.hr.payroll.runs.cancel', $run), ['reason' => 'إعادة احتساب'])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(PayrollRun::STATUS_CANCELLED, $run->fresh()->status);
    }

    public function test_booking_staff_cannot_post_payroll(): void
    {
        $this->actingAs($this->userWithRole('booking-staff'));
        $branch = $this->mainBranch();
        $run = PayrollRun::create([
            'branch_id' => $branch->id, 'period_year' => 2026, 'period_month' => 5,
            'status'    => PayrollRun::STATUS_APPROVED,
        ]);

        $this->post(route('admin.hr.payroll.runs.post', $run))
            ->assertForbidden();
    }

    // ── Helpers ─────────────────────────────────────────────────────────

    private function mainBranch(): Branch
    {
        return Branch::main() ?? Branch::firstOrCreate(
            ['code' => 'BRN-001'],
            ['name' => 'الفرع الرئيسي', 'is_main' => true]
        );
    }

    private function makeEmployee(Branch $branch, float $basicSalary = 4000): Employee
    {
        $dept = Department::firstOrCreate(
            ['code' => 'DEP-001'],
            ['name' => 'الإدارة', 'branch_id' => $branch->id]
        );
        $pos = Position::firstOrCreate(
            ['code' => 'POS-001'],
            ['title' => 'موظف', 'department_id' => $dept->id]
        );

        return Employee::create([
            'full_name'    => 'موظف اختبار',
            'phone'        => '0100' . random_int(1000000, 9999999),
            'branch_id'    => $branch->id,
            'department_id'=> $dept->id,
            'position_id'  => $pos->id,
            'hire_date'    => '2025-01-01',
            'basic_salary' => $basicSalary,
        ]);
    }
}
