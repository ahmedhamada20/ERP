<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Opportunity extends Model
{
    use HasUlids, HasFactory, SoftDeletes, LogsActivity;

    public const STAGE_LABELS = [
        'prospecting'    => 'استكشاف',
        'qualification'  => 'تأهيل',
        'proposal'       => 'عرض مقدم',
        'negotiation'    => 'تفاوض',
        'closed_won'     => 'فوز',
        'closed_lost'    => 'خسارة',
    ];

    public const STAGE_BADGES = [
        'prospecting'    => 'secondary',
        'qualification'  => 'info',
        'proposal'       => 'primary',
        'negotiation'    => 'warning',
        'closed_won'     => 'success',
        'closed_lost'    => 'danger',
    ];

    /** Default probability % per stage — used to seed the slider. */
    public const STAGE_PROBABILITY = [
        'prospecting'   => 20,
        'qualification' => 40,
        'proposal'      => 60,
        'negotiation'   => 80,
        'closed_won'    => 100,
        'closed_lost'   => 0,
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'code', 'title', 'lead_id', 'customer_id', 'booking_type', 'sub_type',
                'stage', 'estimated_value', 'probability', 'expected_close_date',
                'assigned_to', 'converted_booking_type', 'converted_booking_id',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $event) => match ($event) {
                'created' => 'تم إنشاء صفقة',
                'updated' => 'تم تعديل صفقة',
                'deleted' => 'تم حذف صفقة',
                default   => $event,
            })
            ->useLogName('opportunity');
    }

    protected $fillable = [
        'code', 'title', 'lead_id', 'customer_id',
        'booking_type', 'sub_type', 'destination', 'expected_trip_date',
        'pax_count', 'estimated_value', 'probability',
        'stage', 'expected_close_date', 'actual_close_date', 'lost_reason',
        'converted_booking_type', 'converted_booking_id',
        'assigned_to', 'notes', 'created_by',
    ];

    protected $casts = [
        'expected_trip_date'  => 'date',
        'expected_close_date' => 'date',
        'actual_close_date'   => 'date',
        'pax_count'           => 'integer',
        'estimated_value'     => 'decimal:2',
        'probability'         => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (Opportunity $opp) {
            if (empty($opp->code)) {
                $opp->code = self::generateCode();
            }
            if (auth()->check() && empty($opp->created_by)) {
                $opp->created_by = auth()->id();
            }
            if (empty($opp->probability) && isset(self::STAGE_PROBABILITY[$opp->stage])) {
                $opp->probability = self::STAGE_PROBABILITY[$opp->stage];
            }
        });

        static::updating(function (Opportunity $opp) {
            // Auto-set actual_close_date when transitioning to a closed stage
            if ($opp->isDirty('stage') && in_array($opp->stage, ['closed_won', 'closed_lost']) && empty($opp->actual_close_date)) {
                $opp->actual_close_date = now()->toDateString();
            }
        });
    }

    public static function generateCode(): string
    {
        $year = date('Y');
        $next = Sequence::next('opportunity:' . $year);

        return 'OPP-' . $year . '-' . str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }

    // ── Relations ─────────────────────────────────────────────────────
    public function lead()      { return $this->belongsTo(Lead::class); }
    public function customer()  { return $this->belongsTo(Customer::class); }
    public function assignee()  { return $this->belongsTo(User::class, 'assigned_to'); }
    public function creator()   { return $this->belongsTo(User::class, 'created_by'); }

    /** Polymorphic resolution to the converted booking (religious or domestic). */
    public function convertedBooking()
    {
        if (! $this->converted_booking_id || ! $this->converted_booking_type) {
            return null;
        }
        return match ($this->converted_booking_type) {
            'religious' => ReligiousBooking::find($this->converted_booking_id),
            'domestic'  => DomesticBooking::find($this->converted_booking_id),
            default     => null,
        };
    }

    // ── Computed ──────────────────────────────────────────────────────
    /** Weighted forecast value = estimated_value × probability%. */
    public function getWeightedValueAttribute(): float
    {
        return round((float) $this->estimated_value * ($this->probability / 100), 2);
    }

    // ── Labels ────────────────────────────────────────────────────────
    public function getStageLabelAttribute(): string { return self::STAGE_LABELS[$this->stage] ?? $this->stage; }
    public function getStageBadgeAttribute(): string { return self::STAGE_BADGES[$this->stage] ?? 'secondary'; }

    public function getBookingTypeLabelAttribute(): string
    {
        return $this->booking_type === 'religious' ? 'سياحة دينية' : 'سياحة داخلية';
    }

    public function isConverted(): bool { return !is_null($this->converted_booking_id); }
    public function isClosed(): bool    { return in_array($this->stage, ['closed_won', 'closed_lost']); }
    public function isOpen(): bool      { return !$this->isClosed(); }

    // ── Scopes ────────────────────────────────────────────────────────
    public function scopeOpen($query)   { return $query->whereNotIn('stage', ['closed_won', 'closed_lost']); }
    public function scopeWon($query)    { return $query->where('stage', 'closed_won'); }
    public function scopeLost($query)   { return $query->where('stage', 'closed_lost'); }
}
