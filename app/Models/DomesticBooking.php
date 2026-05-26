<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class DomesticBooking extends Model
{
    use HasUlids, HasFactory, SoftDeletes, LogsActivity, BelongsToBranch;

    public const TYPE_LABELS = [
        'hotel_only' => 'إقامة فندقية',
        'package'    => 'باكدج',
        'day_trip'   => 'رحلة يوم',
        'cruise'     => 'رحلة نيلية/بحرية',
        'camp'       => 'مخيم',
        'event'      => 'فعالية',
    ];

    public const ACCOMMODATION_LABELS = [
        'single'      => 'فردي',
        'double'      => 'ثنائي',
        'triple'      => 'ثلاثي',
        'quad'        => 'رباعي',
        'family_room' => 'غرفة عائلية',
        'suite'       => 'جناح',
    ];

    public const STATUS_LABELS = [
        'pending'     => 'قيد الانتظار',
        'confirmed'   => 'مؤكد',
        'in_progress' => 'جارية',
        'completed'   => 'مكتمل',
        'cancelled'   => 'ملغي',
    ];

    public const WORKFLOW_LABELS = [
        'sales'          => 'المبيعات',
        'manager_review' => 'مراجعة المدير',
        'operations'     => 'العمليات',
        'finance'        => 'المالية',
        'closed'         => 'مُقفل',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'booking_number', 'contract_number', 'receipt_number',
                'customer_id', 'program_id', 'hotel_id', 'type',
                'destination_city', 'booking_date', 'trip_date', 'return_date',
                'duration_days', 'duration_nights',
                'adults_count', 'children_count',
                'accommodation_type', 'rooms_count', 'meal_plan', 'transport_type',
                'selling_price', 'total_cost', 'net_profit',
                'status', 'workflow_stage',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $event) => match ($event) {
                'created' => 'تم إنشاء حجز داخلي',
                'updated' => 'تم تعديل حجز داخلي',
                'deleted' => 'تم حذف حجز داخلي',
                default   => $event,
            })
            ->useLogName('domestic_booking');
    }

    protected $fillable = [
        'branch_id',
        'booking_number', 'contract_number', 'receipt_number',
        'customer_id', 'sales_employee_id', 'program_id', 'hotel_id',
        'responsible_manager_id', 'responsible_employee_id',
        'type', 'destination_city', 'destination_area',
        'booking_date', 'trip_date', 'return_date',
        'duration_days', 'duration_nights',
        'adults_count', 'children_count', 'infants_count',
        'children_data', 'guests_data',
        'accommodation_type', 'rooms_count', 'accommodation_grade',
        'meal_plan', 'transport_type',
        'selling_price', 'total_cost', 'net_profit',
        'status', 'workflow_stage',
        'cost_journal_entry_id',
        'cancellation_reason', 'cancelled_at', 'cancelled_by',
        'notes', 'created_by',
    ];

    protected $casts = [
        'booking_date'    => 'date',
        'trip_date'       => 'date',
        'return_date'     => 'date',
        'children_data'   => 'array',
        'guests_data'     => 'array',
        'duration_days'   => 'integer',
        'duration_nights' => 'integer',
        'rooms_count'     => 'integer',
        'adults_count'    => 'integer',
        'children_count'  => 'integer',
        'infants_count'   => 'integer',
        'selling_price'   => 'decimal:2',
        'total_cost'      => 'decimal:2',
        'net_profit'      => 'decimal:2',
        'cancelled_at'    => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (DomesticBooking $booking) {
            if (empty($booking->booking_number)) {
                $booking->booking_number = self::generateBookingNumber();
            }
            if (auth()->check() && empty($booking->created_by)) {
                $booking->created_by = auth()->id();
            }
        });
    }

    public static function generateBookingNumber(): string
    {
        $year = date('Y');
        $next = Sequence::next('domestic_booking:' . $year);

        return 'DM-' . $year . '-' . str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }

    // ── Relations ─────────────────────────────────────────────────────
    public function customer()      { return $this->belongsTo(Customer::class); }
    public function program()       { return $this->belongsTo(DomesticProgram::class, 'program_id'); }
    public function hotel()         { return $this->belongsTo(Hotel::class, 'hotel_id'); }
    public function salesEmployee() { return $this->belongsTo(Employee::class, 'sales_employee_id'); }
    public function manager()       { return $this->belongsTo(User::class, 'responsible_manager_id'); }
    public function employee()      { return $this->belongsTo(User::class, 'responsible_employee_id'); }
    public function creator()   { return $this->belongsTo(User::class, 'created_by'); }
    public function canceller() { return $this->belongsTo(User::class, 'cancelled_by'); }

    public function costJournalEntry() { return $this->belongsTo(JournalEntry::class, 'cost_journal_entry_id'); }

    public function costs()           { return $this->hasMany(DomesticBookingCost::class, 'booking_id'); }
    public function payments()        { return $this->hasMany(DomesticBookingPayment::class, 'booking_id'); }
    public function commissionLines() { return $this->morphMany(PayslipLine::class, 'reference')->where('line_type', PayslipLine::TYPE_COMMISSION); }

    // ── Money helpers (mirror religious booking) ──────────────────────
    public function getTotalPaidAttribute(): float
    {
        $received = (float) $this->payments()
            ->where('payment_type', '!=', 'refund')
            ->sum('amount_egp');

        $refundedOut = (float) $this->payments()
            ->where('payment_type', 'refund')
            ->where('refund_status', 'paid')
            ->sum('amount_egp');

        return $received - $refundedOut;
    }

    public function getPendingRefundsAttribute(): float
    {
        return (float) $this->payments()
            ->where('payment_type', 'refund')
            ->whereIn('refund_status', ['pending', 'approved'])
            ->sum('amount_egp');
    }

    public function getOutstandingBalanceAttribute(): float
    {
        return max(0, (float) $this->selling_price - $this->total_paid);
    }

    public function getProfitMarginAttribute(): float
    {
        return $this->selling_price > 0
            ? round(($this->net_profit / $this->selling_price) * 100, 2)
            : 0;
    }

    public function recalculateTotals(): void
    {
        $cost = (float) $this->costs()->where('is_revenue', false)->sum('amount_egp');
        $this->total_cost = $cost;
        $this->net_profit = (float) $this->selling_price - $cost;
        $this->saveQuietly();
    }

    // ── Labels ────────────────────────────────────────────────────────
    public function getTypeLabelAttribute(): string
    {
        return self::TYPE_LABELS[$this->type] ?? $this->type;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'pending'     => 'warning',
            'confirmed'   => 'info',
            'in_progress' => 'primary',
            'completed'   => 'success',
            'cancelled'   => 'danger',
            default       => 'secondary',
        };
    }

    public function getWorkflowLabelAttribute(): string
    {
        return self::WORKFLOW_LABELS[$this->workflow_stage] ?? $this->workflow_stage;
    }

    public function getAccommodationLabelAttribute(): string
    {
        return self::ACCOMMODATION_LABELS[$this->accommodation_type] ?? $this->accommodation_type;
    }

    public function getMealPlanLabelAttribute(): string
    {
        return DomesticProgram::MEAL_PLAN_LABELS[$this->meal_plan] ?? $this->meal_plan;
    }

    public function getTransportLabelAttribute(): string
    {
        return DomesticProgram::TRANSPORT_LABELS[$this->transport_type] ?? $this->transport_type;
    }

    public function getGradeLabelAttribute(): string
    {
        return DomesticProgram::GRADE_LABELS[$this->accommodation_grade] ?? $this->accommodation_grade;
    }

    // ── Scopes ────────────────────────────────────────────────────────
    public function scopeUpcoming($query)
    {
        return $query->whereDate('trip_date', '>=', now())
                     ->whereNotIn('status', ['cancelled', 'completed']);
    }

    public function scopeOfType($query, string $type) { return $query->where('type', $type); }
    public function scopeActive($query) { return $query->whereNotIn('status', ['cancelled']); }
    public function scopeInCity($query, string $city) { return $query->where('destination_city', $city); }
}
