<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BookingPaymentRequest;
use App\Models\BookingPayment;
use App\Models\ExchangeRate;
use App\Models\ReligiousBooking;
use App\Traits\HandlesImageUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BookingPaymentController extends Controller
{
    use HandlesImageUpload;

    public function store(BookingPaymentRequest $request, ReligiousBooking $booking)
    {
        $this->guardClosedBooking($booking);

        $data = $request->validated();

        if ($data['currency'] !== 'EGP' && empty($data['exchange_rate'])) {
            $data['exchange_rate'] = ExchangeRate::rateFor($data['currency'], 'EGP') ?: 1;
        }
        if ($data['currency'] === 'EGP') {
            $data['exchange_rate'] = 1;
        }

        DB::transaction(function () use ($request, $booking, $data) {
            if ($request->hasFile('attachment')) {
                $data['attachment'] = $this->uploadImage($request->file('attachment'), 'religious/receipts');
            }
            $booking->payments()->create($data);
        });

        return back()->with('success', 'تم تسجيل الدفعة بنجاح');
    }

    public function update(BookingPaymentRequest $request, ReligiousBooking $booking, BookingPayment $payment)
    {
        abort_unless($payment->booking_id === $booking->id, 404);
        $this->guardClosedBooking($booking);

        $data = $request->validated();

        if ($data['currency'] === 'EGP') {
            $data['exchange_rate'] = 1;
        }

        DB::transaction(function () use ($request, $payment, $data) {
            if ($request->hasFile('attachment')) {
                $data['attachment'] = $this->uploadImage($request->file('attachment'), 'religious/receipts', $payment->attachment);
            }
            $payment->update($data);
        });

        return back()->with('success', 'تم تحديث الدفعة');
    }

    public function destroy(ReligiousBooking $booking, BookingPayment $payment)
    {
        abort_unless($payment->booking_id === $booking->id, 404);
        $this->guardClosedBooking($booking);

        $this->deleteImage($payment->attachment);
        $payment->delete();

        return response()->json(['message' => 'تم حذف الدفعة بنجاح']);
    }

    public function approveRefund(Request $request, ReligiousBooking $booking, BookingPayment $payment)
    {
        abort_unless($payment->booking_id === $booking->id, 404);
        $this->guardClosedBooking($booking);
        $this->guardIsPendingRefund($payment);

        $data = $request->validate([
            'approval_notes' => ['nullable', 'string', 'max:500'],
        ]);

        $payment->update([
            'refund_status'  => 'approved',
            'approved_by'    => auth()->id(),
            'approved_at'    => now(),
            'approval_notes' => $data['approval_notes'] ?? null,
        ]);

        return back()->with('success', 'تمت الموافقة على الاسترداد');
    }

    public function rejectRefund(Request $request, ReligiousBooking $booking, BookingPayment $payment)
    {
        abort_unless($payment->booking_id === $booking->id, 404);
        $this->guardClosedBooking($booking);
        $this->guardIsPendingRefund($payment);

        $data = $request->validate([
            'approval_notes' => ['required', 'string', 'max:500'],
        ]);

        $payment->update([
            'refund_status'  => 'rejected',
            'approved_by'    => auth()->id(),
            'approved_at'    => now(),
            'approval_notes' => $data['approval_notes'],
        ]);

        return back()->with('success', 'تم رفض طلب الاسترداد');
    }

    public function markRefundPaid(ReligiousBooking $booking, BookingPayment $payment)
    {
        abort_unless($payment->booking_id === $booking->id, 404);
        $this->guardClosedBooking($booking);

        if (! $payment->isRefund() || $payment->refund_status !== 'approved') {
            abort(422, 'لا يمكن صرف الاسترداد قبل الموافقة عليه');
        }

        $payment->update(['refund_status' => 'paid']);

        return back()->with('success', 'تم تأكيد صرف الاسترداد للعميل');
    }

    private function guardClosedBooking(ReligiousBooking $booking): void
    {
        if ($booking->workflow_stage === 'closed') {
            abort(422, 'الحجز مُقفل ولا يمكن تعديل المدفوعات بعد الإقفال');
        }
    }

    private function guardIsPendingRefund(BookingPayment $payment): void
    {
        if (! $payment->isRefund() || $payment->refund_status !== 'pending') {
            abort(422, 'هذه الدفعة ليست طلب استرداد قيد الانتظار');
        }
    }
}
