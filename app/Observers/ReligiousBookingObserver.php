<?php

namespace App\Observers;

use App\Models\ReligiousBooking;
use App\Services\WhatsApp\WhatsAppNotificationService;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Sends WhatsApp notifications on key religious-booking lifecycle events.
 *
 * Triggers:
 *   updated: status went from pending → confirmed → notify "booking_confirmed"
 *
 * Failure: logged but never blocks the save. Mirror of the auto-posting
 * observer pattern used by BookingPaymentObserver.
 */
class ReligiousBookingObserver
{
    public function __construct(private readonly WhatsAppNotificationService $notifications) {}

    public function updated(ReligiousBooking $booking): void
    {
        if (! $booking->wasChanged('status')) return;
        if ($booking->status !== 'confirmed') return;

        $old = $booking->getOriginal('status');
        if ($old === 'confirmed') return;  // no-op safety

        try {
            $this->notifications->notifyBookingConfirmed($booking);
        } catch (Throwable $e) {
            Log::channel('single')->warning('ReligiousBookingObserver notification failed', [
                'booking_id' => $booking->id,
                'error'      => $e->getMessage(),
            ]);
        }
    }
}
