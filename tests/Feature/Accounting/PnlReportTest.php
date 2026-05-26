<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\Customer;
use App\Models\ReligiousBooking;
use App\Services\Accounting\PnlReport;
use Database\Seeders\ChartOfAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpRolesForTesting;
use Tests\TestCase;

class PnlReportTest extends TestCase
{
    use RefreshDatabase, SetsUpRolesForTesting;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        $this->seed(ChartOfAccountsSeeder::class);
        $this->actingAs($this->userWithRole('super-admin'));
    }

    public function test_empty_period_returns_zero_totals(): void
    {
        $data = app(PnlReport::class)->build(now()->subMonth(), now());

        $this->assertSame(0.0, $data['revenue']['total']);
        $this->assertSame(0.0, $data['gross_profit']);
        $this->assertSame(0.0, $data['net_profit']);
    }

    public function test_single_payment_appears_as_revenue(): void
    {
        $booking = ReligiousBooking::factory()
            ->for(Customer::factory()->create())
            ->withSellingPrice(8000)
            ->create();

        $booking->payments()->create([
            'payment_date' => now(), 'payment_type' => 'deposit', 'currency' => 'EGP',
            'amount' => 8000, 'exchange_rate' => 1, 'method' => 'cash',
        ]);

        $data = app(PnlReport::class)->build(now()->subDay(), now()->addDay());

        $this->assertEqualsWithDelta(8000, $data['revenue']['total'], 0.01);
        $this->assertEqualsWithDelta(8000, $data['gross_profit'], 0.01);
        $this->assertEqualsWithDelta(8000, $data['net_profit'], 0.01);
        $this->assertSame(100.0, $data['net_margin']);
    }

    public function test_close_with_costs_reduces_gross_profit(): void
    {
        $booking = ReligiousBooking::factory()
            ->for(Customer::factory()->create())
            ->withSellingPrice(10000)
            ->create();
        $booking->update(['workflow_stage' => 'finance']);

        $booking->payments()->create([
            'payment_date' => now(), 'payment_type' => 'deposit', 'currency' => 'EGP',
            'amount' => 10000, 'exchange_rate' => 1, 'method' => 'cash',
        ]);

        $booking->costs()->create([
            'category' => 'room', 'currency' => 'EGP', 'amount' => 6000,
            'exchange_rate' => 1, 'quantity' => 1, 'per_unit' => 'total', 'is_revenue' => false,
        ]);

        $this->post(route('admin.religious.bookings.transition', $booking), ['action' => 'close']);

        $data = app(PnlReport::class)->build(now()->subDay(), now()->addDay());

        $this->assertEqualsWithDelta(10000, $data['revenue']['total'], 0.01);
        $this->assertEqualsWithDelta(6000,  $data['cost_of_services']['total'], 0.01);
        $this->assertEqualsWithDelta(4000,  $data['gross_profit'], 0.01); // 10000 - 6000
        $this->assertEqualsWithDelta(4000,  $data['net_profit'], 0.01);
        $this->assertSame(40.0, $data['gross_margin']);
    }

    public function test_negative_net_profit_renders_as_loss(): void
    {
        // Create a manual loss-making JE: only an expense, no revenue
        $expense = Account::where('code', '522')->firstOrFail(); // إيجار المكاتب
        $cash    = Account::where('code', '1111')->firstOrFail();

        $entry = \App\Models\JournalEntry::create([
            'date' => now(), 'description' => 'إيجار',
        ]);
        $entry->lines()->create(['account_id' => $expense->id, 'debit' => 3000, 'credit' => 0]);
        $entry->lines()->create(['account_id' => $cash->id,    'debit' => 0,    'credit' => 3000]);
        app(\App\Services\Accounting\JournalService::class)->post($entry->fresh());

        $data = app(PnlReport::class)->build(now()->subDay(), now()->addDay());

        $this->assertEqualsWithDelta(0,     $data['revenue']['total'], 0.01);
        $this->assertEqualsWithDelta(3000,  $data['operating_expense']['total'], 0.01);
        $this->assertEqualsWithDelta(-3000, $data['net_profit'], 0.01);
    }

    public function test_date_filter_excludes_entries_outside_range(): void
    {
        $booking = ReligiousBooking::factory()
            ->for(Customer::factory()->create())
            ->withSellingPrice(5000)
            ->create();

        $oldPayment = $booking->payments()->create([
            'payment_date' => now()->subMonths(2), 'payment_type' => 'deposit',
            'currency' => 'EGP', 'amount' => 5000, 'exchange_rate' => 1, 'method' => 'cash',
        ]);

        // Override the JE date to match the old payment date
        \App\Models\JournalEntry::where('source_id', $oldPayment->id)
            ->update(['date' => now()->subMonths(2)]);

        // Query for current month only — should exclude
        $data = app(PnlReport::class)->build(now()->startOfMonth(), now()->endOfMonth());
        $this->assertSame(0.0, $data['revenue']['total']);

        // Query for last 3 months — includes
        $data = app(PnlReport::class)->build(now()->subMonths(3), now());
        $this->assertEqualsWithDelta(5000, $data['revenue']['total'], 0.01);
    }

    public function test_http_index_renders_for_accountant(): void
    {
        $this->actingAs($this->userWithRole('accountant'));

        $this->get(route('admin.accounting.reports.pnl'))
            ->assertOk()
            ->assertSee('قائمة الدخل');
    }

    public function test_csv_export_returns_proper_headers(): void
    {
        $response = $this->get(route('admin.accounting.reports.pnl.csv'));
        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
    }

    public function test_booking_staff_cannot_view_pnl(): void
    {
        $this->actingAs($this->userWithRole('booking-staff'))
            ->get(route('admin.accounting.reports.pnl'))
            ->assertForbidden();
    }
}
