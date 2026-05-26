<?php

namespace App\Services\Accounting;

use App\Models\BookingPayment;
use App\Models\JournalEntry;
use Illuminate\Support\Facades\DB;

/**
 * Auto-posts BookingPayment activity to the General Ledger.
 *
 * Posting matrix:
 *   Normal payment (deposit/installment/final, payment_type != 'refund'):
 *     created  → JE created + posted
 *     deleted  → JE cancelled
 *
 *   Refund payment (payment_type='refund'):
 *     created            → no JE (status='pending')
 *     refund_status=paid → JE created + posted (reversal direction)
 *     refund_status ≠ paid (after being paid) → JE cancelled
 *     deleted            → JE cancelled
 */
class BookingPaymentJournalPoster
{
    public function __construct(
        private readonly AccountingMappings $mappings,
        private readonly JournalService $journals,
    ) {}

    /**
     * Post a normal payment (non-refund) to the journal.
     * Direction: DR cash/bank, CR revenue.
     */
    public function postPayment(BookingPayment $payment): JournalEntry
    {
        $booking = $payment->booking;
        $cashAcc = $this->mappings->cashAccountForPayment($payment);
        $revAcc  = $this->mappings->revenueAccountForBookingType($booking->type);

        return DB::transaction(function () use ($payment, $booking, $cashAcc, $revAcc) {
            $entry = JournalEntry::create([
                'date'        => $payment->payment_date,
                'description' => sprintf(
                    'دفعة %s — حجز %s — العميل: %s',
                    $payment->payment_type === 'deposit' ? 'مقدم'
                        : ($payment->payment_type === 'final' ? 'نهائية' : 'قسط'),
                    $booking->booking_number,
                    $booking->customer?->full_name ?? 'غير محدد',
                ),
                'reference'   => $payment->receipt_number,
                'source_type' => 'booking_payment',
                'source_id'   => $payment->id,
            ]);

            $entry->lines()->create([
                'account_id'  => $cashAcc->id,
                'debit'       => $payment->amount_egp,
                'credit'      => 0,
                'description' => "استلام: {$payment->method_label}",
                'line_number' => 1,
            ]);
            $entry->lines()->create([
                'account_id'  => $revAcc->id,
                'debit'       => 0,
                'credit'      => $payment->amount_egp,
                'description' => "إيراد حجز {$booking->booking_number}",
                'line_number' => 2,
            ]);

            $entry = $this->journals->post($entry->fresh());
            $payment->forceFill(['journal_entry_id' => $entry->id])->saveQuietly();

            return $entry;
        });
    }

    /**
     * Post a refund (only called when refund_status transitions to 'paid').
     * Direction: DR revenue, CR cash/bank — reverses the original payment.
     */
    public function postRefund(BookingPayment $refund): JournalEntry
    {
        $booking = $refund->booking;
        $cashAcc = $this->mappings->cashAccountForPayment($refund);
        $revAcc  = $this->mappings->revenueAccountForBookingType($booking->type);

        return DB::transaction(function () use ($refund, $booking, $cashAcc, $revAcc) {
            $entry = JournalEntry::create([
                'date'        => $refund->payment_date,
                'description' => sprintf(
                    'استرداد — حجز %s — السبب: %s',
                    $booking->booking_number,
                    $refund->refund_reason ?: 'غير محدد',
                ),
                'reference'   => $refund->receipt_number,
                'source_type' => 'booking_payment',
                'source_id'   => $refund->id,
            ]);

            $entry->lines()->create([
                'account_id'  => $revAcc->id,
                'debit'       => $refund->amount_egp,
                'credit'      => 0,
                'description' => "عكس إيراد - استرداد للعميل",
                'line_number' => 1,
            ]);
            $entry->lines()->create([
                'account_id'  => $cashAcc->id,
                'debit'       => 0,
                'credit'      => $refund->amount_egp,
                'description' => "صرف للعميل: {$refund->method_label}",
                'line_number' => 2,
            ]);

            $entry = $this->journals->post($entry->fresh());
            $refund->forceFill(['journal_entry_id' => $entry->id])->saveQuietly();

            return $entry;
        });
    }

    /**
     * Cancel the journal entry linked to a payment (if any).
     * Called on payment delete or refund un-paying.
     */
    public function cancelLinkedJournal(BookingPayment $payment, string $reason): void
    {
        if (! $payment->journal_entry_id) return;

        $entry = JournalEntry::find($payment->journal_entry_id);
        if (! $entry || ! $entry->isPosted()) return;

        $this->journals->cancel($entry, $reason);
    }
}
