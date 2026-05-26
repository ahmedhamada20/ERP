<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Lead extends Model
{
    use HasUlids, HasFactory, SoftDeletes, LogsActivity;

    public const STATUS_LABELS = [
        'new'        => 'جديد',
        'contacted'  => 'تم التواصل',
        'qualified'  => 'مؤهل',
        'proposal'   => 'عرض مقدم',
        'won'        => 'فائز',
        'lost'       => 'خاسر',
    ];

    public const STATUS_BADGES = [
        'new'        => 'secondary',
        'contacted'  => 'info',
        'qualified'  => 'primary',
        'proposal'   => 'warning',
        'won'        => 'success',
        'lost'       => 'danger',
    ];

    public const SOURCE_LABELS = [
        'facebook'  => 'فيسبوك',
        'instagram' => 'إنستجرام',
        'whatsapp'  => 'واتساب',
        'website'   => 'الموقع',
        'walk_in'   => 'زيارة المكتب',
        'referral'  => 'توصية',
        'phone'     => 'مكالمة',
        'tiktok'    => 'تيك توك',
        'other'     => 'أخرى',
    ];

    public const INTEREST_LABELS = [
        'hajj'          => 'حج',
        'umrah'         => 'عمرة',
        'domestic'      => 'سياحة داخلية',
        'international' => 'سياحة دولية',
        'other'         => 'أخرى',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'code', 'full_name', 'phone', 'status', 'interest_type',
                'source', 'assigned_to', 'estimated_value', 'lost_reason',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $event) => match ($event) {
                'created' => 'تم إنشاء عميل محتمل',
                'updated' => 'تم تعديل عميل محتمل',
                'deleted' => 'تم حذف عميل محتمل',
                default   => $event,
            })
            ->useLogName('lead');
    }

    protected $fillable = [
        'code', 'full_name', 'phone', 'whatsapp', 'email', 'city',
        'source', 'status', 'interest_type', 'assigned_to',
        'estimated_value', 'expected_close_date',
        'lost_reason', 'lost_at',
        'converted_to_customer_id', 'converted_at',
        'notes', 'created_by',
    ];

    protected $casts = [
        'estimated_value'     => 'decimal:2',
        'expected_close_date' => 'date',
        'lost_at'             => 'datetime',
        'converted_at'        => 'datetime',
    ];

    /** Mirror DB defaults so newly-created models without explicit values still resolve labels. */
    protected $attributes = [
        'status'        => 'new',
        'source'        => 'other',
        'interest_type' => 'other',
    ];

    protected static function booted(): void
    {
        static::creating(function (Lead $lead) {
            if (empty($lead->code)) {
                $lead->code = self::generateCode();
            }
            if (auth()->check() && empty($lead->created_by)) {
                $lead->created_by = auth()->id();
            }
            // Default whatsapp to phone if not given
            if (empty($lead->whatsapp) && !empty($lead->phone)) {
                $lead->whatsapp = $lead->phone;
            }
        });

        static::updating(function (Lead $lead) {
            if ($lead->isDirty('status') && $lead->status === 'lost' && empty($lead->lost_at)) {
                $lead->lost_at = now();
            }
        });
    }

    public static function generateCode(): string
    {
        $year = date('Y');
        $next = Sequence::next('lead:' . $year);

        return 'LEAD-' . $year . '-' . str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }

    // ── Relations ─────────────────────────────────────────────────────
    public function assignee()   { return $this->belongsTo(User::class, 'assigned_to'); }
    public function creator()    { return $this->belongsTo(User::class, 'created_by'); }
    public function customer()   { return $this->belongsTo(Customer::class, 'converted_to_customer_id'); }
    public function activities() { return $this->hasMany(LeadActivity::class)->latest(); }
    public function opportunities() { return $this->hasMany(Opportunity::class); }

    // ── Labels ────────────────────────────────────────────────────────
    public function getStatusLabelAttribute(): string  { return self::STATUS_LABELS[$this->status] ?? $this->status; }
    public function getStatusBadgeAttribute(): string  { return self::STATUS_BADGES[$this->status] ?? 'secondary'; }
    public function getSourceLabelAttribute(): string  { return self::SOURCE_LABELS[$this->source] ?? $this->source; }
    public function getInterestLabelAttribute(): string { return self::INTEREST_LABELS[$this->interest_type] ?? $this->interest_type; }

    // ── Scopes ────────────────────────────────────────────────────────
    public function scopeOpen($query) { return $query->whereNotIn('status', ['won', 'lost']); }
    public function scopeOfStatus($query, string $status) { return $query->where('status', $status); }
    public function scopeAssignedTo($query, string $userId) { return $query->where('assigned_to', $userId); }

    public function isConverted(): bool { return !is_null($this->converted_to_customer_id); }
}
