<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\WhatsApp\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WhatsAppSettingController extends Controller
{
    public function edit(WhatsAppService $service)
    {
        return view('admin.crm.whatsapp.settings', [
            'settings' => [
                'access_token'          => Setting::get('whatsapp.access_token'),
                'phone_number_id'       => Setting::get('whatsapp.phone_number_id'),
                'business_account_id'   => Setting::get('whatsapp.business_account_id'),
                'webhook_verify_token'  => Setting::get('whatsapp.webhook_verify_token'),
                'api_version'           => Setting::get('whatsapp.api_version', 'v18.0'),
                'default_language'      => Setting::get('whatsapp.default_language', 'ar'),

                // Template names for auto-notifications (empty = disabled)
                'tpl_booking_confirmed' => Setting::get('whatsapp.template.booking_confirmed'),
                'tpl_payment_received'  => Setting::get('whatsapp.template.payment_received'),
                'tpl_refund_paid'       => Setting::get('whatsapp.template.refund_paid'),
                'tpl_trip_reminder'     => Setting::get('whatsapp.template.trip_reminder'),
            ],
            'isConfigured' => $service->isConfigured(),
            'webhookUrl'   => url('/api/whatsapp/webhook'),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'access_token'          => ['nullable', 'string', 'max:1000'],
            'phone_number_id'       => ['nullable', 'string', 'max:80'],
            'business_account_id'   => ['nullable', 'string', 'max:80'],
            'webhook_verify_token'  => ['nullable', 'string', 'max:200'],
            'api_version'           => ['nullable', 'string', 'max:10'],
            'default_language'      => ['nullable', 'string', 'max:10'],

            // Template name overrides (leave blank → no auto-send for that event)
            'tpl_booking_confirmed' => ['nullable', 'string', 'max:200'],
            'tpl_payment_received'  => ['nullable', 'string', 'max:200'],
            'tpl_refund_paid'       => ['nullable', 'string', 'max:200'],
            'tpl_trip_reminder'     => ['nullable', 'string', 'max:200'],
        ]);

        // Map of form key → setting key (template settings use a nested key style)
        $settingKeyMap = [
            'tpl_booking_confirmed' => 'whatsapp.template.booking_confirmed',
            'tpl_payment_received'  => 'whatsapp.template.payment_received',
            'tpl_refund_paid'       => 'whatsapp.template.refund_paid',
            'tpl_trip_reminder'     => 'whatsapp.template.trip_reminder',
        ];

        foreach ($data as $key => $value) {
            $settingKey = $settingKeyMap[$key] ?? "whatsapp.{$key}";

            if ($value !== null && $value !== '') {
                Setting::set($settingKey, $value, 'whatsapp', 'text');
            } elseif (array_key_exists($key, $settingKeyMap)) {
                // Empty template → delete (disables auto-send)
                Setting::where('key', $settingKey)->delete();
                Setting::flushCache();
            }
        }

        return back()->with('success', 'تم حفظ إعدادات WhatsApp');
    }

    /** Generate a fresh random verify_token for the webhook. */
    public function regenerateVerifyToken()
    {
        $token = Str::random(40);
        Setting::set('whatsapp.webhook_verify_token', $token, 'whatsapp');

        return back()->with('success', "تم توليد verify token جديد: {$token}");
    }
}
