<?php

namespace Tests\Concerns;

use App\Models\Account;
use Database\Seeders\ChartOfAccountsSeeder;

/**
 * Trait للاختبارات التي تتعامل مع المدفوعات (booking_payments أو
 * domestic_booking_payments). يجلب دليل الحسابات الفعلي ويوفر helpers
 * لاسترجاع IDs الخزن والبنوك المتاحة للاستخدام في الـ test bodies.
 */
trait SeedsCashAccountsForTesting
{
    protected function seedCashAccounts(): void
    {
        $this->seed(ChartOfAccountsSeeder::class);
    }

    protected function defaultCashAccountId(): string
    {
        return Account::query()
            ->where('sub_type', 'cash')
            ->where('is_active', true)
            ->where('is_group', false)
            ->orderBy('code')
            ->value('id');
    }

    protected function defaultBankAccountId(): string
    {
        return Account::query()
            ->where('sub_type', 'bank')
            ->where('is_active', true)
            ->where('is_group', false)
            ->orderBy('code')
            ->value('id');
    }
}
