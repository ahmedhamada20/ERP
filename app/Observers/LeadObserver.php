<?php

namespace App\Observers;

use App\Models\Lead;
use Illuminate\Support\Facades\Cache;

/**
 * يبطل الـ cache لإحصائيات الـ leads عند أي تغيير، مهما كان مصدره
 * (controller, API, console command, factory في اختبارات...).
 *
 * بدون هذا، LeadController يحذف الكاش يدوياً في كل action — وأي
 * مدخل آخر للبيانات (مثل سكربت import أو command artisan) كان
 * يترك الكاش قديم.
 */
class LeadObserver
{
    private const STATS_CACHE_KEY = 'leads.kpi_stats';

    public function saved(Lead $lead): void
    {
        Cache::forget(self::STATS_CACHE_KEY);
    }

    public function deleted(Lead $lead): void
    {
        Cache::forget(self::STATS_CACHE_KEY);
    }

    public function restored(Lead $lead): void
    {
        Cache::forget(self::STATS_CACHE_KEY);
    }
}
