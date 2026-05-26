<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookingPilgrim extends Model
{
    use HasUlids, HasFactory;

    protected $fillable = [
        'booking_id', 'customer_id',
        'full_name', 'full_name_en', 'national_id',
        'passport_number', 'passport_issue_date', 'passport_expiry_date',
        'gender', 'birth_date', 'age_group', 'nationality', 'relationship_to_main',
        'room_assignment', 'bed_number',
        'safa_barcode', 'visa_number', 'visa_status',
        'visa_issued_date', 'visa_expiry_date',
        'passport_image', 'photo', 'notes',
    ];

    protected $casts = [
        'passport_issue_date'  => 'date',
        'passport_expiry_date' => 'date',
        'birth_date'           => 'date',
        'visa_issued_date'     => 'date',
        'visa_expiry_date'     => 'date',
    ];

    public function booking()  { return $this->belongsTo(ReligiousBooking::class, 'booking_id'); }
    public function customer() { return $this->belongsTo(Customer::class); }

    public function getAgeAttribute(): ?int
    {
        return $this->birth_date ? $this->birth_date->age : null;
    }

    public function getVisaStatusLabelAttribute(): string
    {
        return match ($this->visa_status) {
            'pending'   => 'قيد الانتظار',
            'requested' => 'مطلوبة',
            'issued'    => 'صادرة',
            'rejected'  => 'مرفوضة',
            'cancelled' => 'ملغية',
            default     => $this->visa_status,
        };
    }
}
