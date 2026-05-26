<?php

namespace App\Observers;

use App\Models\BookingPayment;
use App\Services\Accounting\BookingPaymentJournalPoster;
use App\Services\WhatsApp\WhatsAppNotificationService;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Auto-creates journal entries when booking payments are saved.
 *
 * Lifecycle:
 *   created  → if non-refund, post normal payment JE
 *   updated  → if refund_status flipped to/from 'paid', react accordingly
 *   deleting → cancel the linked JE (before the FK gets cleared by cascade)
 *
 * Failure mode: any error in posting is logged but NEVER blocks the payment
 * save. The booking-staff workflow keeps moving; the accountant can re-post
 * manually later if the chart of accounts isn't ready.
 */
class BookingPaymentObserver
{
    public function __construct(
        private readonly BookingPaymentJournalPoster $poster,
        private readonly WhatsAppNotificationService $notifications,
    ) {}

    public function created(BookingPayment $payment): void
    {
        // Refunds wait until refund_status='paid' before they post.
        if ($payment->isRefund()) return;

        $this->safely($payment, fn () => $this->poster->postPayment($payment));
        $this->safelyNotify($payment, fn () => $this->notifications->notifyPaymentReceived($payment));
    }

    public function updated(BookingPayment $payment): void
    {
        if (! $payment->wasChanged('refund_status')) return;
        if (! $payment->isRefund()) return;

        $old = $payment->getOriginal('refund_status');
        $new = $payment->refund_status;

        // pending/approved → paid : create reversal JE + notify customer
        if ($new === 'paid' && $old !== 'paid' && ! $payment->journal_entry_id) {
            $this->safely($payment, fn () => $this->poster->postRefund($payment));
            $this->safelyNotify($payment, fn () => $this->notifications->notifyRefundPaid($payment));
            return;
        }

        // paid → anything else (e.g., correction): cancel the JE
        if ($old === 'paid' && $new !== 'paid' && $payment->journal_entry_id) {
            $this->safely($payment, fn () =>
                $this->poster->cancelLinkedJournal($payment, 'تعديل حالة الاسترداد')
            );
        }
    }

    public function deleting(BookingPayment $payment): void
    {
        if (! $payment->journal_entry_id) return;

        $this->safely($payment, fn () =>
            $this->poster->cancelLinkedJournal($payment, "حذف الدفعة {$payment->receipt_number}")
        );
    }

    private function safely(BookingPayment $payment, \Closure $action): void
    {
        try {
            $action();
        } catch (Throwable $e) {
            Log::channel('single')->warning('BookingPaymentObserver: auto-post failed', [
                'payment_id'   => $payment->id,
                'receipt'      => $payment->receipt_number,
                'payment_type' => $payment->payment_type,
                'amount_egp'   => $payment->amount_egp,
                'error'        => $e->getMessage(),
            ]);
        }
    }

    private function safelyNotify(BookingPayment $payment, \Closure $action): void
    {
        try {
            $action();
        } catch (Throwable $e) {
            Log::channel('single')->warning('BookingPaymentObserver: notification failed', [
                'payment_id' => $payment->id,
                'error'      => $e->getMessage(),
            ]);
        }
    }
}
