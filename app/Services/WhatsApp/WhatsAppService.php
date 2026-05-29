<?php

namespace App\Services\WhatsApp;

use App\Models\Setting;
use App\Models\WhatsappMessage;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * Meta WhatsApp Business Cloud API client.
 *
 * Endpoint: https://graph.facebook.com/{version}/{phone_number_id}/messages
 *
 * Credentials live in the `settings` table (group=whatsapp):
 *   whatsapp.access_token         — long-lived access token from Meta
 *   whatsapp.phone_number_id      — the source WhatsApp phone number id
 *   whatsapp.business_account_id  — WhatsApp Business Account id (WABA)
 *   whatsapp.webhook_verify_token — random string we set and Meta echoes back
 *   whatsapp.api_version          — default 'v18.0'
 *   whatsapp.default_language     — template language code, default 'ar'
 *
 * Every send creates a row in `whatsapp_messages` first (status=queued),
 * then hits Meta. On success → status='sent' + whatsapp_message_id. On
 * failure → status='failed' + error_message. Webhooks (handled in
 * WhatsAppMessageController) flip 'sent' → 'delivered' → 'read'.
 */
class WhatsAppService
{
    private const API_BASE = 'https://graph.facebook.com';

    public function isConfigured(): bool
    {
        return !empty(Setting::get('whatsapp.access_token'))
            && !empty(Setting::get('whatsapp.phone_number_id'));
    }

    /**
     * Send a plain text message. ⚠ Meta restricts text messages to within
     * 24 hours of the customer's last message (Customer Service Window).
     * Outside that window you MUST use a template — use sendTemplate().
     */
    public function sendText(
        string $toPhone,
        string $body,
        ?string $relatedType = null,
        ?string $relatedId = null,
    ): WhatsappMessage {
        $this->assertConfigured();

        $message = WhatsappMessage::create([
            'to_phone'     => $this->normalizePhone($toPhone),
            'from_phone'   => Setting::get('whatsapp.phone_number_id'),
            'direction'    => 'outbound',
            'message_type' => 'text',
            'body'         => $body,
            'status'       => 'queued',
            'related_type' => $relatedType,
            'related_id'   => $relatedId,
        ]);

        return $this->dispatch($message, [
            'messaging_product' => 'whatsapp',
            'to'                => $message->to_phone,
            'type'              => 'text',
            'text'              => ['body' => $body, 'preview_url' => true],
        ]);
    }

    /**
     * Send a pre-approved template (the only way to start a conversation
     * outside the 24h Customer Service Window).
     *
     * @param array $params Positional template variables: ['عميل عزيز', '5000', '2026-06-15']
     */
    public function sendTemplate(
        string $toPhone,
        string $templateName,
        array $params = [],
        ?string $relatedType = null,
        ?string $relatedId = null,
        ?string $language = null,
    ): WhatsappMessage {
        $this->assertConfigured();

        $language ??= Setting::get('whatsapp.default_language', 'ar');

        $message = WhatsappMessage::create([
            'to_phone'        => $this->normalizePhone($toPhone),
            'from_phone'      => Setting::get('whatsapp.phone_number_id'),
            'direction'       => 'outbound',
            'message_type'    => 'template',
            'template_name'   => $templateName,
            'template_params' => $params,
            'body'            => $this->renderTemplatePreview($templateName, $params),
            'status'          => 'queued',
            'related_type'    => $relatedType,
            'related_id'      => $relatedId,
        ]);

        $components = [];
        if (!empty($params)) {
            $components[] = [
                'type'       => 'body',
                'parameters' => array_map(
                    fn ($p) => ['type' => 'text', 'text' => (string) $p],
                    $params,
                ),
            ];
        }

        return $this->dispatch($message, [
            'messaging_product' => 'whatsapp',
            'to'                => $message->to_phone,
            'type'              => 'template',
            'template'          => [
                'name'       => $templateName,
                'language'   => ['code' => $language],
                'components' => $components,
            ],
        ]);
    }

