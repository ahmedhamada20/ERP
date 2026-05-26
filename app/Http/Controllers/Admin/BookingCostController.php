<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BookingCost;
use App\Models\ExchangeRate;
use App\Models\ReligiousBooking;
use Illuminate\Http\Request;

class BookingCostController extends Controller
{
    public function store(Request $request, ReligiousBooking $booking)
    {
        $this->guardClosedBooking($booking);

        $data = $request->validate([
            'category'      => ['required', 'in:visa,room,transport,flight,miscellaneous,supervision,tax,activation,profit,gifts,mutawif,commission,bank_fee,insurance,other'],
            'supplier_id'   => ['nullable', 'ulid', 'exists:suppliers,id'],
            'description'   => ['nullable', 'string', 'max:200'],
            'currency'      => ['required', 'in:EGP,SAR,USD'],
            'amount'        => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'exchange_rate' => ['nullable', 'numeric', 'min:0', 'max:9999.9999'],
            'quantity'      => ['required', 'integer', 'min:1', 'max:5000'],
            'per_unit'      => ['required', 'in:per_person,per_room,per_night,per_trip,total'],
            'is_revenue'    => ['nullable', 'boolean'],
            'notes'         => ['nullable', 'string', 'max:500'],
        ]);

        // Auto-fill exchange rate for non-EGP currencies if not provided
        if ($data['currency'] !== 'EGP' && empty($data['exchange_rate'])) {
            $data['exchange_rate'] = ExchangeRate::rateFor($data['currency'], 'EGP') ?: 1;
        }
        if ($data['currency'] === 'EGP') {
            $data['exchange_rate'] = 1;
        }

        $data['is_revenue'] = $data['category'] === 'profit' || ($data['is_revenue'] ?? false);

        $booking->costs()->create($data);

        return back()->with('success', 'تم إضافة بند التكلفة');
    }

    public function update(Request $request, ReligiousBooking $booking, BookingCost $cost)
    {
        abort_unless($cost->booking_id === $booking->id, 404);
        $this->guardClosedBooking($booking);

        if ($cost->is_locked) {
            return back()->with('error', 'هذا البند مقفل ولا يمكن تعديله');
        }

        $data = $request->validate([
            'category'      => ['required', 'in:visa,room,transport,flight,miscellaneous,supervision,tax,activation,profit,gifts,mutawif,commission,bank_fee,insurance,other'],
            'supplier_id'   => ['nullable', 'ulid', 'exists:suppliers,id'],
            'description'   => ['nullable', 'string', 'max:200'],
            'currency'      => ['required', 'in:EGP,SAR,USD'],
            'amount'        => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'exchange_rate' => ['nullable', 'numeric', 'min:0', 'max:9999.9999'],
            'quantity'      => ['required', 'integer', 'min:1', 'max:5000'],
            'per_unit'      => ['required', 'in:per_person,per_room,per_night,per_trip,total'],
            'is_revenue'    => ['nullable', 'boolean'],
            'notes'         => ['nullable', 'string', 'max:500'],
        ]);

        if ($data['currency'] === 'EGP') {
            $data['exchange_rate'] = 1;
        }
        $data['is_revenue'] = $data['category'] === 'profit' || ($data['is_revenue'] ?? false);

        $cost->update($data);

        return back()->with('success', 'تم تحديث بند التكلفة');
    }

    public function destroy(ReligiousBooking $booking, BookingCost $cost)
    {
        abort_unless($cost->booking_id === $booking->id, 404);
        $this->guardClosedBooking($booking);

        if ($cost->is_locked) {
            return response()->json(['message' => 'هذا البند مقفل ولا يمكن حذفه'], 422);
        }

        $cost->delete();
        return response()->json(['message' => 'تم حذف البند بنجاح']);
    }

    private function guardClosedBooking(ReligiousBooking $booking): void
    {
        if ($booking->workflow_stage === 'closed') {
            abort(422, 'الحجز مُقفل ولا يمكن تعديل بنوده المالية');
        }
    }
}
