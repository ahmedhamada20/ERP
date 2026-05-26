<?php

namespace Tests\Feature\Suppliers;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\Supplier;
use App\Models\SupplierInvoice;
use App\Services\Accounting\BalanceCalculator;
use App\Services\Suppliers\SupplierInvoiceService;
use Database\Seeders\ChartOfAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\Concerns\SetsUpRolesForTesting;
use Tests\TestCase;

class SupplierInvoiceServiceTest extends TestCase
{
    use RefreshDatabase, SetsUpRolesForTesting;

    private Supplier $hotel;
    private Account $expenseHotels;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        $this->seed(ChartOfAccountsSeeder::class);
        $this->actingAs($this->userWithRole('accountant'));

        $this->hotel         = Supplier::factory()->ofType('hotel')->create(['name' => 'فندق ت']);
        $this->expenseHotels = Account::where('code', '511')->firstOrFail();
    }

    /* ──────────────────────────────────────────────────────────
       Auto-number + saving hooks
       ────────────────────────────────────────────────────────── */

    public function test_number_auto_generated(): void
    {
        $invoice = SupplierInvoice::factory()->create();

        $year = date('Y');
        $this->assertStringStartsWith("SI-{$year}-", $invoice->number);
    }

    public function test_amount_egp_computed_for_egp_currency(): void
    {
        $invoice = SupplierInvoice::factory()->create([
            'currency'   => 'EGP',
            'amount'     => 800,
            'tax_amount' => 112,
        ]);

        // (800 + 112) * 1 = 912
        $this->assertEqualsWithDelta(912, (float) $invoice->amount_egp, 0.01);
    }

    public function test_amount_egp_computed_for_foreign_currency(): void
    {
        $invoice = SupplierInvoice::factory()->create([
            'currency'      => 'SAR',
            'amount'        => 100,
            'tax_amount'    => 15,
            'exchange_rate' => 8,
        ]);

        // (100 + 15) * 8 = 920
        $this->assertEqualsWithDelta(920, (float) $invoice->amount_egp, 0.01);
    }

    /* ──────────────────────────────────────────────────────────
       Posting flow
       ────────────────────────────────────────────────────────── */

    public function test_posting_invoice_creates_balanced_journal(): void
    {
        $invoice = SupplierInvoice::factory()->create([
            'supplier_id'        => $this->hotel->id,
            'expense_account_id' => $this->expenseHotels->id,
            'currency'           => 'EGP',
            'amount'             => 5000,
            'tax_amount'         => 0,
        ]);

        $posted = app(SupplierInvoiceService::class)->post($invoice);

        $this->assertTrue($posted->isPosted());
        $this->assertNotNull($posted->journal_entry_id);

        $entry = JournalEntry::findOrFail($posted->journal_entry_id);
        $this->assertTrue($entry->isPosted());
        $this->assertEqualsWithDelta(5000, (float) $entry->total_debit, 0.01);
        $this->assertEqualsWithDelta(5000, (float) $entry->total_credit, 0.01);
        $this->assertSame(2, $entry->lines->count()); // DR expense + CR supplier
    }

    public function test_posting_invoice_with_tax_creates_three_line_journal(): void
    {
        $invoice = SupplierInvoice::factory()->create([
            'supplier_id'        => $this->hotel->id,
            'expense_account_id' => $this->expenseHotels->id,
            'currency'           => 'EGP',
            'amount'             => 1000,
            'tax_amount'         => 140,
        ]);

        $posted = app(SupplierInvoiceService::class)->post($invoice);
        $entry  = JournalEntry::findOrFail($posted->journal_entry_id);

        $this->assertSame(3, $entry->lines->count()); // DR expense + DR VAT + CR supplier

        // Check VAT line goes to 2131
        $vatAccount = Account::where('code', '2131')->first();
        $vatLine    = $entry->lines->where('debit', '>', 0)->firstWhere('account_id', $vatAccount->id);
        $this->assertNotNull($vatLine);
        $this->assertEqualsWithDelta(140, (float) $vatLine->debit, 0.01);

        // Supplier credit = amount + tax
        $supplierParent = Account::where('code', '2111')->first();
        $supplierLine   = $entry->lines->where('credit', '>', 0)->firstWhere('account_id', $supplierParent->id);
        $this->assertEqualsWithDelta(1140, (float) $supplierLine->credit, 0.01);
    }

    public function test_posting_routes_supplier_credit_to_correct_parent_by_type(): void
    {
        $airline = Supplier::factory()->ofType('airline')->create();
        $expense = Account::where('code', '512')->firstOrFail(); // تكلفة الطيران

        $invoice = SupplierInvoice::factory()->create([
            'supplier_id'        => $airline->id,
            'expense_account_id' => $expense->id,
            'amount'             => 3000, 'tax_amount' => 0,
        ]);

        $posted = app(SupplierInvoiceService::class)->post($invoice);
        $entry  = JournalEntry::findOrFail($posted->journal_entry_id);

        // CR should go to 2112 (موردين طيران), not 2111
        $airlineParent = Account::where('code', '2112')->first();
        $creditLine = $entry->lines->where('credit', '>', 0)->first();
        $this->assertSame($airlineParent->id, $creditLine->account_id);
    }

    public function test_foreign_currency_invoice_posts_egp_amounts(): void
    {
        $invoice = SupplierInvoice::factory()->create([
            'supplier_id'        => $this->hotel->id,
            'expense_account_id' => $this->expenseHotels->id,
            'currency'           => 'SAR',
            'amount'             => 200,
            'tax_amount'         => 30,
            'exchange_rate'      => 8,
        ]);

        $posted = app(SupplierInvoiceService::class)->post($invoice);
        $entry  = JournalEntry::findOrFail($posted->journal_entry_id);

        // expense = 200 * 8 = 1600 ; vat = 30 * 8 = 240 ; total = 1840
        $this->assertEqualsWithDelta(1840, (float) $entry->total_debit, 0.01);
        $this->assertEqualsWithDelta(1840, (float) $entry->total_credit, 0.01);
    }

    public function test_supplier_parent_balance_increases_after_posting(): void
    {
        $invoice = SupplierInvoice::factory()->create([
            'supplier_id'        => $this->hotel->id,
            'expense_account_id' => $this->expenseHotels->id,
            'amount'             => 2500, 'tax_amount' => 0,
        ]);

        app(SupplierInvoiceService::class)->post($invoice);

        $supplierParent = Account::where('code', '2111')->first();
        $balance = app(BalanceCalculator::class)->naturalBalance($supplierParent->fresh());
        $this->assertEqualsWithDelta(2500, $balance, 0.01); // we owe 2500 to hotels
    }

    /* ──────────────────────────────────────────────────────────
       Guards
       ────────────────────────────────────────────────────────── */

    public function test_posting_an_already_posted_invoice_throws(): void
    {
        $invoice = SupplierInvoice::factory()->create([
            'supplier_id'        => $this->hotel->id,
            'expense_account_id' => $this->expenseHotels->id,
            'amount'             => 100,
        ]);

        $service = app(SupplierInvoiceService::class);
        $service->post($invoice);

        $this->expectException(RuntimeException::class);
        $service->post($invoice->fresh());
    }

    public function test_posting_with_group_expense_account_throws(): void
    {
        $groupAccount = Account::where('code', '5')->firstOrFail(); // المصروفات (group)
        $invoice = SupplierInvoice::factory()->create([
            'supplier_id'        => $this->hotel->id,
            'expense_account_id' => $groupAccount->id,
            'amount'             => 100,
        ]);

        $this->expectException(RuntimeException::class);
        app(SupplierInvoiceService::class)->post($invoice);
    }

    /* ──────────────────────────────────────────────────────────
       Cancellation
       ────────────────────────────────────────────────────────── */

    public function test_cancel_reverses_supplier_balance(): void
    {
        $invoice = SupplierInvoice::factory()->create([
            'supplier_id'        => $this->hotel->id,
            'expense_account_id' => $this->expenseHotels->id,
            'amount'             => 1500, 'tax_amount' => 0,
        ]);

        $service = app(SupplierInvoiceService::class);
        $service->post($invoice);

        $supplierParent = Account::where('code', '2111')->first();
        $this->assertEqualsWithDelta(1500, app(BalanceCalculator::class)->naturalBalance($supplierParent->fresh()), 0.01);

        $service->cancel($invoice->fresh(), 'خطأ في الإدخال');

        $this->assertTrue($invoice->fresh()->isCancelled());
        $this->assertTrue($invoice->fresh()->journalEntry->isCancelled());
        $this->assertEqualsWithDelta(0, app(BalanceCalculator::class)->naturalBalance($supplierParent->fresh()), 0.01);
    }

    public function test_cancelling_draft_invoice_throws(): void
    {
        $invoice = SupplierInvoice::factory()->create([
            'supplier_id'        => $this->hotel->id,
            'expense_account_id' => $this->expenseHotels->id,
        ]);

        $this->expectException(RuntimeException::class);
        app(SupplierInvoiceService::class)->cancel($invoice, 'reason');
    }
}
