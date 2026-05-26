<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Demo team users — مستخدمو فريق العمل التجريبي.
 *
 * Creates a realistic staff roster so bookings can be assigned to actual
 * managers / sellers / accountants. Idempotent via firstOrCreate(email).
 *
 * Default password for all demo users: password
 */
class TeamUsersSeeder extends Seeder
{
    public function run(): void
    {
        activity()->disableLogging();

        $users = [
            // Managers — المديرون
            ['name' => 'أحمد محمود السيد',   'email' => 'ahmed.manager@corex-erp.local',  'phone' => '01001100001', 'role' => 'manager'],
            ['name' => 'محمد عبدالرحمن علي', 'email' => 'mohamed.manager@corex-erp.local', 'phone' => '01001100002', 'role' => 'manager'],

            // Booking staff — موظفو المبيعات
            ['name' => 'خالد إبراهيم محمد', 'email' => 'khaled.sales@corex-erp.local', 'phone' => '01002200001', 'role' => 'booking-staff'],
            ['name' => 'يوسف عمر سعيد',    'email' => 'youssef.sales@corex-erp.local', 'phone' => '01002200002', 'role' => 'booking-staff'],
            ['name' => 'سارة محمد فؤاد',   'email' => 'sara.sales@corex-erp.local',    'phone' => '01002200003', 'role' => 'booking-staff'],
            ['name' => 'منى أحمد كامل',    'email' => 'mona.sales@corex-erp.local',    'phone' => '01002200004', 'role' => 'booking-staff'],
            ['name' => 'فاطمة علي حسن',   'email' => 'fatma.sales@corex-erp.local',   'phone' => '01002200005', 'role' => 'booking-staff'],

            // Accountants — المحاسبون
            ['name' => 'عمر صلاح الدين',  'email' => 'omar.acc@corex-erp.local',   'phone' => '01003300001', 'role' => 'accountant'],
            ['name' => 'مروة حسين عبده',  'email' => 'marwa.acc@corex-erp.local',  'phone' => '01003300002', 'role' => 'accountant'],

            // Operations / Visa team — موظفو العمليات
            ['name' => 'حسام الدين رضا',  'email' => 'hossam.ops@corex-erp.local', 'phone' => '01004400001', 'role' => 'religious-operations'],
            ['name' => 'ندى محمد عبدالله', 'email' => 'nada.ops@corex-erp.local',   'phone' => '01004400002', 'role' => 'religious-operations'],
        ];

        foreach ($users as $row) {
            $user = User::firstOrCreate(
                ['email' => $row['email']],
                [
                    'name'      => $row['name'],
                    'password'  => Hash::make('password'),
                    'phone'     => $row['phone'],
                    'is_active' => true,
                    'locale'    => 'ar',
                ]
            );

            if (!$user->hasRole($row['role'])) {
                $user->assignRole($row['role']);
            }
        }

        $this->command->info('Created ' . count($users) . ' demo team users (password: password)');
    }
}
