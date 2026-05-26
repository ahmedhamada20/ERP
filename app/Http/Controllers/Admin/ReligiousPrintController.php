<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BookingPayment;
use App\Models\ReligiousBooking;
use App\Services\ArabicPdfRenderer;

/**
 * PDF printing — العقود والإيصالات وقوائم المعتمرين.
 *
 * Uses mPDF (via ArabicPdfRenderer) which supports Arabic shaping +
 * BiDi natively. Pass ?stream=1 in the query string to open in
 * browser instead of downloading.
 */
class ReligiousPrintController extends Controller
{
    public function __construct(private ArabicPdfRenderer $pdf)
    {
    }

    public function contract(ReligiousBooking $booking)
    {
        $booking->load(['customer', 'program', 'pilgrims', 'payments']);

        return $this->emit('admin.religious.print.contract', [
            'booking'   => $booking,
            'watermark' => $booking->status === 'cancelled' ? 'ملغي' : null,
        ], 'contract-' . $booking->booking_number . '.pdf', [
            'title' => 'عقد ' . $booking->booking_number,
        ]);
    }

    public function receipt(ReligiousBooking $booking, BookingPayment $payment)
    {
        abort_unless($payment->booking_id === $booking->id, 404);

        $payment->load(['booking.customer', 'booking.program', 'receiver']);

        return $this->emit('admin.religious.print.receipt', [
            'payment' => $payment,
        ], 'receipt-' . $payment->receipt_number . '.pdf', [
            'title' => 'إيصال ' . $payment->receipt_number,
        ]);
    }

    public function manifest(ReligiousBooking $booking)
    {
        $booking->load(['customer', 'program', 'pilgrims']);

        return $this->emit('admin.religious.print.manifest', [
            'booking' => $booking,
        ], 'manifest-' . $booking->booking_number . '.pdf', [
            'title'       => 'قائمة معتمرين ' . $booking->booking_number,
            'orientation' => 'L',
        ]);
    }

    private function emit(string $view, array $data, string $filename, array $opts)
    {
        return request()->boolean('stream')
            ? $this->pdf->stream($view, $data, $filename, $opts)
            : $this->pdf->download($view, $data, $filename, $opts);
    }
}
