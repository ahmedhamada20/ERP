<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\Voucher;
use App\Services\Accounting\BalanceCalculator;
use App\Services\Accounting\VoucherService;
use Database\Seeders\ChartOfAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpRolesForTesting;
use Tests\TestCase;

class PaymentVoucherTest extends TestCase
{
    use RefreshDatabase, SetsUpRolesForTesting;

    private Account $cash;
    private Account $rentExpense;
    private Account $supplierPayable;
    private Account $revenue;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        $this->seed(ChartOfAccountsSeeder::class);

        $this->cash            = Account::where('code', '1111')->firstOrFail(); // خزينة رئيسية
        $this->rentExpense     = Account::where('code', '522')->firstOrFail();  // إيجار المكاتب
        $this->supplierPayable = Account::where('code', '2111')->firstOrFail(); // موردين فنادق
        $this->revenue         = Account::where('code', '412')->firstOrFail();
    }

    public function test_payment_voucher_journal_direction_is_reversed_from_receipt(): void
    {
        $this->actingAs($this->userWithRole('accountant'));

        $voucher = app(VoucherService::class)->create([
            'type'               => 'payment',
            'date'               => now()->toDateString(),
            'cash_account_id'    => $this->cash->id,
            'counter_account_id' => $this->rentExpense->id,
            'party_name'         => 'مكتب العقارات',
            'currency'           => 'EGP',
            'amount'             => 5000,
            'description'        => 'إيجار شهر مايو',
        ]);

        $entry = JournalEntry::findOrFail($voucher->journal_entry_id);
        $debitLine  = $entry->lines->where('debit', '>', 0)->first();
        $creditLine = $entry->lines->where('credit', '>', 0)->first();

        // Payment: مدين المصروف، دائن خزينة (opposite of receipt)
        $this->assertSame($this->rentExpense->id, $debitLine->account_id);
        $this->assertSame($this->cash->id,        $creditLine->account_id);
    }

    public function test_payment_decreases_cash_balance(): void
    {
        $this->actingAs($this->userWithRole('accountant'));

        // First: fund the cash with a receipt of 10,000
        app(VoucherService::class)->create([
            'type' => 'receipt', 'date' => now()->toDateString(),
            'cash_account_id' => $this->cash->id, 'counter_account_id' => $this->revenue->id,
            'party_name' => 'عميل', 'currency' => 'EGP', 'amount' => 10000, 'description' => 'تحصيل',
        ]);

        // Then: pay 3,000 rent
        app(VoucherService::class)->create([
            'type' => 'payment', 'date' => now()->toDateString(),
            'cash_account_id' => $this->cash->id, 'counter_account_id' => $this->rentExpense->id,
            'party_name' => 'مكتب', 'currency' => 'EGP', 'amount' => 3000, 'description' => 'إيجار',
        ]);

        $balance = app(BalanceCalculator::class)->naturalBalance($this->cash->fresh());
        $this->assertEqualsWithDelta(7000, $balance, 0.01);
    }

    public function test_paying_supplier_decreases_payable_balance(): void
    {
        $this->actingAs($this->userWithRole('accountant'));

        // Suppose we owe a supplier 2000 (set via opening balance for simplicity)
        $this->supplierPayable->update(['opening_balance' => 2000]);

        // Pay 800 from cash
        app(VoucherService::class)->create([
            'type' => 'payment', 'date' => now()->toDateString(),
            'cash_account_id' => $this->cash->id, 'counter_account_id' => $this->supplierPayable->id,
            'party_name' => 'مورد', 'currency' => 'EGP', 'amount' => 800, 'description' => 'سداد جزئي',
        ]);

        // Supplier (liability, credit-natured): opening 2000 - debit 800 (we paid down) = 1200
        $balance = app(BalanceCalculator::class)->naturalBalance($this->supplierPayable->fresh());
        $this->assertEqualsWithDelta(1200, $balance, 0.01);
    }

    public function test_accountant_can_create_payment_via_http(): void
    {
        $this->actingAs($this->userWithRole('accountant'))
            ->post(route('admin.accounting.vouchers.payments.store'), [
                'date'               => now()->toDateString(),
                'cash_account_id'    => $this->cash->id,
                'counter_account_id' => $this->rentExpense->id,
                'party_name'         => 'مكتب الإيجار',
                'currency'           => 'EGP',
                'amount'             => 1500,
                'description'        => 'إيجار يونيو',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertSame(1, Voucher::payments()->posted()->count());
    }

    public function test_cancel_payment_restores_cash_balance(): void
    {
        $this->actingAs($this->userWithRole('accountant'));

        // Fund and pay
        app(VoucherService::class)->create([
            'type' => 'receipt', 'date' => now()->toDateString(),
            'cash_account_id' => $this->cash->id, 'counter_account_id' => $this->revenue->id,
            'party_name' => 'عميل', 'currency' => 'EGP', 'amount' => 5000, 'description' => 'تحصيل',
        ]);

        $payment = app(VoucherService::class)->create([
            'type' => 'payment', 'date' => now()->toDateString(),
            'cash_account_id' => $this->cash->id, 'counter_account_id' => $this->rentExpense->id,
            'party_name' => 'مكتب', 'currency' => 'EGP', 'amount' => 2000, 'description' => 'إيجار',
        ]);

        // Balance: 5000 - 2000 = 3000
        $this->assertEqualsWithDelta(3000, app(BalanceCalculator::class)->naturalBalance($this->cash->fresh()), 0.01);

        // Cancel the payment
        app(VoucherService::class)->cancel($payment, 'تم بالخطأ');

        // Balance restored: 5000
        $this->assertEqualsWithDelta(5000, app(BalanceCalculator::class)->naturalBalance($this->cash->fresh()), 0.01);
    }

    public function test_show_route_rejects_receipt_voucher(): void
    {
        $this->actingAs($this->userWithRole('accountant'));

        $receipt = app(VoucherService::class)->create([
            'type' => 'receipt', 'date' => now()->toDateString(),
            'cash_account_id' => $this->cash->id, 'counter_account_id' => $this->revenue->id,
            'party_name' => 'x', 'currency' => 'EGP', 'amount' => 100, 'description' => 'x',
        ]);

        // Accessing a receipt via the payments route should 404
        $this->actingAs($this->userWithRole('accountant'))
            ->get(route('admin.accounting.vouchers.payments.show', $receipt))
            ->assertNotFound();
    }

    public function test_booking_staff_cannot_create_payment(): void
    {
        $this->actingAs($this->userWithRole('booking-staff'))
            ->post(route('admin.accounting.vouchers.payments.store'), [])
            ->assertForbidden();
    }
}
