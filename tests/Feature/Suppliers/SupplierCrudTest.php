<?php

namespace Tests\Feature\Suppliers;

use App\Models\Supplier;
use Database\Seeders\ChartOfAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpRolesForTesting;
use Tests\TestCase;

class SupplierCrudTest extends TestCase
{
    use RefreshDatabase, SetsUpRolesForTesting;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        $this->seed(ChartOfAccountsSeeder::class);
    }

    public function test_accountant_can_view_suppliers_index(): void
    {
        $this->actingAs($this->userWithRole('accountant'))
            ->get(route('admin.suppliers.index'))
            ->assertOk()
            ->assertSee('إدارة الموردين');
    }

    public function test_booking_staff_cannot_view_suppliers(): void
    {
        $this->actingAs($this->userWithRole('booking-staff'))
            ->get(route('admin.suppliers.index'))
            ->assertForbidden();
    }

    public function test_accountant_can_create_supplier(): void
    {
        $this->actingAs($this->userWithRole('accountant'))
            ->post(route('admin.suppliers.store'), [
                'name'               => 'فندق اختبار',
                'type'               => 'hotel',
                'currency'           => 'SAR',
                'country'            => 'السعودية',
                'payment_terms_days' => 45,
                'is_active'          => '1',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $supplier = Supplier::firstOrFail();
        $this->assertSame('فندق اختبار', $supplier->name);
        $this->assertSame('hotel', $supplier->type);
        $this->assertSame(45, $supplier->payment_terms_days);
    }

    public function test_duplicate_email_is_rejected(): void
    {
        Supplier::factory()->create(['email' => 'dup@example.com']);

        $this->actingAs($this->userWithRole('accountant'))
            ->post(route('admin.suppliers.store'), [
                'name'     => 'مكرر',
                'type'     => 'other',
                'currency' => 'EGP',
                'email'    => 'dup@example.com',
            ])
            ->assertSessionHasErrors(['email']);
    }

    public function test_duplicate_tax_number_is_rejected(): void
    {
        Supplier::factory()->create(['tax_number' => '123456789']);

        $this->actingAs($this->userWithRole('accountant'))
            ->post(route('admin.suppliers.store'), [
                'name'       => 'تاني',
                'type'       => 'other',
                'currency'   => 'EGP',
                'tax_number' => '123456789',
            ])
            ->assertSessionHasErrors(['tax_number']);
    }

    public function test_invalid_type_is_rejected(): void
    {
        $this->actingAs($this->userWithRole('accountant'))
            ->post(route('admin.suppliers.store'), [
                'name'     => 'مورد',
                'type'     => 'invalid_type',
                'currency' => 'EGP',
            ])
            ->assertSessionHasErrors(['type']);
    }

    public function test_show_page_renders_supplier_details(): void
    {
        $supplier = Supplier::factory()->create(['name' => 'مورد التجربة', 'opening_balance' => 1234.56]);

        $this->actingAs($this->userWithRole('accountant'))
            ->get(route('admin.suppliers.show', $supplier))
            ->assertOk()
            ->assertSee('مورد التجربة')
            ->assertSee('1,234.56');
    }

    public function test_update_persists_changes(): void
    {
        $supplier = Supplier::factory()->ofType('hotel')->create();

        $this->actingAs($this->userWithRole('accountant'))
            ->put(route('admin.suppliers.update', $supplier), [
                'name'               => 'الاسم بعد التعديل',
                'type'               => 'airline',
                'currency'           => 'EGP',
                'payment_terms_days' => 60,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $supplier->refresh();
        $this->assertSame('الاسم بعد التعديل', $supplier->name);
        $this->assertSame('airline', $supplier->type);
        $this->assertSame(60, $supplier->payment_terms_days);
    }

    public function test_delete_soft_deletes_supplier(): void
    {
        $supplier = Supplier::factory()->create();

        $this->actingAs($this->userWithRole('super-admin'))
            ->delete(route('admin.suppliers.destroy', $supplier))
            ->assertOk();

        $this->assertSoftDeleted('suppliers', ['id' => $supplier->id]);
    }

    public function test_accountant_lacks_delete_permission(): void
    {
        $supplier = Supplier::factory()->create();

        $this->actingAs($this->userWithRole('accountant'))
            ->delete(route('admin.suppliers.destroy', $supplier))
            ->assertForbidden();
    }
}
