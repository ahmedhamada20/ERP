<?php

namespace App\Services\WhatsApp;

use App\Models\BookingPayment;
use App\Models\DomesticBooking;
use App\Models\DomesticBookingPayment;
use App\Models\ReligiousBooking;
use App\Models\Setting;
use App\Models\WhatsappMessage;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Translates business events into pre-approved WhatsApp template sends.
 *
 * Design rules:
 *   - Each notifyXxx() returns null if anything is missing (no customer phone,
 *     no template configured, service not configured). Failure is silent +
 *     logged — auto-notifications must NEVER block a business operation.
 *   - Template names live in `settings.whatsapp.template.*`. Empty = disabled.
 *   - Template parameters are positional ({{1}}, {{2}}, ...) per Meta's spec.
 */
class WhatsAppNotificationService
{
    public function __construct(private readonly WhatsAppService $whatsapp) {}

    /** Booking just transitioned to confirmed/in_progress. */
    public function notifyBookingConfirmed(ReligiousBooking|DomesticBooking $booking): ?WhatsappMessage
    {
        $template = Setting::get('whatsapp.template.booking_confirmed');
        $phone    = $this->customerPhone($booking);

        if (! $template || ! $phone || ! $this->whatsapp->isConfigured()) {
            return null;
        }

        $relatedType = $booking instanceof ReligiousBooking
            ? 'religious_booking' : 'domestic_booking';

        return $this->safeSend($template, $phone, [
            $booking->customer?->full_name ?? 'عميل عزيز',
            $booking->booking_number,
            $booking->trip_date?->format('Y-m-d') ?? '',
            number_format((float) $booking->selling_price, 0) . ' ج.م',
        ], $relatedType, $booking->id);
    }

    /** Customer paid us — send the receipt. */
    public function notifyPaymentReceived(BookingPayment|DomesticBookingPayment $payment): ?WhatsappMessage
    {
        if ($payment->isRefund()) return null;  // refunds use a different template

        $template = Setting::get('whatsapp.template.payment_received');
        if (! $template || ! $this->whatsapp->isConfigured()) {
            return null;
        }

        $booking = $payment->booking;
        $phone   = $this->customerPhone($booking);
        if (! $phone) return null;

        $relatedType = $payment instanceof BookingPayment
            ? 'booking_payment' : 'domestic_booking_payment';

        return $this->safeSend($template, $phone, [
            $booking->customer?->full_name ?? 'عميل عزيز',
            number_format((float) $payment->amount_egp, 2) . ' ج.م',
            $payment->receipt_number,
            number_format((float) $booking->outstanding_balance, 2) . ' ج.م',
        ], $relatedType, $payment->id);
    }

    /** Refund was just marked as paid out. */
    public function notifyRefundPaid(BookingPayment|DomesticBookingPayment $refund): ?WhatsappMessage
    {
        if (! $refund->isRefund() || $refund->refund_status !== 'paid') {
            return null;
        }

        $template = Setting::get('whatsapp.template.refund_paid');
        if (! $template || ! $this->whatsapp->isConfigured()) {
            return null;
        }

        $booking = $refund->booking;
        $phone   = $this->customerPhone($booking);
        if (! $phone) return null;

        $relatedType = $refund instanceof BookingPayment
            ? 'booking_payment' : 'domestic_booking_payment';

        return $this->safeSend($template, $phone, [
            $booking->customer?->full_name ?? 'عميل عزيز',
            number_format((float) $refund->amount_egp, 2) . ' ج.م',
            $refund->refund_reason ?: 'غير محدد',
        ], $relatedType, $refund->id);
    }

    /** Send the 24h-before-trip reminder. */
    public function notifyTripReminder(ReligiousBooking|DomesticBooking $booking): ?WhatsappMessage
    {
        $template = Setting::get('whatsapp.template.trip_reminder');
        $phone    = $this->customerPhone($booking);

        if (! $template || ! $phone || ! $this->whatsapp->isConfigured()) {
            return null;
        }

        $relatedType = $booking instanceof ReligiousBooking
            ? 'religious_booking' : 'domestic_booking';

        $destination = $booking instanceof ReligiousBooking
            ? $booking->type_label
            : $booking->destination_city;

        return $this->safeSend($template, $phone, [
            $booking->customer?->full_name ?? 'عميل عزيز',
            $booking->booking_number,
            $booking->trip_date?->format('Y-m-d') ?? '',
            $destination ?? '',
        ], $relatedType, $booking->id);
    }

    /**
     * Has a trip reminder been attempted for this booking?
     * Counts ANY status (incl. failed) so we don't retry-spam — if the
     * first attempt failed (bad template, transport error, etc.), the
     * admin must intervene manually.
     */
    public function tripReminderAlreadySent(ReligiousBooking|DomesticBooking $booking): bool
    {
        $relatedType = $booking instanceof ReligiousBooking
            ? 'religious_booking' : 'domestic_booking';

        return WhatsappMessage::query()
            ->where('related_type', $relatedType)
            ->where('related_id', $booking->id)
            ->where('template_name', Setting::get('whatsapp.template.trip_reminder'))
            ->exists();
    }

    /** Best-effort send with full error swallowing — auto-notifications never throw upward. */
    private function safeSend(
        string $template,
        string $phone,
        array $params,
        string $relatedType,
        string $relatedId,
    ): ?WhatsappMessage {
        try {
            // WhatsAppService::sendTemplate signature: (toPhone, templateName, params, relatedType, relatedId)
            return $this->whatsapp->sendTemplate($phone, $template, $params, $relatedType, $relatedId);
        } catch (Throwable $e) {
            Log::channel('single')->warning('WhatsApp auto-notification failed', [
                'template'     => $template,
                'phone'        => $phone,
                'related_type' => $relatedType,
                'related_id'   => $relatedId,
                'error'        => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Prefer the customer's whatsapp field; fall back to mobile, then phone.
     * Trims whitespace so " " counts as "no number".
     */
    private function customerPhone(ReligiousBooking|DomesticBooking $booking): ?string
    {
        $customer = $booking->customer;
        if (! $customer) return null;

        foreach ([$customer->whatsapp, $customer->mobile, $customer->phone] as $candidate) {
            $value = trim((string) $candidate);
            if ($value !== '') return $value;
        }
        return null;
    }
}
