<?php

namespace Tests\Feature\Suppliers;

use App\Models\Account;
use App\Models\Supplier;
use App\Models\SupplierInvoice;
use App\Services\Accounting\VoucherService;
use App\Services\Suppliers\SupplierInvoiceService;
use App\Services\Suppliers\SupplierStatementReport;
use Database\Seeders\ChartOfAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpRolesForTesting;
use Tests\TestCase;

class SupplierStatementTest extends TestCase
{
    use RefreshDatabase, SetsUpRolesForTesting;

    private Supplier $hotel;
    private Account $cash;
    private Account $expense;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        $this->seed(ChartOfAccountsSeeder::class);

        $this->actingAs($this->userWithRole('accountant'));

        $this->hotel   = Supplier::factory()->ofType('hotel')->create(['name' => 'فندق ت']);
        $this->cash    = Account::where('code', '1111')->firstOrFail();
        $this->expense = Account::where('code', '511')->firstOrFail();
    }

    private function postInvoice(float $amount, ?string $date = null): SupplierInvoice
    {
        $invoice = SupplierInvoice::factory()->create([
            'supplier_id'        => $this->hotel->id,
            'expense_account_id' => $this->expense->id,
            'invoice_date'       => $date ?: now()->toDateString(),
            'amount'             => $amount,
            'tax_amount'         => 0,
        ]);
        return app(SupplierInvoiceService::class)->post($invoice);
    }

    private function postPayment(float $amount, ?string $date = null, ?string $invoiceId = null): void
    {
        app(VoucherService::class)->create([
            'type'                => 'payment',
            'date'                => $date ?: now()->toDateString(),
            'cash_account_id'     => $this->cash->id,
            'counter_account_id'  => Account::where('code', '2111')->firstOrFail()->id,
            'supplier_id'         => $this->hotel->id,
            'supplier_invoice_id' => $invoiceId,
            'party_name'          => $this->hotel->name,
            'currency'            => 'EGP',
            'amount'              => $amount,
            'exchange_rate'       => 1,
            'description'         => 'سداد',
        ]);
    }

    /* ──────────────────────────────────────────────────────────
       Balance arithmetic
       ────────────────────────────────────────────────────────── */

    public function test_empty_supplier_returns_zero_balances(): void
    {
        $data = app(SupplierStatementReport::class)->build($this->hotel);

        $this->assertSame(0.0, $data['opening']);
        $this->assertSame(0.0, $data['total_invoices']);
        $this->assertSame(0.0, $data['total_payments']);
        $this->assertSame(0.0, $data['closing']);
        $this->assertCount(0, $data['lines']);
    }

    public function test_invoice_increases_running_balance(): void
    {
        $this->postInvoice(5000);

        $data = app(SupplierStatementReport::class)->build($this->hotel, now()->subDay(), now()->addDay());

        $this->assertSame(1, $data['lines']->count());
        $this->assertEqualsWithDelta(5000, $data['lines']->first()->running_balance, 0.01);
        $this->assertEqualsWithDelta(5000, $data['closing'], 0.01);
    }

    public function test_payment_decreases_running_balance(): void
    {
        $this->postInvoice(5000);
        $this->postPayment(2000);

        $data = app(SupplierStatementReport::class)->build($this->hotel, now()->subDay(), now()->addDay());

        $this->assertSame(2, $data['lines']->count());
        $this->assertEqualsWithDelta(3000, $data['closing'], 0.01);
    }

    public function test_opening_balance_includes_supplier_opening_field(): void
    {
        $this->hotel->update(['opening_balance' => 2500]);

        $this->postInvoice(1000);

        $data = app(SupplierStatementReport::class)->build($this->hotel, now()->subDay(), now()->addDay());

        // opening = 2500, period adds 1000 → closing = 3500
        $this->assertEqualsWithDelta(2500, $data['opening'], 0.01);
        $this->assertEqualsWithDelta(3500, $data['closing'], 0.01);
    }

    public function test_opening_balance_rolls_pre_period_activity_forward(): void
    {
        $old = $this->postInvoice(4000);
        $old->update(['invoice_date' => now()->subMonths(2)]);

        $this->postInvoice(1500); // today

        // Query the current month only
        $data = app(SupplierStatementReport::class)->build(
            $this->hotel,
            now()->startOfMonth(),
            now()->endOfMonth(),
        );

        // The old 4000 should appear in opening (not in lines)
        $this->assertEqualsWithDelta(4000, $data['opening'], 0.01);
        $this->assertSame(1, $data['lines']->count());
        $this->assertEqualsWithDelta(5500, $data['closing'], 0.01);
    }

    public function test_only_posted_transactions_are_included(): void
    {
        // Draft invoice — should NOT appear
        SupplierInvoice::factory()->create([
            'supplier_id'        => $this->hotel->id,
            'expense_account_id' => $this->expense->id,
            'amount'             => 999,
            'tax_amount'         => 0,
        ]); // not posted

        $data = app(SupplierStatementReport::class)->build($this->hotel);

        $this->assertCount(0, $data['lines']);
        $this->assertSame(0.0, $data['closing']);
    }

    public function test_lines_ordered_chronologically_with_invoices_before_payments_same_day(): void
    {
        $this->postPayment(500, now()->toDateString());      // same day
        $this->postInvoice(2000, now()->toDateString());     // same day
        $this->postInvoice(1000, now()->subDay()->toDateString()); // yesterday

        $data = app(SupplierStatementReport::class)->build(
            $this->hotel,
            now()->subDays(5),
            now()->addDay(),
        );

        $rows = $data['lines'];
        $this->assertSame(3, $rows->count());

        // First: yesterday's invoice
        $this->assertSame('invoice', $rows[0]->type);
        $this->assertEqualsWithDelta(1000, $rows[0]->running_balance, 0.01);

        // Then: today's invoice (invoice sorts before payment on same date)
        $this->assertSame('invoice', $rows[1]->type);
        $this->assertEqualsWithDelta(3000, $rows[1]->running_balance, 0.01);

        // Then: today's payment
        $this->assertSame('payment', $rows[2]->type);
        $this->assertEqualsWithDelta(2500, $rows[2]->running_balance, 0.01);

        $this->assertEqualsWithDelta(2500, $data['closing'], 0.01);
    }

    /* ──────────────────────────────────────────────────────────
       HTTP layer
       ────────────────────────────────────────────────────────── */

    public function test_picker_renders_without_supplier_id(): void
    {
        $this->get(route('admin.suppliers.statement'))
            ->assertOk()
            ->assertSee('اختر مورد');
    }

    public function test_detail_renders_with_supplier_id(): void
    {
        $this->postInvoice(1500);

        $this->get(route('admin.suppliers.statement', [
            'supplier_id' => $this->hotel->id,
            'from'        => now()->subDay()->format('Y-m-d'),
            'to'          => now()->addDay()->format('Y-m-d'),
        ]))->assertOk()
           ->assertSee($this->hotel->name)
           ->assertSee('1,500.00');
    }

    public function test_csv_download_works(): void
    {
        $this->postInvoice(800);

        $response = $this->get(route('admin.suppliers.statement.csv', ['supplier_id' => $this->hotel->id]));
        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
    }

    public function test_booking_staff_cannot_view_statement(): void
    {
        $this->actingAs($this->userWithRole('booking-staff'))
            ->get(route('admin.suppliers.statement'))
            ->assertForbidden();
    }
}
