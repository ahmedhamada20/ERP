<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class JournalLine extends Model
{
    use HasUlids;

    protected $fillable = [
        'journal_entry_id', 'account_id',
        'debit', 'credit', 'description', 'line_number',
    ];

    protected $casts = [
        'debit'       => 'decimal:2',
        'credit'      => 'decimal:2',
        'line_number' => 'integer',
    ];

    protected static function booted(): void
    {
        // Enforce the debit XOR credit invariant defensively at the model layer
        // (in addition to the DB-level CHECK constraint on MySQL).
        static::saving(function (JournalLine $line) {
            $debit  = (float) $line->debit;
            $credit = (float) $line->credit;

            if ($debit < 0 || $credit < 0) {
                throw new InvalidArgumentException('Debit and credit must be non-negative');
            }
            if ($debit > 0 && $credit > 0) {
                throw new InvalidArgumentException('A journal line cannot have both debit and credit > 0');
            }
            if ($debit == 0 && $credit == 0) {
                throw new InvalidArgumentException('A journal line must have either debit or credit > 0');
            }

            // Block postings to group accounts (containers, not real accounts)
            $account = $line->account ?? Account::find($line->account_id);
            if ($account && $account->is_group) {
                throw new InvalidArgumentException("Cannot post to group account [{$account->code} {$account->name}]");
            }
        });

        // Keep the parent entry's totals in sync with its lines.
        static::saved(fn (JournalLine $line)   => $line->entry?->recalculateTotals());
        static::deleted(fn (JournalLine $line) => $line->entry?->recalculateTotals());
    }

    public function entry()   { return $this->belongsTo(JournalEntry::class, 'journal_entry_id'); }
    public function account() { return $this->belongsTo(Account::class); }

    public function getSideAttribute(): string
    {
        return (float) $this->debit > 0 ? 'debit' : 'credit';
    }

    public function getAmountAttribute(): float
    {
        return (float) ($this->debit > 0 ? $this->debit : $this->credit);
    }
}
