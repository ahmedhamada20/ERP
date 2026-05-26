<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@corex-erp.local'],
            [
                'name'      => 'مدير النظام',
                'password'  => Hash::make('admin123456'),
                'phone'     => '01000000000',
                'is_active' => true,
                'locale'    => 'ar',
            ]
        );

        if (!$admin->hasRole('super-admin')) {
            $admin->assignRole('super-admin');
        }

        $this->command->info('==============================================');
        $this->command->info(' بيانات الدخول الافتراضية:');
        $this->command->info(' البريد:  admin@corex-erp.local');
        $this->command->info(' الباسورد: admin123456');
        $this->command->info('==============================================');
    }
}
