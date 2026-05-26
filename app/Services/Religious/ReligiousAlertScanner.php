<?php

namespace App\Services\Religious;

use App\Models\BookingPilgrim;
use App\Models\ReligiousAlert;
use App\Models\ReligiousBooking;
use Illuminate\Support\Facades\DB;

/**
 * Scans bookings/pilgrims and generates ReligiousAlert rows for issues
 * that need operator attention. Idempotent — won't duplicate active alerts.
 *
 * Used by:
 *  - ReligiousAlertController::scan() (manual button)
 *  - religious:alerts-scan artisan command (hourly schedule)
 */
class ReligiousAlertScanner
{
    /** @return int number of new alerts created */
    public function scan(): int
    {
        $created = 0;

        DB::transaction(function () use (&$created) {
            $created += $this->scanPassportsExpiring();
            $created += $this->scanVisasOverdue();
            $created += $this->scanPaymentsOverdue();
            $created += $this->scanProfitsLow();
        });

        return $created;
    }

    private function scanPassportsExpiring(): int
    {
        $created = 0;

        BookingPilgrim::query()
            ->whereNotNull('passport_expiry_date')
            ->whereBetween('passport_expiry_date', [now(), now()->addMonths(6)])
            ->whereHas('booking', fn ($q) => $q->whereNotIn('status', ['cancelled', 'completed']))
            ->each(function (BookingPilgrim $p) use (&$created) {
                $exists = ReligiousAlert::where('type', 'passport_expiring')
                    ->where('pilgrim_id', $p->id)
                    ->where('is_acknowledged', false)
                    ->exists();
                if ($exists) return;

                ReligiousAlert::create([
                    'booking_id' => $p->booking_id,
                    'pilgrim_id' => $p->id,
                    'type'       => 'passport_expiring',
                    'severity'   => $p->passport_expiry_date < now()->addMonths(2) ? 'critical' : 'warning',
                    'title'      => 'جواز سفر يقارب الانتهاء',
                    'message'    => $p->full_name . ' - جوازه ينتهي بتاريخ ' . $p->passport_expiry_date->format('Y-m-d'),
                    'context'    => ['expiry_date' => $p->passport_expiry_date->toDateString()],
                ]);
                $created++;
            });

        return $created;
    }

    private function scanVisasOverdue(): int
    {
        $created = 0;

        ReligiousBooking::query()
            ->whereBetween('trip_date', [now(), now()->addDays(7)])
            ->whereNotIn('status', ['cancelled', 'completed'])
            ->whereHas('pilgrims', fn ($q) => $q->where('visa_status', '!=', 'issued'))
            ->get()
            ->each(function (ReligiousBooking $b) use (&$created) {
                $exists = ReligiousAlert::where('type', 'visa_overdue')
                    ->where('booking_id', $b->id)
                    ->where('is_acknowledged', false)
                    ->exists();
                if ($exists) return;

                $pendingCount = $b->pilgrims()->where('visa_status', '!=', 'issued')->count();
                ReligiousAlert::create([
                    'booking_id' => $b->id,
                    'type'       => 'visa_overdue',
                    'severity'   => 'critical',
                    'title'      => 'تأشيرات لم تصدر والرحلة قريبة',
                    'message'    => 'الحجز ' . $b->booking_number . ' - ' . $pendingCount . ' تأشيرات لم تصدر، الرحلة بعد ' . now()->diffInDays($b->trip_date) . ' يوم',
                    'context'    => ['pending_visas' => $pendingCount, 'trip_date' => $b->trip_date->toDateString()],
                ]);
                $created++;
            });

        return $created;
    }

    private function scanPaymentsOverdue(): int
    {
        $created = 0;

        ReligiousBooking::query()
            ->whereBetween('trip_date', [now(), now()->addDays(30)])
            ->whereNotIn('status', ['cancelled', 'completed'])
            ->get()
            ->each(function (ReligiousBooking $b) use (&$created) {
                // Uses the accessor — correctly subtracts only PAID refunds.
                $outstanding = (float) $b->selling_price - $b->total_paid;
                if ($outstanding < ((float) $b->selling_price * 0.5)) return;

                $exists = ReligiousAlert::where('type', 'payment_overdue')
                    ->where('booking_id', $b->id)
                    ->where('is_acknowledged', false)
                    ->exists();
                if ($exists) return;

                ReligiousAlert::create([
                    'booking_id' => $b->id,
                    'type'       => 'payment_overdue',
                    'severity'   => 'warning',
                    'title'      => 'دفعة متأخرة',
                    'message'    => 'الحجز ' . $b->booking_number . ' متبقي عليه ' . number_format($outstanding, 0) . ' ج.م والرحلة بعد ' . now()->diffInDays($b->trip_date) . ' يوم',
                    'context'    => ['outstanding' => $outstanding],
                ]);
                $created++;
            });

        return $created;
    }

    private function scanProfitsLow(): int
    {
        $created = 0;

        ReligiousBooking::query()
            ->where('selling_price', '>', 0)
            ->whereNotIn('status', ['cancelled'])
            ->get()
            ->each(function (ReligiousBooking $b) use (&$created) {
                if ($b->profit_margin >= 5) return;

                $exists = ReligiousAlert::where('type', 'profit_low')
                    ->where('booking_id', $b->id)
                    ->where('is_acknowledged', false)
                    ->exists();
                if ($exists) return;

                ReligiousAlert::create([
                    'booking_id' => $b->id,
                    'type'       => 'profit_low',
                    'severity'   => 'info',
                    'title'      => 'ربحية منخفضة',
                    'message'    => 'الحجز ' . $b->booking_number . ' هامش ربحه ' . $b->profit_margin . '% فقط',
                    'context'    => ['margin' => $b->profit_margin],
                ]);
                $created++;
            });

        return $created;
    }
}
