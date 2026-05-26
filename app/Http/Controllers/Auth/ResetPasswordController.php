<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

class ResetPasswordController extends Controller
{
    public function showResetForm(Request $request, string $token)
    {
        return view('auth.passwords.reset', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    public function reset(Request $request)
    {
        $request->validate([
            'token'    => ['required', 'string'],
            'email'    => ['required', 'email'],
            'password' => [
                'required',
                'confirmed',
                PasswordRule::min(10)->mixedCase()->letters()->numbers()->symbols()->uncompromised(),
            ],
        ], [
            'password.confirmed' => 'تأكيد كلمة المرور غير مطابق',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password'       => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()
                ->route('login')
                ->with('success', 'تم تحديث كلمة المرور بنجاح. يمكنك تسجيل الدخول الآن.');
        }

        return back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => $this->translateStatus($status)]);
    }

    private function translateStatus(string $status): string
    {
        return match ($status) {
            Password::INVALID_TOKEN => 'رابط إعادة التعيين غير صالح أو منتهي الصلاحية. اطلب رابطاً جديداً.',
            Password::INVALID_USER  => 'لا يوجد حساب مرتبط بهذا البريد الإلكتروني.',
            Password::RESET_THROTTLED => 'تم إرسال طلبات كثيرة. حاول لاحقاً.',
            default => 'تعذر إعادة تعيين كلمة المرور. حاول مرة أخرى.',
        };
    }
}
