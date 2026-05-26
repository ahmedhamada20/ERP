<?php

namespace Tests\Feature;

use App\Models\Airline;
use App\Models\Customer;
use App\Models\Hotel;
use App\Models\ReligiousBooking;
use App\Models\TransportProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpRolesForTesting;
use Tests\TestCase;

/**
 * Sprint 9: التحقق من أن BookingAccommodation و BookingTransportation
 * يستطيعون الربط بسجلات الماستر (Hotel, Airline, TransportProvider)
 * عبر FKs الجديدة، مع الحفاظ على الحقول النصية كـ fallback.
 */
class MasterDataLinkTest extends TestCase
{
    use RefreshDatabase, SetsUpRolesForTesting;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
    }

    public function test_accommodation_can_link_to_master_hotel(): void
    {
        $booking = ReligiousBooking::factory()
            ->for(Customer::factory()->create())
            ->withSellingPrice(20_000)
            ->create();

        $hotel = Hotel::create([
            'name'      => 'هيلتون مكة',
            'city'      => 'mecca',
            'grade'     => '5_stars',
            'is_active' => true,
        ]);

        $acc = $booking->accommodations()->create([
            'hotel_id'                  => $hotel->id,
            'city'                      => 'mecca',
            'hotel_name'                => $hotel->name,
            'hotel_grade'               => '5_stars',
            'check_in_date'             => '2026-06-01',
            'check_out_date'            => '2026-06-05',
            'nights'                    => 4,
            'rooms_count'               => 2,
            'room_type'                 => 'double',
            'pax_per_room'              => 2,
            'meal_plan'                 => 'bb',
            'room_price_per_night_sar'  => 1000,
            'exchange_rate'             => 12.5,
        ]);

        $this->assertSame($hotel->id, $acc->fresh()->hotel_id);
        $this->assertSame('هيلتون مكة', $acc->fresh()->hotel->name);
    }

    public function test_accommodation_without_hotel_id_still_works(): void
    {
        $booking = ReligiousBooking::factory()
            ->for(Customer::factory()->create())
            ->withSellingPrice(20_000)
            ->create();

        $acc = $booking->accommodations()->create([
            'city'                      => 'medina',
            'hotel_name'                => 'فندق نص حر',
            'hotel_grade'               => '4_stars',
            'check_in_date'             => '2026-06-01',
            'check_out_date'            => '2026-06-03',
            'nights'                    => 2,
            'rooms_count'               => 1,
            'room_type'                 => 'single',
            'pax_per_room'              => 1,
            'meal_plan'                 => 'ro',
            'room_price_per_night_sar'  => 500,
            'exchange_rate'             => 12.5,
        ]);

        $this->assertNull($acc->fresh()->hotel_id);
        $this->assertNull($acc->fresh()->hotel);
        $this->assertSame('فندق نص حر', $acc->fresh()->hotel_name);
    }

    public function test_transportation_can_link_to_master_airline(): void
    {
        $booking = ReligiousBooking::factory()
            ->for(Customer::factory()->create())
            ->withSellingPrice(20_000)
            ->create();

        $airline = Airline::create([
            'code'         => 'AIR-001',
            'airline_name' => 'مصر للطيران',
            'airline_code' => 'MS',
            'route'        => 'CAI-JED',
            'is_active'    => true,
        ]);

        $trans = $booking->transportation()->create([
            'airline_id'      => $airline->id,
            'type'            => 'flight',
            'direction'       => 'outbound',
            'segment'         => 'cai_jed',
            'carrier_name'    => $airline->airline_name,
            'currency'        => 'EGP',
            'cost_per_person' => 5000,
            'pax_count'       => 10,
            'exchange_rate'   => 1,
        ]);

        $this->assertSame($airline->id, $trans->fresh()->airline_id);
        $this->assertSame('مصر للطيران', $trans->fresh()->airline->airline_name);
    }

    public function test_transportation_can_link_to_master_transport_provider(): void
    {
        $booking = ReligiousBooking::factory()
            ->for(Customer::factory()->create())
            ->withSellingPrice(20_000)
            ->create();

        $provider = TransportProvider::create([
            'name'      => 'شركة النخبة للنقل',
            'type'      => 'bus',
            'is_active' => true,
        ]);

        $trans = $booking->transportation()->create([
            'transport_provider_id' => $provider->id,
            'type'                  => 'bus',
            'direction'             => 'internal',
            'segment'               => 'jed_mec',
            'carrier_name'          => $provider->name,
            'currency'              => 'SAR',
            'cost_per_person'       => 100,
            'pax_count'             => 20,
            'exchange_rate'         => 12.5,
        ]);

        $this->assertSame($provider->id, $trans->fresh()->transport_provider_id);
        $this->assertSame('شركة النخبة للنقل', $trans->fresh()->transportProvider->name);
    }

    public function test_master_data_fields_optional_via_http_store(): void
    {
        $booking = ReligiousBooking::factory()
            ->for(Customer::factory()->create())
            ->withSellingPrice(20_000)
            ->create();

        $this->actingAs($this->userWithRole('super-admin'))
            ->post(route('admin.religious.bookings.accommodations.store', $booking), [
                'city'                      => 'mecca',
                'hotel_name'                => 'بدون ربط ماستر',
                'hotel_grade'               => '4_stars',
                'check_in_date'             => '2026-06-01',
                'check_out_date'            => '2026-06-03',
                'nights'                    => 2,
                'rooms_count'               => 1,
                'room_type'                 => 'double',
                'pax_per_room'              => 2,
                'meal_plan'                 => 'bb',
                'room_price_per_night_sar'  => 800,
            ])
            ->assertSessionHasNoErrors();
    }
}
