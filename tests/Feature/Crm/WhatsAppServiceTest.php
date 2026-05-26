<?php

namespace Tests\Feature\Crm;

use App\Models\Setting;
use App\Models\WhatsappMessage;
use App\Services\WhatsApp\WhatsAppService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\Concerns\SetsUpRolesForTesting;
use Tests\TestCase;

class WhatsAppServiceTest extends TestCase
{
    use RefreshDatabase, SetsUpRolesForTesting;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
    }

    private function configureWhatsApp(): void
    {
        Setting::set('whatsapp.access_token', 'test_token', 'whatsapp');
        Setting::set('whatsapp.phone_number_id', '999999', 'whatsapp');
    }

    /* ───────────── Configuration guard ───────────── */

    public function test_is_configured_returns_false_when_credentials_missing(): void
    {
        $service = app(WhatsAppService::class);
        $this->assertFalse($service->isConfigured());
    }

    public function test_is_configured_returns_true_when_credentials_present(): void
    {
        $this->configureWhatsApp();
        $this->assertTrue(app(WhatsAppService::class)->isConfigured());
    }

    public function test_send_text_throws_when_not_configured(): void
    {
        $service = app(WhatsAppService::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('إعدادات WhatsApp');

        $service->sendText('+201001234567', 'hi');
    }

    /* ───────────── Successful send ───────────── */

    public function test_send_text_persists_message_and_marks_sent_on_success(): void
    {
        $this->configureWhatsApp();
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'messages' => [['id' => 'wamid.HBgL1234567890']],
            ], 200),
        ]);

        $message = app(WhatsAppService::class)->sendText('+201001234567', 'مرحباً');

        $this->assertSame('sent', $message->status);
        $this->assertSame('wamid.HBgL1234567890', $message->whatsapp_message_id);
        $this->assertNotNull($message->sent_at);
        $this->assertSame('مرحباً', $message->body);
        $this->assertSame('outbound', $message->direction);
    }

    public function test_send_template_serializes_params_to_meta_format(): void
    {
        $this->configureWhatsApp();
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'messages' => [['id' => 'wamid.test']],
            ], 200),
        ]);

        $message = app(WhatsAppService::class)->sendTemplate(
            toPhone:      '+201001234567',
            templateName: 'booking_confirmed',
            params:       ['أحمد', 'UM-001', '2026-06-15'],
        );

        $this->assertSame('sent', $message->status);
        $this->assertSame('booking_confirmed', $message->template_name);
        $this->assertSame(['أحمد', 'UM-001', '2026-06-15'], $message->template_params);

        Http::assertSent(function ($request) {
            $body = $request->data();
            return $body['type'] === 'template'
                && $body['template']['name'] === 'booking_confirmed'
                && $body['template']['components'][0]['parameters'][0]['text'] === 'أحمد';
        });
    }

    /* ───────────── Failed send ───────────── */

    public function test_send_marks_message_failed_when_meta_returns_error(): void
    {
        $this->configureWhatsApp();
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'error' => ['code' => 131000, 'message' => 'Generic user error'],
            ], 400),
        ]);

        $message = app(WhatsAppService::class)->sendText('+201001234567', 'hi');

        $this->assertSame('failed', $message->status);
        $this->assertSame('131000', $message->error_code);
        $this->assertStringContainsString('Generic user error', $message->error_message);
        $this->assertNotNull($message->failed_at);
        $this->assertNull($message->whatsapp_message_id);
    }

    public function test_send_swallows_transport_exceptions_and_persists_failure(): void
    {
        $this->configureWhatsApp();
        Http::fake(function () {
            throw new \RuntimeException('Connection refused');
        });

        // Should NOT throw — error is persisted on the message row
        $message = app(WhatsAppService::class)->sendText('+201001234567', 'hi');

        $this->assertSame('failed', $message->status);
        $this->assertStringContainsString('Connection refused', $message->error_message);
    }

    /* ───────────── Phone normalization ───────────── */

    public function test_phone_normalization_strips_plus_for_e164(): void
    {
        $this->configureWhatsApp();
        Http::fake([
            'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.x']]], 200),
        ]);

        $message = app(WhatsAppService::class)->sendText('+201001234567', 'hi');

        $this->assertSame('201001234567', $message->to_phone, 'leading + should be stripped');
    }

    public function test_phone_normalization_prefixes_egypt_country_code_for_local_format(): void
    {
        $this->configureWhatsApp();
        Http::fake([
            'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.x']]], 200),
        ]);

        // Local Egyptian "01001234567" → "201001234567"
        $message = app(WhatsAppService::class)->sendText('01001234567', 'hi');

        $this->assertSame('201001234567', $message->to_phone);
    }

    /* ───────────── Webhook ───────────── */

    public function test_webhook_get_returns_challenge_when_token_matches(): void
    {
        Setting::set('whatsapp.webhook_verify_token', 'expected_token_xyz', 'whatsapp');

        $response = $this->get('/api/whatsapp/webhook?hub_mode=subscribe&hub_verify_token=expected_token_xyz&hub_challenge=challenge_123');

        $response->assertOk();
        $this->assertSame('challenge_123', $response->getContent());
    }

    public function test_webhook_get_rejects_wrong_verify_token(): void
    {
        Setting::set('whatsapp.webhook_verify_token', 'expected_token_xyz', 'whatsapp');

        $this->get('/api/whatsapp/webhook?hub_mode=subscribe&hub_verify_token=WRONG&hub_challenge=challenge_123')
            ->assertStatus(403);
    }

    public function test_webhook_post_updates_message_status_to_delivered(): void
    {
        $msg = WhatsappMessage::create([
            'to_phone'            => '201001234567',
            'direction'           => 'outbound',
            'message_type'        => 'text',
            'body'                => 'hi',
            'status'              => 'sent',
            'whatsapp_message_id' => 'wamid.test.789',
            'sent_at'             => now(),
        ]);

        $this->postJson('/api/whatsapp/webhook', [
            'entry' => [[
                'changes' => [[
                    'value' => [
                        'statuses' => [
                            ['id' => 'wamid.test.789', 'status' => 'delivered'],
                        ],
                    ],
                ]],
            ]],
        ])->assertOk();

        $msg->refresh();
        $this->assertSame('delivered', $msg->status);
        $this->assertNotNull($msg->delivered_at);
    }

    public function test_webhook_post_creates_inbound_message_from_meta_payload(): void
    {
        $this->postJson('/api/whatsapp/webhook', [
            'entry' => [[
                'changes' => [[
                    'value' => [
                        'metadata' => ['display_phone_number' => '15555551234'],
                        'messages' => [[
                            'id'   => 'wamid.inbound.123',
                            'from' => '201001234567',
                            'type' => 'text',
                            'text' => ['body' => 'مرحبا، عاوز معلومات عن الباكدج'],
                        ]],
                    ],
                ]],
            ]],
        ])->assertOk();

        $msg = WhatsappMessage::where('whatsapp_message_id', 'wamid.inbound.123')->first();
        $this->assertNotNull($msg, 'inbound message should be persisted');
        $this->assertSame('inbound', $msg->direction);
        $this->assertSame('201001234567', $msg->from_phone);
        $this->assertStringContainsString('مرحبا', $msg->body);
    }

    public function test_webhook_post_is_idempotent_does_not_duplicate_inbound(): void
    {
        $payload = [
            'entry' => [[
                'changes' => [[
                    'value' => [
                        'metadata' => ['display_phone_number' => '15555551234'],
                        'messages' => [[
                            'id'   => 'wamid.dup.test',
                            'from' => '201001234567',
                            'type' => 'text',
                            'text' => ['body' => 'duplicate test'],
                        ]],
                    ],
                ]],
            ]],
        ];

        $this->postJson('/api/whatsapp/webhook', $payload)->assertOk();
        $this->postJson('/api/whatsapp/webhook', $payload)->assertOk();
        $this->postJson('/api/whatsapp/webhook', $payload)->assertOk();

        $this->assertSame(
            1,
            WhatsappMessage::where('whatsapp_message_id', 'wamid.dup.test')->count(),
            'Same wamid should not produce duplicates',
        );
    }
}
