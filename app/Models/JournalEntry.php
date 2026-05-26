<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class JournalEntry extends Model
{
    use HasUlids, HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['number', 'date', 'description', 'status', 'total_debit', 'total_credit', 'source_type', 'source_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('journal_entry');
    }

    protected $fillable = [
        'number', 'date', 'description', 'reference',
        'source_type', 'source_id',
        'total_debit', 'total_credit',
        'status', 'posted_at', 'posted_by',
        'cancelled_at', 'cancelled_by', 'cancellation_reason',
        'created_by',
    ];

    protected $casts = [
        'date'         => 'date',
        'posted_at'    => 'datetime',
        'cancelled_at' => 'datetime',
        'total_debit'  => 'decimal:2',
        'total_credit' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (JournalEntry $entry) {
            if (empty($entry->number)) {
                $entry->number = self::generateNumber();
            }
            if (auth()->check() && empty($entry->created_by)) {
                $entry->created_by = auth()->id();
            }
            if (empty($entry->source_type)) {
                $entry->source_type = 'manual';
            }
        });
    }

    public static function generateNumber(): string
    {
        $year = date('Y');
        $next = Sequence::next('journal_entry:' . $year);

        return 'JE-' . $year . '-' . str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }

    // ── Relations ─────────────────────────────────────────────────────
    public function lines()    { return $this->hasMany(JournalLine::class)->orderBy('line_number'); }
    public function creator()  { return $this->belongsTo(User::class, 'created_by'); }
    public function poster()   { return $this->belongsTo(User::class, 'posted_by'); }
    public function canceller(){ return $this->belongsTo(User::class, 'cancelled_by'); }

    // ── Scopes ────────────────────────────────────────────────────────
    public function scopePosted($q)    { return $q->where('status', 'posted'); }
    public function scopeDraft($q)     { return $q->where('status', 'draft'); }
    public function scopeCancelled($q) { return $q->where('status', 'cancelled'); }

    /** Only entries that affect ledger balances (i.e. excludes drafts and cancelled). */
    public function scopeAffectsLedger($q) { return $q->where('status', 'posted'); }

    // ── State helpers ─────────────────────────────────────────────────
    public function isDraft(): bool     { return $this->status === 'draft'; }
    public function isPosted(): bool    { return $this->status === 'posted'; }
    public function isCancelled(): bool { return $this->status === 'cancelled'; }
    public function isEditable(): bool  { return $this->isDraft(); }

    /**
     * Recompute totals from current lines. Called by JournalLine observers.
     * Uses saveQuietly to avoid recursive observer fires.
     */
    public function recalculateTotals(): void
    {
        $this->total_debit  = (float) $this->lines()->sum('debit');
        $this->total_credit = (float) $this->lines()->sum('credit');
        $this->saveQuietly();
    }

    /** True if total_debit == total_credit (within rounding tolerance). */
    public function isBalanced(): bool
    {
        return abs(((float) $this->total_debit) - ((float) $this->total_credit)) < 0.01;
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
