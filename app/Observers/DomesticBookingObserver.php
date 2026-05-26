<?php

namespace App\Observers;

use App\Models\DomesticBooking;
use App\Services\WhatsApp\WhatsAppNotificationService;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Sends WhatsApp notifications on key domestic-booking lifecycle events.
 * Mirror of ReligiousBookingObserver.
 */
class DomesticBookingObserver
{
    public function __construct(private readonly WhatsAppNotificationService $notifications) {}

    public function updated(DomesticBooking $booking): void
    {
        if (! $booking->wasChanged('status')) return;
        if ($booking->status !== 'confirmed') return;

        $old = $booking->getOriginal('status');
        if ($old === 'confirmed') return;

        try {
            $this->notifications->notifyBookingConfirmed($booking);
        } catch (Throwable $e) {
            Log::channel('single')->warning('DomesticBookingObserver notification failed', [
                'booking_id' => $booking->id,
                'error'      => $e->getMessage(),
            ]);
        }
    }
}
