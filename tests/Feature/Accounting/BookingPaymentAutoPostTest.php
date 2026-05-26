<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\BookingPayment;
use App\Models\Customer;
use App\Models\JournalEntry;
use App\Models\ReligiousBooking;
use App\Services\Accounting\BalanceCalculator;
use Database\Seeders\ChartOfAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpRolesForTesting;
use Tests\TestCase;

/**
 * Validates the integration between the Religious Tourism module and
 * the accounting GL — every booking payment auto-creates a posted JE.
 */
class BookingPaymentAutoPostTest extends TestCase
{
    use RefreshDatabase, SetsUpRolesForTesting;

    private ReligiousBooking $booking;
    private Account $cash;
    private Account $bank;
    private Account $umrahRevenue;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        $this->seed(ChartOfAccountsSeeder::class);

        $this->cash         = Account::where('code', '1111')->firstOrFail();
        $this->bank         = Account::where('code', '1121')->firstOrFail();
        $this->umrahRevenue = Account::where('code', '412')->firstOrFail();

        $this->actingAs($this->userWithRole('accountant'));

        $this->booking = ReligiousBooking::factory()
            ->for(Customer::factory()->create())
            ->withSellingPrice(10_000)
            ->create();
    }

    /* ──────────────────────────────────────────────────────────
       Normal payment posting (deposit/installment/final)
       ────────────────────────────────────────────────────────── */

    public function test_cash_payment_creates_journal_entry_debiting_cashbox(): void
    {
        $payment = $this->booking->payments()->create([
            'payment_date'  => now(),
            'payment_type'  => 'deposit',
            'currency'      => 'EGP',
            'amount'        => 3000,
            'exchange_rate' => 1,
            'method'        => 'cash',
        ]);

        $payment->refresh();
        $this->assertNotNull($payment->journal_entry_id, 'Payment should be linked to a JE');

        $entry = JournalEntry::findOrFail($payment->journal_entry_id);
        $this->assertTrue($entry->isPosted());
        $this->assertSame('booking_payment', $entry->source_type);
        $this->assertSame($payment->id, $entry->source_id);

        // Cash debited, revenue credited
        $debitLine = $entry->lines->where('debit', '>', 0)->first();
        $this->assertSame($this->cash->id, $debitLine->account_id);
        $this->assertEqualsWithDelta(3000, (float) $debitLine->debit, 0.01);

        $creditLine = $entry->lines->where('credit', '>', 0)->first();
        $this->assertSame($this->umrahRevenue->id, $creditLine->account_id);
    }

    public function test_bank_transfer_payment_debits_bank_not_cashbox(): void
    {
        $payment = $this->booking->payments()->create([
            'payment_date'  => now(),
            'payment_type'  => 'installment',
            'currency'      => 'EGP',
            'amount'        => 2500,
            'exchange_rate' => 1,
            'method'        => 'bank_transfer',
        ]);

        $entry = JournalEntry::findOrFail($payment->fresh()->journal_entry_id);
        $debitLine = $entry->lines->where('debit', '>', 0)->first();
        $this->assertSame($this->bank->id, $debitLine->account_id);
    }

    public function test_foreign_currency_payment_posts_egp_amount(): void
    {
        $payment = $this->booking->payments()->create([
            'payment_date'  => now(),
            'payment_type'  => 'deposit',
            'currency'      => 'USD',
            'amount'        => 100,
            'exchange_rate' => 50,
            'method'        => 'bank_transfer',
        ]);

        $entry = JournalEntry::findOrFail($payment->fresh()->journal_entry_id);
        // 100 USD * 50 = 5000 EGP
        $this->assertEqualsWithDelta(5000, (float) $entry->total_debit, 0.01);
        $this->assertEqualsWithDelta(5000, (float) $entry->total_credit, 0.01);
    }

    public function test_cash_balance_increases_after_payment(): void
    {
        $this->booking->payments()->create([
            'payment_date'  => now(),
            'payment_type'  => 'deposit',
            'currency'      => 'EGP',
            'amount'        => 1500,
            'exchange_rate' => 1,
            'method'        => 'cash',
        ]);

        $balance = app(BalanceCalculator::class)->naturalBalance($this->cash->fresh());
        $this->assertEqualsWithDelta(1500, $balance, 0.01);
    }

    public function test_revenue_balance_increases_after_payment(): void
    {
        $this->booking->payments()->create([
            'payment_date'  => now(),
            'payment_type'  => 'final',
            'currency'      => 'EGP',
            'amount'        => 4000,
            'exchange_rate' => 1,
            'method'        => 'cash',
        ]);

        $balance = app(BalanceCalculator::class)->naturalBalance($this->umrahRevenue->fresh());
        $this->assertEqualsWithDelta(4000, $balance, 0.01);
    }

    /* ──────────────────────────────────────────────────────────
       Refund posting (only on refund_status='paid')
       ────────────────────────────────────────────────────────── */

    public function test_refund_pending_does_not_post_journal_entry(): void
    {
        // Seed a normal payment first (so we have something to refund)
        $this->booking->payments()->create([
            'payment_date'  => now(),
            'payment_type'  => 'deposit',
            'currency'      => 'EGP',
            'amount'        => 5000,
            'exchange_rate' => 1,
            'method'        => 'cash',
        ]);

        $refund = $this->booking->payments()->create([
            'payment_date'  => now(),
            'payment_type'  => 'refund',
            'currency'      => 'EGP',
            'amount'        => 1000,
            'exchange_rate' => 1,
            'method'        => 'cash',
            'refund_reason' => 'إلغاء جزئي',
        ]);

        $this->assertSame('pending', $refund->fresh()->refund_status);
        $this->assertNull($refund->fresh()->journal_entry_id, 'Pending refund must NOT have a JE');

        // Cash balance unchanged from refund (only the 5000 deposit affects it)
        $balance = app(BalanceCalculator::class)->naturalBalance($this->cash->fresh());
        $this->assertEqualsWithDelta(5000, $balance, 0.01);
    }

    public function test_refund_paid_posts_reversal_journal_entry(): void
    {
        $this->booking->payments()->create([
            'payment_date'  => now(),
            'payment_type'  => 'deposit',
            'currency'      => 'EGP',
            'amount'        => 5000,
            'exchange_rate' => 1,
            'method'        => 'cash',
        ]);

        $refund = $this->booking->payments()->create([
            'payment_date'  => now(),
            'payment_type'  => 'refund',
            'currency'      => 'EGP',
            'amount'        => 1500,
            'exchange_rate' => 1,
            'method'        => 'cash',
            'refund_reason' => 'استرداد جزئي',
        ]);

        // Approve + mark paid (the observer fires on the refund_status change)
        $refund->update(['refund_status' => 'approved']);
        $refund->update(['refund_status' => 'paid']);

        $refund->refresh();
        $this->assertNotNull($refund->journal_entry_id);

        $entry = JournalEntry::findOrFail($refund->journal_entry_id);
        $this->assertTrue($entry->isPosted());

        // REVERSED direction: revenue debited, cash credited
        $debitLine = $entry->lines->where('debit', '>', 0)->first();
        $this->assertSame($this->umrahRevenue->id, $debitLine->account_id);

        $creditLine = $entry->lines->where('credit', '>', 0)->first();
        $this->assertSame($this->cash->id, $creditLine->account_id);

        // Net cash balance: 5000 in - 1500 refund = 3500
        $balance = app(BalanceCalculator::class)->naturalBalance($this->cash->fresh());
        $this->assertEqualsWithDelta(3500, $balance, 0.01);
    }

    /* ──────────────────────────────────────────────────────────
       Cancellation cascade
       ────────────────────────────────────────────────────────── */

    public function test_deleting_payment_cancels_linked_journal_entry(): void
    {
        $payment = $this->booking->payments()->create([
            'payment_date'  => now(),
            'payment_type'  => 'deposit',
            'currency'      => 'EGP',
            'amount'        => 2000,
            'exchange_rate' => 1,
            'method'        => 'cash',
        ]);

        $journalId = $payment->fresh()->journal_entry_id;
        $this->assertNotNull($journalId);

        // Balance shows 2000 before delete
        $this->assertEqualsWithDelta(2000, app(BalanceCalculator::class)->naturalBalance($this->cash->fresh()), 0.01);

        $payment->delete();

        $entry = JournalEntry::findOrFail($journalId);
        $this->assertTrue($entry->isCancelled(), 'Deleted payment should have cancelled its JE');

        // Balance back to 0
        $this->assertEqualsWithDelta(0, app(BalanceCalculator::class)->naturalBalance($this->cash->fresh()), 0.01);
    }

    /* ──────────────────────────────────────────────────────────
       Failure graceful handling
       ────────────────────────────────────────────────────────── */

    public function test_payment_save_succeeds_even_when_chart_is_missing(): void
    {
        // Pretend the chart wasn't seeded — delete required accounts
        $this->cash->delete();
        Account::where('sub_type', 'cash')->delete();

        // Payment creation should NOT throw; the JE just won't be created
        $payment = $this->booking->payments()->create([
            'payment_date'  => now(),
            'payment_type'  => 'deposit',
            'currency'      => 'EGP',
            'amount'        => 500,
            'exchange_rate' => 1,
            'method'        => 'cash',
        ]);

        $this->assertNotNull($payment->id);
        $this->assertNull($payment->fresh()->journal_entry_id, 'No JE created when chart is broken — but payment still saved');
    }
}
