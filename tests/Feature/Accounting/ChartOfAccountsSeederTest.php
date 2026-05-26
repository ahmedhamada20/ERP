<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use Database\Seeders\ChartOfAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChartOfAccountsSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_chart_with_correct_hierarchy(): void
    {
        $this->seed(ChartOfAccountsSeeder::class);

        // Root accounts: 1-5 (Assets, Liabilities, Equity, Revenue, Expenses)
        $this->assertSame(5, Account::whereNull('parent_id')->count());

        // Every root account must be a group
        $this->assertTrue(Account::where('code', '1')->first()->is_group);
        $this->assertTrue(Account::where('code', '2')->first()->is_group);

        // Postable leaf samples
        $cashbox = Account::where('code', '1111')->first();
        $this->assertNotNull($cashbox, 'الخزينة الرئيسية should exist');
        $this->assertFalse($cashbox->is_group);
        $this->assertSame('cash', $cashbox->sub_type);

        $umrahRev = Account::where('code', '412')->first();
        $this->assertNotNull($umrahRev, 'إيرادات العمرة should exist');
        $this->assertSame('revenue', $umrahRev->type);
        $this->assertSame('credit', $umrahRev->normal_side);
    }

    public function test_seeder_is_idempotent(): void
    {
        $this->seed(ChartOfAccountsSeeder::class);
        $count = Account::count();

        // Running again should not duplicate
        $this->seed(ChartOfAccountsSeeder::class);
        $this->assertSame($count, Account::count());
    }

    public function test_cash_or_bank_scope_returns_only_postable_money_accounts(): void
    {
        $this->seed(ChartOfAccountsSeeder::class);

        $accounts = Account::cashOrBank()->postable()->get();

        // We seed 3 cashboxes + 3 bank accounts = 6 postable money accounts
        $this->assertGreaterThanOrEqual(6, $accounts->count());
        foreach ($accounts as $a) {
            $this->assertContains($a->sub_type, ['cash', 'bank']);
            $this->assertFalse($a->is_group);
        }
    }
}
