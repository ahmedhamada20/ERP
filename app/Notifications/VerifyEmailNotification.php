<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail as BaseVerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Config;

class VerifyEmailNotification extends BaseVerifyEmail
{
    /**
     * Build the email message — Arabic, branded.
     */
    protected function buildMailMessage($url): MailMessage
    {
        return (new MailMessage)
            ->subject('تأكيد البريد الإلكتروني — ' . Config::get('app.name'))
            ->greeting('مرحبًا،')
            ->line('شكراً لانضمامك إلى ' . Config::get('app.name') . '.')
            ->line('يرجى الضغط على الزر أدناه لتأكيد عنوان بريدك الإلكتروني وتفعيل حسابك.')
            ->action('تأكيد البريد الإلكتروني', $url)
            ->line('إذا لم تقم بإنشاء حساب، يمكنك تجاهل هذه الرسالة بأمان.')
            ->salutation('تحياتنا، فريق ' . Config::get('app.name'));
    }
}
