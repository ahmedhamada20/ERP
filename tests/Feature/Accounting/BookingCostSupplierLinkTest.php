<?php

namespace Tests\Feature\Accounting;

use App\Models\Customer;
use App\Models\ReligiousBooking;
use App\Models\Supplier;
use App\Services\Accounting\BookingCostJournalPoster;
use Database\Seeders\ChartOfAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpRolesForTesting;
use Tests\TestCase;

/**
 * يتحقق من تكامل Sprint 8: عندما يحمل البند supplier_id محدد، فإن
 * الـ JE يستخدم حساب الأب الخاص بنوع المورد بدلاً من mapping افتراضي
 * من category.
 */
class BookingCostSupplierLinkTest extends TestCase
{
    use RefreshDatabase, SetsUpRolesForTesting;

    private ReligiousBooking $booking;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        $this->seed(ChartOfAccountsSeeder::class);
        $this->actingAs($this->userWithRole('super-admin'));

        $this->booking = ReligiousBooking::factory()
            ->for(Customer::factory()->create())
            ->withSellingPrice(50_000)
            ->create();
        $this->booking->update(['workflow_stage' => 'finance']);
    }

    public function test_cost_with_hotel_supplier_credits_supplier_parent_account(): void
    {
        $hotelSupplier = Supplier::factory()->create([
            'type' => 'hotel',
            'name' => 'فندق هيلتون مكة',
        ]);

        $this->booking->costs()->create([
            'category'      => 'room',
            'supplier_id'   => $hotelSupplier->id,
            'currency'      => 'EGP',
            'amount'        => 5000,
            'exchange_rate' => 1,
            'quantity'      => 1,
            'per_unit'      => 'total',
            'is_revenue'    => false,
        ]);

        $entry = app(BookingCostJournalPoster::class)->postClosingJournal($this->booking->fresh());

        // CR على 2111 (موردين فنادق) — حساب الأب للنوع hotel
        $creditAccCodes = $entry->lines->where('credit', '>', 0)
            ->pluck('account.code')->sort()->values()->all();
        $this->assertContains('2111', $creditAccCodes);

        // DR على 511 (تكلفة الفنادق)
        $debitAccCodes = $entry->lines->where('debit', '>', 0)
            ->pluck('account.code')->sort()->values()->all();
        $this->assertContains('511', $debitAccCodes);
    }

    public function test_cost_without_supplier_uses_category_fallback(): void
    {
        // بنفس category=room لكن بدون supplier_id — يجب أن يستخدم 2111 من mapping
        $this->booking->costs()->create([
            'category'      => 'room',
            'supplier_id'   => null,
            'currency'      => 'EGP',
            'amount'        => 3000,
            'exchange_rate' => 1,
            'quantity'      => 1,
            'per_unit'      => 'total',
            'is_revenue'    => false,
        ]);

        $entry = app(BookingCostJournalPoster::class)->postClosingJournal($this->booking->fresh());

        $creditAccCodes = $entry->lines->where('credit', '>', 0)
            ->pluck('account.code')->sort()->values()->all();
        $this->assertContains('2111', $creditAccCodes);
    }

    public function test_supplier_id_persists_when_cost_is_saved(): void
    {
        $supplier = Supplier::factory()->create(['type' => 'airline']);

        $cost = $this->booking->costs()->create([
            'category'      => 'flight',
            'supplier_id'   => $supplier->id,
            'currency'      => 'EGP',
            'amount'        => 7500,
            'exchange_rate' => 1,
            'quantity'      => 1,
            'per_unit'      => 'total',
            'is_revenue'    => false,
        ]);

        $this->assertSame($supplier->id, $cost->fresh()->supplier_id);
        $this->assertSame('airline', $cost->fresh()->supplier->type);
    }

    public function test_je_balances_with_mixed_supplier_and_no_supplier_costs(): void
    {
        $hotelSupplier = Supplier::factory()->create(['type' => 'hotel']);

        // تكلفة مع مورد
        $this->booking->costs()->create([
            'category' => 'room', 'supplier_id' => $hotelSupplier->id,
            'currency' => 'EGP', 'amount' => 4000, 'exchange_rate' => 1,
            'quantity' => 1, 'per_unit' => 'total', 'is_revenue' => false,
        ]);
        // تكلفة بدون مورد
        $this->booking->costs()->create([
            'category' => 'visa', 'supplier_id' => null,
            'currency' => 'EGP', 'amount' => 1500, 'exchange_rate' => 1,
            'quantity' => 1, 'per_unit' => 'total', 'is_revenue' => false,
        ]);

        $entry = app(BookingCostJournalPoster::class)->postClosingJournal($this->booking->fresh());

        $this->assertEqualsWithDelta(5500, (float) $entry->total_debit, 0.01);
        $this->assertEqualsWithDelta(5500, (float) $entry->total_credit, 0.01);
        $this->assertTrue($entry->isPosted());
    }

    public function test_cost_validation_accepts_supplier_id_via_http(): void
    {
        $supplier = Supplier::factory()->create(['type' => 'transport']);

        // الحجز يجب ألا يكون في finance/closed حتى يقبل تكاليف جديدة عبر HTTP
        $openBooking = ReligiousBooking::factory()
            ->for(Customer::factory()->create())
            ->withSellingPrice(20_000)
            ->create();

        $this->post(route('admin.religious.bookings.costs.store', $openBooking), [
            'category'    => 'transport',
            'supplier_id' => $supplier->id,
            'currency'    => 'EGP',
            'amount'      => 2000,
            'quantity'    => 1,
            'per_unit'    => 'total',
        ])->assertSessionHasNoErrors();

        $cost = $openBooking->costs()->latest('id')->first();
        $this->assertSame($supplier->id, $cost->supplier_id);
    }

    public function test_cost_rejects_invalid_supplier_id(): void
    {
        $openBooking = ReligiousBooking::factory()
            ->for(Customer::factory()->create())
            ->withSellingPrice(20_000)
            ->create();

        $this->post(route('admin.religious.bookings.costs.store', $openBooking), [
            'category'    => 'transport',
            'supplier_id' => '01HXXXXXXXXXXXXXXXXXXXXXXX', // ulid مزيف
            'currency'    => 'EGP',
            'amount'      => 2000,
            'quantity'    => 1,
            'per_unit'    => 'total',
        ])->assertSessionHasErrors(['supplier_id']);
    }
}
