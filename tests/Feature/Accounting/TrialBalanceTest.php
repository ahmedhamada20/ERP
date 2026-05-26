<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\Customer;
use App\Models\ReligiousBooking;
use App\Services\Accounting\TrialBalanceReport;
use Database\Seeders\ChartOfAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpRolesForTesting;
use Tests\TestCase;

/**
 * Validates the Trial Balance report.
 *
 * Golden rule: Σ debit_column == Σ credit_column for any posted state.
 */
class TrialBalanceTest extends TestCase
{
    use RefreshDatabase, SetsUpRolesForTesting;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        $this->seed(ChartOfAccountsSeeder::class);
        $this->actingAs($this->userWithRole('super-admin'));
    }

    public function test_empty_system_produces_balanced_empty_report(): void
    {
        $data = app(TrialBalanceReport::class)->build();

        $this->assertSame(0.0, $data['totals']['debit']);
        $this->assertSame(0.0, $data['totals']['credit']);
        $this->assertTrue($data['totals']['balanced']);
        $this->assertCount(0, $data['rows']);
    }

    public function test_single_payment_appears_with_balanced_columns(): void
    {
        $booking = ReligiousBooking::factory()
            ->for(Customer::factory()->create())
            ->withSellingPrice(10_000)
            ->create();

        // 5000 EGP cash payment → DR cashbox 5000, CR umrah revenue 5000
        $booking->payments()->create([
            'payment_date'  => now(),
            'payment_type'  => 'deposit',
            'currency'      => 'EGP',
            'amount'        => 5000,
            'exchange_rate' => 1,
            'method'        => 'cash',
        ]);

        $data = app(TrialBalanceReport::class)->build();

        $this->assertTrue($data['totals']['balanced']);
        $this->assertEqualsWithDelta(5000, $data['totals']['debit'], 0.01);
        $this->assertEqualsWithDelta(5000, $data['totals']['credit'], 0.01);

        // Cash (1111) on debit side
        $cashRow = $data['rows']->firstWhere('code', '1111');
        $this->assertNotNull($cashRow);
        $this->assertEqualsWithDelta(5000, $cashRow->debit_column, 0.01);
        $this->assertEqualsWithDelta(0, $cashRow->credit_column, 0.01);

        // Revenue (412) on credit side
        $revRow = $data['rows']->firstWhere('code', '412');
        $this->assertNotNull($revRow);
        $this->assertEqualsWithDelta(5000, $revRow->credit_column, 0.01);
    }

    public function test_opening_balance_is_included(): void
    {
        Account::where('code', '1111')->update(['opening_balance' => 3000]);

        $data = app(TrialBalanceReport::class)->build();

        $cashRow = $data['rows']->firstWhere('code', '1111');
        $this->assertEqualsWithDelta(3000, $cashRow->debit_column, 0.01);
    }

    public function test_credit_natured_opening_balance_appears_on_credit_side(): void
    {
        // Liability (supplier) opening of 2000 — natural side = credit
        Account::where('code', '2111')->update(['opening_balance' => 2000]);

        $data = app(TrialBalanceReport::class)->build();

        $supplierRow = $data['rows']->firstWhere('code', '2111');
        $this->assertEqualsWithDelta(2000, $supplierRow->credit_column, 0.01);
        $this->assertEqualsWithDelta(0, $supplierRow->debit_column, 0.01);
    }

    public function test_zero_balance_accounts_are_excluded_by_default(): void
    {
        // Postable account with no activity and no opening
        $data = app(TrialBalanceReport::class)->build();

        // None of the seeded postable accounts should appear (no activity)
        $this->assertCount(0, $data['rows']);
    }

    public function test_include_zero_flag_returns_all_postable_accounts(): void
    {
        $data = app(TrialBalanceReport::class)->build(null, true);

        // We seeded 30+ postable accounts in the chart
        $this->assertGreaterThan(20, $data['rows']->count());
    }

    public function test_grouped_section_contains_correct_type(): void
    {
        $booking = ReligiousBooking::factory()
            ->for(Customer::factory()->create())
            ->withSellingPrice(5000)
            ->create();

        $booking->payments()->create([
            'payment_date' => now(), 'payment_type' => 'deposit', 'currency' => 'EGP',
            'amount' => 2000, 'exchange_rate' => 1, 'method' => 'cash',
        ]);

        $data = app(TrialBalanceReport::class)->build();

        $this->assertTrue($data['grouped']->has('asset'));
        $this->assertTrue($data['grouped']->has('revenue'));
        // No expense rows yet
        $this->assertFalse($data['grouped']->has('expense'));
    }

    public function test_http_index_renders_for_accountant(): void
    {
        $this->actingAs($this->userWithRole('accountant'));

        $this->get(route('admin.accounting.reports.trial_balance'))
            ->assertOk()
            ->assertSee('ميزان المراجعة');
    }

    public function test_csv_download_returns_proper_headers(): void
    {
        $response = $this->get(route('admin.accounting.reports.trial_balance.csv'));
        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));
    }

    public function test_booking_staff_cannot_view_trial_balance(): void
    {
        $this->actingAs($this->userWithRole('booking-staff'))
            ->get(route('admin.accounting.reports.trial_balance'))
            ->assertForbidden();
    }

    /**
     * Real-world scenario: full booking lifecycle ends up balanced.
     */
    public function test_full_booking_lifecycle_produces_balanced_trial_balance(): void
    {
        $booking = ReligiousBooking::factory()
            ->for(Customer::factory()->create())
            ->withSellingPrice(15_000)
            ->create();
        $booking->update(['workflow_stage' => 'finance']);

        // 2 payments
        $booking->payments()->create([
            'payment_date' => now(), 'payment_type' => 'deposit', 'currency' => 'EGP',
            'amount' => 5000, 'exchange_rate' => 1, 'method' => 'cash',
        ]);
        $booking->payments()->create([
            'payment_date' => now(), 'payment_type' => 'final', 'currency' => 'EGP',
            'amount' => 10000, 'exchange_rate' => 1, 'method' => 'bank_transfer',
        ]);

        // 3 costs
        foreach ([
            ['room',   8000],
            ['flight', 4000],
            ['visa',   1000],
        ] as [$cat, $amt]) {
            $booking->costs()->create([
                'category' => $cat, 'currency' => 'EGP', 'amount' => $amt,
                'exchange_rate' => 1, 'quantity' => 1, 'per_unit' => 'total', 'is_revenue' => false,
            ]);
        }

        // Close → auto-post cost JE
        $this->post(route('admin.religious.bookings.transition', $booking), ['action' => 'close']);

        $data = app(TrialBalanceReport::class)->build();

        // The golden rule must hold no matter how many transactions
        $this->assertTrue($data['totals']['balanced'],
            "TB unbalanced: DR={$data['totals']['debit']} CR={$data['totals']['credit']}");
    }
}
