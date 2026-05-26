<?php

namespace Tests\Feature\Suppliers;

use App\Models\Account;
use App\Models\Supplier;
use App\Models\SupplierInvoice;
use App\Services\Accounting\BalanceCalculator;
use App\Services\Accounting\TrialBalanceReport;
use App\Services\Accounting\VoucherService;
use App\Services\Suppliers\SupplierAgingReport;
use App\Services\Suppliers\SupplierInvoiceService;
use App\Services\Suppliers\SupplierStatementReport;
use Database\Seeders\ChartOfAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpRolesForTesting;
use Tests\TestCase;

/**
 * Sprint 3 integration tests — end-to-end scenarios that exercise the full
 * AP stack: supplier → invoice → payment voucher → statement → aging,
 * cross-checked against the GL.
 */
class SuppliersIntegrationTest extends TestCase
{
    use RefreshDatabase, SetsUpRolesForTesting;

    private Account $cash;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        $this->seed(ChartOfAccountsSeeder::class);
        $this->actingAs($this->userWithRole('super-admin'));

        $this->cash = Account::where('code', '1111')->firstOrFail();
    }

    /* ──────────────────────────────────────────────────────────
       Scenario 1: Single supplier full lifecycle
       ────────────────────────────────────────────────────────── */

    public function test_full_supplier_lifecycle_keeps_books_balanced(): void
    {
        // Setup: hotel supplier with payment terms 30 days
        $hotel    = Supplier::factory()->ofType('hotel')->create(['payment_terms_days' => 30]);
        $expense  = Account::where('code', '511')->firstOrFail();
        $supParent = Account::where('code', '2111')->firstOrFail();

        // Day -60: invoice for 10,000 (with 1,400 VAT) = 11,400 total
        $invoice1 = SupplierInvoice::factory()->create([
            'supplier_id'        => $hotel->id,
            'expense_account_id' => $expense->id,
            'invoice_date'       => now()->subDays(60),
            'due_date'           => now()->subDays(30),
            'amount'             => 10000,
            'tax_amount'         => 1400,
        ]);
        app(SupplierInvoiceService::class)->post($invoice1);

        // Day -20: invoice for 5,000 (no VAT)
        $invoice2 = SupplierInvoice::factory()->create([
            'supplier_id'        => $hotel->id,
            'expense_account_id' => $expense->id,
            'invoice_date'       => now()->subDays(20),
            'due_date'           => now()->addDays(10),
            'amount'             => 5000,
            'tax_amount'         => 0,
        ]);
        app(SupplierInvoiceService::class)->post($invoice2);

        // Day -10: payment 6,000
        app(VoucherService::class)->create([
            'type'                => 'payment',
            'date'                => now()->subDays(10)->toDateString(),
            'cash_account_id'     => $this->cash->id,
            'counter_account_id'  => $supParent->id,
            'supplier_id'         => $hotel->id,
            'party_name'          => $hotel->name,
            'currency'            => 'EGP',
            'amount'              => 6000,
            'exchange_rate'       => 1,
            'description'         => 'سداد جزئي',
        ]);

        // ── Cross-check 1: GL supplier parent (2111) balance ──
        // Invoices added: 11,400 + 5,000 = 16,400
        // Payments:        6,000
        // Net owed:        10,400
        $glBalance = app(BalanceCalculator::class)->naturalBalance($supParent->fresh());
        $this->assertEqualsWithDelta(10_400, $glBalance, 0.01);

        // ── Cross-check 2: Supplier statement closing balance ──
        $stmt = app(SupplierStatementReport::class)->build(
            $hotel->fresh(),
            now()->subDays(90),
            now(),
        );
        $this->assertEqualsWithDelta(10_400, $stmt['closing'], 0.01,
            'Statement closing must match GL parent balance');
        $this->assertSame(3, $stmt['lines']->count()); // 2 invoices + 1 payment

        // ── Cross-check 3: Aging — invoice1 (60 days overdue) and invoice2 (current) ──
        $aging = app(SupplierAgingReport::class)->build();
        $this->assertSame(1, $aging['rows']->count());
        $row = $aging['rows'][0];

        // FIFO: payment 6000 pays down invoice1 (11400 - 6000 = 5400 remaining, 30 days overdue → d_1_30)
        // invoice2 untouched = 5000 in current
        $this->assertEqualsWithDelta(10_400, $row['outstanding'], 0.01);
        $this->assertEqualsWithDelta(5_400,  $row['d_1_30'], 0.01);
        $this->assertEqualsWithDelta(5_000,  $row['current'], 0.01);

        // ── Cross-check 4: Trial Balance still balanced ──
        $tb = app(TrialBalanceReport::class)->build();
        $this->assertTrue($tb['totals']['balanced'],
            "TB unbalanced: DR={$tb['totals']['debit']} CR={$tb['totals']['credit']}");
    }

    /* ──────────────────────────────────────────────────────────
       Scenario 2: Multi-supplier subsidiary equals GL
       ────────────────────────────────────────────────────────── */

    public function test_sum_of_supplier_balances_equals_gl_parent(): void
    {
        $hotelA = Supplier::factory()->ofType('hotel')->create();
        $hotelB = Supplier::factory()->ofType('hotel')->create();
        $expense = Account::where('code', '511')->firstOrFail();
        $supParent = Account::where('code', '2111')->firstOrFail();

        // Hotel A: 5000 invoice, 2000 paid → balance 3000
        $invA = SupplierInvoice::factory()->create([
            'supplier_id' => $hotelA->id, 'expense_account_id' => $expense->id,
            'amount' => 5000, 'tax_amount' => 0,
        ]);
        app(SupplierInvoiceService::class)->post($invA);

        app(VoucherService::class)->create([
            'type' => 'payment', 'date' => now()->toDateString(),
            'cash_account_id' => $this->cash->id, 'counter_account_id' => $supParent->id,
            'supplier_id' => $hotelA->id, 'party_name' => $hotelA->name,
            'currency' => 'EGP', 'amount' => 2000, 'exchange_rate' => 1,
            'description' => 'payment A',
        ]);

        // Hotel B: 8000 invoice, no payment → balance 8000
        $invB = SupplierInvoice::factory()->create([
            'supplier_id' => $hotelB->id, 'expense_account_id' => $expense->id,
            'amount' => 8000, 'tax_amount' => 0,
        ]);
        app(SupplierInvoiceService::class)->post($invB);

        // Sum of subsidiary balances
        $stmtA = app(SupplierStatementReport::class)->build($hotelA, now()->subMonth(), now()->addDay());
        $stmtB = app(SupplierStatementReport::class)->build($hotelB, now()->subMonth(), now()->addDay());
        $subsidiaryTotal = $stmtA['closing'] + $stmtB['closing'];

        // GL parent balance
        $glBalance = app(BalanceCalculator::class)->naturalBalance($supParent->fresh());

        $this->assertEqualsWithDelta(11_000, $subsidiaryTotal, 0.01);
        $this->assertEqualsWithDelta($glBalance, $subsidiaryTotal, 0.01,
            'Subsidiary ledger total must equal GL parent balance (control account principle)');
    }

    /* ──────────────────────────────────────────────────────────
       Scenario 3: Multi-currency supplier
       ────────────────────────────────────────────────────────── */

    public function test_foreign_currency_supplier_translates_to_egp_correctly(): void
    {
        $hotel = Supplier::factory()->ofType('hotel')->create([
            'currency' => 'SAR',
            'payment_terms_days' => 30,
        ]);
        $expense = Account::where('code', '511')->firstOrFail();

        // SAR 1000 with 8 EGP/SAR rate → 8000 EGP
        $invoice = SupplierInvoice::factory()->create([
            'supplier_id'        => $hotel->id,
            'expense_account_id' => $expense->id,
            'currency'           => 'SAR',
            'amount'             => 1000,
            'tax_amount'         => 0,
            'exchange_rate'      => 8,
        ]);
        app(SupplierInvoiceService::class)->post($invoice);

        $this->assertEqualsWithDelta(8000, (float) $invoice->fresh()->amount_egp, 0.01);

        $supParent = Account::where('code', '2111')->firstOrFail();
        $glBalance = app(BalanceCalculator::class)->naturalBalance($supParent->fresh());
        $this->assertEqualsWithDelta(8000, $glBalance, 0.01);

        // Pay 500 SAR at rate 8 → 4000 EGP
        app(VoucherService::class)->create([
            'type'                => 'payment',
            'date'                => now()->toDateString(),
            'cash_account_id'     => $this->cash->id,
            'counter_account_id'  => $supParent->id,
            'supplier_id'         => $hotel->id,
            'supplier_invoice_id' => $invoice->id,
            'party_name'          => $hotel->name,
            'currency'            => 'SAR',
            'amount'              => 500,
            'exchange_rate'       => 8,
            'description'         => 'partial payment',
        ]);

        // Net balance: 8000 - 4000 = 4000 EGP
        $this->assertEqualsWithDelta(4000, app(BalanceCalculator::class)->naturalBalance($supParent->fresh()), 0.01);

        // Trial balance still balanced
        $tb = app(TrialBalanceReport::class)->build();
        $this->assertTrue($tb['totals']['balanced']);
    }

    /* ──────────────────────────────────────────────────────────
       Scenario 4: Cancel invoice → reverse everything
       ────────────────────────────────────────────────────────── */

    public function test_cancel_invoice_removes_from_all_reports(): void
    {
        $hotel = Supplier::factory()->ofType('hotel')->create();
        $expense = Account::where('code', '511')->firstOrFail();
        $supParent = Account::where('code', '2111')->firstOrFail();

        $invoice = SupplierInvoice::factory()->create([
            'supplier_id' => $hotel->id, 'expense_account_id' => $expense->id,
            'amount' => 3000, 'tax_amount' => 0,
        ]);
        app(SupplierInvoiceService::class)->post($invoice);

        $this->assertEqualsWithDelta(3000, app(BalanceCalculator::class)->naturalBalance($supParent->fresh()), 0.01);

        app(SupplierInvoiceService::class)->cancel($invoice->fresh(), 'تم إلغاء الحجز');

        // Everything zero now
        $this->assertEqualsWithDelta(0, app(BalanceCalculator::class)->naturalBalance($supParent->fresh()), 0.01);

        $stmt = app(SupplierStatementReport::class)->build($hotel->fresh(), now()->subMonth(), now()->addDay());
        $this->assertEqualsWithDelta(0, $stmt['closing'], 0.01);

        $aging = app(SupplierAgingReport::class)->build();
        $this->assertCount(0, $aging['rows']);

        // GL still balanced
        $tb = app(TrialBalanceReport::class)->build();
        $this->assertTrue($tb['totals']['balanced']);
    }

    /* ──────────────────────────────────────────────────────────
       Scenario 5: Different supplier types route to different parents
       ────────────────────────────────────────────────────────── */

    public function test_supplier_types_keep_parents_segregated(): void
    {
        $hotel     = Supplier::factory()->ofType('hotel')->create();
        $airline   = Supplier::factory()->ofType('airline')->create();
        $transport = Supplier::factory()->ofType('transport')->create();

        foreach ([
            [$hotel,     '511', '2111', 1000],
            [$airline,   '512', '2112', 2000],
            [$transport, '514', '2113', 1500],
        ] as [$supplier, $expCode, $parentCode, $amount]) {
            $invoice = SupplierInvoice::factory()->create([
                'supplier_id'        => $supplier->id,
                'expense_account_id' => Account::where('code', $expCode)->firstOrFail()->id,
                'amount'             => $amount, 'tax_amount' => 0,
            ]);
            app(SupplierInvoiceService::class)->post($invoice);
        }

        // Each parent account holds only its own supplier type
        $this->assertEqualsWithDelta(1000, app(BalanceCalculator::class)->naturalBalance(Account::where('code', '2111')->first()), 0.01);
        $this->assertEqualsWithDelta(2000, app(BalanceCalculator::class)->naturalBalance(Account::where('code', '2112')->first()), 0.01);
        $this->assertEqualsWithDelta(1500, app(BalanceCalculator::class)->naturalBalance(Account::where('code', '2113')->first()), 0.01);

        // Aging shows all 3 suppliers
        $aging = app(SupplierAgingReport::class)->build();
        $this->assertSame(3, $aging['rows']->count());
        $this->assertEqualsWithDelta(4500, $aging['grand_total'], 0.01);
    }

    /* ──────────────────────────────────────────────────────────
       Scenario 6: Payment without supplier_id (legacy/generic) still works
       ────────────────────────────────────────────────────────── */

    public function test_generic_payment_does_not_pollute_supplier_subsidiary(): void
    {
        $hotel = Supplier::factory()->ofType('hotel')->create();
        $expense = Account::where('code', '511')->firstOrFail();
        $supParent = Account::where('code', '2111')->firstOrFail();
        $rentExpense = Account::where('code', '522')->firstOrFail();

        // 1 supplier invoice on hotels
        $invoice = SupplierInvoice::factory()->create([
            'supplier_id' => $hotel->id, 'expense_account_id' => $expense->id,
            'amount' => 5000, 'tax_amount' => 0,
        ]);
        app(SupplierInvoiceService::class)->post($invoice);

        // Generic rent payment (no supplier link)
        app(VoucherService::class)->create([
            'type' => 'payment', 'date' => now()->toDateString(),
            'cash_account_id' => $this->cash->id,
            'counter_account_id' => $rentExpense->id,
            'party_name' => 'مالك المبنى',
            'currency' => 'EGP', 'amount' => 3000, 'exchange_rate' => 1,
            'description' => 'إيجار',
        ]);

        // Hotel supplier statement: should NOT include the rent payment
        $stmt = app(SupplierStatementReport::class)->build($hotel->fresh(), now()->subMonth(), now()->addDay());
        $this->assertEqualsWithDelta(5000, $stmt['closing'], 0.01,
            'Rent payment without supplier link must not affect supplier balance');
        $this->assertSame(1, $stmt['lines']->count()); // only the invoice

        // GL parent (2111) untouched by rent
        $this->assertEqualsWithDelta(5000, app(BalanceCalculator::class)->naturalBalance($supParent->fresh()), 0.01);

        // But the rent shows in GL expense balance
        $this->assertEqualsWithDelta(3000, app(BalanceCalculator::class)->naturalBalance($rentExpense->fresh()), 0.01);

        // Trial balance still balanced
        $tb = app(TrialBalanceReport::class)->build();
        $this->assertTrue($tb['totals']['balanced']);
    }
}
