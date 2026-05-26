<?php

namespace Tests\Feature\Crm;

use App\Models\Customer;
use App\Models\DomesticBooking;
use App\Models\DomesticBookingPayment;
use App\Models\ReligiousBooking;
use App\Models\Setting;
use App\Models\WhatsappMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\SetsUpRolesForTesting;
use Tests\TestCase;

class WhatsAppNotificationTest extends TestCase
{
    use RefreshDatabase, SetsUpRolesForTesting;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();

        // Fake Meta API — always succeeds, returns a wamid
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'messages' => [['id' => 'wamid.fake.' . uniqid()]],
            ], 200),
        ]);
    }

    private function configureWhatsApp(array $templates = []): void
    {
        Setting::set('whatsapp.access_token', 'test_token', 'whatsapp');
        Setting::set('whatsapp.phone_number_id', '999999', 'whatsapp');

        foreach ($templates as $event => $name) {
            Setting::set("whatsapp.template.{$event}", $name, 'whatsapp');
        }
    }

    private function createDomesticBooking(?Customer $customer = null): DomesticBooking
    {
        $customer ??= Customer::factory()->create([
            'phone'    => '+201001234567',
            'whatsapp' => '+201001234567',
        ]);

        return DomesticBooking::factory()->create([
            'customer_id'   => $customer->id,
            'selling_price' => 10000,
        ]);
    }

    /* ───────────── booking_confirmed trigger ───────────── */

    public function test_booking_confirmed_template_fires_when_status_transitions_to_confirmed(): void
    {
        $this->configureWhatsApp(['booking_confirmed' => 'booking_confirmed_v1']);
        $booking = $this->createDomesticBooking();

        $this->assertSame(0, WhatsappMessage::where('template_name', 'booking_confirmed_v1')->count());

        $booking->update(['status' => 'confirmed']);

        $msg = WhatsappMessage::where('template_name', 'booking_confirmed_v1')->first();
        $this->assertNotNull($msg, 'message should be sent on status=confirmed');
        $this->assertSame($booking->booking_number, $msg->template_params[1]);
        $this->assertSame('domestic_booking', $msg->related_type);
        $this->assertSame($booking->id, $msg->related_id);
    }

    public function test_booking_confirmed_does_not_fire_when_template_is_blank(): void
    {
        $this->configureWhatsApp(); // No templates configured
        $booking = $this->createDomesticBooking();

        $booking->update(['status' => 'confirmed']);

        $this->assertSame(0, WhatsappMessage::count(), 'No template = no message');
    }

    public function test_booking_confirmed_only_fires_on_first_transition_not_on_subsequent_updates(): void
    {
        $this->configureWhatsApp(['booking_confirmed' => 'booking_confirmed_v1']);
        $booking = $this->createDomesticBooking();

        $booking->update(['status' => 'confirmed']);
        $this->assertSame(1, WhatsappMessage::count());

        // Unrelated update — should NOT re-trigger
        $booking->update(['notes' => 'updated notes']);
        $this->assertSame(1, WhatsappMessage::count(), 'Unrelated update must not re-send');
    }

    public function test_no_notification_when_customer_has_no_phone(): void
    {
        $this->configureWhatsApp(['booking_confirmed' => 'booking_confirmed_v1']);
        // phone is NOT NULL in the schema, but whatsapp/mobile are nullable.
        // customerPhone() trims whitespace, so " " counts as "no number".
        $customer = Customer::factory()->create([
            'phone'    => ' ',
            'mobile'   => null,
            'whatsapp' => null,
        ]);
        $booking = $this->createDomesticBooking($customer);

        $booking->update(['status' => 'confirmed']);

        $this->assertSame(0, WhatsappMessage::count(), 'no usable phone → no notification');
    }

    /* ───────────── payment_received trigger ───────────── */

    public function test_payment_received_template_fires_on_payment_create(): void
    {
        $this->configureWhatsApp(['payment_received' => 'payment_received_v1']);
        $booking = $this->createDomesticBooking();

        $payment = $booking->payments()->create([
            'payment_date'  => now(),
            'payment_type'  => 'deposit',
            'currency'      => 'EGP',
            'amount'        => 3000,
            'exchange_rate' => 1,
            'method'        => 'cash',
        ]);

        $msg = WhatsappMessage::where('template_name', 'payment_received_v1')->first();
        $this->assertNotNull($msg);
        $this->assertSame('domestic_booking_payment', $msg->related_type);
        $this->assertSame($payment->id, $msg->related_id);
        // Params: name, amount, receipt, outstanding
        $this->assertSame($payment->receipt_number, $msg->template_params[2]);
        $this->assertStringContainsString('3,000', $msg->template_params[1]);
        $this->assertStringContainsString('7,000', $msg->template_params[3]); // outstanding = 10000-3000
    }

    public function test_payment_received_does_not_fire_for_refund_payments(): void
    {
        $this->configureWhatsApp(['payment_received' => 'payment_received_v1']);
        $booking = $this->createDomesticBooking();

        // First, a real deposit (this WILL fire)
        $booking->payments()->create([
            'payment_date'  => now(),
            'payment_type'  => 'deposit',
            'currency'      => 'EGP',
            'amount'        => 5000,
            'exchange_rate' => 1,
            'method'        => 'cash',
        ]);

        $beforeRefund = WhatsappMessage::where('template_name', 'payment_received_v1')->count();

        // Now a refund — should NOT fire payment_received
        $booking->payments()->create([
            'payment_date'  => now(),
            'payment_type'  => 'refund',
            'currency'      => 'EGP',
            'amount'        => 1000,
            'exchange_rate' => 1,
            'method'        => 'cash',
            'refund_reason' => 'test',
            'refund_status' => 'pending',
        ]);

        $this->assertSame(
            $beforeRefund,
            WhatsappMessage::where('template_name', 'payment_received_v1')->count(),
            'Refunds must not trigger payment_received template',
        );
    }

    /* ───────────── refund_paid trigger ───────────── */

    public function test_refund_paid_template_fires_when_refund_status_becomes_paid(): void
    {
        $this->configureWhatsApp([
            'payment_received' => 'payment_received_v1',
            'refund_paid'      => 'refund_paid_v1',
        ]);
        $booking = $this->createDomesticBooking();

        $booking->payments()->create([
            'payment_date' => now(), 'payment_type' => 'deposit',
            'currency' => 'EGP', 'amount' => 5000, 'exchange_rate' => 1, 'method' => 'cash',
        ]);

        $refund = $booking->payments()->create([
            'payment_date' => now(), 'payment_type' => 'refund',
            'currency' => 'EGP', 'amount' => 1500, 'exchange_rate' => 1, 'method' => 'cash',
            'refund_reason' => 'سبب الاسترداد للاختبار',
            'refund_status' => 'pending',
        ]);

        // No refund_paid message yet
        $this->assertSame(0, WhatsappMessage::where('template_name', 'refund_paid_v1')->count());

        // Approve then mark paid
        $refund->update(['refund_status' => 'approved']);
        $refund->update(['refund_status' => 'paid']);

        $msg = WhatsappMessage::where('template_name', 'refund_paid_v1')->first();
        $this->assertNotNull($msg);
        $this->assertStringContainsString('1,500', $msg->template_params[1]);
        $this->assertSame('سبب الاسترداد للاختبار', $msg->template_params[2]);
    }

    /* ───────────── trip_reminder + idempotency ───────────── */

    public function test_trip_reminder_command_sends_for_tomorrow_bookings_only(): void
    {
        $this->configureWhatsApp(['trip_reminder' => 'trip_reminder_v1']);

        // Booking with trip_date = tomorrow → should be reminded
        $tomorrow = $this->createDomesticBooking();
        $tomorrow->update(['trip_date' => now()->addDay()->toDateString()]);

        // Booking with trip_date = next week → should NOT
        $nextWeek = $this->createDomesticBooking(
            Customer::factory()->create(['phone' => '+201007777777', 'whatsapp' => '+201007777777'])
        );
        $nextWeek->update(['trip_date' => now()->addDays(7)->toDateString()]);

        Artisan::call('whatsapp:send-trip-reminders');

        $messages = WhatsappMessage::where('template_name', 'trip_reminder_v1')->get();
        $this->assertCount(1, $messages);
        $this->assertSame($tomorrow->id, $messages->first()->related_id);
    }

    public function test_trip_reminder_command_is_idempotent_on_consecutive_runs(): void
    {
        $this->configureWhatsApp(['trip_reminder' => 'trip_reminder_v1']);

        $booking = $this->createDomesticBooking();
        $booking->update(['trip_date' => now()->addDay()->toDateString()]);

        Artisan::call('whatsapp:send-trip-reminders');
        Artisan::call('whatsapp:send-trip-reminders');
        Artisan::call('whatsapp:send-trip-reminders');

        $this->assertSame(
            1,
            WhatsappMessage::where('template_name', 'trip_reminder_v1')->count(),
            '3 runs should still result in 1 message',
        );
    }

    public function test_trip_reminder_skips_cancelled_bookings(): void
    {
        $this->configureWhatsApp(['trip_reminder' => 'trip_reminder_v1']);

        $booking = $this->createDomesticBooking();
        $booking->update([
            'trip_date' => now()->addDay()->toDateString(),
            'status'    => 'cancelled',
        ]);

        Artisan::call('whatsapp:send-trip-reminders');

        $this->assertSame(0, WhatsappMessage::count());
    }

    public function test_dry_run_does_not_send_messages(): void
    {
        $this->configureWhatsApp(['trip_reminder' => 'trip_reminder_v1']);

        $booking = $this->createDomesticBooking();
        $booking->update(['trip_date' => now()->addDay()->toDateString()]);

        Artisan::call('whatsapp:send-trip-reminders', ['--dry-run' => true]);

        $this->assertSame(0, WhatsappMessage::count());
    }
}
