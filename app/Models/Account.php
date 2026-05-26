<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Account extends Model
{
    use HasUlids, HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['code', 'name', 'type', 'sub_type', 'parent_id', 'is_group', 'is_active', 'currency'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('account');
    }

    protected $fillable = [
        'code', 'name', 'name_en',
        'type', 'sub_type',
        'parent_id', 'is_group',
        'is_active', 'is_system', 'currency',
        'opening_balance', 'opening_balance_date',
        'notes', 'created_by',
    ];

    protected $casts = [
        'is_group'             => 'boolean',
        'is_active'            => 'boolean',
        'is_system'            => 'boolean',
        'opening_balance'      => 'decimal:2',
        'opening_balance_date' => 'date',
    ];

    // ── Relations ─────────────────────────────────────────────────────
    public function parent()   { return $this->belongsTo(self::class, 'parent_id'); }
    public function children() { return $this->hasMany(self::class, 'parent_id')->orderBy('code'); }
    public function creator()  { return $this->belongsTo(User::class, 'created_by'); }

    /** Recursive descendants — useful for "sum balance of this branch" queries. */
    public function descendants()
    {
        return $this->children()->with('descendants');
    }

    // ── Scopes ────────────────────────────────────────────────────────
    public function scopeActive($q)        { return $q->where('is_active', true); }
    public function scopePostable($q)      { return $q->where('is_group', false); }
    public function scopeOfType($q, string $type) { return $q->where('type', $type); }
    public function scopeCashOrBank($q)    { return $q->whereIn('sub_type', ['cash', 'bank']); }

    // ── Convention helpers ────────────────────────────────────────────
    /**
     * Normal balance side: debit-natured (asset/expense) or credit-natured (liability/equity/revenue).
     * Used for sign convention in reports and trial balance.
     */
    public function getNormalSideAttribute(): string
    {
        return in_array($this->type, ['asset', 'expense'], true) ? 'debit' : 'credit';
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'asset'     => 'أصول',
            'liability' => 'خصوم',
            'equity'    => 'حقوق ملكية',
            'revenue'   => 'إيرادات',
            'expense'   => 'مصروفات',
            default     => $this->type,
        };
    }

    public function getSubTypeLabelAttribute(): ?string
    {
        return match ($this->sub_type) {
            'current_asset'        => 'أصول متداولة',
            'fixed_asset'          => 'أصول ثابتة',
            'other_asset'          => 'أصول أخرى',
            'current_liability'    => 'خصوم متداولة',
            'long_term_liability'  => 'خصوم طويلة الأجل',
            'equity'               => 'حقوق ملكية',
            'operating_revenue'    => 'إيرادات تشغيلية',
            'other_revenue'        => 'إيرادات أخرى',
            'cost_of_services'     => 'تكلفة الخدمات',
            'operating_expense'    => 'مصروفات تشغيلية',
            'other_expense'        => 'مصروفات أخرى',
            'cash'                 => 'خزينة',
            'bank'                 => 'حساب بنكي',
            default                => null,
        };
    }

    public function getFullNameAttribute(): string
    {
        return $this->code . ' — ' . $this->name;
    }

    /**
     * Suggest the next available code in the chart hierarchy.
     *
     * Convention: child code = parent_code + N, where N is the lowest unused
     * positive integer among existing siblings (1..9, then 10..99, ...).
     * For root-level accounts (no parent), uses the canonical first-digit
     * mapping by type (asset=1, liability=2, equity=3, revenue=4, expense=5)
     * and falls back to scanning 6..9 if the canonical slot is taken.
     */
    public static function suggestNextCode(?string $parentId, ?string $type = null): string
    {
        if ($parentId) {
            $parent = static::find($parentId);
            if (! $parent) return '';

            $prefix = $parent->code;
            $prefixLen = strlen($prefix);

            $suffixes = static::where('parent_id', $parentId)
                ->pluck('code')
                ->map(fn ($c) => substr((string) $c, $prefixLen))
                ->filter(fn ($s) => $s !== '' && ctype_digit($s))
                ->map(fn ($s) => (int) $s)
                ->all();

            $next = empty($suffixes) ? 1 : max($suffixes) + 1;
            return $prefix . $next;
        }

        if ($type) {
            $rootMap = ['asset' => '1', 'liability' => '2', 'equity' => '3', 'revenue' => '4', 'expense' => '5'];
            $candidate = $rootMap[$type] ?? null;
            if ($candidate && ! static::where('code', $candidate)->exists()) {
                return $candidate;
            }
            for ($i = 6; $i <= 9; $i++) {
                if (! static::where('code', (string) $i)->exists()) return (string) $i;
            }
        }

        return '';
    }
}
