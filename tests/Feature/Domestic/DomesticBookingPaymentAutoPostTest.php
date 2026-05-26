<?php

namespace Tests\Feature\Domestic;

use App\Models\Account;
use App\Models\Customer;
use App\Models\DomesticBooking;
use App\Models\JournalEntry;
use Database\Seeders\ChartOfAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpRolesForTesting;
use Tests\TestCase;

/**
 * Validates GL integration for DomesticBooking payments — every payment
 * auto-creates a posted JE: DR cash/bank, CR 413 (إيرادات السياحة الداخلية).
 */
class DomesticBookingPaymentAutoPostTest extends TestCase
{
    use RefreshDatabase, SetsUpRolesForTesting;

    private DomesticBooking $booking;
    private Account $cash;
    private Account $bank;
    private Account $domesticRevenue;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        $this->seed(ChartOfAccountsSeeder::class);

        $this->cash            = Account::where('code', '1111')->firstOrFail();
        $this->bank            = Account::where('code', '1121')->firstOrFail();
        $this->domesticRevenue = Account::where('code', '413')->firstOrFail();

        $this->actingAs($this->userWithRole('accountant'));

        $this->booking = DomesticBooking::factory()
            ->for(Customer::factory()->create())
            ->withSellingPrice(10_000)
            ->create();
    }

    /* ──────────────────────────────────────────────────────────
       Normal payment posting
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
        $this->assertSame('domestic_booking_payment', $entry->source_type);
        $this->assertSame($payment->id, $entry->source_id);

        // Cash debited, domestic revenue credited
        $debitLine  = $entry->lines->where('debit', '>', 0)->first();
        $creditLine = $entry->lines->where('credit', '>', 0)->first();

        $this->assertSame($this->cash->id, $debitLine->account_id);
        $this->assertEqualsWithDelta(3000, (float) $debitLine->debit, 0.01);

        $this->assertSame($this->domesticRevenue->id, $creditLine->account_id);
        $this->assertEqualsWithDelta(3000, (float) $creditLine->credit, 0.01);
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

    public function test_instapay_payment_debits_bank_account(): void
    {
        $payment = $this->booking->payments()->create([
            'payment_date'  => now(),
            'payment_type'  => 'installment',
            'currency'      => 'EGP',
            'amount'        => 1500,
            'exchange_rate' => 1,
            'method'        => 'instapay',
        ]);

        $entry = JournalEntry::findOrFail($payment->fresh()->journal_entry_id);
        $debitLine = $entry->lines->where('debit', '>', 0)->first();
        $this->assertSame($this->bank->id, $debitLine->account_id);
    }

    public function test_payment_je_is_balanced(): void
    {
        $payment = $this->booking->payments()->create([
            'payment_date'  => now(),
            'payment_type'  => 'deposit',
            'currency'      => 'EGP',
            'amount'        => 4500,
            'exchange_rate' => 1,
            'method'        => 'cash',
        ]);

        $entry = JournalEntry::findOrFail($payment->fresh()->journal_entry_id);
        $totalDr = (float) $entry->lines->sum('debit');
        $totalCr = (float) $entry->lines->sum('credit');

        $this->assertEqualsWithDelta($totalDr, $totalCr, 0.01, 'Payment JE must be balanced');
        $this->assertEqualsWithDelta(4500, $totalDr, 0.01);
    }

    /* ──────────────────────────────────────────────────────────
       Refund posting (only when status='paid')
       ────────────────────────────────────────────────────────── */

    public function test_refund_at_pending_status_does_not_post_je(): void
    {
        $this->booking->payments()->create([
            'payment_date' => now(), 'payment_type' => 'deposit',
            'currency' => 'EGP', 'amount' => 5000, 'exchange_rate' => 1, 'method' => 'cash',
        ]);

        $refund = $this->booking->payments()->create([
            'payment_date'  => now(),
            'payment_type'  => 'refund',
            'currency'      => 'EGP',
            'amount'        => 1500,
            'exchange_rate' => 1,
            'method'        => 'cash',
            'refund_reason' => 'test',
            'refund_status' => 'pending',
        ]);

        $this->assertNull($refund->fresh()->journal_entry_id, 'Pending refund should not be posted');
    }

    public function test_refund_at_paid_status_creates_reversal_je(): void
    {
        $this->booking->payments()->create([
            'payment_date' => now(), 'payment_type' => 'deposit',
            'currency' => 'EGP', 'amount' => 5000, 'exchange_rate' => 1, 'method' => 'cash',
        ]);

        $refund = $this->booking->payments()->create([
            'payment_date'  => now(),
            'payment_type'  => 'refund',
            'currency'      => 'EGP',
            'amount'        => 1500,
            'exchange_rate' => 1,
            'method'        => 'cash',
            'refund_reason' => 'test',
            'refund_status' => 'pending',
        ]);

        // Approve then mark as paid — triggers the JE
        $refund->update(['refund_status' => 'approved']);
        $refund->update(['refund_status' => 'paid']);

        $refund->refresh();
        $this->assertNotNull($refund->journal_entry_id, 'Paid refund should have a JE');

        $entry = JournalEntry::with('lines')->findOrFail($refund->journal_entry_id);
        $this->assertTrue($entry->isPosted());

        // Reversal direction: DR revenue, CR cash (opposite of normal payment)
        $debitLine  = $entry->lines->where('debit', '>', 0)->first();
        $creditLine = $entry->lines->where('credit', '>', 0)->first();

        $this->assertSame($this->domesticRevenue->id, $debitLine->account_id);
        $this->assertSame($this->cash->id, $creditLine->account_id);
        $this->assertEqualsWithDelta(1500, (float) $debitLine->debit, 0.01);
    }

    /* ──────────────────────────────────────────────────────────
       JE lifecycle on delete / refund un-pay
       ────────────────────────────────────────────────────────── */

    public function test_deleting_payment_cancels_linked_je(): void
    {
        $payment = $this->booking->payments()->create([
            'payment_date'  => now(),
            'payment_type'  => 'deposit',
            'currency'      => 'EGP',
            'amount'        => 2000,
            'exchange_rate' => 1,
            'method'        => 'cash',
        ]);

        $jeId = $payment->fresh()->journal_entry_id;
        $this->assertNotNull($jeId);

        $payment->delete();

        $entry = JournalEntry::findOrFail($jeId);
        $this->assertTrue($entry->isCancelled(), 'JE should be cancelled when payment is deleted');
    }
}
