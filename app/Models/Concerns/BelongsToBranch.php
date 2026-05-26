<?php

namespace App\Models\Concerns;

use App\Models\Branch;
use App\Models\Employee;

/**
 * Adds branch_id awareness to a model.
 *
 * Auto-fills branch_id on create:
 *   1. If the caller already set it, leave it alone
 *   2. Else try the current authenticated user's employee record's branch
 *   3. Else fall back to Branch::main()
 *
 * The host model must:
 *   - Have a `branch_id` column
 *   - Include 'branch_id' in $fillable
 *   - Call `static::bootBelongsToBranch()` from its own booted() OR
 *     just `use BelongsToBranch;` and Eloquent will auto-boot the trait.
 */
trait BelongsToBranch
{
    public static function bootBelongsToBranch(): void
    {
        static::creating(function ($model) {
            if (! empty($model->branch_id)) {
                return;
            }

            // 1) From current user → employee → branch
            if (auth()->check()) {
                $branchId = Employee::query()
                    ->where('user_id', auth()->id())
                    ->value('branch_id');
                if ($branchId) {
                    $model->branch_id = $branchId;
                    return;
                }
            }

            // 2) Fallback to main branch
            $model->branch_id = Branch::main()?->id;
        });
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function scopeInBranch($query, ?string $branchId)
    {
        return $branchId ? $query->where('branch_id', $branchId) : $query;
    }
}
