<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Services\Accounting\BalanceCalculator;
use App\Services\Accounting\JournalService;
use Database\Seeders\ChartOfAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpRolesForTesting;
use Tests\TestCase;

class CashBalanceTest extends TestCase
{
    use RefreshDatabase, SetsUpRolesForTesting;

    private Account $cash;
    private Account $bank;
    private Account $revenue;
    private Account $expense;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        $this->seed(ChartOfAccountsSeeder::class);

        $this->cash    = Account::where('code', '1111')->firstOrFail();  // الخزينة الرئيسية (asset, debit-natured)
        $this->bank    = Account::where('code', '1121')->firstOrFail();  // CIB ج
        $this->revenue = Account::where('code', '412')->firstOrFail();   // إيرادات العمرة (credit-natured)
        $this->expense = Account::where('code', '511')->firstOrFail();   // تكلفة الفنادق (debit-natured)
    }

    /* ──────────────────────────────────────────────────────────
       BalanceCalculator core behaviour
       ────────────────────────────────────────────────────────── */

    public function test_only_posted_entries_count_toward_balance(): void
    {
        // Draft entry — should NOT affect balance
        $draft = $this->makeEntry();
        $draft->lines()->create(['account_id' => $this->cash->id,    'debit' => 1000, 'credit' => 0]);
        $draft->lines()->create(['account_id' => $this->revenue->id, 'debit' => 0,    'credit' => 1000]);

        $calc = app(BalanceCalculator::class);
        $this->assertEqualsWithDelta(0, $calc->naturalBalance($this->cash->fresh()), 0.01);

        // Post it — now it should count
        app(JournalService::class)->post($draft->fresh());
        $this->assertEqualsWithDelta(1000, $calc->naturalBalance($this->cash->fresh()), 0.01);
    }

    public function test_cancelled_entry_is_excluded_from_balance(): void
    {
        $entry = $this->postedEntry($this->cash, $this->revenue, 500);

        $calc = app(BalanceCalculator::class);
        $this->assertEqualsWithDelta(500, $calc->naturalBalance($this->cash->fresh()), 0.01);

        app(JournalService::class)->cancel($entry->fresh(), 'test');

        $this->assertEqualsWithDelta(0, $calc->naturalBalance($this->cash->fresh()), 0.01);
    }

    public function test_natural_balance_handles_debit_natured_account(): void
    {
        // Cash gets 1000 in, 300 out → balance = 700 (asset, debit-natured)
        $this->postedEntry($this->cash, $this->revenue, 1000);
        $this->postedEntry($this->expense, $this->cash, 300);

        $calc = app(BalanceCalculator::class);
        $this->assertEqualsWithDelta(700, $calc->naturalBalance($this->cash->fresh()), 0.01);
    }

    public function test_natural_balance_handles_credit_natured_account(): void
    {
        // Revenue earns 2000 (credit) — natural balance = 2000 for revenue
        $this->postedEntry($this->cash, $this->revenue, 2000);

        $calc = app(BalanceCalculator::class);
        $this->assertEqualsWithDelta(2000, $calc->naturalBalance($this->revenue->fresh()), 0.01);
    }

    public function test_opening_balance_is_included(): void
    {
        $this->cash->update(['opening_balance' => 5000]);
        $this->postedEntry($this->cash, $this->revenue, 1000);

        $calc = app(BalanceCalculator::class);
        // 5000 opening + 1000 debit = 6000
        $this->assertEqualsWithDelta(6000, $calc->naturalBalance($this->cash->fresh()), 0.01);
    }

    public function test_bulk_sums_returns_correct_per_account_totals(): void
    {
        $this->postedEntry($this->cash, $this->revenue, 1000);
        $this->postedEntry($this->bank, $this->revenue, 500);

        $sums = app(BalanceCalculator::class)->rawSumsBulk([
            $this->cash->id, $this->bank->id, $this->revenue->id,
        ]);

        $this->assertEqualsWithDelta(1000, $sums[$this->cash->id]['debit'], 0.01);
        $this->assertEqualsWithDelta(500,  $sums[$this->bank->id]['debit'], 0.01);
        $this->assertEqualsWithDelta(1500, $sums[$this->revenue->id]['credit'], 0.01);
    }

    public function test_date_filter_restricts_balance_calculation(): void
    {
        $e1 = $this->postedEntry($this->cash, $this->revenue, 1000);
        $e1->update(['date' => now()->subMonth()]);

        $this->postedEntry($this->cash, $this->revenue, 500); // today

        $calc = app(BalanceCalculator::class);
        // Up to yesterday — only old entry counts
        $upTo = now()->subDay()->toDateTime();
        $sums = $calc->rawSums($this->cash->id, null, $upTo);
        $this->assertEqualsWithDelta(1000, $sums['debit'], 0.01);
    }

    /* ──────────────────────────────────────────────────────────
       CashAccountController HTTP
       ────────────────────────────────────────────────────────── */

    public function test_cash_index_displays_balances(): void
    {
        $this->postedEntry($this->cash, $this->revenue, 750);

        $this->actingAs($this->userWithRole('accountant'))
            ->get(route('admin.accounting.cash.index'))
            ->assertOk()
            ->assertSee('الخزينة الرئيسية')
            ->assertSee('750.00');
    }

    public function test_cash_show_displays_movements(): void
    {
        $this->postedEntry($this->cash, $this->revenue, 1000);

        $this->actingAs($this->userWithRole('accountant'))
            ->get(route('admin.accounting.cash.show', $this->cash))
            ->assertOk()
            ->assertSee('1,000.00');
    }

    public function test_show_returns_404_for_non_cash_bank_account(): void
    {
        $this->actingAs($this->userWithRole('accountant'))
            ->get(route('admin.accounting.cash.show', $this->revenue))
            ->assertNotFound();
    }

    /* Helpers */
    private function makeEntry(): JournalEntry
    {
        return JournalEntry::create(['date' => now(), 'description' => 'test']);
    }

    private function postedEntry(Account $debit, Account $credit, float $amount): JournalEntry
    {
        $entry = $this->makeEntry();
        $entry->lines()->create(['account_id' => $debit->id,  'debit' => $amount, 'credit' => 0]);
        $entry->lines()->create(['account_id' => $credit->id, 'debit' => 0,       'credit' => $amount]);
        return app(JournalService::class)->post($entry->fresh());
    }
}
