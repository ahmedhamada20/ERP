<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Supplier extends Model
{
    use HasUlids, HasFactory, SoftDeletes, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['code', 'name', 'type', 'phone', 'tax_number', 'opening_balance', 'is_active'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('supplier');
    }

    protected $fillable = [
        'code', 'name', 'name_en', 'type',
        'contact_person', 'phone', 'mobile', 'email',
        'address', 'city', 'country',
        'tax_number', 'commercial_register',
        'currency', 'opening_balance', 'opening_balance_date',
        'payment_terms_days',
        'is_active', 'notes', 'created_by',
    ];

    protected $casts = [
        'opening_balance'      => 'decimal:2',
        'opening_balance_date' => 'date',
        'payment_terms_days'   => 'integer',
        'is_active'            => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (Supplier $s) {
            if (empty($s->code)) {
                $s->code = self::generateCode();
            }
            if (auth()->check() && empty($s->created_by)) {
                $s->created_by = auth()->id();
            }
        });
    }

    public static function generateCode(): string
    {
        $year = date('Y');
        $next = Sequence::next('supplier:' . $year);

        return 'SUP-' . $year . '-' . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    /** GL parent account code for this supplier type. */
    public function parentAccountCode(): string
    {
        return match ($this->type) {
            'hotel'     => '2111',
            'airline'   => '2112',
            'transport' => '2113',
            'visa'      => '2114',
            default     => '2115',
        };
    }

    public function parentAccount()
    {
        return $this->belongsTo(Account::class, 'type', 'type')
            ->whereRaw('1=0'); // placeholder, use parentAccountModel() for actual fetch
    }

    /** Convenience: resolve the GL parent Account by code at query time. */
    public function parentAccountModel(): ?Account
    {
        return Account::where('code', $this->parentAccountCode())->first();
    }

    // ── Relations ─────────────────────────────────────────────────────
    public function creator()             { return $this->belongsTo(User::class, 'created_by'); }
    public function invoices()            { return $this->hasMany(SupplierInvoice::class); }
    public function bookingCosts()        { return $this->hasMany(BookingCost::class); }
    public function domesticBookingCosts(){ return $this->hasMany(DomesticBookingCost::class); }

    // ── Scopes ────────────────────────────────────────────────────────
    public function scopeActive($q)        { return $q->where('is_active', true); }
    public function scopeOfType($q, string $type) { return $q->where('type', $type); }

    // ── Display ──────────────────────────────────────────────────────
    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'hotel'     => 'فنادق',
            'airline'   => 'طيران',
            'transport' => 'نقل',
            'visa'      => 'تأشيرات',
            'other'     => 'أخرى',
            default     => $this->type,
        };
    }
}
