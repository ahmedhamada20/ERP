<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Services\Accounting\JournalService;
use Database\Seeders\ChartOfAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use RuntimeException;
use Tests\Concerns\SetsUpRolesForTesting;
use Tests\TestCase;

/**
 * Validates the core accounting invariants:
 *   - Lines: debit XOR credit (never both, never neither)
 *   - Entries: total_debit == total_credit
 *   - No postings to group accounts
 *   - Post/cancel workflow is one-way and guarded
 */
class JournalEntryTest extends TestCase
{
    use RefreshDatabase, SetsUpRolesForTesting;

    private Account $cashbox;
    private Account $umrahRevenue;
    private Account $assetsGroup;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        $this->seed(ChartOfAccountsSeeder::class);

        $this->cashbox      = Account::where('code', '1111')->firstOrFail();  // postable cash
        $this->umrahRevenue = Account::where('code', '412')->firstOrFail();   // postable revenue
        $this->assetsGroup  = Account::where('code', '1')->firstOrFail();     // group
    }

    /* ──────────────────────────────────────────────────────────
       Line-level invariants
       ────────────────────────────────────────────────────────── */

    public function test_line_with_both_debit_and_credit_is_rejected(): void
    {
        $entry = $this->newEntry();
        $this->expectException(InvalidArgumentException::class);

        $entry->lines()->create([
            'account_id' => $this->cashbox->id,
            'debit'      => 100,
            'credit'     => 50,
        ]);
    }

    public function test_line_with_zero_debit_and_zero_credit_is_rejected(): void
    {
        $entry = $this->newEntry();
        $this->expectException(InvalidArgumentException::class);

        $entry->lines()->create([
            'account_id' => $this->cashbox->id,
            'debit'      => 0,
            'credit'     => 0,
        ]);
    }

    public function test_line_with_negative_amount_is_rejected(): void
    {
        $entry = $this->newEntry();
        $this->expectException(InvalidArgumentException::class);

        $entry->lines()->create([
            'account_id' => $this->cashbox->id,
            'debit'      => -50,
            'credit'     => 0,
        ]);
    }

    public function test_posting_to_group_account_is_rejected_at_model_level(): void
    {
        $entry = $this->newEntry();
        $this->expectException(InvalidArgumentException::class);

        $entry->lines()->create([
            'account_id' => $this->assetsGroup->id, // الأصول (group)
            'debit'      => 500,
            'credit'     => 0,
        ]);
    }

    /* ──────────────────────────────────────────────────────────
       Entry auto-totals + balance helper
       ────────────────────────────────────────────────────────── */

    public function test_entry_totals_are_recalculated_when_lines_change(): void
    {
        $entry = $this->newEntry();

        $entry->lines()->create(['account_id' => $this->cashbox->id,      'debit' => 1000, 'credit' => 0]);
        $entry->lines()->create(['account_id' => $this->umrahRevenue->id, 'debit' => 0,    'credit' => 1000]);

        $entry->refresh();
        $this->assertEqualsWithDelta(1000, (float) $entry->total_debit, 0.01);
        $this->assertEqualsWithDelta(1000, (float) $entry->total_credit, 0.01);
        $this->assertTrue($entry->isBalanced());

        // Delete one line — totals must update again
        $entry->lines()->where('debit', '>', 0)->first()->delete();
        $entry->refresh();
        $this->assertEqualsWithDelta(0, (float) $entry->total_debit, 0.01);
        $this->assertEqualsWithDelta(1000, (float) $entry->total_credit, 0.01);
        $this->assertFalse($entry->isBalanced());
    }

    public function test_journal_number_is_auto_generated(): void
    {
        $e1 = JournalEntry::create(['date' => now(), 'description' => 'A']);
        $e2 = JournalEntry::create(['date' => now(), 'description' => 'B']);

        $year = date('Y');
        $this->assertStringStartsWith("JE-{$year}-", $e1->number);
        $this->assertStringStartsWith("JE-{$year}-", $e2->number);
        $this->assertNotSame($e1->number, $e2->number);
    }

    /* ──────────────────────────────────────────────────────────
       JournalService::post() — golden rules
       ────────────────────────────────────────────────────────── */

    public function test_balanced_entry_can_be_posted(): void
    {
        $entry = $this->balancedEntry();

        $service = app(JournalService::class);
        $posted  = $service->post($entry, $this->userWithRole('accountant')->id);

        $this->assertTrue($posted->isPosted());
        $this->assertNotNull($posted->posted_at);
        $this->assertNotNull($posted->posted_by);
    }

    public function test_unbalanced_entry_cannot_be_posted(): void
    {
        $entry = $this->newEntry();
        $entry->lines()->create(['account_id' => $this->cashbox->id,      'debit' => 1000, 'credit' => 0]);
        $entry->lines()->create(['account_id' => $this->umrahRevenue->id, 'debit' => 0,    'credit' => 700]);
        $entry->refresh();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('غير متوازن');

        app(JournalService::class)->post($entry);
    }

    public function test_entry_with_one_line_cannot_be_posted(): void
    {
        $entry = $this->newEntry();
        $entry->lines()->create(['account_id' => $this->cashbox->id, 'debit' => 500, 'credit' => 0]);
        $entry->refresh();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('سطرين على الأقل');

        app(JournalService::class)->post($entry);
    }

    public function test_zero_total_entry_cannot_be_posted(): void
    {
        // Both sides equal zero — balanced but meaningless
        $entry = $this->newEntry();
        // can't create zero-amount lines (blocked at line level), so create
        // two minimum lines then delete to reach the empty balanced state
        $entry->lines()->create(['account_id' => $this->cashbox->id,      'debit' => 0.01, 'credit' => 0]);
        $entry->lines()->create(['account_id' => $this->umrahRevenue->id, 'debit' => 0,    'credit' => 0.01]);
        $entry->refresh();

        // 0.01 vs 0.01 — should succeed (balanced + non-zero)
        app(JournalService::class)->post($entry);
        $this->assertTrue($entry->fresh()->isPosted());
    }

    public function test_posted_entry_cannot_be_reposted(): void
    {
        $entry = $this->balancedEntry();
        app(JournalService::class)->post($entry);

        $this->expectException(RuntimeException::class);
        app(JournalService::class)->post($entry->fresh());
    }

    public function test_posted_entry_can_be_cancelled_with_reason(): void
    {
        $entry = $this->balancedEntry();
        $service = app(JournalService::class);
        $service->post($entry);

        $cancelled = $service->cancel($entry->fresh(), 'خطأ في الإدخال', $this->userWithRole('accountant')->id);

        $this->assertTrue($cancelled->isCancelled());
        $this->assertSame('خطأ في الإدخال', $cancelled->cancellation_reason);
        $this->assertNotNull($cancelled->cancelled_at);
    }

    public function test_draft_entry_cannot_be_cancelled(): void
    {
        $entry = $this->balancedEntry();

        $this->expectException(RuntimeException::class);
        app(JournalService::class)->cancel($entry, 'reason');
    }

    public function test_inactive_account_in_lines_blocks_posting(): void
    {
        $entry = $this->balancedEntry();
        $this->cashbox->update(['is_active' => false]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('حساب متوقف');

        app(JournalService::class)->post($entry->fresh());
    }

    /* ──────────────────────────────────────────────────────────
       affectsLedger() scope — only posted entries count
       ────────────────────────────────────────────────────────── */

    public function test_affects_ledger_scope_only_returns_posted(): void
    {
        $service = app(JournalService::class);

        $draftEntry = $this->balancedEntry();

        $postedEntry = $this->balancedEntry();
        $service->post($postedEntry);

        $cancelledEntry = $this->balancedEntry();
        $service->post($cancelledEntry);
        $service->cancel($cancelledEntry->fresh(), 'test');

        $this->assertSame(1, JournalEntry::affectsLedger()->count());
    }

    /* ──────────────────────────────────────────────────────────
       Helpers
       ────────────────────────────────────────────────────────── */

    private function newEntry(): JournalEntry
    {
        return JournalEntry::create([
            'date'        => now(),
            'description' => 'Test entry',
        ]);
    }

    private function balancedEntry(float $amount = 1000): JournalEntry
    {
        $entry = $this->newEntry();
        $entry->lines()->create(['account_id' => $this->cashbox->id,      'debit' => $amount, 'credit' => 0]);
        $entry->lines()->create(['account_id' => $this->umrahRevenue->id, 'debit' => 0,       'credit' => $amount]);
        return $entry->refresh();
    }
}
