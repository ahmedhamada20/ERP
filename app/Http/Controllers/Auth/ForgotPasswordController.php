<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ForgotPasswordController extends Controller
{
    /** Max email-link requests per email+IP before lockout */
    private const MAX_REQUESTS = 3;

    /** Lockout window — 15 minutes */
    private const LOCKOUT_SECONDS = 900;

    public function showLinkRequestForm()
    {
        return view('auth.passwords.email');
    }

    public function sendResetLinkEmail(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $key = $this->throttleKey($request);

        if (RateLimiter::tooManyAttempts($key, self::MAX_REQUESTS)) {
            $seconds = RateLimiter::availableIn($key);
            throw ValidationException::withMessages([
                'email' => sprintf(
                    'تم إرسال طلبات كثيرة. حاول مرة أخرى بعد %d دقيقة.',
                    (int) ceil($seconds / 60),
                ),
            ]);
        }

        RateLimiter::hit($key, self::LOCKOUT_SECONDS);

        // Always return a "success" response to prevent email enumeration —
        // attackers shouldn't be able to discover which emails are registered.
        Password::sendResetLink($request->only('email'));

        return back()->with('success',
            'إذا كان البريد الإلكتروني مسجلاً عندنا، فستصلك رسالة بخطوات إعادة تعيين كلمة المرور خلال دقائق.'
        );
    }

    private function throttleKey(Request $request): string
    {
        return 'pwd-reset|' . Str::lower((string) $request->input('email')) . '|' . $request->ip();
    }
}
