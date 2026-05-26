<?php

namespace App\Services\Payroll;

/**
 * Egyptian salary tax — progressive bracket calculator.
 *
 * Convention used here:
 *   - Brackets in config['payroll.income_tax.brackets'] apply to ANNUAL
 *     TAXABLE income (= annual gross - exemption).
 *   - We annualize the monthly earnings, subtract the exemption ONCE,
 *     run the result through the brackets, then divide by 12.
 *
 * Example with default brackets (0/10/15/20/22.5/25/27.5):
 *   Monthly taxable = 7,000  →  annual taxable = 84,000
 *   Subtract 29,000 exemption  →  51,000 to tax
 *      First 40,000 @ 0%   = 0
 *      Next  11,000 @ 10%  = 1,100
 *   Annual tax = 1,100  →  monthly = 91.67
 */
class IncomeTaxCalculator
{
    private array $brackets;
    private float $exemption;

    public function __construct(?array $brackets = null, ?float $exemption = null)
    {
        $this->brackets  = $brackets  ?? config('payroll.income_tax.brackets');
        $this->exemption = $exemption ?? (float) config('payroll.income_tax.annual_exemption');
    }

    public function calculateMonthly(float $monthlyTaxableEarnings): float
    {
        if ($monthlyTaxableEarnings <= 0) return 0;

        $annualTax = $this->calculateAnnual($monthlyTaxableEarnings * 12);

        return round($annualTax / 12, 2);
    }

    /**
     * Apply brackets to ANNUAL taxable income (gross - exemption).
     * Each bracket: ['upto' => annual_ceiling_after_exemption, 'rate' => %].
     * Top bracket has upto=null (open-ended).
     */
    public function calculateAnnual(float $annualGross): float
    {
        $taxable = $annualGross - $this->exemption;
        if ($taxable <= 0) return 0;

        $tax  = 0.0;
        $prev = 0.0;

        foreach ($this->brackets as $bracket) {
            $ceiling = $bracket['upto'];
            $rate    = (float) $bracket['rate'];

            // slice covers (prev, min(ceiling, taxable)]
            $sliceTop  = is_null($ceiling) ? $taxable : min($ceiling, $taxable);
            $sliceSize = max(0, $sliceTop - $prev);

            if ($sliceSize > 0) {
                $tax += $sliceSize * ($rate / 100);
            }

            $prev = is_null($ceiling) ? $taxable : $ceiling;

            if ($prev >= $taxable - 0.005) break;
        }

        return round($tax, 2);
    }
}
