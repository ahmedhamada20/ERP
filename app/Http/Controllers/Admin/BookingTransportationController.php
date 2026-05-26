<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BookingTransportation;
use App\Models\ExchangeRate;
use App\Models\ReligiousBooking;
use Illuminate\Http\Request;

class BookingTransportationController extends Controller
{
    public function store(Request $request, ReligiousBooking $booking)
    {
        $data = $this->validateRow($request);

        if ($data['currency'] !== 'EGP' && empty($data['exchange_rate'])) {
            $data['exchange_rate'] = ExchangeRate::rateFor($data['currency'], 'EGP') ?: 1;
        }
        if ($data['currency'] === 'EGP') {
            $data['exchange_rate'] = 1;
        }

        $booking->transportation()->create($data);

        return back()->with('success', 'تم إضافة وسيلة النقل');
    }

    public function update(Request $request, ReligiousBooking $booking, BookingTransportation $transportation)
    {
        abort_unless($transportation->booking_id === $booking->id, 404);

        $data = $this->validateRow($request);
        if ($data['currency'] === 'EGP') {
            $data['exchange_rate'] = 1;
        }

        $transportation->update($data);
        return back()->with('success', 'تم تحديث وسيلة النقل');
    }

    public function destroy(ReligiousBooking $booking, BookingTransportation $transportation)
    {
        abort_unless($transportation->booking_id === $booking->id, 404);
        $transportation->delete();
        return response()->json(['message' => 'تم حذف وسيلة النقل']);
    }

    private function validateRow(Request $request): array
    {
        return $request->validate([
            'type'                  => ['required', 'in:flight,bus,train,vip'],
            'direction'             => ['required', 'in:outbound,inbound,internal'],
            'segment'               => ['required', 'in:cai_jed,jed_cai,jed_mec,mec_med,med_jed,other'],
            'airline_id'            => ['nullable', 'ulid', 'exists:airlines,id'],
            'transport_provider_id' => ['nullable', 'ulid', 'exists:transport_providers,id'],
            'carrier_name'          => ['nullable', 'string', 'max:120'],
            'reference'          => ['nullable', 'string', 'max:80'],
            'departure_location' => ['nullable', 'string', 'max:120'],
            'arrival_location'   => ['nullable', 'string', 'max:120'],
            'departure_at'       => ['nullable', 'date'],
            'arrival_at'         => ['nullable', 'date'],
            'currency'           => ['required', 'in:EGP,SAR,USD'],
            'cost_per_person'    => ['required', 'numeric', 'min:0', 'max:9999999.99'],
            'pax_count'          => ['required', 'integer', 'min:1', 'max:500'],
            'exchange_rate'      => ['nullable', 'numeric', 'min:0', 'max:9999.9999'],
            'notes'              => ['nullable', 'string', 'max:500'],
        ]);
    }
}
