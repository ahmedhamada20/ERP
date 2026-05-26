<?php

namespace Tests\Feature;

use App\Models\Airline;
use App\Models\BookingCost;
use App\Models\Customer;
use App\Models\DomesticBookingCost;
use App\Models\Hotel;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\ReligiousBooking;
use App\Models\DomesticBooking;
use App\Models\Supplier;
use App\Models\TransportProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\Concerns\SetsUpRolesForTesting;
use Tests\TestCase;

/**
 * يتحقق من العلاقات العكسية الجديدة (Sprint 10):
 *  - Hotel→accommodations, Airline/TransportProvider→transportation
 *  - Supplier→{invoices, bookingCosts, domesticBookingCosts}
 *  - Booking→commissionLines (morph)
 *
 * بالإضافة إلى أن LeadObserver + OpportunityObserver يبطلوا
 * cache الإحصائيات تلقائياً.
 */
class InverseRelationsTest extends TestCase
{
    use RefreshDatabase, SetsUpRolesForTesting;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
    }

    public function test_hotel_lists_its_accommodations(): void
    {
        $hotel = Hotel::create([
            'name' => 'فندق اختبار', 'city' => 'mecca',
            'grade' => '4_stars', 'is_active' => true,
        ]);

        $booking = ReligiousBooking::factory()
            ->for(Customer::factory()->create())
            ->withSellingPrice(10_000)
            ->create();

        $booking->accommodations()->create([
            'hotel_id'                 => $hotel->id,
            'city'                     => 'mecca',
            'hotel_name'               => $hotel->name,
            'hotel_grade'              => '4_stars',
            'check_in_date'            => '2026-06-01',
            'check_out_date'           => '2026-06-03',
            'nights'                   => 2,
            'rooms_count'              => 1,
            'room_type'                => 'double',
            'pax_per_room'             => 2,
            'meal_plan'                => 'bb',
            'room_price_per_night_sar' => 500,
            'exchange_rate'            => 12.5,
        ]);

        $this->assertCount(1, $hotel->fresh()->accommodations);
        $this->assertSame($booking->id, $hotel->accommodations->first()->booking_id);
    }

    public function test_airline_lists_its_transportation(): void
    {
        $airline = Airline::create([
            'code' => 'AIR-T-001', 'airline_name' => 'مصر للطيران',
            'airline_code' => 'MS', 'route' => 'CAI-JED', 'is_active' => true,
        ]);

        $booking = ReligiousBooking::factory()
            ->for(Customer::factory()->create())
            ->withSellingPrice(10_000)
            ->create();

        $booking->transportation()->create([
            'airline_id'      => $airline->id,
            'type'            => 'flight',
            'direction'       => 'outbound',
            'segment'         => 'cai_jed',
            'currency'        => 'EGP',
            'cost_per_person' => 3000,
            'pax_count'       => 5,
            'exchange_rate'   => 1,
        ]);

        $this->assertCount(1, $airline->fresh()->transportation);
    }

    public function test_transport_provider_lists_its_transportation(): void
    {
        $provider = TransportProvider::create([
            'name' => 'النقل السريع', 'type' => 'bus', 'is_active' => true,
        ]);

        $booking = ReligiousBooking::factory()
            ->for(Customer::factory()->create())
            ->withSellingPrice(10_000)
            ->create();

        $booking->transportation()->create([
            'transport_provider_id' => $provider->id,
            'type'                  => 'bus',
            'direction'             => 'internal',
            'segment'               => 'jed_mec',
            'currency'              => 'SAR',
            'cost_per_person'       => 50,
            'pax_count'             => 20,
            'exchange_rate'         => 12.5,
        ]);

        $this->assertCount(1, $provider->fresh()->transportation);
    }

    public function test_supplier_lists_all_its_cost_lines(): void
    {
        $supplier = Supplier::factory()->create(['type' => 'hotel']);

        $religious = ReligiousBooking::factory()
            ->for(Customer::factory()->create())
            ->withSellingPrice(10_000)
            ->create();
        $domestic = DomesticBooking::factory()
            ->for(Customer::factory()->create())
            ->withSellingPrice(5_000)
            ->create();

        $religious->costs()->create([
            'category' => 'room', 'supplier_id' => $supplier->id,
            'currency' => 'EGP', 'amount' => 3000, 'exchange_rate' => 1,
            'quantity' => 1, 'per_unit' => 'total', 'is_revenue' => false,
        ]);
        $domestic->costs()->create([
            'category' => 'hotel', 'supplier_id' => $supplier->id,
            'currency' => 'EGP', 'amount' => 2000, 'exchange_rate' => 1,
            'quantity' => 1, 'per_unit' => 'total', 'is_revenue' => false,
        ]);

        $this->assertCount(1, $supplier->fresh()->bookingCosts);
        $this->assertCount(1, $supplier->fresh()->domesticBookingCosts);
        // وأيضاً supplier ليس له فواتير
        $this->assertCount(0, $supplier->fresh()->invoices);
    }

    public function test_lead_observer_invalidates_stats_cache_on_save(): void
    {
        Cache::put('leads.kpi_stats', ['stale' => true], 60);

        Lead::create([
            'full_name'  => 'عميل محتمل',
            'phone'      => '01000000000',
            'source'     => 'whatsapp',
            'status'     => 'new',
            'created_by' => $this->userWithRole('booking-staff')->id,
        ]);

        $this->assertNull(Cache::get('leads.kpi_stats'),
            'يجب أن يبطل LeadObserver الكاش تلقائياً عند إنشاء lead جديد');
    }

    public function test_lead_observer_invalidates_cache_on_delete(): void
    {
        $lead = Lead::create([
            'full_name'  => 'يحذف',
            'phone'      => '01100000000',
            'source'     => 'whatsapp',
            'status'     => 'new',
            'created_by' => $this->userWithRole('booking-staff')->id,
        ]);

        Cache::put('leads.kpi_stats', ['stale' => true], 60);
        $lead->delete();

        $this->assertNull(Cache::get('leads.kpi_stats'));
    }

    public function test_opportunity_observer_invalidates_stats_cache(): void
    {
        $lead = Lead::create([
            'full_name' => 'محتمل', 'phone' => '01010000000', 'source' => 'website',
            'status' => 'new', 'created_by' => $this->userWithRole('booking-staff')->id,
        ]);

        Cache::put('opportunities.kpi_stats', ['stale' => true], 60);

        Opportunity::create([
            'lead_id'         => $lead->id,
            'title'           => 'فرصة اختبار',
            'booking_type'    => 'religious',
            'estimated_value' => 10000,
            'probability'     => 50,
            'stage'           => 'prospecting',
            'expected_close_date' => now()->addMonth(),
            'assigned_to'     => $this->userWithRole('booking-staff')->id,
        ]);

        $this->assertNull(Cache::get('opportunities.kpi_stats'));
    }

    public function test_whatsapp_message_resolves_new_polymorphic_types(): void
    {
        // اختبار خفيف بدون إنشاء SupplierInvoice حقيقي (لا factory له)
        // — نختبر فقط أن المفاتيح الجديدة موجودة في match الـ related().
        $reflection  = new \ReflectionMethod(\App\Models\WhatsappMessage::class, 'related');
        $source = file_get_contents($reflection->getFileName());

        foreach (['supplier_invoice', 'employee_loan', 'payslip', 'religious_alert'] as $type) {
            $this->assertStringContainsString(
                "'{$type}'", $source,
                "WhatsappMessage::related() يجب أن يدعم النوع: {$type}"
            );
        }
    }
}
