<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\Voucher;
use App\Services\Accounting\BalanceCalculator;
use App\Services\Accounting\VoucherService;
use Database\Seeders\ChartOfAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\Concerns\SetsUpRolesForTesting;
use Tests\TestCase;

class ReceiptVoucherTest extends TestCase
{
    use RefreshDatabase, SetsUpRolesForTesting;

    private Account $cash;
    private Account $bank;
    private Account $revenue;
    private Account $assetsGroup;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        $this->seed(ChartOfAccountsSeeder::class);

        $this->cash        = Account::where('code', '1111')->firstOrFail();
        $this->bank        = Account::where('code', '1121')->firstOrFail();
        $this->revenue     = Account::where('code', '412')->firstOrFail();
        $this->assetsGroup = Account::where('code', '1')->firstOrFail();
    }

    /* ──────────────────────────────────────────────────────────
       VoucherService behaviour
       ────────────────────────────────────────────────────────── */

    public function test_create_receipt_voucher_posts_journal_entry_automatically(): void
    {
        $this->actingAs($this->userWithRole('accountant'));

        $voucher = app(VoucherService::class)->create([
            'type'               => 'receipt',
            'date'               => now()->toDateString(),
            'cash_account_id'    => $this->cash->id,
            'counter_account_id' => $this->revenue->id,
            'party_name'         => 'أحمد علي',
            'currency'           => 'EGP',
            'amount'             => 1500,
            'description'        => 'دفعة مقدمة على رحلة',
        ]);

        $this->assertTrue($voucher->isPosted());
        $this->assertNotNull($voucher->journal_entry_id);

        $entry = JournalEntry::findOrFail($voucher->journal_entry_id);
        $this->assertTrue($entry->isPosted());
        $this->assertSame('voucher', $entry->source_type);
        $this->assertSame($voucher->id, $entry->source_id);

        // 2 lines: cash debit + revenue credit
        $this->assertSame(2, $entry->lines->count());
        $debitLine  = $entry->lines->where('debit', '>', 0)->first();
        $creditLine = $entry->lines->where('credit', '>', 0)->first();
        $this->assertSame($this->cash->id,    $debitLine->account_id);
        $this->assertSame($this->revenue->id, $creditLine->account_id);
        $this->assertEqualsWithDelta(1500, (float) $debitLine->debit, 0.01);
    }

    public function test_receipt_voucher_increases_cash_balance(): void
    {
        $this->actingAs($this->userWithRole('accountant'));

        app(VoucherService::class)->create([
            'type'               => 'receipt',
            'date'               => now()->toDateString(),
            'cash_account_id'    => $this->cash->id,
            'counter_account_id' => $this->revenue->id,
            'party_name'         => 'عميل',
            'currency'           => 'EGP',
            'amount'             => 2500,
            'description'        => 'تحصيل',
        ]);

        $balance = app(BalanceCalculator::class)->naturalBalance($this->cash->fresh());
        $this->assertEqualsWithDelta(2500, $balance, 0.01);
    }

    public function test_foreign_currency_voucher_converts_to_egp_in_journal(): void
    {
        $this->actingAs($this->userWithRole('accountant'));

        $voucher = app(VoucherService::class)->create([
            'type'               => 'receipt',
            'date'               => now()->toDateString(),
            'cash_account_id'    => $this->bank->id,
            'counter_account_id' => $this->revenue->id,
            'party_name'         => 'Foreign Client',
            'currency'           => 'USD',
            'amount'             => 100,
            'exchange_rate'      => 50,
            'description'        => 'USD payment',
        ]);

        $this->assertEqualsWithDelta(5000, (float) $voucher->amount_egp, 0.01);

        $entry = JournalEntry::find($voucher->journal_entry_id);
        $this->assertEqualsWithDelta(5000, (float) $entry->total_debit, 0.01);
        $this->assertEqualsWithDelta(5000, (float) $entry->total_credit, 0.01);
    }

    public function test_voucher_with_same_cash_and_counter_account_is_rejected(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('نفس حساب الخزينة');

        app(VoucherService::class)->create([
            'type'               => 'receipt',
            'date'               => now()->toDateString(),
            'cash_account_id'    => $this->cash->id,
            'counter_account_id' => $this->cash->id,
            'party_name'         => 'x',
            'currency'           => 'EGP',
            'amount'             => 100,
            'description'        => 'x',
        ]);
    }

    public function test_voucher_with_non_cash_first_account_is_rejected(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('خزينة أو حساب بنكي');

        app(VoucherService::class)->create([
            'type'               => 'receipt',
            'date'               => now()->toDateString(),
            'cash_account_id'    => $this->revenue->id, // not cash/bank
            'counter_account_id' => $this->cash->id,
            'party_name'         => 'x',
            'currency'           => 'EGP',
            'amount'             => 100,
            'description'        => 'x',
        ]);
    }

    public function test_voucher_with_group_counter_account_is_rejected(): void
    {
        $this->expectException(RuntimeException::class);

        app(VoucherService::class)->create([
            'type'               => 'receipt',
            'date'               => now()->toDateString(),
            'cash_account_id'    => $this->cash->id,
            'counter_account_id' => $this->assetsGroup->id, // group
            'party_name'         => 'x',
            'currency'           => 'EGP',
            'amount'             => 100,
            'description'        => 'x',
        ]);
    }

    public function test_cancel_voucher_cancels_linked_journal_entry(): void
    {
        $this->actingAs($this->userWithRole('accountant'));

        $voucher = app(VoucherService::class)->create([
            'type'               => 'receipt',
            'date'               => now()->toDateString(),
            'cash_account_id'    => $this->cash->id,
            'counter_account_id' => $this->revenue->id,
            'party_name'         => 'x',
            'currency'           => 'EGP',
            'amount'             => 1000,
            'description'        => 'x',
        ]);

        // Balance is 1000 before cancel
        $this->assertEqualsWithDelta(1000, app(BalanceCalculator::class)->naturalBalance($this->cash->fresh()), 0.01);

        app(VoucherService::class)->cancel($voucher, 'خطأ في الإدخال');

        $this->assertTrue($voucher->fresh()->isCancelled());
        $this->assertTrue($voucher->journalEntry->fresh()->isCancelled());
        $this->assertEqualsWithDelta(0, app(BalanceCalculator::class)->naturalBalance($this->cash->fresh()), 0.01);
    }

    /* ──────────────────────────────────────────────────────────
       HTTP layer
       ────────────────────────────────────────────────────────── */

    public function test_accountant_can_create_receipt_via_http(): void
    {
        $this->actingAs($this->userWithRole('accountant'))
            ->post(route('admin.accounting.vouchers.receipts.store'), [
                'date'               => now()->toDateString(),
                'cash_account_id'    => $this->cash->id,
                'counter_account_id' => $this->revenue->id,
                'party_name'         => 'عميل اختبار',
                'currency'           => 'EGP',
                'amount'             => 500,
                'description'        => 'دفعة من العميل',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertSame(1, Voucher::receipts()->posted()->count());
    }

    public function test_validation_rejects_negative_amount(): void
    {
        $this->actingAs($this->userWithRole('accountant'))
            ->post(route('admin.accounting.vouchers.receipts.store'), [
                'date'               => now()->toDateString(),
                'cash_account_id'    => $this->cash->id,
                'counter_account_id' => $this->revenue->id,
                'party_name'         => 'x',
                'currency'           => 'EGP',
                'amount'             => -100,
                'description'        => 'x',
            ])
            ->assertSessionHasErrors(['amount']);
    }

    public function test_validation_rejects_same_account_on_both_sides(): void
    {
        $this->actingAs($this->userWithRole('accountant'))
            ->post(route('admin.accounting.vouchers.receipts.store'), [
                'date'               => now()->toDateString(),
                'cash_account_id'    => $this->cash->id,
                'counter_account_id' => $this->cash->id,
                'party_name'         => 'x',
                'currency'           => 'EGP',
                'amount'             => 100,
                'description'        => 'x',
            ])
            ->assertSessionHasErrors(['counter_account_id']);
    }

    public function test_booking_staff_cannot_create_receipt(): void
    {
        $this->actingAs($this->userWithRole('booking-staff'))
            ->post(route('admin.accounting.vouchers.receipts.store'), [])
            ->assertForbidden();
    }
}
