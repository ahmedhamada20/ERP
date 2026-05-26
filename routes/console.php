<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Live exchange rates — يومياً 8 صباحاً بتوقيت القاهرة.
// open.er-api.com يحدّث القيم مرة في اليوم، فلا داعي لاستدعاءات أكثر.
Schedule::command('exchange-rates:sync')
    ->dailyAt('08:00')
    ->timezone('Africa/Cairo')
    ->withoutOverlapping()
    ->onOneServer();

// Religious alerts scanner — كل ساعة بتوقيت القاهرة.
// يفحص الجوازات اللي بتقرب على الانتهاء، التأشيرات المتأخرة،
// المدفوعات المتأخرة، والربحية المنخفضة، وينشئ تنبيهات نشطة.
Schedule::command('religious:alerts-scan')
    ->hourly()
    ->timezone('Africa/Cairo')
    ->withoutOverlapping(10)
    ->onOneServer()
    ->runInBackground();

// Daily DB + uploads backup — 02:30 ص بتوقيت القاهرة (ساعة الضغط الأقل).
Schedule::command('backup:run')
    ->dailyAt('02:30')
    ->timezone('Africa/Cairo')
    ->withoutOverlapping(30)
    ->onOneServer()
    ->emailOutputOnFailure(env('BACKUP_NOTIFY_EMAIL'));

// Backup retention cleanup — 02:45 ص، بعد ما الـ run خلص.
Schedule::command('backup:clean')
    ->dailyAt('02:45')
    ->timezone('Africa/Cairo')
    ->withoutOverlapping(10)
    ->onOneServer();

// WhatsApp trip reminders — 9 صباحاً يومياً، يبعت تذكير قبل السفر بـ 24 ساعة.
// Idempotent — لو ارتد التشغيل لا يرسل مرة تانية على نفس الحجز.
Schedule::command('whatsapp:send-trip-reminders')
    ->dailyAt('09:00')
    ->timezone('Africa/Cairo')
    ->withoutOverlapping(10)
    ->onOneServer();
