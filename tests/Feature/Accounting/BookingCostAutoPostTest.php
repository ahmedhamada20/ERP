<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\BookingCost;
use App\Models\Customer;
use App\Models\JournalEntry;
use App\Models\ReligiousBooking;
use App\Services\Accounting\BalanceCalculator;
use App\Services\Accounting\BookingCostJournalPoster;
use Database\Seeders\ChartOfAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpRolesForTesting;
use Tests\TestCase;

class BookingCostAutoPostTest extends TestCase
{
    use RefreshDatabase, SetsUpRolesForTesting;

    private ReligiousBooking $booking;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        $this->seed(ChartOfAccountsSeeder::class);

        // super-admin has both manage_costs (for cost creation) AND update
        // (for workflow transition) — accountant lacks update.
        $this->actingAs($this->userWithRole('super-admin'));

        $this->booking = ReligiousBooking::factory()
            ->for(Customer::factory()->create())
            ->withSellingPrice(20_000)
            ->create();

        $this->booking->update(['workflow_stage' => 'finance']);
    }

    private function addCost(string $category, float $amount): BookingCost
    {
        return $this->booking->costs()->create([
            'category'      => $category,
            'currency'      => 'EGP',
            'amount'        => $amount,
            'exchange_rate' => 1,
            'quantity'      => 1,
            'per_unit'      => 'total',
            'is_revenue'    => false,
        ]);
    }

    /* ──────────────────────────────────────────────────────────
       Aggregation correctness
       ────────────────────────────────────────────────────────── */

    public function test_close_creates_single_consolidated_journal_entry(): void
    {
        // Mix categories
        $this->addCost('room', 8000);
        $this->addCost('room', 2000);    // same category — should aggregate
        $this->addCost('flight', 5000);
        $this->addCost('visa', 1000);

        $entry = app(BookingCostJournalPoster::class)->postClosingJournal($this->booking->fresh());

        $this->assertTrue($entry->isPosted());
        $this->assertSame('booking_cost', $entry->source_type);
        $this->assertSame($this->booking->id, $entry->source_id);

        $this->booking->refresh();
        $this->assertSame($entry->id, $this->booking->cost_journal_entry_id);

        // 3 DR + 3 CR = 6 lines (room/flight/visa each side)
        $this->assertSame(6, $entry->lines->count());

        // Aggregation check: room cost = 8000 + 2000 = 10000
        $roomLine = $entry->lines->where('debit', '>', 0)
            ->first(fn ($l) => $l->account->code === '511');
        $this->assertEqualsWithDelta(10000, (float) $roomLine->debit, 0.01);

        // Total balanced
        $this->assertEqualsWithDelta(16000, (float) $entry->total_debit, 0.01);
        $this->assertEqualsWithDelta(16000, (float) $entry->total_credit, 0.01);
    }

    public function test_revenue_cost_rows_are_excluded(): void
    {
        $this->addCost('room', 5000);

        // is_revenue=true row — should NOT contribute
        $this->booking->costs()->create([
            'category' => 'profit', 'currency' => 'EGP', 'amount' => 3000,
            'exchange_rate' => 1, 'quantity' => 1, 'per_unit' => 'total',
            'is_revenue' => true,
        ]);

        $entry = app(BookingCostJournalPoster::class)->postClosingJournal($this->booking->fresh());

        $this->assertEqualsWithDelta(5000, (float) $entry->total_debit, 0.01);
    }

    public function test_category_mapping_routes_to_correct_accounts(): void
    {
        $this->addCost('room', 1000);     // → 511 / 2111
        $this->addCost('flight', 1000);   // → 512 / 2112
        $this->addCost('transport', 1000);// → 514 / 2113
        $this->addCost('visa', 1000);     // → 513 / 2114
        $this->addCost('insurance', 500); // → 516 / 2115 (fallback)
        $this->addCost('bank_fee', 500);  // → 525 / 2115

        $entry = app(BookingCostJournalPoster::class)->postClosingJournal($this->booking->fresh());

        $debitCodes = $entry->lines
            ->where('debit', '>', 0)
            ->pluck('account.code')
            ->sort()
            ->values()
            ->all();
        $this->assertSame(['511', '512', '513', '514', '516', '525'], $debitCodes);

        $creditCodes = $entry->lines
            ->where('credit', '>', 0)
            ->pluck('account.code')
            ->sort()
            ->values()
            ->unique()
            ->all();
        // insurance + bank_fee both → 2115 (one row, not two)
        $this->assertContains('2115', $creditCodes);
        $this->assertContains('2111', $creditCodes);
    }

    public function test_unknown_category_falls_back_to_other_accounts(): void
    {
        $this->addCost('miscellaneous', 1500);
        $this->addCost('other', 500);

        $entry = app(BookingCostJournalPoster::class)->postClosingJournal($this->booking->fresh());

        // both → 519 (expense) + 2115 (supplier), aggregated into 1 line each
        $this->assertSame(2, $entry->lines->count());

        $debitLine = $entry->lines->where('debit', '>', 0)->first();
        $this->assertSame('519', $debitLine->account->code);
        $this->assertEqualsWithDelta(2000, (float) $debitLine->debit, 0.01);
    }

    /* ──────────────────────────────────────────────────────────
       Balance integration
       ────────────────────────────────────────────────────────── */

    public function test_close_increases_expense_balances(): void
    {
        $this->addCost('room', 6000);

        app(BookingCostJournalPoster::class)->postClosingJournal($this->booking->fresh());

        $hotelExpense = Account::where('code', '511')->first();
        $balance = app(BalanceCalculator::class)->naturalBalance($hotelExpense->fresh());
        $this->assertEqualsWithDelta(6000, $balance, 0.01);
    }

    public function test_close_increases_supplier_payable_balances(): void
    {
        $this->addCost('flight', 4000);

        app(BookingCostJournalPoster::class)->postClosingJournal($this->booking->fresh());

        $flightSupplier = Account::where('code', '2112')->first();
        $balance = app(BalanceCalculator::class)->naturalBalance($flightSupplier->fresh());
        $this->assertEqualsWithDelta(4000, $balance, 0.01); // we now owe 4000
    }

    /* ──────────────────────────────────────────────────────────
       Empty / edge cases
       ────────────────────────────────────────────────────────── */

    public function test_no_costs_throws(): void
    {
        $this->expectExceptionMessage('لا يوجد بنود تكلفة');
        app(BookingCostJournalPoster::class)->postClosingJournal($this->booking->fresh());
    }

    public function test_only_revenue_costs_throws(): void
    {
        $this->booking->costs()->create([
            'category' => 'profit', 'currency' => 'EGP', 'amount' => 1000,
            'exchange_rate' => 1, 'quantity' => 1, 'per_unit' => 'total', 'is_revenue' => true,
        ]);

        $this->expectExceptionMessage('لا يوجد بنود تكلفة');
        app(BookingCostJournalPoster::class)->postClosingJournal($this->booking->fresh());
    }

    /* ──────────────────────────────────────────────────────────
       Cancel reverses the entry
       ────────────────────────────────────────────────────────── */

    public function test_cancel_closing_journal_reverses_balances(): void
    {
        $this->addCost('room', 3000);
        app(BookingCostJournalPoster::class)->postClosingJournal($this->booking->fresh());

        // Balance check: 511 = 3000, 2111 = 3000
        $hotelExpense = Account::where('code', '511')->first();
        $hotelSupplier = Account::where('code', '2111')->first();
        $this->assertEqualsWithDelta(3000, app(BalanceCalculator::class)->naturalBalance($hotelExpense->fresh()), 0.01);
        $this->assertEqualsWithDelta(3000, app(BalanceCalculator::class)->naturalBalance($hotelSupplier->fresh()), 0.01);

        app(BookingCostJournalPoster::class)->cancelClosingJournal($this->booking->fresh(), 'إلغاء حجز');

        $this->assertEqualsWithDelta(0, app(BalanceCalculator::class)->naturalBalance($hotelExpense->fresh()), 0.01);
        $this->assertEqualsWithDelta(0, app(BalanceCalculator::class)->naturalBalance($hotelSupplier->fresh()), 0.01);
    }

    /* ──────────────────────────────────────────────────────────
       HTTP transition integration
       ────────────────────────────────────────────────────────── */

    public function test_close_transition_via_controller_posts_journal(): void
    {
        $this->addCost('room', 2000);
        $this->addCost('flight', 1000);

        $this->post(route('admin.religious.bookings.transition', $this->booking), [
            'action' => 'close',
        ])->assertSessionHasNoErrors();

        $this->booking->refresh();
        $this->assertSame('closed', $this->booking->workflow_stage);
        $this->assertNotNull($this->booking->cost_journal_entry_id);

        $entry = JournalEntry::findOrFail($this->booking->cost_journal_entry_id);
        $this->assertTrue($entry->isPosted());
        $this->assertEqualsWithDelta(3000, (float) $entry->total_debit, 0.01);
    }

    public function test_cancel_transition_after_close_reverses_journal(): void
    {
        $this->addCost('room', 5000);

        // Close
        $this->post(route('admin.religious.bookings.transition', $this->booking), ['action' => 'close']);

        $entryId = $this->booking->fresh()->cost_journal_entry_id;
        $this->assertNotNull($entryId);
        $this->assertTrue(JournalEntry::find($entryId)->isPosted());

        // Cancel
        $this->post(route('admin.religious.bookings.transition', $this->booking), [
            'action' => 'cancel',
            'reason' => 'العميل ألغى',
        ])->assertSessionHasNoErrors();

        $this->assertTrue(JournalEntry::find($entryId)->isCancelled());
    }

    public function test_close_does_not_break_when_chart_missing(): void
    {
        // Delete the expense account that "room" maps to
        Account::where('code', '511')->delete();

        $this->addCost('room', 1000);

        // Should not throw — gets a "warning" flash instead
        $response = $this->post(route('admin.religious.bookings.transition', $this->booking), [
            'action' => 'close',
        ]);

        $response->assertRedirect();
        $this->booking->refresh();
        // workflow still moved to closed
        $this->assertSame('closed', $this->booking->workflow_stage);
        // but JE was not posted
        $this->assertNull($this->booking->cost_journal_entry_id);
    }
}
