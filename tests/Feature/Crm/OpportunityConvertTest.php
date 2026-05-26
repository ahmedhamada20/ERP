<?php

namespace Tests\Feature\Crm;

use App\Models\Customer;
use App\Models\DomesticBooking;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\ReligiousBooking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpRolesForTesting;
use Tests\TestCase;

class OpportunityConvertTest extends TestCase
{
    use RefreshDatabase, SetsUpRolesForTesting;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
    }

    public function test_opportunity_probability_auto_sets_from_stage_on_create(): void
    {
        $opp = Opportunity::factory()->atStage('proposal')->create(['probability' => null]);
        $this->assertSame(60, $opp->probability, 'proposal stage should default to 60%');

        $opp2 = Opportunity::factory()->atStage('negotiation')->create(['probability' => null]);
        $this->assertSame(80, $opp2->probability);
    }

    public function test_weighted_value_is_estimated_times_probability(): void
    {
        $opp = Opportunity::factory()->create([
            'estimated_value' => 25000,
            'probability'     => 60,
        ]);

        $this->assertEqualsWithDelta(15000.0, $opp->weighted_value, 0.01);
    }

    public function test_stage_change_to_closed_won_auto_sets_actual_close_date(): void
    {
        $opp = Opportunity::factory()->atStage('proposal')->create();
        $this->assertNull($opp->actual_close_date);

        $opp->update(['stage' => 'closed_won']);

        $this->assertNotNull($opp->fresh()->actual_close_date);
    }

    public function test_convert_religious_opportunity_creates_religious_booking(): void
    {
        $customer = Customer::factory()->create();
        $opp = Opportunity::factory()->religious('umrah')->create([
            'customer_id'      => $customer->id,
            'estimated_value'  => 30000,
            'pax_count'        => 2,
        ]);

        $this->actingAs($this->userWithRole('manager'))
            ->post(route('admin.crm.opportunities.convert', $opp), [
                'customer_id'   => $customer->id,
                'trip_date'     => now()->addDays(30)->toDateString(),
                'selling_price' => 30000,
            ])
            ->assertRedirect();

        $opp->refresh();
        $this->assertSame('closed_won', $opp->stage);
        $this->assertSame(100, $opp->probability);
        $this->assertNotNull($opp->actual_close_date);
        $this->assertSame('religious', $opp->converted_booking_type);
        $this->assertNotNull($opp->converted_booking_id);

        $booking = ReligiousBooking::findOrFail($opp->converted_booking_id);
        $this->assertSame('umrah', $booking->type);
        $this->assertSame($customer->id, $booking->customer_id);
        $this->assertSame(2, $booking->adults_count);
        $this->assertEqualsWithDelta(30000, (float) $booking->selling_price, 0.01);
        $this->assertStringContainsString($opp->code, $booking->notes);
    }

    public function test_convert_domestic_opportunity_creates_domestic_booking(): void
    {
        $customer = Customer::factory()->create();
        $opp = Opportunity::factory()->domestic('package')->create([
            'customer_id'     => $customer->id,
            'destination'     => 'شرم الشيخ',
            'estimated_value' => 18000,
            'pax_count'       => 4,
        ]);

        $this->actingAs($this->userWithRole('manager'))
            ->post(route('admin.crm.opportunities.convert', $opp), [
                'customer_id'   => $customer->id,
                'trip_date'     => now()->addDays(20)->toDateString(),
                'selling_price' => 18000,
            ])
            ->assertRedirect();

        $booking = DomesticBooking::findOrFail($opp->fresh()->converted_booking_id);
        $this->assertSame('package', $booking->type);
        $this->assertSame('شرم الشيخ', $booking->destination_city);
        $this->assertSame(4, $booking->adults_count);
        $this->assertSame(2, $booking->rooms_count, 'rooms_count should be ceil(pax/2)');
    }

    public function test_convert_with_lead_and_create_customer_flag_creates_customer_from_lead(): void
    {
        $lead = Lead::factory()->create([
            'full_name' => 'سعاد أحمد',
            'phone'     => '+201112223333',
            'whatsapp'  => '+201112223333',
            'email'     => 'souad@example.com',
            'status'    => 'qualified',
        ]);

        $opp = Opportunity::factory()->religious('umrah')->create([
            'lead_id'     => $lead->id,
            'customer_id' => null,
        ]);

        $this->actingAs($this->userWithRole('manager'))
            ->post(route('admin.crm.opportunities.convert', $opp), [
                'create_customer' => '1',
                'trip_date'       => now()->addDays(30)->toDateString(),
                'selling_price'   => 25000,
            ])
            ->assertRedirect();

        // Customer was created
        $customer = Customer::where('phone', '+201112223333')->first();
        $this->assertNotNull($customer);
        $this->assertSame('سعاد أحمد', $customer->full_name);
        $this->assertSame('souad@example.com', $customer->email);
        $this->assertStringContainsString($opp->code, $customer->notes);

        // Lead was marked converted
        $lead->refresh();
        $this->assertSame('won', $lead->status);
        $this->assertSame($customer->id, $lead->converted_to_customer_id);
        $this->assertNotNull($lead->converted_at);

        // Opportunity points to the new booking
        $opp->refresh();
        $this->assertSame($customer->id, $opp->customer_id);
        $booking = ReligiousBooking::findOrFail($opp->converted_booking_id);
        $this->assertSame($customer->id, $booking->customer_id);
    }

    public function test_already_converted_opportunity_cannot_convert_again(): void
    {
        $customer = Customer::factory()->create();
        $booking  = ReligiousBooking::factory()->create();
        $opp = Opportunity::factory()->create([
            'customer_id'            => $customer->id,
            'converted_booking_type' => 'religious',
            'converted_booking_id'   => $booking->id,
            'stage'                  => 'closed_won',
        ]);

        $this->actingAs($this->userWithRole('manager'))
            ->post(route('admin.crm.opportunities.convert', $opp), [
                'customer_id'   => $customer->id,
                'trip_date'     => now()->addDays(30)->toDateString(),
                'selling_price' => 20000,
            ])
            ->assertRedirect()
            ->assertSessionHas('error');

        // Same booking — no new one
        $this->assertSame($booking->id, $opp->fresh()->converted_booking_id);
        $this->assertSame(1, ReligiousBooking::count());
    }

    public function test_already_converted_opportunity_cannot_be_edited(): void
    {
        $customer = Customer::factory()->create();
        $booking  = ReligiousBooking::factory()->create();
        $opp = Opportunity::factory()->create([
            'customer_id'            => $customer->id,
            'converted_booking_type' => 'religious',
            'converted_booking_id'   => $booking->id,
            'stage'                  => 'closed_won',
        ]);

        $this->actingAs($this->userWithRole('manager'))
            ->put(route('admin.crm.opportunities.update', $opp), [
                'title'           => 'new title',
                'booking_type'    => 'religious',
                'pax_count'       => 3,
                'estimated_value' => 30000,
            ])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertNotSame('new title', $opp->fresh()->title);
    }

    public function test_kanban_stage_drag_auto_updates_probability(): void
    {
        $opp = Opportunity::factory()->atStage('prospecting')->create(['probability' => 20]);

        $this->actingAs($this->userWithRole('manager'))
            ->postJson(route('admin.crm.opportunities.update_stage', $opp), [
                'stage' => 'negotiation',
            ])
            ->assertOk()
            ->assertJsonPath('probability', 80);

        $this->assertSame('negotiation', $opp->fresh()->stage);
        $this->assertSame(80, $opp->fresh()->probability);
    }

    public function test_converted_opportunity_stage_cannot_change(): void
    {
        $customer = Customer::factory()->create();
        $booking  = ReligiousBooking::factory()->create();
        $opp = Opportunity::factory()->create([
            'customer_id'            => $customer->id,
            'converted_booking_type' => 'religious',
            'converted_booking_id'   => $booking->id,
            'stage'                  => 'closed_won',
        ]);

        $this->actingAs($this->userWithRole('manager'))
            ->postJson(route('admin.crm.opportunities.update_stage', $opp), [
                'stage' => 'negotiation',
            ])
            ->assertStatus(422);

        $this->assertSame('closed_won', $opp->fresh()->stage);
    }
}
