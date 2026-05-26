<?php

namespace Database\Seeders;

use App\Models\Account;
use Illuminate\Database\Seeder;

/**
 * Default Egyptian tourism Chart of Accounts.
 *
 * Hierarchy structure:
 *   Level 1: Major category (Assets, Liabilities, Equity, Revenue, Expenses)
 *   Level 2: Sub-category (Current Assets, Fixed Assets, ...)
 *   Level 3: Account group (Cash, Banks, Customers, ...)
 *   Level 4: Postable account (Main cashbox, CIB account, ...)
 *
 * Code convention: dot-free numeric, first digit = type.
 * is_group=true → can't post; is_system=true → can't be deleted from UI.
 */
class ChartOfAccountsSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->chart() as $row) {
            $this->seedAccount($row, null);
        }
    }

    private function seedAccount(array $row, ?string $parentId): void
    {
        $account = Account::updateOrCreate(
            ['code' => $row['code']],
            [
                'name'       => $row['name'],
                'name_en'    => $row['name_en'] ?? null,
                'type'       => $row['type'],
                'sub_type'   => $row['sub_type'] ?? null,
                'parent_id'  => $parentId,
                'is_group'   => ! empty($row['children']),
                'is_system'  => true,
                'is_active'  => true,
                'currency'   => 'EGP',
            ]
        );

        foreach ($row['children'] ?? [] as $child) {
            $this->seedAccount($child, $account->id);
        }
    }

    /** @return array<int, array{code:string, name:string, name_en?:string, type:string, sub_type?:string, children?:array}> */
    private function chart(): array
    {
        return [
            // ═══════════════════════════════════════════════════════════
            // 1 - الأصول (Assets)
            // ═══════════════════════════════════════════════════════════
            ['code' => '1', 'name' => 'الأصول', 'name_en' => 'Assets', 'type' => 'asset', 'children' => [
                ['code' => '11', 'name' => 'أصول متداولة', 'name_en' => 'Current Assets', 'type' => 'asset', 'sub_type' => 'current_asset', 'children' => [
                    ['code' => '111', 'name' => 'النقدية', 'name_en' => 'Cash', 'type' => 'asset', 'sub_type' => 'current_asset', 'children' => [
                        ['code' => '1111', 'name' => 'الخزينة الرئيسية', 'type' => 'asset', 'sub_type' => 'cash'],
                        ['code' => '1112', 'name' => 'خزينة الفرع', 'type' => 'asset', 'sub_type' => 'cash'],
                        ['code' => '1113', 'name' => 'خزينة العملات الأجنبية', 'type' => 'asset', 'sub_type' => 'cash'],
                    ]],
                    ['code' => '112', 'name' => 'البنوك', 'name_en' => 'Banks', 'type' => 'asset', 'sub_type' => 'current_asset', 'children' => [
                        ['code' => '1121', 'name' => 'بنك CIB - جنيه', 'type' => 'asset', 'sub_type' => 'bank'],
                        ['code' => '1122', 'name' => 'بنك مصر - جنيه', 'type' => 'asset', 'sub_type' => 'bank'],
                        ['code' => '1123', 'name' => 'بنك CIB - دولار', 'type' => 'asset', 'sub_type' => 'bank'],
                    ]],
                    ['code' => '113', 'name' => 'العملاء', 'name_en' => 'Accounts Receivable', 'type' => 'asset', 'sub_type' => 'current_asset', 'children' => [
                        ['code' => '1131', 'name' => 'عملاء حجوزات دينية', 'type' => 'asset', 'sub_type' => 'current_asset'],
                        ['code' => '1132', 'name' => 'عملاء سياحة داخلية', 'type' => 'asset', 'sub_type' => 'current_asset'],
                        ['code' => '1133', 'name' => 'عملاء سياحة دولية', 'type' => 'asset', 'sub_type' => 'current_asset'],
                        ['code' => '1134', 'name' => 'عملاء وكلاء', 'type' => 'asset', 'sub_type' => 'current_asset'],
                    ]],
                    ['code' => '114', 'name' => 'مدفوعات مقدماً ومخزون', 'type' => 'asset', 'sub_type' => 'current_asset', 'children' => [
                        ['code' => '1141', 'name' => 'تسليفات موظفين', 'type' => 'asset', 'sub_type' => 'current_asset'],
                        ['code' => '1142', 'name' => 'دفعات مقدمة لموردين', 'type' => 'asset', 'sub_type' => 'current_asset'],
                        ['code' => '1143', 'name' => 'مصاريف مدفوعة مقدماً', 'type' => 'asset', 'sub_type' => 'current_asset'],
                    ]],
                ]],
                ['code' => '12', 'name' => 'أصول ثابتة', 'name_en' => 'Fixed Assets', 'type' => 'asset', 'sub_type' => 'fixed_asset', 'children' => [
                    ['code' => '121', 'name' => 'سيارات ووسائل نقل', 'type' => 'asset', 'sub_type' => 'fixed_asset'],
                    ['code' => '122', 'name' => 'أجهزة حاسب آلي', 'type' => 'asset', 'sub_type' => 'fixed_asset'],
                    ['code' => '123', 'name' => 'أثاث ومفروشات', 'type' => 'asset', 'sub_type' => 'fixed_asset'],
                    ['code' => '124', 'name' => 'تجهيزات المكتب', 'type' => 'asset', 'sub_type' => 'fixed_asset'],
                    ['code' => '129', 'name' => 'مجمع إهلاك الأصول الثابتة', 'type' => 'asset', 'sub_type' => 'fixed_asset'],
                ]],
            ]],

            // ═══════════════════════════════════════════════════════════
            // 2 - الخصوم (Liabilities)
            // ═══════════════════════════════════════════════════════════
            ['code' => '2', 'name' => 'الخصوم', 'name_en' => 'Liabilities', 'type' => 'liability', 'children' => [
                ['code' => '21', 'name' => 'خصوم متداولة', 'name_en' => 'Current Liabilities', 'type' => 'liability', 'sub_type' => 'current_liability', 'children' => [
                    ['code' => '211', 'name' => 'الموردين', 'name_en' => 'Accounts Payable', 'type' => 'liability', 'sub_type' => 'current_liability', 'children' => [
                        ['code' => '2111', 'name' => 'موردين فنادق', 'type' => 'liability', 'sub_type' => 'current_liability'],
                        ['code' => '2112', 'name' => 'موردين طيران', 'type' => 'liability', 'sub_type' => 'current_liability'],
                        ['code' => '2113', 'name' => 'موردين نقل', 'type' => 'liability', 'sub_type' => 'current_liability'],
                        ['code' => '2114', 'name' => 'موردين تأشيرات', 'type' => 'liability', 'sub_type' => 'current_liability'],
                        ['code' => '2115', 'name' => 'موردين متنوعون', 'type' => 'liability', 'sub_type' => 'current_liability'],
                    ]],
                    ['code' => '212', 'name' => 'دائنون متنوعون', 'type' => 'liability', 'sub_type' => 'current_liability', 'children' => [
                        ['code' => '2121', 'name' => 'دفعات مقدمة من عملاء', 'type' => 'liability', 'sub_type' => 'current_liability'],
                        ['code' => '2122', 'name' => 'مرتبات مستحقة', 'type' => 'liability', 'sub_type' => 'current_liability'],
                        ['code' => '2123', 'name' => 'مصاريف مستحقة', 'type' => 'liability', 'sub_type' => 'current_liability'],
                    ]],
                    ['code' => '213', 'name' => 'الضرائب المستحقة', 'type' => 'liability', 'sub_type' => 'current_liability', 'children' => [
                        ['code' => '2131', 'name' => 'ضريبة القيمة المضافة', 'type' => 'liability', 'sub_type' => 'current_liability'],
                        ['code' => '2132', 'name' => 'ضريبة كسب العمل', 'type' => 'liability', 'sub_type' => 'current_liability'],
                        ['code' => '2133', 'name' => 'التأمينات الاجتماعية', 'type' => 'liability', 'sub_type' => 'current_liability'],
                    ]],
                ]],
                ['code' => '22', 'name' => 'خصوم طويلة الأجل', 'name_en' => 'Long-term Liabilities', 'type' => 'liability', 'sub_type' => 'long_term_liability', 'children' => [
                    ['code' => '221', 'name' => 'قروض بنكية طويلة الأجل', 'type' => 'liability', 'sub_type' => 'long_term_liability'],
                ]],
            ]],

            // ═══════════════════════════════════════════════════════════
            // 3 - حقوق الملكية (Equity)
            // ═══════════════════════════════════════════════════════════
            ['code' => '3', 'name' => 'حقوق الملكية', 'name_en' => 'Equity', 'type' => 'equity', 'children' => [
                ['code' => '31', 'name' => 'رأس المال', 'type' => 'equity', 'sub_type' => 'equity'],
                ['code' => '32', 'name' => 'الاحتياطيات', 'type' => 'equity', 'sub_type' => 'equity'],
                ['code' => '33', 'name' => 'الأرباح المحتجزة', 'type' => 'equity', 'sub_type' => 'equity'],
                ['code' => '34', 'name' => 'جاري الشركاء', 'type' => 'equity', 'sub_type' => 'equity'],
                ['code' => '39', 'name' => 'أرباح / خسائر الفترة', 'type' => 'equity', 'sub_type' => 'equity'],
            ]],

            // ═══════════════════════════════════════════════════════════
            // 4 - الإيرادات (Revenue)
            // ═══════════════════════════════════════════════════════════
            ['code' => '4', 'name' => 'الإيرادات', 'name_en' => 'Revenue', 'type' => 'revenue', 'children' => [
                ['code' => '41', 'name' => 'إيرادات تشغيلية', 'name_en' => 'Operating Revenue', 'type' => 'revenue', 'sub_type' => 'operating_revenue', 'children' => [
                    ['code' => '411', 'name' => 'إيرادات الحج', 'type' => 'revenue', 'sub_type' => 'operating_revenue'],
                    ['code' => '412', 'name' => 'إيرادات العمرة', 'type' => 'revenue', 'sub_type' => 'operating_revenue'],
                    ['code' => '413', 'name' => 'إيرادات السياحة الداخلية', 'type' => 'revenue', 'sub_type' => 'operating_revenue'],
                    ['code' => '414', 'name' => 'إيرادات السياحة الدولية', 'type' => 'revenue', 'sub_type' => 'operating_revenue'],
                    ['code' => '415', 'name' => 'إيرادات حجز فنادق فردي', 'type' => 'revenue', 'sub_type' => 'operating_revenue'],
                    ['code' => '416', 'name' => 'إيرادات تذاكر طيران فردية', 'type' => 'revenue', 'sub_type' => 'operating_revenue'],
                    ['code' => '417', 'name' => 'إيرادات تأشيرات فردية', 'type' => 'revenue', 'sub_type' => 'operating_revenue'],
                ]],
                ['code' => '42', 'name' => 'إيرادات أخرى', 'name_en' => 'Other Revenue', 'type' => 'revenue', 'sub_type' => 'other_revenue', 'children' => [
                    ['code' => '421', 'name' => 'أرباح فروق العملة', 'type' => 'revenue', 'sub_type' => 'other_revenue'],
                    ['code' => '422', 'name' => 'إيرادات متنوعة', 'type' => 'revenue', 'sub_type' => 'other_revenue'],
                    ['code' => '423', 'name' => 'فوائد بنكية دائنة', 'type' => 'revenue', 'sub_type' => 'other_revenue'],
                ]],
            ]],

            // ═══════════════════════════════════════════════════════════
            // 5 - المصروفات (Expenses)
            // ═══════════════════════════════════════════════════════════
            ['code' => '5', 'name' => 'المصروفات', 'name_en' => 'Expenses', 'type' => 'expense', 'children' => [
                ['code' => '51', 'name' => 'تكلفة الخدمات', 'name_en' => 'Cost of Services', 'type' => 'expense', 'sub_type' => 'cost_of_services', 'children' => [
                    ['code' => '511', 'name' => 'تكلفة الفنادق', 'type' => 'expense', 'sub_type' => 'cost_of_services'],
                    ['code' => '512', 'name' => 'تكلفة الطيران', 'type' => 'expense', 'sub_type' => 'cost_of_services'],
                    ['code' => '513', 'name' => 'تكلفة التأشيرات', 'type' => 'expense', 'sub_type' => 'cost_of_services'],
                    ['code' => '514', 'name' => 'تكلفة النقل والمواصلات', 'type' => 'expense', 'sub_type' => 'cost_of_services'],
                    ['code' => '515', 'name' => 'تكلفة المطوفين', 'type' => 'expense', 'sub_type' => 'cost_of_services'],
                    ['code' => '516', 'name' => 'تكلفة التأمين على المسافرين', 'type' => 'expense', 'sub_type' => 'cost_of_services'],
                    ['code' => '517', 'name' => 'هدايا للعملاء', 'type' => 'expense', 'sub_type' => 'cost_of_services'],
                    ['code' => '518', 'name' => 'عمولات مبيعات', 'type' => 'expense', 'sub_type' => 'cost_of_services'],
                    ['code' => '519', 'name' => 'مصاريف تشغيلية متنوعة للرحلات', 'type' => 'expense', 'sub_type' => 'cost_of_services'],
                ]],
                ['code' => '52', 'name' => 'مصروفات تشغيلية', 'name_en' => 'Operating Expenses', 'type' => 'expense', 'sub_type' => 'operating_expense', 'children' => [
                    ['code' => '521', 'name' => 'مرتبات الموظفين', 'type' => 'expense', 'sub_type' => 'operating_expense'],
                    ['code' => '522', 'name' => 'إيجار المكاتب', 'type' => 'expense', 'sub_type' => 'operating_expense'],
                    ['code' => '523', 'name' => 'كهرباء ومياه', 'type' => 'expense', 'sub_type' => 'operating_expense'],
                    ['code' => '524', 'name' => 'اتصالات وإنترنت', 'type' => 'expense', 'sub_type' => 'operating_expense'],
                    ['code' => '525', 'name' => 'مصاريف بنكية', 'type' => 'expense', 'sub_type' => 'operating_expense'],
                    ['code' => '526', 'name' => 'مصاريف صيانة', 'type' => 'expense', 'sub_type' => 'operating_expense'],
                    ['code' => '527', 'name' => 'مصاريف نظافة', 'type' => 'expense', 'sub_type' => 'operating_expense'],
                    ['code' => '528', 'name' => 'دعاية وإعلان', 'type' => 'expense', 'sub_type' => 'operating_expense'],
                    ['code' => '529', 'name' => 'مصاريف عمومية وإدارية', 'type' => 'expense', 'sub_type' => 'operating_expense'],
                ]],
                ['code' => '53', 'name' => 'مصروفات أخرى', 'name_en' => 'Other Expenses', 'type' => 'expense', 'sub_type' => 'other_expense', 'children' => [
                    ['code' => '531', 'name' => 'خسائر فروق العملة', 'type' => 'expense', 'sub_type' => 'other_expense'],
                    ['code' => '532', 'name' => 'فوائد بنكية مدينة', 'type' => 'expense', 'sub_type' => 'other_expense'],
                    ['code' => '533', 'name' => 'إهلاك الأصول الثابتة', 'type' => 'expense', 'sub_type' => 'other_expense'],
                    ['code' => '534', 'name' => 'مصاريف متنوعة', 'type' => 'expense', 'sub_type' => 'other_expense'],
                ]],
            ]],
        ];
    }
}
