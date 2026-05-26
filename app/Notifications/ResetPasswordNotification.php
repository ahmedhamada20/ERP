<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Lang;

class ResetPasswordNotification extends Notification
{
    use Queueable;

    public function __construct(public string $token)
    {
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $url = url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

        $expireMinutes = Config::get('auth.passwords.' . Config::get('auth.defaults.passwords') . '.expire');

        return (new MailMessage)
            ->subject('استعادة كلمة المرور — ' . Config::get('app.name'))
            ->greeting('مرحبًا ' . ($notifiable->name ?? ''))
            ->line('تلقينا طلبًا لإعادة تعيين كلمة المرور الخاصة بحسابك.')
            ->action('إعادة تعيين كلمة المرور', $url)
            ->line("هذا الرابط صالح لمدة {$expireMinutes} دقيقة.")
            ->line('إذا لم تطلب إعادة تعيين كلمة المرور، يمكنك تجاهل هذه الرسالة بأمان — لن يتغير شيء.')
            ->salutation('تحياتنا، فريق ' . Config::get('app.name'));
    }
}
