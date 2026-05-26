<?php

namespace App\Services\Accounting;

use App\Models\DomesticBookingPayment;
use App\Models\JournalEntry;
use Illuminate\Support\Facades\DB;

/**
 * Auto-posts DomesticBookingPayment activity to the General Ledger.
 *
 * Posting matrix mirrors religious bookings:
 *   Normal payment (deposit/installment/final):
 *     created  → DR cash/bank, CR 413 (إيرادات السياحة الداخلية)
 *     deleted  → JE cancelled
 *
 *   Refund payment (payment_type='refund'):
 *     created            → no JE (status='pending')
 *     refund_status=paid → JE created (DR 413, CR cash/bank) — reversal
 *     paid → not-paid    → JE cancelled
 *     deleted            → JE cancelled
 */
class DomesticBookingPaymentJournalPoster
{
    public function __construct(
        private readonly AccountingMappings $mappings,
        private readonly JournalService $journals,
    ) {}

    public function postPayment(DomesticBookingPayment $payment): JournalEntry
    {
        $booking = $payment->booking;
        $cashAcc = $this->mappings->cashAccountForPayment($payment);
        $revAcc  = $this->mappings->revenueAccountForDomestic();

        return DB::transaction(function () use ($payment, $booking, $cashAcc, $revAcc) {
            $entry = JournalEntry::create([
                'date'        => $payment->payment_date,
                'description' => sprintf(
                    'دفعة %s — حجز داخلي %s — العميل: %s',
                    $payment->payment_type === 'deposit' ? 'مقدم'
                        : ($payment->payment_type === 'final' ? 'نهائية' : 'قسط'),
                    $booking->booking_number,
                    $booking->customer?->full_name ?? 'غير محدد',
                ),
                'reference'   => $payment->receipt_number,
                'source_type' => 'domestic_booking_payment',
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
                'description' => "إيراد حجز داخلي {$booking->booking_number}",
                'line_number' => 2,
            ]);

            $entry = $this->journals->post($entry->fresh());
            $payment->forceFill(['journal_entry_id' => $entry->id])->saveQuietly();

            return $entry;
        });
    }

    public function postRefund(DomesticBookingPayment $refund): JournalEntry
    {
        $booking = $refund->booking;
        $cashAcc = $this->mappings->cashAccountForPayment($refund);
        $revAcc  = $this->mappings->revenueAccountForDomestic();

        return DB::transaction(function () use ($refund, $booking, $cashAcc, $revAcc) {
            $entry = JournalEntry::create([
                'date'        => $refund->payment_date,
                'description' => sprintf(
                    'استرداد — حجز داخلي %s — السبب: %s',
                    $booking->booking_number,
                    $refund->refund_reason ?: 'غير محدد',
                ),
                'reference'   => $refund->receipt_number,
                'source_type' => 'domestic_booking_payment',
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

    public function cancelLinkedJournal(DomesticBookingPayment $payment, string $reason): void
    {
        if (! $payment->journal_entry_id) return;

        $entry = JournalEntry::find($payment->journal_entry_id);
        if (! $entry || ! $entry->isPosted()) return;

        $this->journals->cancel($entry, $reason);
    }
}
