<?php

namespace Tests\Feature\Crm;

use App\Models\Customer;
use App\Models\Lead;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpRolesForTesting;
use Tests\TestCase;

class LeadLifecycleTest extends TestCase
{
    use RefreshDatabase, SetsUpRolesForTesting;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
    }

    public function test_creating_a_lead_auto_assigns_to_current_user_and_copies_phone_to_whatsapp(): void
    {
        $user = $this->userWithRole('manager');

        $this->actingAs($user)
            ->post(route('admin.crm.leads.store'), [
                'full_name'     => 'تجربة الإنشاء',
                'phone'         => '+201001234567',
                'source'        => 'instagram',
                'interest_type' => 'umrah',
                // whatsapp omitted intentionally
                // assigned_to omitted intentionally
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $lead = Lead::latest('id')->first();

        $this->assertSame('+201001234567', $lead->phone);
        $this->assertSame('+201001234567', $lead->whatsapp, 'whatsapp should default to phone');
        $this->assertSame($user->id, $lead->assigned_to, 'assigned_to should default to current user');
        $this->assertStringStartsWith('LEAD-', $lead->code);
    }

    public function test_status_change_via_update_creates_a_status_change_activity(): void
    {
        $lead = Lead::factory()->ofStatus('new')->create();

        $this->actingAs($this->userWithRole('manager'))
            ->put(route('admin.crm.leads.update', $lead), [
                'full_name'     => $lead->full_name,
                'phone'         => $lead->phone,
                'source'        => $lead->source,
                'interest_type' => $lead->interest_type,
                'status'        => 'contacted',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('contacted', $lead->fresh()->status);

        $activity = $lead->activities()->where('type', 'status_change')->first();
        $this->assertNotNull($activity, 'status_change activity should be auto-logged');
        $this->assertStringContainsString('جديد', $activity->body);
        $this->assertStringContainsString('تم التواصل', $activity->body);
    }

    public function test_status_change_via_kanban_drag_creates_activity_and_returns_json(): void
    {
        $lead = Lead::factory()->ofStatus('new')->create();

        $response = $this->actingAs($this->userWithRole('manager'))
            ->postJson(route('admin.crm.leads.update_status', $lead), [
                'status' => 'qualified',
            ]);

        $response->assertOk()
            ->assertJsonPath('status_label', 'مؤهل');

        $this->assertSame('qualified', $lead->fresh()->status);
        $this->assertSame(1, $lead->activities()->where('type', 'status_change')->count());
    }

    public function test_status_lost_auto_sets_lost_at_timestamp(): void
    {
        $lead = Lead::factory()->ofStatus('proposal')->create();

        $lead->update(['status' => 'lost', 'lost_reason' => 'العميل رفض السعر']);

        $this->assertNotNull($lead->fresh()->lost_at);
    }

    public function test_convert_to_customer_creates_customer_marks_lead_won_and_logs_activity(): void
    {
        $lead = Lead::factory()->ofStatus('proposal')->create([
            'full_name' => 'أحمد محمود',
            'phone'     => '+201234567890',
            'whatsapp'  => '+201234567890',
            'email'     => 'ahmed@example.com',
            'city'      => 'الإسكندرية',
        ]);

        $this->actingAs($this->userWithRole('manager'))
            ->post(route('admin.crm.leads.convert', $lead))
            ->assertRedirect()
            ->assertSessionHas('success');

        $lead->refresh();

        $this->assertSame('won', $lead->status);
        $this->assertNotNull($lead->converted_to_customer_id);
        $this->assertNotNull($lead->converted_at);

        $customer = Customer::findOrFail($lead->converted_to_customer_id);
        $this->assertSame('أحمد محمود', $customer->full_name);
        $this->assertSame('+201234567890', $customer->phone);
        $this->assertSame('+201234567890', $customer->whatsapp);
        $this->assertSame('ahmed@example.com', $customer->email);
        $this->assertStringContainsString($lead->code, $customer->notes);

        // Activity logged
        $activity = $lead->activities()->latest('id')->first();
        $this->assertSame('status_change', $activity->type);
        $this->assertStringContainsString($customer->code, $activity->body);
    }

    public function test_converted_lead_cannot_be_converted_again(): void
    {
        $customer = Customer::factory()->create();
        $lead = Lead::factory()->create([
            'converted_to_customer_id' => $customer->id,
            'converted_at'             => now(),
            'status'                   => 'won',
        ]);

        $this->actingAs($this->userWithRole('manager'))
            ->post(route('admin.crm.leads.convert', $lead))
            ->assertRedirect()
            ->assertSessionHas('error');

        // Should still point to the same customer (no new one created)
        $this->assertSame($customer->id, $lead->fresh()->converted_to_customer_id);
    }

    public function test_converted_lead_cannot_be_deleted(): void
    {
        $customer = Customer::factory()->create();
        $lead = Lead::factory()->create([
            'converted_to_customer_id' => $customer->id,
            'converted_at'             => now(),
            'status'                   => 'won',
        ]);

        $this->actingAs($this->userWithRole('manager'))
            ->deleteJson(route('admin.crm.leads.destroy', $lead))
            ->assertStatus(422);

        $this->assertNotNull($lead->fresh(), 'lead should still exist');
    }

    public function test_status_change_activity_cannot_be_deleted(): void
    {
        // status_change activities are created by the controller's updateStatus
        // endpoint — go through the AJAX endpoint to generate one
        $lead = Lead::factory()->ofStatus('new')->create();

        $this->actingAs($this->userWithRole('manager'))
            ->postJson(route('admin.crm.leads.update_status', $lead), ['status' => 'contacted'])
            ->assertOk();

        $activity = $lead->activities()->where('type', 'status_change')->firstOrFail();

        $this->actingAs($this->userWithRole('manager'))
            ->deleteJson(route('admin.crm.leads.activities.destroy', [$lead, $activity]))
            ->assertStatus(422);

        $this->assertNotNull($activity->fresh());
    }

    public function test_booking_staff_cannot_delete_leads(): void
    {
        $lead = Lead::factory()->create();

        $this->actingAs($this->userWithRole('booking-staff'))
            ->deleteJson(route('admin.crm.leads.destroy', $lead))
            ->assertForbidden();
    }
}
