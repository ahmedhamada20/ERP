<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BookingDocument;
use App\Models\ReligiousBooking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BookingDocumentController extends Controller
{
    public function store(Request $request, ReligiousBooking $booking)
    {
        $data = $request->validate([
            'pilgrim_id'  => ['nullable', 'exists:booking_pilgrims,id'],
            'category'    => ['required', 'in:passport,national_id,visa,vaccination,medical,insurance,ticket,contract,receipt,photo,mahram,other'],
            'title'       => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:500'],
            'issue_date'  => ['nullable', 'date'],
            'expiry_date' => ['nullable', 'date'],
            'file'        => ['required', 'file', 'max:10240', 'mimes:jpg,jpeg,png,webp,pdf,doc,docx'],
        ]);

        // Reject if pilgrim doesn't belong to this booking
        if (!empty($data['pilgrim_id'])) {
            $belongs = $booking->pilgrims()->where('id', $data['pilgrim_id'])->exists();
            abort_unless($belongs, 404);
        }

        $file = $request->file('file');
        $path = $file->store('religious/documents/' . $booking->id, 'public');

        BookingDocument::create([
            'booking_id'      => $booking->id,
            'pilgrim_id'      => $data['pilgrim_id'] ?? null,
            'category'        => $data['category'],
            'title'           => $data['title'],
            'description'     => $data['description'] ?? null,
            'file_path'       => $path,
            'file_name'       => $file->getClientOriginalName(),
            'mime_type'       => $file->getMimeType(),
            'file_size_bytes' => $file->getSize(),
            'issue_date'      => $data['issue_date']  ?? null,
            'expiry_date'     => $data['expiry_date'] ?? null,
            'uploaded_by'     => auth()->id(),
        ]);

        return back()->with('success', 'تم رفع الوثيقة بنجاح');
    }

    public function download(ReligiousBooking $booking, BookingDocument $document)
    {
        abort_unless($document->booking_id === $booking->id, 404);
        abort_unless(Storage::disk('public')->exists($document->file_path), 404, 'الملف غير موجود');

        return Storage::disk('public')->download($document->file_path, $document->file_name);
    }

    public function destroy(ReligiousBooking $booking, BookingDocument $document)
    {
        abort_unless($document->booking_id === $booking->id, 404);

        if (Storage::disk('public')->exists($document->file_path)) {
            Storage::disk('public')->delete($document->file_path);
        }
        $document->delete();

        return response()->json(['message' => 'تم حذف الوثيقة']);
    }
}
