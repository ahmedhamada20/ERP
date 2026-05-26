<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use Database\Seeders\ChartOfAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpRolesForTesting;
use Tests\TestCase;

class AccountManagementTest extends TestCase
{
    use RefreshDatabase, SetsUpRolesForTesting;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        $this->seed(ChartOfAccountsSeeder::class);
    }

    public function test_accountant_can_view_chart_of_accounts(): void
    {
        $this->actingAs($this->userWithRole('accountant'))
            ->get(route('admin.accounting.accounts.index'))
            ->assertOk()
            ->assertSee('دليل الحسابات');
    }

    public function test_accountant_can_create_new_account(): void
    {
        $parent = Account::where('code', '113')->first(); // عملاء

        $this->actingAs($this->userWithRole('accountant'))
            ->post(route('admin.accounting.accounts.store'), [
                'code'      => '1199',
                'name'      => 'عميل اختبار',
                'type'      => 'asset',
                'sub_type'  => 'current_asset',
                'parent_id' => $parent->id,
                'is_group'  => false,
                'is_active' => true,
                'currency'  => 'EGP',
            ])
            ->assertRedirect(route('admin.accounting.accounts.index'));

        $this->assertDatabaseHas('accounts', ['code' => '1199', 'name' => 'عميل اختبار']);
    }

    public function test_duplicate_code_is_rejected(): void
    {
        $this->actingAs($this->userWithRole('accountant'))
            ->post(route('admin.accounting.accounts.store'), [
                'code'      => '1111', // already exists (الخزينة الرئيسية)
                'name'      => 'خزينة مكررة',
                'type'      => 'asset',
                'currency'  => 'EGP',
            ])
            ->assertSessionHasErrors(['code']);
    }

    public function test_non_numeric_code_is_rejected(): void
    {
        $this->actingAs($this->userWithRole('accountant'))
            ->post(route('admin.accounting.accounts.store'), [
                'code'      => 'ABC-1',
                'name'      => 'كود غير صحيح',
                'type'      => 'asset',
                'currency'  => 'EGP',
            ])
            ->assertSessionHasErrors(['code']);
    }

    public function test_account_type_must_match_parent_type(): void
    {
        $assetParent = Account::where('code', '11')->first(); // أصول متداولة

        $this->actingAs($this->userWithRole('accountant'))
            ->post(route('admin.accounting.accounts.store'), [
                'code'      => '1999',
                'name'      => 'تصنيف غلط',
                'type'      => 'expense', // expense under asset parent — invalid
                'parent_id' => $assetParent->id,
                'currency'  => 'EGP',
            ])
            ->assertSessionHasErrors(['type']);
    }

    public function test_parent_must_be_a_group_account(): void
    {
        $leaf = Account::where('code', '1111')->first(); // postable cashbox

        $this->actingAs($this->userWithRole('accountant'))
            ->post(route('admin.accounting.accounts.store'), [
                'code'      => '11111',
                'name'      => 'تحت حساب تفصيلي',
                'type'      => 'asset',
                'parent_id' => $leaf->id, // leaf, not a group
                'currency'  => 'EGP',
            ])
            ->assertSessionHasErrors(['parent_id']);
    }

    public function test_circular_parent_is_rejected(): void
    {
        $parent = Account::where('code', '113')->first(); // عملاء (group)
        $child  = Account::where('code', '1131')->first(); // عملاء حجوزات دينية

        // Try to make 113's parent = 1131 (which is its own child) → cycle
        $this->actingAs($this->userWithRole('accountant'))
            ->put(route('admin.accounting.accounts.update', $parent), [
                'code'      => $parent->code,
                'name'      => $parent->name,
                'type'      => $parent->type,
                'parent_id' => $child->id,
                'currency'  => 'EGP',
            ])
            ->assertSessionHasErrors(['parent_id']);
    }

    public function test_system_account_cannot_be_deleted(): void
    {
        $systemAccount = Account::where('code', '1111')->first(); // is_system
        $this->assertTrue($systemAccount->is_system);

        $this->actingAs($this->userWithRole('accountant'))
            ->delete(route('admin.accounting.accounts.destroy', $systemAccount))
            ->assertStatus(422);

        $this->assertNotNull($systemAccount->fresh());
    }

    public function test_account_with_children_cannot_be_deleted(): void
    {
        // Create a non-system parent with a child first
        $parent = Account::create([
            'code'     => '1999',
            'name'     => 'أب مؤقت',
            'type'     => 'asset',
            'is_group' => true,
            'is_system'=> false,
            'currency' => 'EGP',
        ]);
        Account::create([
            'code'      => '19991',
            'name'      => 'ابن',
            'type'      => 'asset',
            'parent_id' => $parent->id,
            'is_system' => false,
            'currency'  => 'EGP',
        ]);

        $this->actingAs($this->userWithRole('accountant'))
            ->delete(route('admin.accounting.accounts.destroy', $parent))
            ->assertStatus(422);
    }

    public function test_booking_staff_cannot_access_chart_of_accounts(): void
    {
        $this->actingAs($this->userWithRole('booking-staff'))
            ->get(route('admin.accounting.accounts.index'))
            ->assertForbidden();
    }
}
