<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class EmailVerificationController extends Controller
{
    /** Max resend attempts per user before lockout */
    private const MAX_RESENDS = 3;

    /** Lockout window — 10 minutes */
    private const LOCKOUT_SECONDS = 600;

    /**
     * "تأكيد بريدك الإلكتروني" landing page — shown after login if not verified
     * (when middleware 'verified' is enabled), or via direct URL.
     */
    public function notice(Request $request)
    {
        if ($request->user()?->hasVerifiedEmail()) {
            return redirect()->intended(route('admin.dashboard'))
                ->with('success', 'بريدك الإلكتروني موّثق بالفعل.');
        }

        return view('auth.verify-email');
    }

    /**
     * Handle the signed verification URL click.
     * EmailVerificationRequest automatically validates the signature + hash.
     */
    public function verify(EmailVerificationRequest $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('admin.dashboard'))
                ->with('info', 'البريد الإلكتروني موّثق مسبقاً.');
        }

        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));
        }

        return redirect()->intended(route('admin.dashboard'))
            ->with('success', 'تم تأكيد بريدك الإلكتروني بنجاح.');
    }

    /**
     * Re-send the verification email — throttled per user.
     */
    public function resend(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('admin.dashboard'));
        }

        $key = 'verify-email|' . $request->user()->getKey();

        if (RateLimiter::tooManyAttempts($key, self::MAX_RESENDS)) {
            $seconds = RateLimiter::availableIn($key);
            throw ValidationException::withMessages([
                'email' => sprintf(
                    'تم إرسال طلبات كثيرة. حاول مرة أخرى بعد %d دقيقة.',
                    (int) ceil($seconds / 60),
                ),
            ]);
        }

        RateLimiter::hit($key, self::LOCKOUT_SECONDS);
        $request->user()->sendEmailVerificationNotification();

        return back()->with('success', 'تم إرسال رابط التأكيد إلى بريدك الإلكتروني.');
    }
}
