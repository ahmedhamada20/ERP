<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

/**
 * Demo suppliers covering all 5 types — only seeded outside production
 * (orchestrated by DatabaseSeeder demo block).
 */
class SupplierDemoSeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = [
            ['hotel',     'فندق هيلتون مكة',           'Hilton Mecca',          '012-XXX-1234', 'mecca@hilton.com'],
            ['hotel',     'فندق إنتركونتيننتال المدينة', 'Intercontinental Madinah','012-XXX-5678', 'madinah@ic.com'],
            ['airline',   'مصر للطيران',                 'EgyptAir',              '012-XXX-1010', 'corp@egyptair.com'],
            ['transport', 'شركة النقل الذهبي',           'Golden Transport',      '012-XXX-2020', 'ops@goldentr.com'],
            ['visa',      'مكتب تأشيرات الخليج',         'Gulf Visa Office',      '012-XXX-3030', 'visa@gulfoffice.com'],
        ];

        foreach ($suppliers as [$type, $name, $nameEn, $phone, $email]) {
            Supplier::firstOrCreate(
                ['name' => $name],
                [
                    'name_en'             => $nameEn,
                    'type'                => $type,
                    'phone'               => $phone,
                    'mobile'              => $phone,
                    'email'               => $email,
                    'currency'            => $type === 'hotel' ? 'SAR' : 'EGP',
                    'payment_terms_days'  => 30,
                    'country'             => $type === 'hotel' ? 'السعودية' : 'مصر',
                    'is_active'           => true,
                ]
            );
        }
    }
}
