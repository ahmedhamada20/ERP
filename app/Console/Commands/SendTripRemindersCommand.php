<?php

namespace App\Console\Commands;

use App\Models\DomesticBooking;
use App\Models\ReligiousBooking;
use App\Models\Setting;
use App\Services\WhatsApp\WhatsAppNotificationService;
use Illuminate\Console\Command;

/**
 * Sends WhatsApp trip reminders 24h before trip_date.
 *
 * Run daily via the scheduler. Idempotent — skips bookings that already
 * have a posted reminder message (tracked via WhatsappMessage related_*).
 *
 * Usage:
 *   php artisan whatsapp:send-trip-reminders
 *   php artisan whatsapp:send-trip-reminders --days=2   (custom lookahead)
 *   php artisan whatsapp:send-trip-reminders --dry-run  (preview only)
 */
class SendTripRemindersCommand extends Command
{
    protected $signature = 'whatsapp:send-trip-reminders
                            {--days=1 : Days before trip_date to send the reminder}
                            {--dry-run : Only list bookings without sending}';

    protected $description = 'Send WhatsApp reminders to customers travelling soon';

    public function handle(WhatsAppNotificationService $notifications): int
    {
        if (! Setting::get('whatsapp.template.trip_reminder')) {
            $this->warn('قالب تذكير السفر غير مُكوَّن (whatsapp.template.trip_reminder). تخطّي.');
            return self::SUCCESS;
        }

        $days = (int) $this->option('days');
        $dry  = (bool) $this->option('dry-run');

        $targetDate = now()->addDays($days)->toDateString();
        $this->info("البحث عن حجوزات بتاريخ سفر = {$targetDate}" . ($dry ? ' (dry-run)' : ''));

        $religious = ReligiousBooking::query()
            ->with('customer')
            ->whereDate('trip_date', $targetDate)
            ->whereNotIn('status', ['cancelled', 'completed'])
            ->get();

        $domestic = DomesticBooking::query()
            ->with('customer')
            ->whereDate('trip_date', $targetDate)
            ->whereNotIn('status', ['cancelled', 'completed'])
            ->get();

        $this->line("- {$religious->count()} حجز ديني");
        $this->line("- {$domestic->count()} حجز داخلي");

        $sent = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($religious->concat($domestic) as $booking) {
            if ($notifications->tripReminderAlreadySent($booking)) {
                $skipped++;
                $this->line("  · {$booking->booking_number}: مُذكَّر من قبل → تخطّي");
                continue;
            }

            if ($dry) {
                $this->info("  ✓ سيتم الإرسال: {$booking->booking_number} → {$booking->customer?->full_name}");
                continue;
            }

            $msg = $notifications->notifyTripReminder($booking);

            if ($msg && $msg->status !== 'failed') {
                $sent++;
                $this->info("  ✓ تم: {$booking->booking_number} → {$booking->customer?->full_name}");
            } else {
                $failed++;
                $reason = $msg?->error_message ?? 'بدون عميل/رقم/إعداد';
                $this->warn("  ✗ فشل: {$booking->booking_number} → {$reason}");
            }
        }

        $this->newLine();
        $this->info("النتيجة: مُرسلة={$sent} | متخطاة={$skipped} | فاشلة={$failed}");

        return self::SUCCESS;
    }
}
