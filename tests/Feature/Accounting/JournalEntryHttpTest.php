<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\JournalEntry;
use Database\Seeders\ChartOfAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpRolesForTesting;
use Tests\TestCase;

/**
 * HTTP-layer tests for the Manual Journal Entry UI/CRUD.
 * Model-layer invariants live in JournalEntryTest.
 */
class JournalEntryHttpTest extends TestCase
{
    use RefreshDatabase, SetsUpRolesForTesting;

    private Account $cash;
    private Account $revenue;
    private Account $assetsGroup;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        $this->seed(ChartOfAccountsSeeder::class);

        $this->cash        = Account::where('code', '1111')->firstOrFail();
        $this->revenue     = Account::where('code', '412')->firstOrFail();
        $this->assetsGroup = Account::where('code', '1')->firstOrFail();
    }

    public function test_accountant_can_create_balanced_journal_entry(): void
    {
        $this->actingAs($this->userWithRole('accountant'))
            ->post(route('admin.accounting.journal.store'), [
                'date'        => now()->toDateString(),
                'description' => 'استلام دفعة عميل',
                'reference'   => 'UM-2026-000001',
                'lines'       => [
                    ['account_id' => $this->cash->id,    'debit' => 5000, 'credit' => 0, 'description' => 'استلام نقدي'],
                    ['account_id' => $this->revenue->id, 'debit' => 0,    'credit' => 5000, 'description' => 'إيراد عمرة'],
                ],
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $entry = JournalEntry::firstOrFail();
        $this->assertSame(2, $entry->lines->count());
        $this->assertEqualsWithDelta(5000, (float) $entry->total_debit, 0.01);
        $this->assertTrue($entry->isDraft());
    }

    public function test_unbalanced_entry_returns_validation_error(): void
    {
        $this->actingAs($this->userWithRole('accountant'))
            ->post(route('admin.accounting.journal.store'), [
                'date'        => now()->toDateString(),
                'description' => 'قيد غير متوازن',
                'lines'       => [
                    ['account_id' => $this->cash->id,    'debit' => 1000, 'credit' => 0],
                    ['account_id' => $this->revenue->id, 'debit' => 0,    'credit' => 800],
                ],
            ])
            ->assertSessionHasErrors(['lines']);

        $this->assertSame(0, JournalEntry::count());
    }

    public function test_line_with_both_debit_and_credit_is_rejected_by_form_request(): void
    {
        $this->actingAs($this->userWithRole('accountant'))
            ->post(route('admin.accounting.journal.store'), [
                'date'        => now()->toDateString(),
                'description' => 'سطر مغلوط',
                'lines'       => [
                    ['account_id' => $this->cash->id,    'debit' => 1000, 'credit' => 500],
                    ['account_id' => $this->revenue->id, 'debit' => 0,    'credit' => 1500],
                ],
            ])
            ->assertSessionHasErrors(['lines.0.debit']);
    }

    public function test_posting_to_group_account_is_rejected_by_form_request(): void
    {
        $this->actingAs($this->userWithRole('accountant'))
            ->post(route('admin.accounting.journal.store'), [
                'date'        => now()->toDateString(),
                'description' => 'محاولة على حساب رئيسي',
                'lines'       => [
                    ['account_id' => $this->assetsGroup->id, 'debit' => 1000, 'credit' => 0],
                    ['account_id' => $this->revenue->id,     'debit' => 0,    'credit' => 1000],
                ],
            ])
            ->assertSessionHasErrors(['lines.0.account_id']);
    }

    public function test_single_line_entry_is_rejected(): void
    {
        $this->actingAs($this->userWithRole('accountant'))
            ->post(route('admin.accounting.journal.store'), [
                'date'        => now()->toDateString(),
                'description' => 'سطر واحد',
                'lines'       => [
                    ['account_id' => $this->cash->id, 'debit' => 1000, 'credit' => 0],
                ],
            ])
            ->assertSessionHasErrors(['lines']);
    }

    public function test_create_with_post_immediately_posts_the_entry(): void
    {
        $this->actingAs($this->userWithRole('accountant'))
            ->post(route('admin.accounting.journal.store'), [
                'date'             => now()->toDateString(),
                'description'      => 'قيد وترحيل مباشر',
                'post_immediately' => '1',
                'lines'            => [
                    ['account_id' => $this->cash->id,    'debit' => 2000, 'credit' => 0],
                    ['account_id' => $this->revenue->id, 'debit' => 0,    'credit' => 2000],
                ],
            ])
            ->assertRedirect();

        $entry = JournalEntry::firstOrFail();
        $this->assertTrue($entry->isPosted());
        $this->assertNotNull($entry->posted_at);
    }

    public function test_posted_entry_cannot_be_edited_via_http(): void
    {
        $entry = $this->makePostedEntry();

        $this->actingAs($this->userWithRole('accountant'))
            ->get(route('admin.accounting.journal.edit', $entry))
            ->assertRedirect(route('admin.accounting.journal.show', $entry));

        // Send a valid balanced payload — the controller's status guard should
        // still abort(422) because the entry is no longer in draft.
        $this->actingAs($this->userWithRole('accountant'))
            ->put(route('admin.accounting.journal.update', $entry), [
                'date'        => now()->toDateString(),
                'description' => 'محاولة تعديل',
                'lines'       => [
                    ['account_id' => $this->cash->id,    'debit' => 1, 'credit' => 0],
                    ['account_id' => $this->revenue->id, 'debit' => 0, 'credit' => 1],
                ],
            ])
            ->assertStatus(422);
    }

    public function test_posted_entry_cannot_be_deleted(): void
    {
        $entry = $this->makePostedEntry();

        $this->actingAs($this->userWithRole('accountant'))
            ->delete(route('admin.accounting.journal.destroy', $entry))
            ->assertStatus(422);

        $this->assertNotNull($entry->fresh());
    }

    public function test_posted_entry_can_be_cancelled_with_reason(): void
    {
        $entry = $this->makePostedEntry();

        $this->actingAs($this->userWithRole('accountant'))
            ->post(route('admin.accounting.journal.cancel', $entry), [
                'cancellation_reason' => 'خطأ في الكود',
            ])
            ->assertRedirect();

        $this->assertTrue($entry->fresh()->isCancelled());
        $this->assertSame('خطأ في الكود', $entry->fresh()->cancellation_reason);
    }

    public function test_booking_staff_cannot_view_journal(): void
    {
        $this->actingAs($this->userWithRole('booking-staff'))
            ->get(route('admin.accounting.journal.index'))
            ->assertForbidden();
    }

    /* Helpers */
    private function makePostedEntry(): JournalEntry
    {
        $entry = JournalEntry::create(['date' => now(), 'description' => 'test']);
        $entry->lines()->create(['account_id' => $this->cash->id,    'debit' => 1000, 'credit' => 0]);
        $entry->lines()->create(['account_id' => $this->revenue->id, 'debit' => 0,    'credit' => 1000]);
        app(\App\Services\Accounting\JournalService::class)->post($entry->fresh());
        return $entry->fresh();
    }
}
