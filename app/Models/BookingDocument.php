<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class BookingDocument extends Model
{
    use HasUlids;

    protected $fillable = [
        'booking_id', 'pilgrim_id',
        'category', 'title', 'description',
        'file_path', 'file_name', 'mime_type', 'file_size_bytes',
        'issue_date', 'expiry_date', 'uploaded_by',
    ];

    protected $casts = [
        'issue_date'      => 'date',
        'expiry_date'     => 'date',
        'file_size_bytes' => 'integer',
    ];

    public const CATEGORY_LABELS = [
        'passport'     => 'جواز سفر',
        'national_id'  => 'بطاقة قومية',
        'visa'         => 'تأشيرة',
        'vaccination'  => 'شهادة تطعيم',
        'medical'      => 'تقرير طبي',
        'insurance'    => 'تأمين سفر',
        'ticket'       => 'تذكرة',
        'contract'     => 'عقد موقع',
        'receipt'      => 'إيصال',
        'photo'        => 'صورة شخصية',
        'mahram'       => 'وثيقة محرم',
        'other'        => 'أخرى',
    ];

    public function booking() { return $this->belongsTo(ReligiousBooking::class, 'booking_id'); }
    public function pilgrim() { return $this->belongsTo(BookingPilgrim::class, 'pilgrim_id'); }
    public function uploader() { return $this->belongsTo(User::class, 'uploaded_by'); }

    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORY_LABELS[$this->category] ?? $this->category;
    }

    public function getFileSizeHumanAttribute(): string
    {
        if (!$this->file_size_bytes) return '—';
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = (float) $this->file_size_bytes;
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 1) . ' ' . $units[$i];
    }

    public function getFileUrlAttribute(): string
    {
        return asset('storage/' . $this->file_path);
    }

    public function getIsImageAttribute(): bool
    {
        return $this->mime_type && str_starts_with($this->mime_type, 'image/');
    }

    public function getIsPdfAttribute(): bool
    {
        return $this->mime_type === 'application/pdf';
    }

    public function getIsExpiringAttribute(): bool
    {
        return $this->expiry_date && $this->expiry_date <= now()->addMonths(6);
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->expiry_date && $this->expiry_date < now();
    }
}
