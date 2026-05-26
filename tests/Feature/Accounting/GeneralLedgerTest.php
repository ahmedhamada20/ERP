<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\Customer;
use App\Models\JournalEntry;
use App\Models\ReligiousBooking;
use App\Services\Accounting\GeneralLedgerReport;
use App\Services\Accounting\JournalService;
use Database\Seeders\ChartOfAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpRolesForTesting;
use Tests\TestCase;

class GeneralLedgerTest extends TestCase
{
    use RefreshDatabase, SetsUpRolesForTesting;

    private Account $cash;
    private Account $revenue;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        $this->seed(ChartOfAccountsSeeder::class);
        $this->actingAs($this->userWithRole('super-admin'));

        $this->cash    = Account::where('code', '1111')->firstOrFail();
        $this->revenue = Account::where('code', '412')->firstOrFail();
    }

    private function postedJe(Account $debit, Account $credit, float $amount, ?string $date = null): JournalEntry
    {
        $entry = JournalEntry::create([
            'date'        => $date ?: now(),
            'description' => 'Test',
        ]);
        $entry->lines()->create(['account_id' => $debit->id,  'debit' => $amount, 'credit' => 0]);
        $entry->lines()->create(['account_id' => $credit->id, 'debit' => 0,       'credit' => $amount]);
        return app(JournalService::class)->post($entry->fresh());
    }

    /* ──────────────────────────────────────────────────────────
       Core calculations
       ────────────────────────────────────────────────────────── */

    public function test_empty_account_returns_zero_balances(): void
    {
        $data = app(GeneralLedgerReport::class)->build($this->cash);

        $this->assertSame(0.0, $data['opening']);
        $this->assertSame(0.0, $data['closing']);
        $this->assertCount(0, $data['lines']);
    }

    public function test_running_balance_accumulates_correctly_for_debit_natured_account(): void
    {
        // Three entries on cashbox: +1000, +500, -300 = closing 1200
        $this->postedJe($this->cash, $this->revenue, 1000);
        $this->postedJe($this->cash, $this->revenue, 500);
        // Out-flow: credit cash via an expense
        $expense = Account::where('code', '522')->firstOrFail();
        $this->postedJe($expense, $this->cash, 300);

        $data = app(GeneralLedgerReport::class)->build($this->cash);

        $this->assertSame(3, $data['lines']->count());

        $balances = $data['lines']->pluck('running_balance')->all();
        $this->assertEqualsWithDelta(1000, $balances[0], 0.01); // after +1000
        $this->assertEqualsWithDelta(1500, $balances[1], 0.01); // after +500
        $this->assertEqualsWithDelta(1200, $balances[2], 0.01); // after -300
        $this->assertEqualsWithDelta(1200, $data['closing'], 0.01);
    }

    public function test_running_balance_for_credit_natured_revenue_account(): void
    {
        // Revenue: each posting credits it → balance grows
        $this->postedJe($this->cash, $this->revenue, 2000);
        $this->postedJe($this->cash, $this->revenue, 1500);

        $data = app(GeneralLedgerReport::class)->build($this->revenue);

        $balances = $data['lines']->pluck('running_balance')->all();
        $this->assertEqualsWithDelta(2000, $balances[0], 0.01);
        $this->assertEqualsWithDelta(3500, $balances[1], 0.01);
        $this->assertEqualsWithDelta(3500, $data['closing'], 0.01);
    }

    public function test_opening_balance_field_is_included_in_starting_position(): void
    {
        $this->cash->update(['opening_balance' => 5000]);

        $this->postedJe($this->cash, $this->revenue, 1000);

        $data = app(GeneralLedgerReport::class)->build($this->cash);

        $this->assertEqualsWithDelta(5000, $data['opening'], 0.01);
        $this->assertEqualsWithDelta(6000, $data['lines']->first()->running_balance, 0.01);
        $this->assertEqualsWithDelta(6000, $data['closing'], 0.01);
    }

    public function test_opening_uses_only_entries_before_from_date(): void
    {
        // Two entries: one 60 days ago, one today
        $oldEntry = $this->postedJe($this->cash, $this->revenue, 2000);
        $oldEntry->update(['date' => now()->subDays(60)]);

        $this->postedJe($this->cash, $this->revenue, 800);

        // Build for last 30 days: old entry is in opening, new one is movement
        $data = app(GeneralLedgerReport::class)->build(
            $this->cash,
            now()->subDays(30),
            now()->addDay(),
        );

        $this->assertEqualsWithDelta(2000, $data['opening'], 0.01);
        $this->assertSame(1, $data['lines']->count());
        $this->assertEqualsWithDelta(2800, $data['closing'], 0.01);
    }

    public function test_period_totals_match_line_sums(): void
    {
        $this->postedJe($this->cash, $this->revenue, 1000);
        $this->postedJe($this->cash, $this->revenue, 500);
        $expense = Account::where('code', '522')->firstOrFail();
        $this->postedJe($expense, $this->cash, 200);

        $data = app(GeneralLedgerReport::class)->build($this->cash);

        $this->assertEqualsWithDelta(1500, $data['period_debit'], 0.01);  // 1000 + 500
        $this->assertEqualsWithDelta(200,  $data['period_credit'], 0.01); // -200
    }

    public function test_cancelled_entries_excluded(): void
    {
        $entry = $this->postedJe($this->cash, $this->revenue, 1500);
        app(JournalService::class)->cancel($entry, 'test');

        $data = app(GeneralLedgerReport::class)->build($this->cash);

        $this->assertCount(0, $data['lines']);
        $this->assertSame(0.0, $data['closing']);
    }

    /* ──────────────────────────────────────────────────────────
       HTTP layer
       ────────────────────────────────────────────────────────── */

    public function test_picker_renders_without_account_id(): void
    {
        $this->get(route('admin.accounting.reports.general_ledger'))
            ->assertOk()
            ->assertSee('اختر حساب');
    }

    public function test_detail_renders_when_account_id_provided(): void
    {
        $this->postedJe($this->cash, $this->revenue, 1000);

        $this->get(route('admin.accounting.reports.general_ledger', ['account_id' => $this->cash->id]))
            ->assertOk()
            ->assertSee('دفتر أستاذ')
            ->assertSee('1,000.00');
    }

    public function test_csv_download_works(): void
    {
        $response = $this->get(route('admin.accounting.reports.general_ledger.csv', ['account_id' => $this->cash->id]));
        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
    }

    public function test_group_account_cannot_be_queried(): void
    {
        $group = Account::where('code', '1')->firstOrFail();

        $this->get(route('admin.accounting.reports.general_ledger', ['account_id' => $group->id]))
            ->assertNotFound();
    }

    public function test_booking_staff_cannot_view(): void
    {
        $this->actingAs($this->userWithRole('booking-staff'))
            ->get(route('admin.accounting.reports.general_ledger'))
            ->assertForbidden();
    }

    /* ──────────────────────────────────────────────────────────
       Real-world integration
       ────────────────────────────────────────────────────────── */

    public function test_cashbox_ledger_after_booking_lifecycle(): void
    {
        $booking = ReligiousBooking::factory()
            ->for(Customer::factory()->create())
            ->withSellingPrice(10_000)
            ->create();

        $booking->payments()->create([
            'payment_date' => now(), 'payment_type' => 'deposit',
            'currency' => 'EGP', 'amount' => 3000, 'exchange_rate' => 1, 'method' => 'cash',
        ]);
        $booking->payments()->create([
            'payment_date' => now(), 'payment_type' => 'installment',
            'currency' => 'EGP', 'amount' => 2000, 'exchange_rate' => 1, 'method' => 'cash',
        ]);

        $data = app(GeneralLedgerReport::class)->build($this->cash);

        $this->assertSame(2, $data['lines']->count());
        $this->assertEqualsWithDelta(3000, $data['lines']->first()->running_balance, 0.01);
        $this->assertEqualsWithDelta(5000, $data['lines']->last()->running_balance, 0.01);
        $this->assertEqualsWithDelta(5000, $data['closing'], 0.01);
    }
}
