<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ReligiousBooking extends Model
{
    use HasUlids, HasFactory, SoftDeletes, LogsActivity, BelongsToBranch;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'booking_number', 'contract_number', 'receipt_number',
                'customer_id', 'program_id', 'type',
                'booking_date', 'trip_date', 'duration_days',
                'adults_count', 'children_count',
                'visa_type', 'accommodation_type', 'meal_plan', 'transport_type',
                'selling_price', 'total_cost', 'net_profit',
                'status', 'workflow_stage',
                'safa_barcode', 'safa_visa_group_number',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $event) => match ($event) {
                'created' => 'تم إنشاء حجز ديني',
                'updated' => 'تم تعديل حجز ديني',
                'deleted' => 'تم حذف حجز ديني',
                default   => $event,
            })
            ->useLogName('religious_booking');
    }

    protected $fillable = [
        'branch_id',
        'booking_number', 'contract_number', 'receipt_number',
        'customer_id', 'sales_employee_id', 'program_id',
        'responsible_manager_id', 'responsible_employee_id',
        'type', 'booking_date', 'trip_date', 'return_date', 'duration_days',
        'adults_count', 'children_count', 'infants_count', 'children_data',
        'visa_type', 'accommodation_type', 'meal_plan', 'transport_type', 'mutawif_grade',
        'selling_price', 'total_cost', 'net_profit', 'exchange_rate_sar',
        'status', 'workflow_stage',
        'safa_barcode', 'safa_visa_group_number', 'safa_synced_at',
        'umrah_portal_ref', 'umrah_portal_synced_at',
        'cancellation_reason', 'cancelled_at', 'cancelled_by',
        'cost_journal_entry_id',
        'notes', 'created_by',
    ];

    protected $casts = [
        'booking_date'           => 'date',
        'trip_date'              => 'date',
        'return_date'            => 'date',
        'children_data'          => 'array',
        'duration_days'          => 'integer',
        'adults_count'           => 'integer',
        'children_count'         => 'integer',
        'infants_count'          => 'integer',
        'selling_price'          => 'decimal:2',
        'total_cost'             => 'decimal:2',
        'net_profit'             => 'decimal:2',
        'exchange_rate_sar'      => 'decimal:4',
        'safa_synced_at'         => 'datetime',
        'umrah_portal_synced_at' => 'datetime',
        'cancelled_at'           => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (ReligiousBooking $booking) {
            if (empty($booking->booking_number)) {
                $booking->booking_number = self::generateBookingNumber($booking->type);
            }
            if (empty($booking->exchange_rate_sar)) {
                $booking->exchange_rate_sar = ExchangeRate::rateFor('SAR', 'EGP');
            }
            if (auth()->check() && empty($booking->created_by)) {
                $booking->created_by = auth()->id();
            }
        });
    }

    public static function generateBookingNumber(string $type): string
    {
        $year = date('Y');
        $code = $type === 'hajj' ? 'HJ' : 'UM';
        $next = Sequence::next('religious_booking:' . $code . ':' . $year);

        return $code . '-' . $year . '-' . str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }

    // ── Relations ─────────────────────────────────────────────────────
    public function customer()      { return $this->belongsTo(Customer::class); }
    public function program()       { return $this->belongsTo(ReligiousProgram::class, 'program_id'); }
    public function salesEmployee() { return $this->belongsTo(Employee::class, 'sales_employee_id'); }
    public function manager()       { return $this->belongsTo(User::class, 'responsible_manager_id'); }
    public function employee()      { return $this->belongsTo(User::class, 'responsible_employee_id'); }
    public function creator()   { return $this->belongsTo(User::class, 'created_by'); }
    public function canceller() { return $this->belongsTo(User::class, 'cancelled_by'); }

    public function costJournalEntry() { return $this->belongsTo(JournalEntry::class, 'cost_journal_entry_id'); }

    public function pilgrims()         { return $this->hasMany(BookingPilgrim::class, 'booking_id'); }
    public function accommodations()   { return $this->hasMany(BookingAccommodation::class, 'booking_id'); }
    public function transportation()   { return $this->hasMany(BookingTransportation::class, 'booking_id'); }
    public function costs()            { return $this->hasMany(BookingCost::class, 'booking_id'); }
    public function payments()         { return $this->hasMany(BookingPayment::class, 'booking_id'); }
    public function alerts()           { return $this->hasMany(ReligiousAlert::class, 'booking_id'); }
    public function documents()        { return $this->hasMany(BookingDocument::class, 'booking_id'); }
    public function commissionLines()  { return $this->morphMany(PayslipLine::class, 'reference')->where('line_type', PayslipLine::TYPE_COMMISSION); }

    // ── Money helpers ─────────────────────────────────────────────────
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

    /**
     * Recompute total_cost and net_profit from current cost rows.
     * Called after a cost line is added/updated/removed.
     */
    public function recalculateTotals(): void
    {
        $cost   = (float) $this->costs()->where('is_revenue', false)->sum('amount_egp');
        $this->total_cost = $cost;
        $this->net_profit = (float) $this->selling_price - $cost;
        $this->saveQuietly();
    }

    // ── Labels ────────────────────────────────────────────────────────
    public function getTypeLabelAttribute(): string
    {
        return $this->type === 'hajj' ? 'حج' : 'عمرة';
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending'     => 'قيد الانتظار',
            'confirmed'   => 'مؤكد',
            'in_progress' => 'جارية',
            'completed'   => 'مكتمل',
            'cancelled'   => 'ملغي',
            default       => $this->status,
        };
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
        return match ($this->workflow_stage) {
            'sales'           => 'المبيعات',
            'manager_review'  => 'مراجعة المدير',
            'operations'      => 'العمليات',
            'finance'         => 'المالية',
            'closed'          => 'مُقفل',
            default           => $this->workflow_stage,
        };
    }

    public function getVisaTypeLabelAttribute(): string
    {
        return match ($this->visa_type) {
            'standard' => 'عادية',
            'haram'    => 'حرم',
            'kaaba'    => 'كعبة',
            default    => $this->visa_type,
        };
    }

    public function getAccommodationLabelAttribute(): string
    {
        return match ($this->accommodation_type) {
            'single'     => 'فردي',
            'double'     => 'ثنائي',
            'triple'     => 'ثلاثي',
            'quad'       => 'رباعي',
            'quintuple'  => 'خماسي',
            'sextuple'   => 'سداسي',
            default      => $this->accommodation_type,
        };
    }

    // ── Scopes ────────────────────────────────────────────────────────
    public function scopeUpcoming($query)
    {
        return $query->whereDate('trip_date', '>=', now())
                     ->whereNotIn('status', ['cancelled', 'completed']);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeActive($query)
    {
        return $query->whereNotIn('status', ['cancelled']);
    }
}
