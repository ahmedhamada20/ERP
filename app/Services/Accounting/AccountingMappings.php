<?php

namespace App\Services\Accounting;

use App\Models\Account;
use RuntimeException;

/**
 * Resolves which Account to use for various business postings.
 *
 * Current mappings (smart defaults, no user config yet):
 *   payment method → cash or bank account (first active match)
 *   booking type   → revenue account (411 hajj, 412 umrah)
 *
 * Future: persist overrides in `settings` table so each company
 * can choose specific accounts.
 */
class AccountingMappings
{
    /**
     * يُرجع الحساب المرتبط بدفعة معينة:
     *   - إذا كانت الدفعة تحمل cash_account_id محدد، نُرجعه (السلوك الجديد)
     *   - وإلا نقع على fallback القديم: أول حساب من النوع المناسب
     *     (للتوافق مع البيانات القديمة قبل ربط cash_account_id)
     */
    public function cashAccountForPayment(object $payment): Account
    {
        if (! empty($payment->cash_account_id)) {
            $account = Account::query()
                ->where('id', $payment->cash_account_id)
                ->where('is_active', true)
                ->where('is_group', false)
                ->first();

            if (! $account) {
                throw new RuntimeException(
                    "حساب الخزينة/البنك المحدد للدفعة غير موجود أو غير نشط (ID: {$payment->cash_account_id})."
                );
            }

            return $account;
        }

        return $this->cashAccountFor($payment->method);
    }

    /**
     * Map a payment method → cash/bank Account (legacy fallback).
     *
     * - 'cash' → first active account with sub_type='cash'
     * - everything else → first active account with sub_type='bank'
     *
     * يُستخدم فقط لو الدفعة لا تحمل cash_account_id (بيانات قديمة).
     */
    public function cashAccountFor(string $method): Account
    {
        $subType = $method === 'cash' ? 'cash' : 'bank';

        $account = Account::query()
            ->where('sub_type', $subType)
            ->where('is_active', true)
            ->where('is_group', false)
            ->orderBy('code')
            ->first();

        if (! $account) {
            throw new RuntimeException(
                "لا يوجد حساب نشط من نوع [{$subType}] في دليل الحسابات. "
                . 'يجب إنشاء حساب خزينة وبنك على الأقل قبل تسجيل المدفوعات.'
            );
        }

        return $account;
    }

    /**
     * Map a religious booking type → revenue Account.
     *
     * - 'umrah' → code 412 (إيرادات العمرة)
     * - 'hajj'  → code 411 (إيرادات الحج)
     */
    public function revenueAccountForBookingType(string $type): Account
    {
        $code = match ($type) {
            'hajj'  => '411',
            'umrah' => '412',
            default => null,
        };

        if (! $code) {
            throw new RuntimeException("نوع الحجز غير معروف للترحيل المحاسبي: {$type}");
        }

        return $this->fetchByCode($code, 'حساب الإيراد');
    }

    /**
     * Map a domestic booking → revenue Account (413 إيرادات السياحة الداخلية).
     * All domestic types route to the same revenue account; sub-analysis
     * comes from the booking type field in reports, not separate accounts.
     */
    public function revenueAccountForDomestic(): Account
    {
        return $this->fetchByCode('413', 'حساب إيرادات السياحة الداخلية');
    }

    /**
     * Map a BookingCost.category → expense Account (the DEBIT side at close).
     */
    public function expenseAccountForCostCategory(string $category): Account
    {
        $code = match ($category) {
            'room'              => '511', // تكلفة الفنادق
            'flight'            => '512', // تكلفة الطيران
            'visa'              => '513', // تكلفة التأشيرات
            'transport'         => '514', // تكلفة النقل والمواصلات
            'mutawif'           => '515', // تكلفة المطوفين
            'insurance'         => '516', // تكلفة التأمين
            'gifts'             => '517', // هدايا للعملاء
            'commission'        => '518', // عمولات مبيعات
            'bank_fee'          => '525', // مصاريف بنكية
            default             => '519', // مصاريف تشغيلية متنوعة للرحلات
        };

        return $this->fetchByCode($code, 'حساب التكلفة');
    }

