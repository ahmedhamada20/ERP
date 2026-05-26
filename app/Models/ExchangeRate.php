<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class ExchangeRate extends Model
{
    use HasUlids;

    protected $fillable = [
        'from_currency', 'to_currency', 'rate',
        'effective_date', 'is_active', 'notes', 'created_by',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'is_active'      => 'boolean',
        'rate'           => 'decimal:4',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Resolve the active rate for a currency pair on (or before) a date.
     * Used when stamping a booking with its historical FX rate.
     */
    public static function rateFor(string $from, string $to = 'EGP', ?\DateTimeInterface $on = null): float
    {
        $on = $on ?? now();

        $rate = self::query()
            ->where('from_currency', $from)
            ->where('to_currency', $to)
            ->where('is_active', true)
            ->whereDate('effective_date', '<=', $on)
            ->orderByDesc('effective_date')
            ->value('rate');

        return (float) ($rate ?? 0);
    }
}
