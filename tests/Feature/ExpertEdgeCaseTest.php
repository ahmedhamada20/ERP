<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\BookingPayment;
use App\Models\Customer;
use App\Models\JournalEntry;
use App\Models\ReligiousBooking;
use App\Models\Sequence;
use App\Models\Supplier;
use Database\Seeders\ChartOfAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsCashAccountsForTesting;
use Tests\Concerns\SetsUpRolesForTesting;
use Tests\TestCase;

/**
 * اختبارات حالات الحافة (edge cases) كما يصممها خبير Laravel
 * يفحص النظام بحثاً عن ثغرات مخفية.
 *
 * 1. سلامة العملات (currency hygiene)
 * 2. حدود الـ permissions على الـ HTTP
 * 3. سلامة الـ FK constraints
 * 4. سلامة JE balance (debit == credit)
 * 5. تكامل sequences عبر سنين مختلفة
 * 6. سلوك soft-delete وعلاقات restored
 */
class ExpertEdgeCaseTest extends TestCase
{
    use RefreshDatabase, SetsUpRolesForTesting, SeedsCashAccountsForTesting;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        $this->seedCashAccounts();
    }

    // ── 1. سلامة العملات ────────────────────────────────────────────

    public function test_foreign_currency_payment_converts_to_egp_correctly(): void
    {
        $booking = ReligiousBooking::factory()
            ->for(Customer::factory()->create())
            ->withSellingPrice(100_000)
            ->create();

        $payment = $booking->payments()->create([
            'payment_date'    => now(),
            'payment_type'    => 'deposit',
            'currency'        => 'SAR',
            'amount'          => 1000,
            'exchange_rate'   => 12.5,
            'method'          => 'cash',
            'cash_account_id' => $this->defaultCashAccountId(),
        ]);

        // 1000 SAR * 12.5 = 12500 EGP
        $this->assertEqualsWithDelta(12500.0, (float) $payment->fresh()->amount_egp, 0.01);
    }

    public function test_egp_payment_always_uses_rate_one(): void
    {
        $booking = ReligiousBooking::factory()
            ->for(Customer::factory()->create())
            ->withSellingPrice(50_000)
            ->create();

        $payment = $booking->payments()->create([
            'payment_date'    => now(),
            'payment_type'    => 'deposit',
            'currency'        => 'EGP',
            'amount'          => 5000,
            'exchange_rate'   => 99.99, // قيمة شاذة — يجب تجاهلها
            'method'          => 'cash',
            'cash_account_id' => $this->defaultCashAccountId(),
        ]);

        $this->assertEqualsWithDelta(5000.0, (float) $payment->fresh()->amount_egp, 0.01);
    }

    // ── 2. حدود الـ permissions ────────────────────────────────────

    public function test_booking_staff_cannot_post_payment_without_manage_payments_permission(): void
    {
        $booking = ReligiousBooking::factory()
            ->for(Customer::factory()->create())
            ->withSellingPrice(10_000)
            ->create();

        // booking-staff role لا يملك religious_bookings.manage_payments
        $response = $this->actingAs($this->userWithRole('booking-staff'))
            ->post(route('admin.religious.bookings.payments.store', $booking), [
                'payment_date'    => now()->toDateString(),
                'payment_type'    => 'deposit',
                'currency'        => 'EGP',
                'amount'          => 1000,
                'method'          => 'cash',
                'cash_account_id' => $this->defaultCashAccountId(),
            ]);

        $response->assertForbidden();
        $this->assertSame(0, $booking->payments()->count());
    }

    public function test_unauthenticated_user_redirected_from_admin_routes(): void
    {
        $this->get(route('admin.dashboard'))
            ->assertRedirect(route('login'));
    }

    // ── 3. سلامة FK constraints ───────────────────────────────────

    public function test_deleting_account_with_existing_payments_is_prevented(): void
    {
        $booking = ReligiousBooking::factory()
            ->for(Customer::factory()->create())
            ->withSellingPrice(10_000)
            ->create();

        $cashAccount = Account::find($this->defaultCashAccountId());
        $booking->payments()->create([
            'payment_date'    => now(),
            'payment_type'    => 'deposit',
            'currency'        => 'EGP',
            'amount'          => 3000,
            'exchange_rate'   => 1,
            'method'          => 'cash',
            'cash_account_id' => $cashAccount->id,
        ]);

        // restrictOnDelete يجب أن يمنع
        $this->expectException(\Illuminate\Database\QueryException::class);
        $cashAccount->delete();
    }

    public function test_deleting_supplier_with_existing_bookingCosts_nulls_them(): void
    {
        // FK = nullOnDelete في booking_costs.supplier_id
        $supplier = Supplier::factory()->create();
        $booking  = ReligiousBooking::factory()
            ->for(Customer::factory()->create())
            ->withSellingPrice(10_000)
            ->create();

        $cost = $booking->costs()->create([
            'category' => 'room', 'supplier_id' => $supplier->id,
            'currency' => 'EGP', 'amount' => 1000, 'exchange_rate' => 1,
            'quantity' => 1, 'per_unit' => 'total', 'is_revenue' => false,
        ]);

        $supplier->forceDelete();

        $this->assertNull($cost->fresh()->supplier_id);
        $this->assertNotNull($cost->fresh(), 'البند لا يجب أن يُحذف عند حذف المورد');
    }

    // ── 4. سلامة JE balance ───────────────────────────────────────

    public function test_every_posted_je_has_balanced_debit_credit(): void
    {
        // أنشئ عدة مدفوعات لإنشاء JEs
        $booking = ReligiousBooking::factory()
            ->for(Customer::factory()->create())
            ->withSellingPrice(50_000)
            ->create();

        for ($i = 0; $i < 5; $i++) {
            $booking->payments()->create([
                'payment_date'    => now(),
                'payment_type'    => 'installment',
                'currency'        => 'EGP',
                'amount'          => 1000 + ($i * 200),
                'exchange_rate'   => 1,
                'method'          => 'cash',
                'cash_account_id' => $this->defaultCashAccountId(),
            ]);
        }

        // فحص كل JE
        $posted = JournalEntry::where('status', 'posted')->get();
        $this->assertGreaterThan(0, $posted->count());

        foreach ($posted as $entry) {
            $this->assertEqualsWithDelta(
                (float) $entry->total_debit,
                (float) $entry->total_credit,
                0.01,
                "JE #{$entry->number} غير متوازن: D={$entry->total_debit} C={$entry->total_credit}"
            );
        }
    }

    // ── 5. تكامل sequences عبر سنين ──────────────────────────────

    public function test_sequence_for_different_years_is_independent(): void
    {
        Sequence::next('test:2025');
        Sequence::next('test:2025');
        Sequence::next('test:2025'); // 3
        $next2026 = Sequence::next('test:2026'); // يبدأ من 1
        $this->assertSame(1, $next2026);

        $next2025 = Sequence::next('test:2025'); // 4
        $this->assertSame(4, $next2025);
    }

    public function test_sequence_handles_concurrent_burst_without_duplicates(): void
    {
        $generated = [];
        for ($i = 0; $i < 200; $i++) {
            $generated[] = Sequence::next('test:burst');
        }
        $this->assertSame(200, count(array_unique($generated)),
            'sequence يجب أن يولّد 200 رقم فريد بدون تكرار');
        $this->assertSame(range(1, 200), $generated,
            'sequence يجب أن يولّد أرقام متتالية من 1 إلى 200');
    }

    // ── 6. soft-delete + restore ─────────────────────────────────

    public function test_soft_deleted_booking_excluded_from_total_paid(): void
    {
        $booking = ReligiousBooking::factory()
            ->for(Customer::factory()->create())
            ->withSellingPrice(10_000)
            ->create();

        $payment = $booking->payments()->create([
            'payment_date'    => now(),
            'payment_type'    => 'deposit',
            'currency'        => 'EGP',
            'amount'          => 3000,
            'exchange_rate'   => 1,
            'method'          => 'cash',
            'cash_account_id' => $this->defaultCashAccountId(),
        ]);

        $this->assertEqualsWithDelta(3000.0, (float) $booking->fresh()->total_paid, 0.01);

        $payment->delete();

        $this->assertEqualsWithDelta(0.0, (float) $booking->fresh()->total_paid, 0.01);
    }

    // ── 7. حقول حساسة ──────────────────────────────────────────

    public function test_user_cannot_mass_assign_journal_entry_id_via_payment_request(): void
    {
        $booking = ReligiousBooking::factory()
            ->for(Customer::factory()->create())
            ->withSellingPrice(10_000)
            ->create();

        // المستخدم يحاول حقن journal_entry_id يدوياً
        $this->actingAs($this->userWithRole('accountant'))
            ->post(route('admin.religious.bookings.payments.store', $booking), [
                'payment_date'     => now()->toDateString(),
                'payment_type'     => 'deposit',
                'currency'         => 'EGP',
                'amount'           => 1000,
                'method'           => 'cash',
                'cash_account_id'  => $this->defaultCashAccountId(),
                'journal_entry_id' => 'fake-id-injected-by-attacker',
            ]);

        $payment = $booking->payments()->latest('id')->first();
        // journal_entry_id يجب أن يأتي من Poster، ليس من المستخدم
        $this->assertNotSame('fake-id-injected-by-attacker', $payment->journal_entry_id);
    }
}
