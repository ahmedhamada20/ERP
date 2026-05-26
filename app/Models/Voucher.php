<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Voucher extends Model
{
    use HasUlids, HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['number', 'type', 'date', 'cash_account_id', 'counter_account_id',
                       'party_name', 'amount', 'currency', 'amount_egp', 'status'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('voucher');
    }

    protected $fillable = [
        'number', 'type', 'date',
        'cash_account_id', 'counter_account_id',
        'party_type', 'party_id', 'party_name',
        'supplier_id', 'supplier_invoice_id',
        'currency', 'amount', 'exchange_rate', 'amount_egp',
        'description', 'reference', 'attachment',
        'journal_entry_id',
        'status', 'posted_at', 'posted_by',
        'cancelled_at', 'cancelled_by', 'cancellation_reason',
        'created_by',
    ];

    protected $casts = [
        'date'          => 'date',
        'posted_at'     => 'datetime',
        'cancelled_at'  => 'datetime',
        'amount'        => 'decimal:2',
        'exchange_rate' => 'decimal:4',
        'amount_egp'    => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (Voucher $v) {
            if (empty($v->number)) {
                $v->number = self::generateNumber($v->type);
            }
            if (auth()->check() && empty($v->created_by)) {
                $v->created_by = auth()->id();
            }
        });

        static::saving(function (Voucher $v) {
            $rate = $v->currency === 'EGP' ? 1 : (float) $v->exchange_rate;
            $v->amount_egp = round((float) $v->amount * $rate, 2);
        });
    }

    public static function generateNumber(string $type): string
    {
        $year   = date('Y');
        $code   = $type === 'receipt' ? 'VR' : 'VP';
        $next   = Sequence::next('voucher:' . $code . ':' . $year);

        return $code . '-' . $year . '-' . str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }

    // ── Relations ─────────────────────────────────────────────────────
    public function cashAccount()    { return $this->belongsTo(Account::class, 'cash_account_id'); }
    public function counterAccount() { return $this->belongsTo(Account::class, 'counter_account_id'); }
    public function journalEntry()   { return $this->belongsTo(JournalEntry::class); }
    public function supplier()       { return $this->belongsTo(Supplier::class); }
    public function supplierInvoice(){ return $this->belongsTo(SupplierInvoice::class); }
    public function creator()        { return $this->belongsTo(User::class, 'created_by'); }
    public function poster()         { return $this->belongsTo(User::class, 'posted_by'); }
    public function canceller()      { return $this->belongsTo(User::class, 'cancelled_by'); }

    // ── Scopes ────────────────────────────────────────────────────────
    public function scopeReceipts($q) { return $q->where('type', 'receipt'); }
    public function scopePayments($q) { return $q->where('type', 'payment'); }
    public function scopePosted($q)   { return $q->where('status', 'posted'); }

    // ── Helpers ───────────────────────────────────────────────────────
    public function isPosted(): bool    { return $this->status === 'posted'; }
    public function isCancelled(): bool { return $this->status === 'cancelled'; }
    public function isReceipt(): bool   { return $this->type === 'receipt'; }
    public function isPayment(): bool   { return $this->type === 'payment'; }

    public function getTypeLabelAttribute(): string
    {
        return $this->isReceipt() ? 'سند قبض' : 'سند صرف';
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'draft'     => 'مسودة',
            'posted'    => 'مرحّل',
            'cancelled' => 'ملغي',
            default     => $this->status,
        };
    }
}
