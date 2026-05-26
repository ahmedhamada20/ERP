<?php

namespace Tests\Feature\Domestic;

use App\Models\DomesticBooking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsCashAccountsForTesting;
use Tests\Concerns\SetsUpRolesForTesting;
use Tests\TestCase;

/**
 * Validates Sprint 4 financial guardrails for domestic bookings:
 *  - Overpayment is rejected
 *  - Payment mutations are locked after booking close
 *  - Refund flow respects approval workflow + reservation cap
 *
 * Mirrors PaymentValidationTest (religious) — same invariants must hold
 * for domestic since both share the same overpayment-guard logic.
 */
class DomesticPaymentValidationTest extends TestCase
{
    use RefreshDatabase, SetsUpRolesForTesting, SeedsCashAccountsForTesting;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        $this->seedCashAccounts();
    }

    /* ──────────────────────────────────────────────────────────
       Overpayment validation
       ────────────────────────────────────────────────────────── */

    public function test_payment_amount_within_balance_is_accepted(): void
    {
        $booking = DomesticBooking::factory()->withSellingPrice(10_000)->create();

        $this->actingAs($this->userWithRole('accountant'))
            ->post(route('admin.domestic.bookings.payments.store', $booking), [
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
        $booking = DomesticBooking::factory()->withSellingPrice(10_000)->create();

        $this->actingAs($this->userWithRole('accountant'))
            ->post(route('admin.domestic.bookings.payments.store', $booking), [
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
        $booking = DomesticBooking::factory()->withSellingPrice(10_000)->create();
        $booking->payments()->create([
            'payment_date'  => now(),
            'payment_type'  => 'deposit',
            'currency'      => 'EGP',
            'amount'        => 9_500,
            'exchange_rate' => 1,
            'method'        => 'cash',
        ]);

        $this->actingAs($this->userWithRole('accountant'))
            ->post(route('admin.domestic.bookings.payments.store', $booking), [
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

    public function test_payment_at_exact_remaining_boundary_is_accepted(): void
    {
        $booking = DomesticBooking::factory()->withSellingPrice(10_000)->create();
        $booking->payments()->create([
            'payment_date'  => now(),
            'payment_type'  => 'deposit',
            'currency'      => 'EGP',
            'amount'        => 7_000,
            'exchange_rate' => 1,
            'method'        => 'cash',
        ]);

        $this->actingAs($this->userWithRole('accountant'))
            ->post(route('admin.domestic.bookings.payments.store', $booking), [
                'payment_date' => now()->toDateString(),
                'payment_type' => 'final',
                'currency'     => 'EGP',
                'amount'       => 3_000, // exactly the remainder
                'method'          => 'cash',
                'cash_account_id' => $this->defaultCashAccountId(),
            ])
            ->assertSessionHasNoErrors();

        $this->assertEqualsWithDelta(10_000, $booking->fresh()->total_paid, 0.01);
    }

    /* ──────────────────────────────────────────────────────────
       Locked-booking guards
       ────────────────────────────────────────────────────────── */

    public function test_payment_create_on_closed_booking_returns_422(): void
    {
        $booking = DomesticBooking::factory()->closed()->create();

        $this->actingAs($this->userWithRole('accountant'))
            ->post(route('admin.domestic.bookings.payments.store', $booking), [
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

    public function test_cost_create_on_closed_booking_returns_422(): void
    {
        $booking = DomesticBooking::factory()->closed()->create();

        $this->actingAs($this->userWithRole('accountant'))
            ->post(route('admin.domestic.bookings.costs.store', $booking), [
                'category' => 'hotel',
                'currency' => 'EGP',
                'amount'   => 100,
                'quantity' => 1,
                'per_unit' => 'total',
            ])
            ->assertStatus(422);

        $this->assertSame(0, $booking->costs()->count());
    }

    public function test_payment_delete_on_closed_booking_returns_422(): void
    {
        $booking = DomesticBooking::factory()->create();
        $payment = $booking->payments()->create([
            'payment_date'  => now(),
            'payment_type'  => 'deposit',
            'currency'      => 'EGP',
            'amount'        => 500,
            'exchange_rate' => 1,
            'method'        => 'cash',
        ]);

        $booking->update(['workflow_stage' => 'closed', 'status' => 'completed']);

        $this->actingAs($this->userWithRole('accountant'))
            ->delete(route('admin.domestic.bookings.payments.destroy', [$booking, $payment]))
            ->assertStatus(422);

        $this->assertNotNull($payment->fresh());
    }

    /* ──────────────────────────────────────────────────────────
       Refund workflow
       ────────────────────────────────────────────────────────── */

    public function test_refund_requires_reason(): void
    {
        $booking = DomesticBooking::factory()->withSellingPrice(10_000)->create();
        $booking->payments()->create([
            'payment_date'  => now(),
            'payment_type'  => 'deposit',
            'currency'      => 'EGP',
            'amount'        => 5_000,
            'exchange_rate' => 1,
            'method'        => 'cash',
        ]);

        $this->actingAs($this->userWithRole('accountant'))
            ->post(route('admin.domestic.bookings.payments.store', $booking), [
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
        $booking = DomesticBooking::factory()->withSellingPrice(10_000)->create();
        $booking->payments()->create([
            'payment_date'  => now(),
            'payment_type'  => 'deposit',
            'currency'      => 'EGP',
            'amount'        => 3_000,
            'exchange_rate' => 1,
            'method'        => 'cash',
        ]);

        $this->actingAs($this->userWithRole('accountant'))
            ->post(route('admin.domestic.bookings.payments.store', $booking), [
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
        $booking = DomesticBooking::factory()->withSellingPrice(10_000)->create();
        $booking->payments()->create([
            'payment_date'  => now(),
            'payment_type'  => 'deposit',
            'currency'      => 'EGP',
            'amount'        => 5_000,
            'exchange_rate' => 1,
            'method'        => 'cash',
        ]);

        $accountant = $this->userWithRole('accountant');

        // First refund 3,000 — succeeds
        $this->actingAs($accountant)
            ->post(route('admin.domestic.bookings.payments.store', $booking), [
                'payment_date'  => now()->toDateString(),
                'payment_type'  => 'refund',
                'currency'      => 'EGP',
                'amount'        => 3_000,
                'method'          => 'cash',
                'cash_account_id' => $this->defaultCashAccountId(),
                'refund_reason' =>'استرداد جزئي',
            ])
            ->assertSessionHasNoErrors();

        // Second refund 3,000 — total 6,000 > 5,000 received. Reject.
        $this->actingAs($accountant)
            ->post(route('admin.domestic.bookings.payments.store', $booking), [
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
        $booking = DomesticBooking::factory()->withSellingPrice(10_000)->create();
        $booking->payments()->create([
            'payment_date'  => now(),
            'payment_type'  => 'deposit',
            'currency'      => 'EGP',
            'amount'        => 5_000,
            'exchange_rate' => 1,
            'method'        => 'cash',
        ]);

        $this->assertEqualsWithDelta(5_000, $booking->fresh()->total_paid, 0.01);

        // 1. Accountant requests refund
        $this->actingAs($this->userWithRole('accountant'))
            ->post(route('admin.domestic.bookings.payments.store', $booking), [
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
        $this->assertEqualsWithDelta(5_000, $booking->fresh()->total_paid, 0.01);

        // 2. Manager approves
        $this->actingAs($this->userWithRole('manager'))
            ->post(route('admin.domestic.bookings.payments.approve_refund', [$booking, $refund]))
            ->assertSessionHasNoErrors();

        $this->assertSame('approved', $refund->fresh()->refund_status);
        $this->assertEqualsWithDelta(5_000, $booking->fresh()->total_paid, 0.01);

        // 3. Accountant marks paid
        $this->actingAs($this->userWithRole('accountant'))
            ->post(route('admin.domestic.bookings.payments.mark_refund_paid', [$booking, $refund]))
            ->assertSessionHasNoErrors();

        $this->assertSame('paid', $refund->fresh()->refund_status);
        $this->assertEqualsWithDelta(3_000, $booking->fresh()->total_paid, 0.01);
    }

    public function test_accountant_cannot_approve_refund_without_permission(): void
    {
        $booking = DomesticBooking::factory()->withSellingPrice(10_000)->create();
        $booking->payments()->create([
            'payment_date'  => now(),
            'payment_type'  => 'deposit',
            'currency'      => 'EGP',
            'amount'        => 5_000,
            'exchange_rate' => 1,
            'method'        => 'cash',
        ]);

        $refund = $booking->payments()->create([
            'payment_date'  => now(),
            'payment_type'  => 'refund',
            'currency'      => 'EGP',
            'amount'        => 1_000,
            'exchange_rate' => 1,
            'method'        => 'cash',
            'refund_reason' => 'test',
            'refund_status' => 'pending',
        ]);

        // Accountant role does NOT have domestic_bookings.approve_refund
        $this->actingAs($this->userWithRole('accountant'))
            ->post(route('admin.domestic.bookings.payments.approve_refund', [$booking, $refund]))
            ->assertForbidden();

        $this->assertSame('pending', $refund->fresh()->refund_status);
    }

    public function test_rejected_refund_releases_reservation(): void
    {
        $booking = DomesticBooking::factory()->withSellingPrice(10_000)->create();
        $booking->payments()->create([
            'payment_date'  => now(),
            'payment_type'  => 'deposit',
            'currency'      => 'EGP',
            'amount'        => 5_000,
            'exchange_rate' => 1,
            'method'        => 'cash',
        ]);

        // Pending refund 3,000 — reserves balance
        $refund = $booking->payments()->create([
            'payment_date'  => now(),
            'payment_type'  => 'refund',
            'currency'      => 'EGP',
            'amount'        => 3_000,
            'exchange_rate' => 1,
            'method'        => 'cash',
            'refund_reason' => 'pending request',
            'refund_status' => 'pending',
        ]);

        // Manager rejects — should free up the reservation
        $this->actingAs($this->userWithRole('manager'))
            ->post(route('admin.domestic.bookings.payments.reject_refund', [$booking, $refund]), [
                'approval_notes' => 'العميل غيّر رأيه',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('rejected', $refund->fresh()->refund_status);

        // Now a NEW payment for 5,000 should be accepted (since rejected refund doesn't consume)
        $this->actingAs($this->userWithRole('accountant'))
            ->post(route('admin.domestic.bookings.payments.store', $booking), [
                'payment_date' => now()->toDateString(),
                'payment_type' => 'installment',
                'currency'     => 'EGP',
                'amount'       => 5_000, // 5,000 + new 5,000 = 10,000 = selling price
                'method'          => 'cash',
                'cash_account_id' => $this->defaultCashAccountId(),
            ])
            ->assertSessionHasNoErrors();
    }
}
