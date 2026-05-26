<?php

namespace Tests\Feature;

use App\Models\BookingPayment;
use App\Models\ReligiousBooking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsCashAccountsForTesting;
use Tests\Concerns\SetsUpRolesForTesting;
use Tests\TestCase;

/**
 * Validates Sprint 1 financial guardrails:
 *  - Overpayment is rejected (Step 1)
 *  - Payment mutations are locked after booking close (Step 2)
 *  - Refund flow respects approval workflow + reservation cap (Step 3)
 */
class PaymentValidationTest extends TestCase
{
    use RefreshDatabase, SetsUpRolesForTesting, SeedsCashAccountsForTesting;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        $this->seedCashAccounts();
    }

    /** الحقول الافتراضية المطلوبة في كل تسجيل دفعة بعد Sprint 7.4. */
    private function cashDefaults(): array
    {
        return ['cash_account_id' => $this->defaultCashAccountId()];
    }

    /* ──────────────────────────────────────────────────────────
       Step 1: Overpayment validation
       ────────────────────────────────────────────────────────── */

    public function test_payment_amount_within_balance_is_accepted(): void
    {
        $booking = ReligiousBooking::factory()->withSellingPrice(10_000)->create();

        $this->actingAs($this->userWithRole('accountant'))
            ->post(route('admin.religious.bookings.payments.store', $booking), [
                'payment_date' => now()->toDateString(),
                'payment_type' => 'deposit',
                'currency'     => 'EGP',
                'amount'       => 3_000,
                'method'          => 'cash',
                'cash_account_id' => $this->defaultCashAccountId(),
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(1, $booking->payments()->count());
    }

    public function test_payment_exceeding_selling_price_is_rejected(): void
    {
        $booking = ReligiousBooking::factory()->withSellingPrice(10_000)->create();

        $this->actingAs($this->userWithRole('accountant'))
            ->post(route('admin.religious.bookings.payments.store', $booking), [
                'payment_date' => now()->toDateString(),
                'payment_type' => 'deposit',
                'currency'     => 'EGP',
                'amount'       => 12_000,
                'method'          => 'cash',
                'cash_account_id' => $this->defaultCashAccountId(),
            ])
            ->assertSessionHasErrors(['amount']);

        $this->assertSame(0, $booking->payments()->count());
    }

    public function test_second_payment_overshooting_remaining_is_rejected(): void
    {
        $booking = ReligiousBooking::factory()->withSellingPrice(10_000)->create();
        $booking->payments()->create([
            'payment_date' => now(),
            'payment_type' => 'deposit',
            'currency'     => 'EGP',
            'amount'       => 9_500,
            'exchange_rate'=> 1,
            'method'       => 'cash',
        ]);

        $this->actingAs($this->userWithRole('accountant'))
            ->post(route('admin.religious.bookings.payments.store', $booking), [
                'payment_date' => now()->toDateString(),
                'payment_type' => 'installment',
                'currency'     => 'EGP',
                'amount'       => 600, // would total 10,100 — over the 10,000 selling price
                'method'          => 'cash',
                'cash_account_id' => $this->defaultCashAccountId(),
            ])
            ->assertSessionHasErrors(['amount']);

        $this->assertSame(1, $booking->payments()->count());
    }

    /* ──────────────────────────────────────────────────────────
       Step 2: Locked-booking guards
       ────────────────────────────────────────────────────────── */

    public function test_payment_create_on_closed_booking_returns_422(): void
    {
        $booking = ReligiousBooking::factory()->closed()->create();

        $this->actingAs($this->userWithRole('accountant'))
            ->post(route('admin.religious.bookings.payments.store', $booking), [
                'payment_date' => now()->toDateString(),
                'payment_type' => 'installment',
                'currency'     => 'EGP',
                'amount'       => 100,
                'method'          => 'cash',
                'cash_account_id' => $this->defaultCashAccountId(),
            ])
            ->assertStatus(422);

        $this->assertSame(0, $booking->payments()->count());
    }

    public function test_payment_delete_on_closed_booking_returns_422(): void
    {
        $booking = ReligiousBooking::factory()->create();
        $payment = $booking->payments()->create([
            'payment_date' => now(),
            'payment_type' => 'deposit',
            'currency'     => 'EGP',
            'amount'       => 500,
            'exchange_rate'=> 1,
            'method'       => 'cash',
        ]);

        // Close the booking after the payment was added
        $booking->update(['workflow_stage' => 'closed', 'status' => 'completed']);

        $this->actingAs($this->userWithRole('accountant'))
            ->delete(route('admin.religious.bookings.payments.destroy', [$booking, $payment]))
            ->assertStatus(422);

        $this->assertNotNull($payment->fresh());
    }

    /* ──────────────────────────────────────────────────────────
       Step 3: Refund workflow
       ────────────────────────────────────────────────────────── */

    public function test_refund_requires_reason(): void
    {
        $booking = ReligiousBooking::factory()->withSellingPrice(10_000)->create();
        $booking->payments()->create([
            'payment_date' => now(),
            'payment_type' => 'deposit',
            'currency'     => 'EGP',
            'amount'       => 5_000,
            'exchange_rate'=> 1,
            'method'       => 'cash',
        ]);

        $this->actingAs($this->userWithRole('accountant'))
            ->post(route('admin.religious.bookings.payments.store', $booking), [
                'payment_date' => now()->toDateString(),
                'payment_type' => 'refund',
                'currency'     => 'EGP',
                'amount'       => 1_000,
                'method'       => 'cash',
                // refund_reason omitted on purpose
            ])
            ->assertSessionHasErrors(['refund_reason']);
    }

    public function test_refund_amount_exceeding_available_is_rejected(): void
    {
        $booking = ReligiousBooking::factory()->withSellingPrice(10_000)->create();
        $booking->payments()->create([
            'payment_date' => now(),
            'payment_type' => 'deposit',
            'currency'     => 'EGP',
            'amount'       => 3_000,
            'exchange_rate'=> 1,
            'method'       => 'cash',
        ]);

        $this->actingAs($this->userWithRole('accountant'))
            ->post(route('admin.religious.bookings.payments.store', $booking), [
                'payment_date'  => now()->toDateString(),
                'payment_type'  => 'refund',
                'currency'      => 'EGP',
                'amount'        => 5_000, // > 3,000 received
                'method'          => 'cash',
                'cash_account_id' => $this->defaultCashAccountId(),
                'refund_reason' =>'إلغاء الرحلة',
            ])
            ->assertSessionHasErrors(['amount']);

        $this->assertSame(1, $booking->payments()->count());
    }

    public function test_pending_refund_reserves_balance_preventing_double_refund(): void
    {
        $booking = ReligiousBooking::factory()->withSellingPrice(10_000)->create();
        $booking->payments()->create([
            'payment_date' => now(),
            'payment_type' => 'deposit',
            'currency'     => 'EGP',
            'amount'       => 5_000,
            'exchange_rate'=> 1,
            'method'       => 'cash',
        ]);

        $accountant = $this->userWithRole('accountant');

        // First refund request for 3,000 — should succeed
        $this->actingAs($accountant)
            ->post(route('admin.religious.bookings.payments.store', $booking), [
                'payment_date'  => now()->toDateString(),
                'payment_type'  => 'refund',
                'currency'      => 'EGP',
                'amount'        => 3_000,
                'method'          => 'cash',
                'cash_account_id' => $this->defaultCashAccountId(),
                'refund_reason' =>'استرداد جزئي',
            ])
            ->assertSessionHasNoErrors();

        // Second refund request for 3,000 — total would be 6,000 > 5,000 received. Reject.
        $this->actingAs($accountant)
            ->post(route('admin.religious.bookings.payments.store', $booking), [
                'payment_date'  => now()->toDateString(),
                'payment_type'  => 'refund',
                'currency'      => 'EGP',
                'amount'        => 3_000,
                'method'          => 'cash',
                'cash_account_id' => $this->defaultCashAccountId(),
                'refund_reason' =>'استرداد ثاني',
            ])
            ->assertSessionHasErrors(['amount']);

        $this->assertSame(1, $booking->payments()->where('payment_type', 'refund')->count());
    }

    public function test_refund_full_lifecycle_updates_total_paid(): void
    {
        $booking = ReligiousBooking::factory()->withSellingPrice(10_000)->create();
        $booking->payments()->create([
            'payment_date' => now(),
            'payment_type' => 'deposit',
            'currency'     => 'EGP',
            'amount'       => 5_000,
            'exchange_rate'=> 1,
            'method'       => 'cash',
        ]);

        $this->assertEqualsWithDelta(5_000, $booking->fresh()->total_paid, 0.01);

        // 1. Accountant requests refund
        $this->actingAs($this->userWithRole('accountant'))
            ->post(route('admin.religious.bookings.payments.store', $booking), [
                'payment_date'  => now()->toDateString(),
                'payment_type'  => 'refund',
                'currency'      => 'EGP',
                'amount'        => 2_000,
                'method'          => 'cash',
                'cash_account_id' => $this->defaultCashAccountId(),
                'refund_reason' =>'تخفيض السعر',
            ])
            ->assertSessionHasNoErrors();

        $refund = $booking->payments()->where('payment_type', 'refund')->firstOrFail();
        $this->assertSame('pending', $refund->refund_status);

        // total_paid not yet reduced (refund is only pending)
        $this->assertEqualsWithDelta(5_000, $booking->fresh()->total_paid, 0.01);

        // 2. Manager approves
        $this->actingAs($this->userWithRole('manager'))
            ->post(route('admin.religious.bookings.payments.approve_refund', [$booking, $refund]))
            ->assertSessionHasNoErrors();

        $this->assertSame('approved', $refund->fresh()->refund_status);

        // still not reduced — approved but not yet paid out to customer
        $this->assertEqualsWithDelta(5_000, $booking->fresh()->total_paid, 0.01);

        // 3. Accountant marks paid (money actually sent to customer)
        $this->actingAs($this->userWithRole('accountant'))
            ->post(route('admin.religious.bookings.payments.mark_refund_paid', [$booking, $refund]))
            ->assertSessionHasNoErrors();

        $this->assertSame('paid', $refund->fresh()->refund_status);

        // NOW total_paid is reduced
        $this->assertEqualsWithDelta(3_000, $booking->fresh()->total_paid, 0.01);
    }

    /* ──────────────────────────────────────────────────────────
       Step 4 (Sprint 7.2): Per-payment refund cap — prevent double refund
       ────────────────────────────────────────────────────────── */

    public function test_refund_exceeding_specific_payment_amount_is_rejected(): void
    {
        $booking = ReligiousBooking::factory()->withSellingPrice(20_000)->create();
        $p1 = $booking->payments()->create([
            'payment_date'  => now(),
            'payment_type'  => 'deposit',
            'currency'      => 'EGP',
            'amount'        => 3_000,
            'exchange_rate' => 1,
            'method'        => 'cash',
        ]);
        // دفعة أخرى أكبر — تكفي على مستوى الحجز لكن لا تخص p1
        $booking->payments()->create([
            'payment_date'  => now(),
            'payment_type'  => 'installment',
            'currency'      => 'EGP',
            'amount'        => 10_000,
            'exchange_rate' => 1,
            'method'        => 'cash',
        ]);

        // محاولة استرداد 5000 من p1 رغم أن قيمتها 3000 فقط
        $this->actingAs($this->userWithRole('accountant'))
            ->post(route('admin.religious.bookings.payments.store', $booking), [
                'payment_date'        => now()->toDateString(),
                'payment_type'        => 'refund',
                'currency'            => 'EGP',
                'amount'              => 5_000,
                'method'              => 'cash',
                'cash_account_id'     => $this->defaultCashAccountId(),
                'refund_reason'       =>'محاولة استرداد مزدوج',
                'refunded_payment_id' => $p1->id,
            ])
            ->assertSessionHasErrors(['amount']);
    }

    public function test_double_full_refund_of_same_payment_is_rejected(): void
    {
        $booking = ReligiousBooking::factory()->withSellingPrice(20_000)->create();
        $p1 = $booking->payments()->create([
            'payment_date'  => now(),
            'payment_type'  => 'deposit',
            'currency'      => 'EGP',
            'amount'        => 5_000,
            'exchange_rate' => 1,
            'method'        => 'cash',
        ]);
        $booking->payments()->create([
            'payment_date'  => now(),
            'payment_type'  => 'installment',
            'currency'      => 'EGP',
            'amount'        => 10_000,
            'exchange_rate' => 1,
            'method'        => 'cash',
        ]);

        $accountant = $this->userWithRole('accountant');

        // استرداد كامل من p1
        $this->actingAs($accountant)
            ->post(route('admin.religious.bookings.payments.store', $booking), [
                'payment_date'        => now()->toDateString(),
                'payment_type'        => 'refund',
                'currency'            => 'EGP',
                'amount'              => 5_000,
                'method'              => 'cash',
                'cash_account_id'     => $this->defaultCashAccountId(),
                'refund_reason'       =>'استرداد أول',
                'refunded_payment_id' => $p1->id,
            ])
            ->assertSessionHasNoErrors();

        // محاولة استرداد ثاني من نفس p1 — يجب أن يُرفض حتى لو كان الحجز كله مرتاح
        $this->actingAs($accountant)
            ->post(route('admin.religious.bookings.payments.store', $booking), [
                'payment_date'        => now()->toDateString(),
                'payment_type'        => 'refund',
                'currency'            => 'EGP',
                'amount'              => 1_000,
                'method'              => 'cash',
                'cash_account_id'     => $this->defaultCashAccountId(),
                'refund_reason'       =>'محاولة استرداد ثاني من نفس الإيصال',
                'refunded_payment_id' => $p1->id,
            ])
            ->assertSessionHasErrors(['amount']);

        $this->assertSame(1, $booking->payments()->where('refunded_payment_id', $p1->id)->count());
    }

    public function test_partial_refunds_summing_to_original_amount_is_allowed(): void
    {
        $booking = ReligiousBooking::factory()->withSellingPrice(20_000)->create();
        $p1 = $booking->payments()->create([
            'payment_date'  => now(),
            'payment_type'  => 'deposit',
            'currency'      => 'EGP',
            'amount'        => 5_000,
            'exchange_rate' => 1,
            'method'        => 'cash',
        ]);

        $accountant = $this->userWithRole('accountant');

        // استرداد جزئي 2000
        $this->actingAs($accountant)
            ->post(route('admin.religious.bookings.payments.store', $booking), [
                'payment_date'        => now()->toDateString(),
                'payment_type'        => 'refund',
                'currency'            => 'EGP',
                'amount'              => 2_000,
                'method'              => 'cash',
                'cash_account_id'     => $this->defaultCashAccountId(),
                'refund_reason'       =>'استرداد جزئي 1',
                'refunded_payment_id' => $p1->id,
            ])
            ->assertSessionHasNoErrors();

        // استرداد جزئي ثانٍ 3000 — المجموع = 5000 = قيمة p1 — مسموح
        $this->actingAs($accountant)
            ->post(route('admin.religious.bookings.payments.store', $booking), [
                'payment_date'        => now()->toDateString(),
                'payment_type'        => 'refund',
                'currency'            => 'EGP',
                'amount'              => 3_000,
                'method'              => 'cash',
                'cash_account_id'     => $this->defaultCashAccountId(),
                'refund_reason'       =>'استرداد جزئي 2',
                'refunded_payment_id' => $p1->id,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(2, $booking->payments()->where('refunded_payment_id', $p1->id)->count());
    }

    /* ──────────────────────────────────────────────────────────
       Step 5 (Sprint 7.4): cash_account_id binding to specific account
       ────────────────────────────────────────────────────────── */

    public function test_payment_without_cash_account_id_is_rejected(): void
    {
        $booking = ReligiousBooking::factory()->withSellingPrice(10_000)->create();

        $this->actingAs($this->userWithRole('accountant'))
            ->post(route('admin.religious.bookings.payments.store', $booking), [
                'payment_date' => now()->toDateString(),
                'payment_type' => 'deposit',
                'currency'     => 'EGP',
                'amount'       => 3_000,
                'method'       => 'cash',
                // عمداً بدون cash_account_id
            ])
            ->assertSessionHasErrors(['cash_account_id']);
    }

    public function test_cash_payment_must_use_cash_typed_account(): void
    {
        $booking = ReligiousBooking::factory()->withSellingPrice(10_000)->create();

        // محاولة استخدام حساب بنك مع طريقة "نقدي"
        $this->actingAs($this->userWithRole('accountant'))
            ->post(route('admin.religious.bookings.payments.store', $booking), [
                'payment_date'    => now()->toDateString(),
                'payment_type'    => 'deposit',
                'currency'        => 'EGP',
                'amount'          => 3_000,
                'method'          => 'cash',
                'cash_account_id' => $this->defaultBankAccountId(),
            ])
            ->assertSessionHasErrors(['cash_account_id']);
    }

    public function test_bank_transfer_must_use_bank_typed_account(): void
    {
        $booking = ReligiousBooking::factory()->withSellingPrice(10_000)->create();

        // محاولة استخدام حساب خزينة مع طريقة "تحويل بنكي"
        $this->actingAs($this->userWithRole('accountant'))
            ->post(route('admin.religious.bookings.payments.store', $booking), [
                'payment_date'    => now()->toDateString(),
                'payment_type'    => 'deposit',
                'currency'        => 'EGP',
                'amount'          => 3_000,
                'method'          => 'bank_transfer',
                'cash_account_id' => $this->defaultCashAccountId(),
            ])
            ->assertSessionHasErrors(['cash_account_id']);
    }

    public function test_payment_persists_chosen_cash_account_id(): void
    {
        $booking = ReligiousBooking::factory()->withSellingPrice(10_000)->create();
        $bankId  = $this->defaultBankAccountId();

        $this->actingAs($this->userWithRole('accountant'))
            ->post(route('admin.religious.bookings.payments.store', $booking), [
                'payment_date'    => now()->toDateString(),
                'payment_type'    => 'deposit',
                'currency'        => 'EGP',
                'amount'          => 3_000,
                'method'          => 'bank_transfer',
                'cash_account_id' => $bankId,
            ])
            ->assertSessionHasNoErrors();

        $payment = $booking->payments()->latest('id')->first();
        $this->assertSame($bankId, $payment->cash_account_id);
    }

    public function test_accountant_cannot_approve_refund_without_permission(): void
    {
        $booking = ReligiousBooking::factory()->withSellingPrice(10_000)->create();
        $booking->payments()->create([
            'payment_date' => now(),
            'payment_type' => 'deposit',
            'currency'     => 'EGP',
            'amount'       => 5_000,
            'exchange_rate'=> 1,
            'method'       => 'cash',
        ]);

        $refund = $booking->payments()->create([
            'payment_date' => now(),
            'payment_type' => 'refund',
            'currency'     => 'EGP',
            'amount'       => 1_000,
            'exchange_rate'=> 1,
            'method'       => 'cash',
            'refund_reason'=> 'test',
            'refund_status'=> 'pending',
        ]);

        // Accountant role does NOT have religious_bookings.approve_refund
        $this->actingAs($this->userWithRole('accountant'))
            ->post(route('admin.religious.bookings.payments.approve_refund', [$booking, $refund]))
            ->assertForbidden();

        $this->assertSame('pending', $refund->fresh()->refund_status);
    }
}
