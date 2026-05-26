<?php

namespace Tests\Feature\Suppliers;

use App\Models\Account;
use App\Models\Supplier;
use App\Models\SupplierInvoice;
use App\Models\Voucher;
use App\Services\Accounting\BalanceCalculator;
use App\Services\Accounting\VoucherService;
use App\Services\Suppliers\SupplierInvoiceService;
use Database\Seeders\ChartOfAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpRolesForTesting;
use Tests\TestCase;

/**
 * Validates the Sprint 3 Step 5 wiring: a payment voucher can now be
 * targeted at a specific supplier (and optionally a specific invoice)
 * and the GL postings + linkage all flow through correctly.
 */
class SupplierPaymentVoucherTest extends TestCase
{
    use RefreshDatabase, SetsUpRolesForTesting;

    private Supplier $hotel;
    private Account $cash;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        $this->seed(ChartOfAccountsSeeder::class);

        $this->hotel = Supplier::factory()->ofType('hotel')->create();
        $this->cash  = Account::where('code', '1111')->firstOrFail();
    }

    /* ──────────────────────────────────────────────────────────
       Controller: supplier auto-fills counter_account
       ────────────────────────────────────────────────────────── */

    public function test_supplier_payment_routes_credit_to_supplier_parent_account(): void
    {
        $supplierParent = Account::where('code', '2111')->firstOrFail();

        $response = $this->actingAs($this->userWithRole('accountant'))
            ->post(route('admin.accounting.vouchers.payments.store'), [
                'date'               => now()->toDateString(),
                'cash_account_id'    => $this->cash->id,
                // Submit any postable account as counter — supplier_id should override
                'counter_account_id' => Account::where('code', '522')->firstOrFail()->id,
                'supplier_id'        => $this->hotel->id,
                'party_name'         => 'irrelevant',
                'currency'           => 'EGP',
                'amount'             => 1500,
                'description'        => 'سداد جزئي للفندق',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $voucher = Voucher::firstOrFail();
        $this->assertSame($supplierParent->id, $voucher->counter_account_id,
            'Counter account should auto-resolve to supplier parent (2111)');
        $this->assertSame($this->hotel->id, $voucher->supplier_id);
        $this->assertSame('supplier', $voucher->party_type);
    }

    public function test_supplier_payment_decreases_supplier_balance(): void
    {
        // First: create an invoice that makes us owe the supplier 5000
        $invoice = SupplierInvoice::factory()->create([
            'supplier_id'        => $this->hotel->id,
            'expense_account_id' => Account::where('code', '511')->firstOrFail()->id,
            'amount'             => 5000,
            'tax_amount'         => 0,
        ]);
        app(SupplierInvoiceService::class)->post($invoice);

        $supplierParent = Account::where('code', '2111')->firstOrFail();
        $this->assertEqualsWithDelta(5000, app(BalanceCalculator::class)->naturalBalance($supplierParent->fresh()), 0.01);

        // Pay 2000 against the supplier
        $this->actingAs($this->userWithRole('accountant'))
            ->post(route('admin.accounting.vouchers.payments.store'), [
                'date'               => now()->toDateString(),
                'cash_account_id'    => $this->cash->id,
                'counter_account_id' => $supplierParent->id, // will be overwritten anyway
                'supplier_id'        => $this->hotel->id,
                'party_name'         => $this->hotel->name,
                'currency'           => 'EGP',
                'amount'             => 2000,
                'description'        => 'سداد جزئي',
            ])
            ->assertSessionHasNoErrors();

        // Supplier balance now = 5000 - 2000 = 3000
        $this->assertEqualsWithDelta(3000, app(BalanceCalculator::class)->naturalBalance($supplierParent->fresh()), 0.01);
    }

    public function test_supplier_payment_can_link_to_specific_invoice(): void
    {
        $invoice = SupplierInvoice::factory()->create([
            'supplier_id'        => $this->hotel->id,
            'expense_account_id' => Account::where('code', '511')->firstOrFail()->id,
            'amount'             => 1500,
        ]);
        app(SupplierInvoiceService::class)->post($invoice);

        $this->actingAs($this->userWithRole('accountant'))
            ->post(route('admin.accounting.vouchers.payments.store'), [
                'date'                => now()->toDateString(),
                'cash_account_id'     => $this->cash->id,
                'counter_account_id'  => Account::where('code', '2111')->firstOrFail()->id,
                'supplier_id'         => $this->hotel->id,
                'supplier_invoice_id' => $invoice->id,
                'party_name'          => $this->hotel->name,
                'currency'            => 'EGP',
                'amount'              => 1500,
                'description'         => 'سداد كامل للفاتورة',
            ])
            ->assertSessionHasNoErrors();

        $voucher = Voucher::firstOrFail();
        $this->assertSame($invoice->id, $voucher->supplier_invoice_id);
    }

    /* ──────────────────────────────────────────────────────────
       Validation: invoice must belong to supplier
       ────────────────────────────────────────────────────────── */

    public function test_validation_rejects_invoice_from_different_supplier(): void
    {
        $otherSupplier = Supplier::factory()->ofType('hotel')->create();
        $invoiceOfOther = SupplierInvoice::factory()->create([
            'supplier_id'        => $otherSupplier->id,
            'expense_account_id' => Account::where('code', '511')->firstOrFail()->id,
            'amount'             => 500,
        ]);

        $this->actingAs($this->userWithRole('accountant'))
            ->post(route('admin.accounting.vouchers.payments.store'), [
                'date'                => now()->toDateString(),
                'cash_account_id'     => $this->cash->id,
                'counter_account_id'  => Account::where('code', '2111')->firstOrFail()->id,
                'supplier_id'         => $this->hotel->id,           // hotel A
                'supplier_invoice_id' => $invoiceOfOther->id,        // invoice belongs to hotel B
                'party_name'          => 'x',
                'currency'            => 'EGP',
                'amount'              => 100,
                'description'         => 'mismatch',
            ])
            ->assertSessionHasErrors(['supplier_invoice_id']);
    }

    public function test_validation_rejects_invoice_without_supplier(): void
    {
        $invoice = SupplierInvoice::factory()->create([
            'supplier_id'        => $this->hotel->id,
            'expense_account_id' => Account::where('code', '511')->firstOrFail()->id,
            'amount'             => 100,
        ]);

        $this->actingAs($this->userWithRole('accountant'))
            ->post(route('admin.accounting.vouchers.payments.store'), [
                'date'                => now()->toDateString(),
                'cash_account_id'     => $this->cash->id,
                'counter_account_id'  => Account::where('code', '2111')->firstOrFail()->id,
                'supplier_invoice_id' => $invoice->id, // no supplier_id
                'party_name'          => 'x',
                'currency'            => 'EGP',
                'amount'              => 100,
                'description'         => 'no supplier',
            ])
            ->assertSessionHasErrors(['supplier_id']);
    }

    /* ──────────────────────────────────────────────────────────
       Backward compatibility: non-supplier payments still work
       ────────────────────────────────────────────────────────── */

    public function test_payment_without_supplier_still_works_normally(): void
    {
        $rent = Account::where('code', '522')->firstOrFail();

        $this->actingAs($this->userWithRole('accountant'))
            ->post(route('admin.accounting.vouchers.payments.store'), [
                'date'               => now()->toDateString(),
                'cash_account_id'    => $this->cash->id,
                'counter_account_id' => $rent->id,
                'party_name'         => 'مالك المبنى',
                'currency'           => 'EGP',
                'amount'             => 2500,
                'description'        => 'إيجار',
            ])
            ->assertSessionHasNoErrors();

        $voucher = Voucher::firstOrFail();
        $this->assertNull($voucher->supplier_id);
        $this->assertSame($rent->id, $voucher->counter_account_id);
    }
}
