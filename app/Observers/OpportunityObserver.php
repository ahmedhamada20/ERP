<?php

namespace App\Observers;

use App\Models\Opportunity;
use Illuminate\Support\Facades\Cache;

/**
 * يبطل cache إحصائيات الفرص عند أي تغيير. مثل LeadObserver.
 */
class OpportunityObserver
{
    private const STATS_CACHE_KEY = 'opportunities.kpi_stats';

    public function saved(Opportunity $opportunity): void
    {
        Cache::forget(self::STATS_CACHE_KEY);
    }

    public function deleted(Opportunity $opportunity): void
    {
        Cache::forget(self::STATS_CACHE_KEY);
    }

    public function restored(Opportunity $opportunity): void
    {
        Cache::forget(self::STATS_CACHE_KEY);
    }
}
