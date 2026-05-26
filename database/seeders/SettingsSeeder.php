<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            'company_name'    => 'كوركس لإدارة السياحة',
            'company_email'   => 'info@corex-erp.local',
            'company_phone'   => '+20 100 000 0000',
            'company_address' => 'القاهرة - مصر',
            'company_tax'     => '',
            'currency'        => 'EGP',
            'currency_symbol' => 'ج.م',
            'timezone'        => 'Africa/Cairo',
        ];

        foreach ($defaults as $key => $value) {
            Setting::firstOrCreate(
                ['key' => $key],
                ['value' => $value, 'group' => 'general', 'type' => 'text']
            );
        }
    }
}
