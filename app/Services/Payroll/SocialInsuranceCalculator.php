<?php

namespace App\Services\Payroll;

/**
 * Computes the employee's share of social insurance for one month.
 *
 * Rule: rate% of MIN(monthly_gross, cap). The cap exists because Egyptian
 * social insurance has a maximum insurable wage — earnings above it aren't
 * subject to SI contributions for either party.
 */
class SocialInsuranceCalculator
{
    private float $rate;
    private float $cap;

    public function __construct(?float $rate = null, ?float $cap = null)
    {
        $this->rate = $rate ?? (float) config('payroll.social_insurance.employee_rate');
        $this->cap  = $cap  ?? (float) config('payroll.social_insurance.monthly_cap');
    }

    public function calculate(float $monthlyGross): float
    {
        if ($monthlyGross <= 0) return 0;

        $insurable = min($monthlyGross, $this->cap);

        return round($insurable * ($this->rate / 100), 2);
    }
}
