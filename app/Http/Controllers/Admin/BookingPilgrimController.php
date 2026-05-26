<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BookingPilgrim;
use App\Models\ReligiousBooking;
use App\Traits\HandlesImageUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BookingPilgrimController extends Controller
{
    use HandlesImageUpload;

    public function store(Request $request, ReligiousBooking $booking)
    {
        $data = $request->validate([
            'customer_id'          => ['nullable', 'exists:customers,id'],
            'full_name'            => ['required', 'string', 'max:200'],
            'full_name_en'         => ['nullable', 'string', 'max:200'],
            'national_id'          => ['nullable', 'string', 'max:20'],
            'passport_number'      => ['nullable', 'string', 'max:30'],
            'passport_issue_date'  => ['nullable', 'date'],
            'passport_expiry_date' => ['nullable', 'date'],
            'gender'               => ['required', 'in:male,female'],
            'birth_date'           => ['nullable', 'date'],
            'age_group'            => ['required', 'in:adult,child,infant'],
            'nationality'          => ['nullable', 'string', 'max:80'],
            'relationship_to_main' => ['required', 'in:self,spouse,parent,child,sibling,other'],
            'room_assignment'      => ['nullable', 'string', 'max:40'],
            'bed_number'           => ['nullable', 'integer', 'min:1', 'max:10'],
            'passport_image'       => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
            'photo'                => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
            'notes'                => ['nullable', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($request, $booking, $data) {
            foreach (['passport_image', 'photo'] as $field) {
                if ($request->hasFile($field)) {
                    $data[$field] = $this->uploadImage($request->file($field), 'religious/pilgrims/' . $field);
                }
            }

            $booking->pilgrims()->create($data);
        });

        return back()->with('success', 'تم إضافة المعتمر بنجاح');
    }

    public function update(Request $request, ReligiousBooking $booking, BookingPilgrim $pilgrim)
    {
        abort_unless($pilgrim->booking_id === $booking->id, 404);

        $data = $request->validate([
            'full_name'            => ['required', 'string', 'max:200'],
            'full_name_en'         => ['nullable', 'string', 'max:200'],
            'national_id'          => ['nullable', 'string', 'max:20'],
            'passport_number'      => ['nullable', 'string', 'max:30'],
            'passport_issue_date'  => ['nullable', 'date'],
            'passport_expiry_date' => ['nullable', 'date'],
            'gender'               => ['required', 'in:male,female'],
            'birth_date'           => ['nullable', 'date'],
            'age_group'            => ['required', 'in:adult,child,infant'],
            'nationality'          => ['nullable', 'string', 'max:80'],
            'relationship_to_main' => ['required', 'in:self,spouse,parent,child,sibling,other'],
            'room_assignment'      => ['nullable', 'string', 'max:40'],
            'bed_number'           => ['nullable', 'integer', 'min:1', 'max:10'],
            'safa_barcode'         => ['nullable', 'string', 'max:80'],
            'visa_number'          => ['nullable', 'string', 'max:80'],
            'visa_status'          => ['required', 'in:pending,requested,issued,rejected,cancelled'],
            'visa_issued_date'     => ['nullable', 'date'],
            'visa_expiry_date'     => ['nullable', 'date'],
            'passport_image'       => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
            'photo'                => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
            'notes'                => ['nullable', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($request, $pilgrim, $data) {
            foreach (['passport_image', 'photo'] as $field) {
                if ($request->hasFile($field)) {
                    $data[$field] = $this->uploadImage($request->file($field), 'religious/pilgrims/' . $field, $pilgrim->$field);
                }
            }
            $pilgrim->update($data);
        });

        return back()->with('success', 'تم تحديث بيانات المعتمر');
    }

    public function destroy(ReligiousBooking $booking, BookingPilgrim $pilgrim)
    {
        abort_unless($pilgrim->booking_id === $booking->id, 404);

        foreach (['passport_image', 'photo'] as $field) {
            $this->deleteImage($pilgrim->$field);
        }
        $pilgrim->delete();

        return response()->json(['message' => 'تم حذف المعتمر بنجاح']);
    }
}
