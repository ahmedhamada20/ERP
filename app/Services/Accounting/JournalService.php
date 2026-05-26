<?php

namespace App\Services\Accounting;

use App\Models\JournalEntry;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Central authority for journal-entry state transitions.
 *
 *   draft → posted    (post)
 *   posted → cancelled (cancel)
 *
 * Posting validates the golden rules:
 *   - Entry must have >= 2 lines
 *   - Sum(debits) == Sum(credits) (within rounding tolerance)
 *   - Each line's account must be active and non-group
 */
class JournalService
{
    public function post(JournalEntry $entry, ?string $userId = null): JournalEntry
    {
        if (! $entry->isDraft()) {
            throw new RuntimeException("لا يمكن ترحيل قيد حالته [{$entry->status_label}]");
        }

        $this->assertValid($entry);

        DB::transaction(function () use ($entry, $userId) {
            $entry->forceFill([
                'status'    => 'posted',
                'posted_at' => now(),
                'posted_by' => $userId ?? auth()->id(),
            ])->saveQuietly();
        });

        return $entry->refresh();
    }

    public function cancel(JournalEntry $entry, string $reason, ?string $userId = null): JournalEntry
    {
        if (! $entry->isPosted()) {
            throw new RuntimeException("لا يمكن إلغاء قيد غير مرحّل");
        }

        DB::transaction(function () use ($entry, $reason, $userId) {
            $entry->forceFill([
                'status'              => 'cancelled',
                'cancelled_at'        => now(),
                'cancelled_by'        => $userId ?? auth()->id(),
                'cancellation_reason' => $reason,
            ])->saveQuietly();
        });

        return $entry->refresh();
    }

    /**
     * Validate a draft entry is postable. Throws on first failure.
     */
    public function assertValid(JournalEntry $entry): void
    {
        $entry->loadMissing('lines.account');

        if ($entry->lines->count() < 2) {
            throw new RuntimeException('القيد يجب أن يحتوي على سطرين على الأقل');
        }

        $debit  = (float) $entry->lines->sum('debit');
        $credit = (float) $entry->lines->sum('credit');

        if (abs($debit - $credit) >= 0.01) {
            throw new RuntimeException(sprintf(
                'القيد غير متوازن: إجمالي المدين = %s، إجمالي الدائن = %s',
                number_format($debit, 2),
                number_format($credit, 2),
            ));
        }

        if ($debit < 0.01) {
            throw new RuntimeException('إجمالي القيد لا يمكن أن يكون صفر');
        }

        foreach ($entry->lines as $line) {
            $a = $line->account;
            if (! $a) {
                throw new RuntimeException('سطر بدون حساب صحيح');
            }
            if ($a->is_group) {
                throw new RuntimeException("لا يمكن الترحيل على حساب مجمّع: {$a->code} — {$a->name}");
            }
            if (! $a->is_active) {
                throw new RuntimeException("حساب متوقف: {$a->code} — {$a->name}");
            }
        }
    }
}
