<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class SupplierInvoice extends Model
{
    use HasUlids, HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['number', 'supplier_id', 'invoice_date', 'currency', 'amount',
                       'tax_amount', 'amount_egp', 'status'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('supplier_invoice');
    }

    protected $fillable = [
        'number', 'supplier_id', 'expense_account_id',
        'invoice_date', 'due_date', 'supplier_reference', 'description',
        'currency', 'amount', 'tax_amount', 'exchange_rate', 'amount_egp',
        'attachment',
        'journal_entry_id',
        'status', 'posted_at', 'posted_by',
        'cancelled_at', 'cancelled_by', 'cancellation_reason',
        'created_by',
    ];

    protected $casts = [
        'invoice_date'  => 'date',
        'due_date'      => 'date',
        'posted_at'     => 'datetime',
        'cancelled_at'  => 'datetime',
        'amount'        => 'decimal:2',
        'tax_amount'    => 'decimal:2',
        'exchange_rate' => 'decimal:4',
        'amount_egp'    => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (SupplierInvoice $i) {
            if (empty($i->number)) {
                $i->number = self::generateNumber();
            }
            if (auth()->check() && empty($i->created_by)) {
                $i->created_by = auth()->id();
            }
        });

        static::saving(function (SupplierInvoice $i) {
            // amount_egp = (amount + tax) × exchange_rate
            $rate    = $i->currency === 'EGP' ? 1 : (float) $i->exchange_rate;
            $total   = (float) $i->amount + (float) $i->tax_amount;
            $i->amount_egp = round($total * $rate, 2);
        });
    }

    public static function generateNumber(): string
    {
        $year = date('Y');
        $next = Sequence::next('supplier_invoice:' . $year);

        return 'SI-' . $year . '-' . str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }

    // ── Relations ─────────────────────────────────────────────────────
    public function supplier()       { return $this->belongsTo(Supplier::class); }
    public function expenseAccount() { return $this->belongsTo(Account::class, 'expense_account_id'); }
    public function journalEntry()   { return $this->belongsTo(JournalEntry::class); }
    public function creator()        { return $this->belongsTo(User::class, 'created_by'); }
    public function poster()         { return $this->belongsTo(User::class, 'posted_by'); }
    public function canceller()      { return $this->belongsTo(User::class, 'cancelled_by'); }

    // ── Scopes ────────────────────────────────────────────────────────
    public function scopePosted($q)    { return $q->where('status', 'posted'); }
    public function scopeDraft($q)     { return $q->where('status', 'draft'); }
    public function scopeCancelled($q) { return $q->where('status', 'cancelled'); }
    public function scopeOverdue($q)   {
        return $q->where('status', 'posted')->whereDate('due_date', '<', now());
    }

    // ── Helpers ───────────────────────────────────────────────────────
    public function isDraft(): bool     { return $this->status === 'draft'; }
    public function isPosted(): bool    { return $this->status === 'posted'; }
    public function isCancelled(): bool { return $this->status === 'cancelled'; }

    public function getTotalAttribute(): float
    {
        return round((float) $this->amount + (float) $this->tax_amount, 2);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'draft'     => 'مسودة',
            'posted'    => 'مرحّلة',
            'cancelled' => 'ملغاة',
            default     => (string) $this->status,
        };
    }
}