    /**
     * Send a media message (image / audio / video / document).
     *
     * The file is first uploaded to Meta's /media endpoint to obtain a media
     * id, then referenced by id in the message — keeps our media private
     * (no public link handed to Meta). `$publicUrl` is OUR own copy used to
     * render the bubble in the chat UI. Like text, this is subject to the 24h
     * Customer Service Window.
     *
     * @param string $type One of: image|audio|video|document
     */
    public function sendMedia(
        string $toPhone,
        string $type,
        string $absolutePath,
        string $mime,
        ?string $publicUrl = null,
        ?string $caption = null,
        ?string $filename = null,
        ?string $relatedType = null,
        ?string $relatedId = null,
    ): WhatsappMessage {
        $this->assertConfigured();

        $message = WhatsappMessage::create([
            'to_phone'       => $this->normalizePhone($toPhone),
            'from_phone'     => Setting::get('whatsapp.phone_number_id'),
            'direction'      => 'outbound',
            'message_type'   => $type,
            'body'           => $caption,
            'media_url'      => $publicUrl,
            'media_mime'     => $mime,
            'media_filename' => $filename,
            'status'         => 'queued',
            'related_type'   => $relatedType,
            'related_id'     => $relatedId,
        ]);

        $mediaId = $this->uploadMedia($absolutePath, $mime);
        if (! $mediaId) {
            $message->update([
                'status'        => 'failed',
                'failed_at'     => now(),
                'error_message' => 'فشل رفع الملف إلى Meta (تحقق من نوع/حجم الملف).',
            ]);
            return $message->fresh();
        }

        $media = ['id' => $mediaId];
        if ($type === 'document' && $filename) $media['filename'] = $filename;
        if (in_array($type, ['image', 'video'], true) && $caption) $media['caption'] = $caption;

        return $this->dispatch($message, [
            'messaging_product' => 'whatsapp',
            'to'                => $message->to_phone,
            'type'              => $type,
            $type               => $media,
        ]);
    }