    /**
     * Map a BookingCost.category → supplier payable / liability Account
     * (the CREDIT side at close — what we "owe" against this cost).
     *
     * Tax is special: it credits the VAT payable (not a supplier).
     */
    public function supplierAccountForCostCategory(string $category): Account
    {
        $code = match ($category) {
            'room'      => '2111', // موردين فنادق
            'flight'    => '2112', // موردين طيران
            'transport' => '2113', // موردين نقل
            'visa'      => '2114', // موردين تأشيرات
            'tax'       => '2131', // ضريبة القيمة المضافة (liability)
            default     => '2115', // موردين متنوعون (catch-all)
        };

        return $this->fetchByCode($code, 'حساب المورد');
    }

    /**
     * Map a DomesticBookingCost.category → expense Account.
     *
     * Categories specific to domestic tourism: hotel/meals/activities/private_car
     * map onto the existing chart of accounts. No mutawif/visa here.
     */
    public function expenseAccountForDomesticCostCategory(string $category): Account
    {
        $code = match ($category) {
            'hotel', 'room'        => '511', // تكلفة الفنادق
            'flight'               => '512', // تكلفة الطيران
            'transport',
            'private_car'          => '514', // تكلفة النقل والمواصلات
            'insurance'            => '516', // تكلفة التأمين
            'gifts'                => '517', // هدايا للعملاء
            'commission'           => '518', // عمولات مبيعات
            'bank_fee'             => '525', // مصاريف بنكية
            'meals',
            'activities',
            'supervision',
            'activation',
            'miscellaneous',
            'other'                => '519', // مصاريف تشغيلية متنوعة للرحلات
            default                => '519',
        };

        return $this->fetchByCode($code, 'حساب التكلفة');
    }

    /**
     * Map a DomesticBookingCost.category → supplier/liability Account.
     */
    public function supplierAccountForDomesticCostCategory(string $category): Account
    {
        $code = match ($category) {
            'hotel', 'room'        => '2111', // موردين فنادق
            'flight'               => '2112', // موردين طيران
            'transport',
            'private_car'          => '2113', // موردين نقل
            'tax'                  => '2131', // VAT
            default                => '2115', // موردين متنوعون
        };

        return $this->fetchByCode($code, 'حساب المورد');
    }

    // ── Payroll mappings (Sprint 6 Step 5.3) ────────────────────────────

    /** مرتبات الموظفين (DR side of payroll posting). */
    public function salaryExpenseAccount(): Account
    {
        return $this->fetchByCode('521', 'حساب مرتبات الموظفين');
    }

    /** عمولات مبيعات (DR side — separates commission from base salary). */
    public function commissionExpenseAccount(): Account
    {
        return $this->fetchByCode('518', 'حساب عمولات المبيعات');
    }

    /** مرتبات مستحقة (CR — net pay liability until cash is actually disbursed). */
    public function salariesPayableAccount(): Account
    {
        return $this->fetchByCode('2122', 'حساب مرتبات مستحقة');
    }

    /** التأمينات الاجتماعية المستحقة (CR — employee SI contributions). */
    public function socialInsurancePayableAccount(): Account
    {
        return $this->fetchByCode('2133', 'حساب التأمينات الاجتماعية المستحقة');
    }

    /** ضريبة كسب العمل المستحقة (CR — income tax withheld). */
    public function incomeTaxPayableAccount(): Account
    {
        return $this->fetchByCode('2132', 'حساب ضريبة كسب العمل المستحقة');
    }

    /**
     * تسليفات موظفين (asset). CR'd on payroll to reduce the receivable
     * as the employee pays back the loan via salary deduction.
     */
    public function employeeLoansReceivableAccount(): Account
    {
        return $this->fetchByCode('1141', 'حساب تسليفات الموظفين');
    }

    private function fetchByCode(string $code, string $label): Account
    {
        $account = Account::query()
            ->where('code', $code)
            ->where('is_active', true)
            ->where('is_group', false)
            ->first();

        if (! $account) {
            throw new RuntimeException(
                "{$label} بكود [{$code}] غير موجود أو غير نشط. "
                . 'تأكد من تشغيل ChartOfAccountsSeeder.'
            );
        }

        return $account;
    }
}
