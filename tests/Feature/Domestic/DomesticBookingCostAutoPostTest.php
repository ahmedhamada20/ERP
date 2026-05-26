<?php

namespace Tests\Feature\Domestic;

use App\Models\Account;
use App\Models\Customer;
use App\Models\DomesticBooking;
use App\Models\DomesticBookingCost;
use App\Models\JournalEntry;
use App\Services\Accounting\DomesticBookingCostJournalPoster;
use Database\Seeders\ChartOfAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpRolesForTesting;
use Tests\TestCase;

/**
 * Validates consolidated cost JE posting on domestic booking close.
 * Tests aggregation, balance, close-via-controller, and cancel reversal.
 */
class DomesticBookingCostAutoPostTest extends TestCase
{
    use RefreshDatabase, SetsUpRolesForTesting;

    private DomesticBooking $booking;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        $this->seed(ChartOfAccountsSeeder::class);

        $this->actingAs($this->userWithRole('super-admin'));

        $this->booking = DomesticBooking::factory()
            ->for(Customer::factory()->create())
            ->withSellingPrice(20_000)
            ->create();

        $this->booking->update(['workflow_stage' => 'finance']);
    }

    private function addCost(string $category, float $amount): DomesticBookingCost
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
        // Mix categories — hotel + room both map to 511, transport + private_car both map to 514
        $this->addCost('hotel', 6000);
        $this->addCost('room', 2000);          // same expense account (511) — aggregates
        $this->addCost('transport', 800);
        $this->addCost('private_car', 1200);   // same expense account (514) — aggregates
        $this->addCost('activities', 500);

        $entry = app(DomesticBookingCostJournalPoster::class)->postClosingJournal($this->booking->fresh());

        $this->assertTrue($entry->isPosted());
        $this->assertSame('domestic_booking_cost', $entry->source_type);
        $this->assertSame($this->booking->id, $entry->source_id);

        $this->booking->refresh();
        $this->assertSame($entry->id, $this->booking->cost_journal_entry_id);

        // 3 unique expense accounts: 511 (hotel+room), 514 (transport+private_car), 519 (activities)
        // + 3 mirroring supplier accounts: 2111, 2113, 2115 — total 6 lines
        $this->assertSame(6, $entry->lines->count());

        // Aggregation: hotel(6000) + room(2000) = 8000 on account 511
        $hotelDr = $entry->lines->where('debit', '>', 0)
            ->first(fn ($l) => $l->account->code === '511');
        $this->assertNotNull($hotelDr);
        $this->assertEqualsWithDelta(8000, (float) $hotelDr->debit, 0.01);

        // transport + private_car = 2000 on account 514
        $transportDr = $entry->lines->where('debit', '>', 0)
            ->first(fn ($l) => $l->account->code === '514');
        $this->assertEqualsWithDelta(2000, (float) $transportDr->debit, 0.01);

        // activities → 519 (misc operating expenses)
        $miscDr = $entry->lines->where('debit', '>', 0)
            ->first(fn ($l) => $l->account->code === '519');
        $this->assertEqualsWithDelta(500, (float) $miscDr->debit, 0.01);
    }

    public function test_cost_je_is_balanced(): void
    {
        $this->addCost('hotel', 6000);
        $this->addCost('transport', 800);
        $this->addCost('meals', 400);

        $entry = app(DomesticBookingCostJournalPoster::class)->postClosingJournal($this->booking->fresh());

        $totalDr = (float) $entry->lines->sum('debit');
        $totalCr = (float) $entry->lines->sum('credit');

        $this->assertEqualsWithDelta($totalDr, $totalCr, 0.01, 'Cost JE must be balanced');
        $this->assertEqualsWithDelta(7200, $totalDr, 0.01);
    }

    public function test_revenue_marker_costs_are_excluded_from_je(): void
    {
        $this->addCost('hotel', 5000);

        // Revenue marker — should NOT appear in cost JE
        $this->booking->costs()->create([
            'category'      => 'profit',
            'currency'      => 'EGP',
            'amount'        => 3000,
            'exchange_rate' => 1,
            'quantity'      => 1,
            'per_unit'      => 'total',
            'is_revenue'    => true,
        ]);

        $entry = app(DomesticBookingCostJournalPoster::class)->postClosingJournal($this->booking->fresh());

        $totalDr = (float) $entry->lines->sum('debit');
        $this->assertEqualsWithDelta(5000, $totalDr, 0.01, 'Only the hotel cost should be in the JE');
    }

    public function test_close_with_no_costs_does_not_create_je(): void
    {
        // No costs added — close should succeed but skip JE
        $response = $this->actingAs($this->userWithRole('super-admin'))
            ->post(route('admin.domestic.bookings.transition', $this->booking), ['action' => 'close']);

        $response->assertRedirect();
        $this->booking->refresh();
        $this->assertSame('closed', $this->booking->workflow_stage);
        $this->assertNull($this->booking->cost_journal_entry_id);
    }

    /* ──────────────────────────────────────────────────────────
       Controller transition integration
       ────────────────────────────────────────────────────────── */

    public function test_close_via_controller_auto_posts_cost_je(): void
    {
        $this->addCost('hotel', 4000);
        $this->addCost('flight', 2500);

        $this->actingAs($this->userWithRole('super-admin'))
            ->post(route('admin.domestic.bookings.transition', $this->booking), ['action' => 'close'])
            ->assertRedirect();

        $this->booking->refresh();
        $this->assertSame('closed', $this->booking->workflow_stage);
        $this->assertSame('completed', $this->booking->status);
        $this->assertNotNull($this->booking->cost_journal_entry_id);

        // Costs should be locked
        $this->assertTrue($this->booking->costs()->where('is_locked', false)->doesntExist());
    }

    public function test_cancel_after_close_reverses_cost_je(): void
    {
        $this->addCost('hotel', 4000);
        $this->addCost('transport', 1000);

        // Close → cost JE posted
        $this->actingAs($this->userWithRole('super-admin'))
            ->post(route('admin.domestic.bookings.transition', $this->booking), ['action' => 'close']);

        $this->booking->refresh();
        $jeId = $this->booking->cost_journal_entry_id;
        $this->assertNotNull($jeId);

        $entry = JournalEntry::findOrFail($jeId);
        $this->assertTrue($entry->isPosted());

        // Cancel → JE should be cancelled
        $this->actingAs($this->userWithRole('super-admin'))
            ->post(route('admin.domestic.bookings.transition', $this->booking), [
                'action' => 'cancel',
                'reason' => 'العميل غيّر رأيه',
            ]);

        $entry->refresh();
        $this->assertTrue($entry->isCancelled(), 'Cost JE should be cancelled when closed booking is cancelled');

        $this->booking->refresh();
        $this->assertSame('cancelled', $this->booking->status);
    }
}