    /**
     * Upload a local file to Meta's /media endpoint. Returns the media id
     * (valid ~30 days) used to send the message, or null on failure.
     */
    public function uploadMedia(string $absolutePath, string $mime): ?string
    {
        $this->assertConfigured();

        $url = sprintf(
            '%s/%s/%s/media',
            self::API_BASE,
            Setting::get('whatsapp.api_version', 'v18.0'),
            Setting::get('whatsapp.phone_number_id'),
        );

        try {
            $response = Http::withToken(Setting::get('whatsapp.access_token'))
                ->timeout(60)
                ->attach('file', file_get_contents($absolutePath), basename($absolutePath), ['Content-Type' => $mime])
                ->post($url, [
                    'messaging_product' => 'whatsapp',
                    'type'              => $mime,
                ]);

            if ($response->successful()) {
                return $response->json('id');
            }

            Log::channel('single')->warning('WhatsApp media upload failed', [
                'http'  => $response->status(),
                'error' => $response->json('error', $response->body()),
            ]);
        } catch (Throwable $e) {
            Log::channel('single')->error('WhatsApp media upload error', ['error' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * Resolve an inbound media id to its temporary download URL + mime.
     * Meta media URLs are short-lived and require the bearer token to fetch.
     *
     * @return array{url:string,mime:?string}|null
     */
    public function resolveMediaUrl(string $mediaId): ?array
    {
        $this->assertConfigured();

        $url = sprintf('%s/%s/%s', self::API_BASE, Setting::get('whatsapp.api_version', 'v18.0'), $mediaId);

        try {
            $response = Http::withToken(Setting::get('whatsapp.access_token'))
                ->acceptJson()->timeout(30)->get($url);

            if ($response->successful() && $response->json('url')) {
                return ['url' => $response->json('url'), 'mime' => $response->json('mime_type')];
            }
        } catch (Throwable $e) {
            Log::channel('single')->error('WhatsApp media resolve error', ['id' => $mediaId, 'error' => $e->getMessage()]);
        }

        return null;
    }

    /** Download raw bytes from a (token-protected) Meta media URL. */
    public function downloadMedia(string $mediaUrl): ?string
    {
        $this->assertConfigured();

        try {
            $response = Http::withToken(Setting::get('whatsapp.access_token'))
                ->timeout(60)->get($mediaUrl);

            return $response->successful() ? $response->body() : null;
        } catch (Throwable $e) {
            Log::channel('single')->error('WhatsApp media download error', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Update an inbound or outbound message's status from a webhook callback.
     * Called by WhatsAppMessageController::webhook().
     */
    public function updateStatusFromWebhook(string $wamid, string $status, ?array $errorPayload = null): void
    {
        $message = WhatsappMessage::where('whatsapp_message_id', $wamid)->first();
        if (! $message) {
            Log::channel('single')->info('WhatsApp webhook for unknown message', [
                'wamid' => $wamid, 'status' => $status,
            ]);
            return;
        }

        $update = ['status' => $status];

        match ($status) {
            'sent'      => $update['sent_at']      ??= $message->sent_at ?? now(),
            'delivered' => $update['delivered_at'] = now(),
            'read'      => $update['read_at']      = now(),
            'failed'    => array_merge($update, [
                'failed_at'     => now(),
                'error_code'    => $errorPayload['code'] ?? null,
                'error_message' => $errorPayload['title'] ?? ($errorPayload['message'] ?? null),
            ]),
            default     => null,
        };

        if ($status === 'failed') {
            $update['failed_at']     = now();
            $update['error_code']    = $errorPayload['code']    ?? null;
            $update['error_message'] = $errorPayload['title']   ?? ($errorPayload['message'] ?? null);
        }

        $message->update($update);
    }

    /** Verify webhook subscription. Returns the challenge string if valid. */
    public function verifyWebhook(string $mode, string $token, string $challenge): ?string
    {
        $expected = Setting::get('whatsapp.webhook_verify_token');
        if ($mode === 'subscribe' && $expected && hash_equals((string) $expected, $token)) {
            return $challenge;
        }
        return null;
    }

    /** Dispatch a queued message to Meta. */
    private function dispatch(WhatsappMessage $message, array $payload): WhatsappMessage
    {
        $url = sprintf(
            '%s/%s/%s/messages',
            self::API_BASE,
            Setting::get('whatsapp.api_version', 'v18.0'),
            Setting::get('whatsapp.phone_number_id'),
        );

        try {
            /** @var Response $response */
            $response = Http::withToken(Setting::get('whatsapp.access_token'))
                ->acceptJson()
                ->timeout(20)
                ->post($url, $payload);

            if ($response->successful()) {
                $wamid = $response->json('messages.0.id');
                $message->update([
                    'status'              => 'sent',
                    'whatsapp_message_id' => $wamid,
                    'sent_at'             => now(),
                ]);
            } else {
                $err = $response->json('error', []);
                $message->update([
                    'status'        => 'failed',
                    'failed_at'     => now(),
                    'error_code'    => (string) ($err['code'] ?? $response->status()),
                    'error_message' => $err['message'] ?? $response->body(),
                ]);
                Log::channel('single')->warning('WhatsApp send failed', [
                    'message_id' => $message->id,
                    'to'         => $message->to_phone,
                    'http'       => $response->status(),
                    'error'      => $err,
                ]);
            }
        } catch (Throwable $e) {
            $message->update([
                'status'        => 'failed',
                'failed_at'     => now(),
                'error_message' => $e->getMessage(),
            ]);
            Log::channel('single')->error('WhatsApp transport error', [
                'message_id' => $message->id,
                'error'      => $e->getMessage(),
            ]);
        }

        return $message->fresh();
    }

    private function assertConfigured(): void
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException(
                'إعدادات WhatsApp Cloud API غير مكتملة. '
                . 'الرجاء ضبط access_token و phone_number_id من شاشة الإعدادات.'
            );
        }
    }

    /**
     * Meta requires phones in E.164 without the leading +. Egypt example:
     * "+201001234567" → "201001234567". Already-clean numbers pass through.
     */
    private function normalizePhone(string $phone): string
    {
        $cleaned = preg_replace('/\D/', '', $phone);
        // If starts with 0 (local Egyptian format), prefix the country code 20
        if (str_starts_with($cleaned, '0') && strlen($cleaned) === 11) {
            $cleaned = '20' . substr($cleaned, 1);
        }
        return $cleaned;
    }

    /** Best-effort text preview for a template message (for the logs UI). */
    private function renderTemplatePreview(string $templateName, array $params): string
    {
        $paramsStr = !empty($params) ? ' [' . implode(', ', $params) . ']' : '';
        return "[Template: {$templateName}]{$paramsStr}";
    }
}
