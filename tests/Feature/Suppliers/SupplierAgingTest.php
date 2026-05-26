<?php

namespace Tests\Feature\Suppliers;

use App\Models\Account;
use App\Models\Supplier;
use App\Models\SupplierInvoice;
use App\Services\Accounting\VoucherService;
use App\Services\Suppliers\SupplierAgingReport;
use App\Services\Suppliers\SupplierInvoiceService;
use Database\Seeders\ChartOfAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpRolesForTesting;
use Tests\TestCase;

/**
 * Validates the FIFO payment allocation + age-bucketing logic in the
 * Suppliers Aging report.
 */
class SupplierAgingTest extends TestCase
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

        $this->hotel   = Supplier::factory()->ofType('hotel')->create([
            'name' => 'فندق ت',
            'payment_terms_days' => 30,
        ]);
        $this->cash    = Account::where('code', '1111')->firstOrFail();
        $this->expense = Account::where('code', '511')->firstOrFail();
    }

    private function postInvoice(float $amount, string $invoiceDate, ?string $dueDate = null): SupplierInvoice
    {
        $invoice = SupplierInvoice::factory()->create([
            'supplier_id'        => $this->hotel->id,
            'expense_account_id' => $this->expense->id,
            'invoice_date'       => $invoiceDate,
            'due_date'           => $dueDate,
            'amount'             => $amount,
            'tax_amount'         => 0,
        ]);
        return app(SupplierInvoiceService::class)->post($invoice);
    }

    private function postPayment(float $amount, string $date): void
    {
        app(VoucherService::class)->create([
            'type'                => 'payment',
            'date'                => $date,
            'cash_account_id'     => $this->cash->id,
            'counter_account_id'  => Account::where('code', '2111')->firstOrFail()->id,
            'supplier_id'         => $this->hotel->id,
            'party_name'          => $this->hotel->name,
            'currency'            => 'EGP',
            'amount'              => $amount,
            'exchange_rate'       => 1,
            'description'         => 'سداد',
        ]);
    }

    /* ──────────────────────────────────────────────────────────
       Empty / zero cases
       ────────────────────────────────────────────────────────── */

    public function test_supplier_without_invoices_does_not_appear(): void
    {
        $data = app(SupplierAgingReport::class)->build();
        $this->assertCount(0, $data['rows']);
        $this->assertSame(0.0, $data['grand_total']);
    }

    public function test_fully_paid_supplier_does_not_appear(): void
    {
        $this->postInvoice(1000, now()->subDays(60)->toDateString());
        $this->postPayment(1000, now()->subDays(30)->toDateString());

        $data = app(SupplierAgingReport::class)->build();
        $this->assertCount(0, $data['rows']);
    }

    /* ──────────────────────────────────────────────────────────
       Bucketing
       ────────────────────────────────────────────────────────── */

    public function test_invoice_not_yet_due_goes_to_current_bucket(): void
    {
        // invoice today, due in 30 days
        $this->postInvoice(500, now()->toDateString(), now()->addDays(30)->toDateString());

        $data = app(SupplierAgingReport::class)->build();
        $this->assertCount(1, $data['rows']);
        $row = $data['rows'][0];

        $this->assertEqualsWithDelta(500, $row['current'], 0.01);
        $this->assertEqualsWithDelta(0,   $row['d_1_30'],  0.01);
        $this->assertEqualsWithDelta(500, $row['outstanding'], 0.01);
    }

    public function test_invoice_overdue_15_days_goes_to_1_30_bucket(): void
    {
        // due 15 days ago
        $this->postInvoice(800, now()->subDays(45)->toDateString(), now()->subDays(15)->toDateString());

        $data = app(SupplierAgingReport::class)->build();
        $row = $data['rows'][0];
        $this->assertEqualsWithDelta(800, $row['d_1_30'], 0.01);
    }

    public function test_invoice_overdue_70_days_goes_to_61_90_bucket(): void
    {
        $this->postInvoice(1200, now()->subDays(120)->toDateString(), now()->subDays(70)->toDateString());

        $data = app(SupplierAgingReport::class)->build();
        $row = $data['rows'][0];
        $this->assertEqualsWithDelta(1200, $row['d_61_90'], 0.01);
    }

    public function test_invoice_overdue_200_days_goes_to_120_plus_bucket(): void
    {
        $this->postInvoice(700, now()->subDays(300)->toDateString(), now()->subDays(200)->toDateString());

        $data = app(SupplierAgingReport::class)->build();
        $row = $data['rows'][0];
        $this->assertEqualsWithDelta(700, $row['d_120_plus'], 0.01);
    }

    public function test_due_date_falls_back_to_invoice_date_plus_payment_terms(): void
    {
        // No due_date — uses invoice_date + 30 days (supplier's payment_terms_days)
        // invoice 50 days ago → effective due 20 days ago → overdue 20 days → 1-30 bucket
        $this->postInvoice(900, now()->subDays(50)->toDateString());

        $data = app(SupplierAgingReport::class)->build();
        $row = $data['rows'][0];
        $this->assertEqualsWithDelta(900, $row['d_1_30'], 0.01);
    }

    /* ──────────────────────────────────────────────────────────
       FIFO payment allocation
       ────────────────────────────────────────────────────────── */

    public function test_payment_pays_down_oldest_invoice_first(): void
    {
        // 2 invoices: old (75 days overdue → bucket 61-90) + new (current)
        $this->postInvoice(1000, now()->subDays(120)->toDateString(), now()->subDays(75)->toDateString()); // 61-90 bucket
        $this->postInvoice(600,  now()->toDateString(), now()->addDays(30)->toDateString());               // current

        // Pay 700 — applies to oldest first: 1000 - 700 = 300 remaining on old invoice
        $this->postPayment(700, now()->toDateString());

        $data = app(SupplierAgingReport::class)->build();
        $row = $data['rows'][0];

        // Old invoice: 1000 - 700 = 300 remaining in 61-90
        $this->assertEqualsWithDelta(300, $row['d_61_90'], 0.01);
        // New invoice: 600 untouched, in current
        $this->assertEqualsWithDelta(600, $row['current'], 0.01);
        // Total = 900
        $this->assertEqualsWithDelta(900, $row['outstanding'], 0.01);
    }

    public function test_payment_overflows_to_next_invoice(): void
    {
        $this->postInvoice(500,  now()->subDays(60)->toDateString(), now()->subDays(30)->toDateString());
        $this->postInvoice(1000, now()->toDateString(), now()->addDays(30)->toDateString());

        // Pay 800 — clears 500 old + applies 300 to new (leaving 700 on new)
        $this->postPayment(800, now()->toDateString());

        $data = app(SupplierAgingReport::class)->build();
        $row = $data['rows'][0];

        $this->assertEqualsWithDelta(0,   $row['d_1_30'], 0.01); // old fully cleared
        $this->assertEqualsWithDelta(700, $row['current'], 0.01); // new partially paid
    }

    public function test_opening_balance_lands_in_120_plus_bucket(): void
    {
        $this->hotel->update(['opening_balance' => 2500]);

        $data = app(SupplierAgingReport::class)->build();
        $row = $data['rows'][0];

        $this->assertEqualsWithDelta(2500, $row['d_120_plus'], 0.01);
        $this->assertEqualsWithDelta(2500, $row['outstanding'], 0.01);
    }

    public function test_payment_clears_opening_balance_first(): void
    {
        $this->hotel->update(['opening_balance' => 1000]);
        $this->postInvoice(500, now()->toDateString(), now()->addDays(30)->toDateString());

        // Pay 1200 — clears opening 1000 + applies 200 to invoice
        $this->postPayment(1200, now()->toDateString());

        $data = app(SupplierAgingReport::class)->build();
        $row = $data['rows'][0];

        $this->assertEqualsWithDelta(0,   $row['d_120_plus'], 0.01); // opening cleared
        $this->assertEqualsWithDelta(300, $row['current'], 0.01);     // 500 - 200 = 300 remaining
    }

    /* ──────────────────────────────────────────────────────────
       Multi-supplier totals
       ────────────────────────────────────────────────────────── */

    public function test_grand_total_sums_across_suppliers(): void
    {
        // Supplier 1: hotel — 1000 current
        $this->postInvoice(1000, now()->toDateString(), now()->addDays(30)->toDateString());

        // Supplier 2: airline — 2000 current
        $airline = Supplier::factory()->ofType('airline')->create();
        $airlineInvoice = SupplierInvoice::factory()->create([
            'supplier_id'        => $airline->id,
            'expense_account_id' => Account::where('code', '512')->firstOrFail()->id,
            'invoice_date'       => now()->toDateString(),
            'due_date'           => now()->addDays(30)->toDateString(),
            'amount'             => 2000, 'tax_amount' => 0,
        ]);
        app(SupplierInvoiceService::class)->post($airlineInvoice);

        $data = app(SupplierAgingReport::class)->build();

        $this->assertSame(2, $data['rows']->count());
        $this->assertEqualsWithDelta(3000, $data['grand_total'], 0.01);
        $this->assertEqualsWithDelta(3000, $data['totals']['current'], 0.01);
    }

    /* ──────────────────────────────────────────────────────────
       HTTP layer
       ────────────────────────────────────────────────────────── */

    public function test_aging_page_renders(): void
    {
        $this->postInvoice(500, now()->subDays(60)->toDateString(), now()->subDays(30)->toDateString());

        $this->get(route('admin.suppliers.aging'))
            ->assertOk()
            ->assertSee('أعمار ديون الموردين')
            ->assertSee($this->hotel->name);
    }

    public function test_csv_download_works(): void
    {
        $this->postInvoice(500, now()->toDateString());

        $response = $this->get(route('admin.suppliers.aging.csv'));
        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
    }

    public function test_booking_staff_cannot_view_aging(): void
    {
        $this->actingAs($this->userWithRole('booking-staff'))
            ->get(route('admin.suppliers.aging'))
            ->assertForbidden();
    }
}
