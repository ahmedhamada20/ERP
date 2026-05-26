<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DomesticBooking;
use App\Models\DomesticBookingCost;
use App\Models\ExchangeRate;
use Illuminate\Http\Request;

class DomesticBookingCostController extends Controller
{
    private const CATEGORIES = 'hotel,room,transport,private_car,flight,meals,activities,supervision,tax,activation,profit,gifts,commission,bank_fee,insurance,miscellaneous,other';

    public function store(Request $request, DomesticBooking $booking)
    {
        $this->guardClosedBooking($booking);

        $data = $request->validate([
            'category'      => ['required', 'in:' . self::CATEGORIES],
            'supplier_id'   => ['nullable', 'ulid', 'exists:suppliers,id'],
            'description'   => ['nullable', 'string', 'max:200'],
            'currency'      => ['required', 'in:EGP,USD,EUR'],
            'amount'        => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'exchange_rate' => ['nullable', 'numeric', 'min:0', 'max:9999.9999'],
            'quantity'      => ['required', 'integer', 'min:1', 'max:5000'],
            'per_unit'      => ['required', 'in:per_person,per_room,per_night,per_trip,total'],
            'is_revenue'    => ['nullable', 'boolean'],
            'notes'         => ['nullable', 'string', 'max:500'],
        ]);

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

    public function update(Request $request, DomesticBooking $booking, DomesticBookingCost $cost)
    {
        abort_unless($cost->booking_id === $booking->id, 404);
        $this->guardClosedBooking($booking);

        if ($cost->is_locked) {
            return back()->with('error', 'هذا البند مقفل ولا يمكن تعديله');
        }

        $data = $request->validate([
            'category'      => ['required', 'in:' . self::CATEGORIES],
            'supplier_id'   => ['nullable', 'ulid', 'exists:suppliers,id'],
            'description'   => ['nullable', 'string', 'max:200'],
            'currency'      => ['required', 'in:EGP,USD,EUR'],
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

    public function destroy(DomesticBooking $booking, DomesticBookingCost $cost)
    {
        abort_unless($cost->booking_id === $booking->id, 404);
        $this->guardClosedBooking($booking);

        if ($cost->is_locked) {
            return response()->json(['message' => 'هذا البند مقفل ولا يمكن حذفه'], 422);
        }

        $cost->delete();
        return response()->json(['message' => 'تم حذف البند بنجاح']);
    }

    private function guardClosedBooking(DomesticBooking $booking): void
    {
        if ($booking->workflow_stage === 'closed') {
            abort(422, 'الحجز مُقفل ولا يمكن تعديل بنوده المالية');
        }
    }
}
