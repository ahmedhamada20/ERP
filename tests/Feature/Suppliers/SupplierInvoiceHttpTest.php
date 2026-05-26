<?php

namespace Tests\Feature\Suppliers;

use App\Models\Account;
use App\Models\Supplier;
use App\Models\SupplierInvoice;
use Database\Seeders\ChartOfAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpRolesForTesting;
use Tests\TestCase;

class SupplierInvoiceHttpTest extends TestCase
{
    use RefreshDatabase, SetsUpRolesForTesting;

    private Supplier $hotel;
    private Account $expenseHotels;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        $this->seed(ChartOfAccountsSeeder::class);

        $this->hotel         = Supplier::factory()->ofType('hotel')->create();
        $this->expenseHotels = Account::where('code', '511')->firstOrFail();
    }

    public function test_accountant_can_create_draft_invoice(): void
    {
        $this->actingAs($this->userWithRole('accountant'))
            ->post(route('admin.supplier_invoices.store'), [
                'supplier_id'        => $this->hotel->id,
                'expense_account_id' => $this->expenseHotels->id,
                'invoice_date'       => now()->toDateString(),
                'due_date'           => now()->addDays(30)->toDateString(),
                'description'        => 'فاتورة فندق مايو',
                'currency'           => 'EGP',
                'amount'             => 5000,
                'tax_amount'         => 700,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $invoice = SupplierInvoice::firstOrFail();
        $this->assertSame('draft', $invoice->status);
        $this->assertEqualsWithDelta(5700, (float) $invoice->amount_egp, 0.01);
    }

    public function test_post_immediately_creates_and_posts_invoice(): void
    {
        $this->actingAs($this->userWithRole('accountant'))
            ->post(route('admin.supplier_invoices.store'), [
                'supplier_id'        => $this->hotel->id,
                'expense_account_id' => $this->expenseHotels->id,
                'invoice_date'       => now()->toDateString(),
                'description'        => 'فاتورة وترحيل مباشر',
                'currency'           => 'EGP',
                'amount'             => 2000,
                'tax_amount'         => 0,
                'post_immediately'   => '1',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $invoice = SupplierInvoice::firstOrFail();
        $this->assertTrue($invoice->isPosted());
        $this->assertNotNull($invoice->journal_entry_id);
    }

    public function test_validation_rejects_due_date_before_invoice_date(): void
    {
        $this->actingAs($this->userWithRole('accountant'))
            ->post(route('admin.supplier_invoices.store'), [
                'supplier_id'        => $this->hotel->id,
                'expense_account_id' => $this->expenseHotels->id,
                'invoice_date'       => now()->toDateString(),
                'due_date'           => now()->subDays(10)->toDateString(),
                'description'        => 'تاريخ غلط',
                'currency'           => 'EGP',
                'amount'             => 100,
            ])
            ->assertSessionHasErrors(['due_date']);
    }

    public function test_validation_rejects_zero_amount(): void
    {
        $this->actingAs($this->userWithRole('accountant'))
            ->post(route('admin.supplier_invoices.store'), [
                'supplier_id'        => $this->hotel->id,
                'expense_account_id' => $this->expenseHotels->id,
                'invoice_date'       => now()->toDateString(),
                'description'        => 'صفر',
                'currency'           => 'EGP',
                'amount'             => 0,
            ])
            ->assertSessionHasErrors(['amount']);
    }

    public function test_draft_invoice_can_be_posted_via_http(): void
    {
        $invoice = SupplierInvoice::factory()->create([
            'supplier_id'        => $this->hotel->id,
            'expense_account_id' => $this->expenseHotels->id,
            'amount'             => 1500,
        ]);

        $this->actingAs($this->userWithRole('accountant'))
            ->post(route('admin.supplier_invoices.post', $invoice))
            ->assertRedirect();

        $this->assertTrue($invoice->fresh()->isPosted());
    }

    public function test_posted_invoice_can_be_cancelled_with_reason(): void
    {
        $invoice = SupplierInvoice::factory()->create([
            'supplier_id'        => $this->hotel->id,
            'expense_account_id' => $this->expenseHotels->id,
            'amount'             => 1000,
        ]);

        // post first
        $this->actingAs($this->userWithRole('accountant'))
            ->post(route('admin.supplier_invoices.post', $invoice));

        // then cancel
        $this->actingAs($this->userWithRole('accountant'))
            ->post(route('admin.supplier_invoices.cancel', $invoice), [
                'cancellation_reason' => 'خطأ',
            ])
            ->assertRedirect();

        $this->assertTrue($invoice->fresh()->isCancelled());
    }

    public function test_posted_invoice_cannot_be_deleted(): void
    {
        $invoice = SupplierInvoice::factory()->create([
            'supplier_id'        => $this->hotel->id,
            'expense_account_id' => $this->expenseHotels->id,
            'amount'             => 800,
        ]);
        $this->actingAs($this->userWithRole('accountant'))
            ->post(route('admin.supplier_invoices.post', $invoice));

        $this->actingAs($this->userWithRole('accountant'))
            ->delete(route('admin.supplier_invoices.destroy', $invoice->fresh()))
            ->assertStatus(422);
    }

    public function test_draft_invoice_can_be_deleted(): void
    {
        $invoice = SupplierInvoice::factory()->create([
            'supplier_id'        => $this->hotel->id,
            'expense_account_id' => $this->expenseHotels->id,
        ]);

        $this->actingAs($this->userWithRole('accountant'))
            ->delete(route('admin.supplier_invoices.destroy', $invoice))
            ->assertOk();

        $this->assertNull($invoice->fresh());
    }

    public function test_booking_staff_cannot_view_invoices(): void
    {
        $this->actingAs($this->userWithRole('booking-staff'))
            ->get(route('admin.supplier_invoices.index'))
            ->assertForbidden();
    }

    public function test_show_page_renders_invoice(): void
    {
        $invoice = SupplierInvoice::factory()->create([
            'supplier_id'        => $this->hotel->id,
            'expense_account_id' => $this->expenseHotels->id,
            'amount'             => 1500,
            'description'        => 'فاتورة عرض اختبار',
        ]);

        $this->actingAs($this->userWithRole('accountant'))
            ->get(route('admin.supplier_invoices.show', $invoice))
            ->assertOk()
            ->assertSee('فاتورة عرض اختبار')
            ->assertSee($invoice->number);
    }
}
