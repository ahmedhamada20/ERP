<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BookingAccommodation;
use App\Models\ExchangeRate;
use App\Models\ReligiousBooking;
use Illuminate\Http\Request;

class BookingAccommodationController extends Controller
{
    public function store(Request $request, ReligiousBooking $booking)
    {
        $data = $this->validateRow($request);

        if (empty($data['exchange_rate'])) {
            $data['exchange_rate'] = ExchangeRate::rateFor('SAR', 'EGP') ?: $booking->exchange_rate_sar ?: 1;
        }

        $booking->accommodations()->create($data);

        return back()->with('success', 'تم إضافة السكن');
    }

    public function update(Request $request, ReligiousBooking $booking, BookingAccommodation $accommodation)
    {
        abort_unless($accommodation->booking_id === $booking->id, 404);
        $accommodation->update($this->validateRow($request));
        return back()->with('success', 'تم تحديث السكن');
    }

    public function destroy(ReligiousBooking $booking, BookingAccommodation $accommodation)
    {
        abort_unless($accommodation->booking_id === $booking->id, 404);
        $accommodation->delete();
        return response()->json(['message' => 'تم حذف السكن']);
    }

    private function validateRow(Request $request): array
    {
        return $request->validate([
            'city'                     => ['required', 'in:mecca,medina,jeddah,other'],
            'hotel_id'                 => ['nullable', 'ulid', 'exists:hotels,id'],
            'hotel_name'               => ['required', 'string', 'max:200'],
            'hotel_grade'              => ['required', 'in:economy,4_stars,5_stars'],
            'hotel_distance_meters'    => ['nullable', 'string', 'max:30'],
            'check_in_date'            => ['required', 'date'],
            'check_out_date'           => ['required', 'date', 'after:check_in_date'],
            'nights'                   => ['required', 'integer', 'min:1', 'max:60'],
            'rooms_count'              => ['required', 'integer', 'min:1', 'max:500'],
            'room_type'                => ['required', 'in:single,double,triple,quad,quintuple,sextuple'],
            'pax_per_room'             => ['required', 'integer', 'min:1', 'max:6'],
            'meal_plan'                => ['required', 'in:ro,bb,hb,fb,pp,hp'],
            'room_price_per_night_sar' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'exchange_rate'            => ['nullable', 'numeric', 'min:0', 'max:9999.9999'],
            'confirmation_number'      => ['nullable', 'string', 'max:80'],
            'notes'                    => ['nullable', 'string', 'max:500'],
        ]);
    }
}
