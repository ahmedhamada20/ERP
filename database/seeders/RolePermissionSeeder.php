<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            // Users
            'users.view', 'users.create', 'users.update', 'users.delete',
            // Roles
            'roles.view', 'roles.create', 'roles.update', 'roles.delete',
            // Customers
            'customers.view', 'customers.create', 'customers.update', 'customers.delete',
            // Settings
            'settings.view', 'settings.update',
            // Reports (placeholder for future modules)
            'reports.view',
            // Audit log
            'audit.view',

            // ─── Religious tourism — السياحة الدينية ──────────────────
            // Programs (قوالب البرامج) — manager-only by default
            'religious_programs.view',
            'religious_programs.create',
            'religious_programs.update',
            'religious_programs.delete',

            // Bookings (حجوزات الحج والعمرة) — sales staff can create
            'religious_bookings.view',
            'religious_bookings.create',
            'religious_bookings.update',
            'religious_bookings.delete',

            // Costs — only managers/finance can edit financial breakdown
            'religious_bookings.manage_costs',

            // Payments — finance team
            'religious_bookings.manage_payments',

            // Refund approval — only managers (separate from manage_payments)
            'religious_bookings.approve_refund',

            // Workflow transitions
            'religious_bookings.approve',
            'religious_bookings.close',
            'religious_bookings.cancel',

            // Safa / Umrah Portal integration
            'religious_bookings.sync_safa',
            'religious_bookings.sync_umrah_portal',

            // Exchange rates — finance manager
            'exchange_rates.view',
            'exchange_rates.manage',

            // Reports
            'religious.reports',

            // Alerts
            'religious.alerts.view',
            'religious.alerts.acknowledge',

            // ─── Domestic tourism — السياحة الداخلية ──────────────────
            // Programs (قوالب البرامج الداخلية)
            'domestic_programs.view',
            'domestic_programs.create',
            'domestic_programs.update',
            'domestic_programs.delete',

            // Bookings (حجوزات السياحة الداخلية) — reserved for Step 3
            'domestic_bookings.view',
            'domestic_bookings.create',
            'domestic_bookings.update',
            'domestic_bookings.delete',
            'domestic_bookings.manage_costs',
            'domestic_bookings.manage_payments',
            'domestic_bookings.approve_refund',
            'domestic_bookings.approve',
            'domestic_bookings.close',
            'domestic_bookings.cancel',

            // Reports
            'domestic.reports',

            // ─── CRM (إدارة علاقات العملاء) ───────────────────────────
            // Leads (العملاء المحتملون)
            'leads.view',
            'leads.create',
            'leads.update',
            'leads.delete',
            'leads.assign',          // إعادة إسناد لموظف آخر
            'leads.convert',         // تحويل lead لعميل
            'leads.activities.create',
            'leads.activities.delete',

            // Opportunities (الصفقات) — for Step 3
            'opportunities.view',
            'opportunities.create',
            'opportunities.update',
            'opportunities.delete',
            'opportunities.convert', // تحويل صفقة لحجز

            // WhatsApp — for Steps 4-5
            'whatsapp.send',
            'whatsapp.view_logs',
            'whatsapp.manage_settings',

            // CRM Reports
            'crm.reports',

            // ─── HR (الموارد البشرية) ─────────────────────────────────
            // Branches (الفروع)
            'branches.view',
            'branches.create',
            'branches.update',
            'branches.delete',

            // Departments + Positions
            'departments.view', 'departments.create', 'departments.update', 'departments.delete',
            'positions.view',   'positions.create',   'positions.update',   'positions.delete',

            // Employees (الموظفين)
            'employees.view',
            'employees.create',
            'employees.update',
            'employees.delete',
            'employees.view_salary',         // الرواتب حساسة — صلاحية منفصلة
            'employees.manage_documents',

            // Payroll (المرتبات) — for Step 5
            'payroll.view',
            'payroll.process',
            'payroll.approve',
            'payroll.reports',

            // ─── Service Catalogs (الطيران، التأشيرات، الفنادق، النقل) ─
            'catalog.airlines.view',  'catalog.airlines.manage',
            'catalog.visas.view',     'catalog.visas.manage',
            'catalog.hotels.view',    'catalog.hotels.manage',
            'catalog.transport.view', 'catalog.transport.manage',

            // ─── Suppliers (الموردون) ─────────────────────────────────
            'suppliers.view',
            'suppliers.create',
            'suppliers.update',
            'suppliers.delete',
            'supplier_invoices.view',
            'supplier_invoices.create',
            'supplier_invoices.post',
            'supplier_invoices.cancel',
            'suppliers.reports',

            // ─── Accounting (المحاسبة) ────────────────────────────────
            // Chart of Accounts (دليل الحسابات)
            'accounting.chart.view',
            'accounting.chart.manage',
            // Journal Entries (القيود اليومية)
            'accounting.journal.view',
            'accounting.journal.create',
            'accounting.journal.post',
            'accounting.journal.delete',
            // Vouchers (سندات القبض/الصرف)
            'accounting.vouchers.view',
            'accounting.vouchers.create',
            // Reports (التقارير المحاسبية)
            'accounting.reports.trial_balance',
            'accounting.reports.pnl',
            'accounting.reports.general_ledger',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        // Super Admin — full access
        $superAdmin = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $superAdmin->syncPermissions(Permission::where('guard_name', 'web')->get());

        // Manager — most permissions including cost/financial editing
        $manager = Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'web']);
        $manager->syncPermissions([
            'users.view', 'users.create', 'users.update',
            'customers.view', 'customers.create', 'customers.update', 'customers.delete',
            'settings.view',
            'reports.view',

            'religious_programs.view', 'religious_programs.create',
            'religious_programs.update', 'religious_programs.delete',
            'religious_bookings.view', 'religious_bookings.create',
            'religious_bookings.update', 'religious_bookings.delete',
            'religious_bookings.manage_costs',
            'religious_bookings.approve_refund',
            'religious_bookings.approve', 'religious_bookings.cancel',
            'religious_bookings.sync_safa', 'religious_bookings.sync_umrah_portal',

            // Domestic — manager has full access
            'domestic_programs.view', 'domestic_programs.create',
            'domestic_programs.update', 'domestic_programs.delete',
            'domestic_bookings.view', 'domestic_bookings.create',
            'domestic_bookings.update', 'domestic_bookings.delete',
            'domestic_bookings.manage_costs',
            'domestic_bookings.approve_refund',
            'domestic_bookings.approve', 'domestic_bookings.cancel',
            'domestic.reports',

            // CRM — manager runs the sales floor
            'leads.view', 'leads.create', 'leads.update', 'leads.delete',
            'leads.assign', 'leads.convert',
            'leads.activities.create', 'leads.activities.delete',
            'opportunities.view', 'opportunities.create',
            'opportunities.update', 'opportunities.delete', 'opportunities.convert',
            'whatsapp.send', 'whatsapp.view_logs', 'whatsapp.manage_settings',
            'crm.reports',

            // HR — manager sees org chart + payroll reports, doesn't process payroll
            'branches.view', 'branches.create', 'branches.update', 'branches.delete',
            'departments.view', 'departments.create', 'departments.update',
            'positions.view',   'positions.create',   'positions.update',
            'employees.view', 'employees.create', 'employees.update',
            'employees.view_salary', 'employees.manage_documents',
            'payroll.view', 'payroll.reports',

            'exchange_rates.view',
            'religious.reports',
            'religious.alerts.view', 'religious.alerts.acknowledge',

            // Service catalogs — managers can view + manage
            'catalog.airlines.view', 'catalog.airlines.manage',
            'catalog.visas.view',    'catalog.visas.manage',
            'catalog.hotels.view',   'catalog.hotels.manage',
            'catalog.transport.view','catalog.transport.manage',

            // Accounting — managers see everything but don't post journals
            'accounting.chart.view',
            'accounting.journal.view',
            'accounting.vouchers.view',
            'accounting.reports.trial_balance',
            'accounting.reports.pnl',
            'accounting.reports.general_ledger',

            // Suppliers — manager can manage suppliers + post invoices
            'suppliers.view', 'suppliers.create', 'suppliers.update',
            'supplier_invoices.view', 'supplier_invoices.create',
            'supplier_invoices.post', 'supplier_invoices.cancel',
            'suppliers.reports',
        ]);

        // Booking staff — sales team, can sell but NOT edit costs (matches brief)
        $booking = Role::firstOrCreate(['name' => 'booking-staff', 'guard_name' => 'web']);
        $booking->syncPermissions([
            'customers.view', 'customers.create', 'customers.update',
            'religious_programs.view',
            'religious_bookings.view', 'religious_bookings.create', 'religious_bookings.update',
            'religious.alerts.view',

            // Domestic — booking staff can sell domestic tours too
            'domestic_programs.view',
            'domestic_bookings.view', 'domestic_bookings.create', 'domestic_bookings.update',

            // CRM — booking staff manages their own leads + can send WhatsApp
            'leads.view', 'leads.create', 'leads.update',
            'leads.convert', 'leads.activities.create',
            'opportunities.view', 'opportunities.create', 'opportunities.update',
            'opportunities.convert',
            'whatsapp.send', 'whatsapp.view_logs',

            // Sales staff need read access to catalogs to pick services from
            'catalog.airlines.view',
            'catalog.visas.view',
            'catalog.hotels.view',
            'catalog.transport.view',
        ]);

        // Accountant — finance + payments
        $accountant = Role::firstOrCreate(['name' => 'accountant', 'guard_name' => 'web']);
        $accountant->syncPermissions([
            'customers.view',
            'reports.view',
            'religious_bookings.view',
            'religious_bookings.manage_payments',
            'religious_bookings.manage_costs',
            'religious_bookings.close',
            'exchange_rates.view', 'exchange_rates.manage',
            'religious.reports',
            'religious.alerts.view', 'religious.alerts.acknowledge',

            // Domestic — accountant manages payments + closes
            'domestic_bookings.view',
            'domestic_bookings.manage_payments',
            'domestic_bookings.manage_costs',
            'domestic_bookings.close',
            'domestic.reports',

            // HR — accountant processes payroll (sensitive: salaries + GL posting)
            'branches.view', 'departments.view', 'positions.view',
            'employees.view', 'employees.view_salary',
            'payroll.view', 'payroll.process', 'payroll.approve', 'payroll.reports',

            // Accounting — accountant does the day-to-day journal work
            'accounting.chart.view',
            'accounting.chart.manage',
            'accounting.journal.view',
            'accounting.journal.create',
            'accounting.journal.post',
            'accounting.journal.delete',
            'accounting.vouchers.view',
            'accounting.vouchers.create',
            'accounting.reports.trial_balance',
            'accounting.reports.pnl',
            'accounting.reports.general_ledger',

            // Suppliers — accountant does invoice + payment work
            'suppliers.view', 'suppliers.create', 'suppliers.update',
            'supplier_invoices.view', 'supplier_invoices.create',
            'supplier_invoices.post', 'supplier_invoices.cancel',
            'suppliers.reports',
        ]);

        // Religious-operations staff — handles visa/Safa/operations
        $operations = Role::firstOrCreate(['name' => 'religious-operations', 'guard_name' => 'web']);
        $operations->syncPermissions([
            'customers.view',
            'religious_programs.view',
            'religious_bookings.view', 'religious_bookings.update',
            'religious_bookings.sync_safa', 'religious_bookings.sync_umrah_portal',
            'religious.alerts.view', 'religious.alerts.acknowledge',

            // Operations team manages the visa + transport catalogs
            'catalog.airlines.view',
            'catalog.visas.view',    'catalog.visas.manage',
            'catalog.hotels.view',
            'catalog.transport.view','catalog.transport.manage',
        ]);
    }
}
