<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Customer extends Model
{
    use HasUlids, HasFactory, SoftDeletes, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'code', 'full_name', 'full_name_en', 'national_id',
                'passport_number', 'passport_expiry_date',
                'phone', 'mobile', 'whatsapp', 'email',
                'city', 'country', 'type', 'status',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $event) => match ($event) {
                'created' => 'تم إنشاء عميل',
                'updated' => 'تم تعديل بيانات عميل',
                'deleted' => 'تم حذف عميل',
                default   => $event,
            })
            ->useLogName('customer');
    }

    protected $fillable = [
        'code', 'full_name', 'full_name_en',
        'national_id', 'passport_number', 'passport_issue_date',
        'passport_expiry_date', 'passport_issue_place',
        'gender', 'birth_date', 'nationality', 'religion', 'marital_status',
        'phone', 'mobile', 'whatsapp', 'email',
        'address', 'city', 'governorate', 'country',
        'type', 'status',
        'photo', 'passport_image', 'national_id_image',
        'notes', 'created_by',
    ];

    protected $casts = [
        'birth_date'           => 'date',
        'passport_issue_date'  => 'date',
        'passport_expiry_date' => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function (Customer $customer) {
            if (empty($customer->code)) {
                $customer->code = self::generateCode();
            }
            if (auth()->check() && empty($customer->created_by)) {
                $customer->created_by = auth()->id();
            }
        });
    }

    public static function generateCode(): string
    {
        $year = date('Y');
        $next = Sequence::next('customer:' . $year);

        return 'CUS-' . $year . '-' . str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }

    // ── Relations ────────────────────────────────────────────────────
    public function creator()           { return $this->belongsTo(User::class, 'created_by'); }
    public function religiousBookings() { return $this->hasMany(ReligiousBooking::class); }
    public function domesticBookings()  { return $this->hasMany(DomesticBooking::class); }
    public function leads()             { return $this->hasMany(Lead::class, 'converted_to_customer_id'); }

    public function getPhotoUrlAttribute(): string
    {
        return $this->photo
            ? asset('storage/' . $this->photo)
            : 'https://ui-avatars.com/api/?name=' . urlencode($this->full_name) . '&background=c9a227&color=fff&rounded=true';
    }

    public function getGenderLabelAttribute(): string
    {
        return $this->gender === 'female' ? 'أنثى' : 'ذكر';
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'active'      => 'نشط',
            'inactive'    => 'غير نشط',
            'blacklisted' => 'محظور',
            default       => $this->status,
        };
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'active'      => 'success',
            'inactive'    => 'secondary',
            'blacklisted' => 'danger',
            default       => 'info',
        };
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'individual' => 'فرد',
            'agency'     => 'وكيل',
            'group'      => 'مجموعة',
            default      => $this->type,
        };
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeSearch($query, ?string $term)
    {
        if (!$term) return $query;
        return $query->where(function ($q) use ($term) {
            $q->where('full_name', 'like', "%{$term}%")
              ->orWhere('full_name_en', 'like', "%{$term}%")
              ->orWhere('code', 'like', "%{$term}%")
              ->orWhere('phone', 'like', "%{$term}%")
              ->orWhere('national_id', 'like', "%{$term}%")
              ->orWhere('passport_number', 'like', "%{$term}%");
        });
    }
}
