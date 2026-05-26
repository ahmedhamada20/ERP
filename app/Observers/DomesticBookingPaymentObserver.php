<?php

namespace App\Observers;

use App\Models\DomesticBookingPayment;
use App\Services\Accounting\DomesticBookingPaymentJournalPoster;
use App\Services\WhatsApp\WhatsAppNotificationService;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Auto-creates journal entries when domestic booking payments are saved.
 * Mirrors BookingPaymentObserver — failure logs but never blocks the save.
 */
class DomesticBookingPaymentObserver
{
    public function __construct(
        private readonly DomesticBookingPaymentJournalPoster $poster,
        private readonly WhatsAppNotificationService $notifications,
    ) {}

    public function created(DomesticBookingPayment $payment): void
    {
        if ($payment->isRefund()) return;

        $this->safely($payment, fn () => $this->poster->postPayment($payment));
        $this->safelyNotify($payment, fn () => $this->notifications->notifyPaymentReceived($payment));
    }

    public function updated(DomesticBookingPayment $payment): void
    {
        if (! $payment->wasChanged('refund_status')) return;
        if (! $payment->isRefund()) return;

        $old = $payment->getOriginal('refund_status');
        $new = $payment->refund_status;

        if ($new === 'paid' && $old !== 'paid' && ! $payment->journal_entry_id) {
            $this->safely($payment, fn () => $this->poster->postRefund($payment));
            $this->safelyNotify($payment, fn () => $this->notifications->notifyRefundPaid($payment));
            return;
        }

        if ($old === 'paid' && $new !== 'paid' && $payment->journal_entry_id) {
            $this->safely($payment, fn () =>
                $this->poster->cancelLinkedJournal($payment, 'تعديل حالة الاسترداد')
            );
        }
    }

    public function deleting(DomesticBookingPayment $payment): void
    {
        if (! $payment->journal_entry_id) return;

        $this->safely($payment, fn () =>
            $this->poster->cancelLinkedJournal($payment, "حذف الدفعة {$payment->receipt_number}")
        );
    }

    private function safely(DomesticBookingPayment $payment, \Closure $action): void
    {
        try {
            $action();
        } catch (Throwable $e) {
            Log::channel('single')->warning('DomesticBookingPaymentObserver: auto-post failed', [
                'payment_id'   => $payment->id,
                'receipt'      => $payment->receipt_number,
                'payment_type' => $payment->payment_type,
                'amount_egp'   => $payment->amount_egp,
                'error'        => $e->getMessage(),
            ]);
        }
    }

    private function safelyNotify(DomesticBookingPayment $payment, \Closure $action): void
    {
        try {
            $action();
        } catch (Throwable $e) {
            Log::channel('single')->warning('DomesticBookingPaymentObserver: notification failed', [
                'payment_id' => $payment->id,
                'error'      => $e->getMessage(),
            ]);
        }
    }
}
