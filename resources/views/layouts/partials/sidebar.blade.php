@php
    $routeName = request()->route() ? request()->route()->getName() : '';
    $isActive  = fn($prefix) => str_starts_with($routeName, $prefix) ? 'active' : '';
@endphp

<aside class="sidebar">
    {{-- Brand --}}
    <div class="brand">
        <div class="brand-logo"><i class="bi bi-stars"></i></div>
        <div class="brand-text">
            CoreX <span class="badge-erp">ERP</span>
        </div>
    </div>

    {{-- Menu --}}
    <nav class="menu">
        <a href="{{ route('admin.dashboard') }}" class="{{ $isActive('admin.dashboard') }}">
            <span><i class="menu-icon bi bi-house-door"></i> الرئيسية</span>
        </a>

      

        @can('customers.view')
        <a href="{{ route('admin.customers.index') }}" class="{{ $isActive('admin.customers') }}">
            <span><i class="menu-icon bi bi-people"></i> العملاء</span>
        </a>
        @endcan

      

        @canany(['religious_programs.view', 'religious_bookings.view'])
        <div class="menu-group {{ str_starts_with($routeName, 'admin.religious') ? 'open' : '' }}">
            <a href="#" class="menu-group-toggle {{ str_starts_with($routeName, 'admin.religious') ? 'active' : '' }}"
               onclick="this.parentElement.classList.toggle('open'); return false;">
                <span><i class="menu-icon bi bi-mosque"></i> السياحة الدينية</span>
                <i class="bi bi-chevron-down chev"></i>
            </a>
            <div class="submenu">
                @can('religious_bookings.view')
                <a href="{{ route('admin.religious.bookings.index') }}" class="{{ $isActive('admin.religious.bookings') }}">
                    <span><i class="menu-icon bi bi-journal-bookmark"></i> الحجوزات</span>
                </a>
                @endcan
                @can('religious_programs.view')
                <a href="{{ route('admin.religious.programs.index') }}" class="{{ $isActive('admin.religious.programs') }}">
                    <span><i class="menu-icon bi bi-collection"></i> البرامج</span>
                </a>
                @endcan
                @can('religious.alerts.view')
                <a href="{{ route('admin.religious.alerts.index') }}" class="{{ $isActive('admin.religious.alerts') }}">
                    <span><i class="menu-icon bi bi-bell"></i> التنبيهات</span>
                </a>
                @endcan
                @canany(['religious_bookings.sync_safa', 'religious_bookings.sync_umrah_portal'])
                <a href="{{ route('admin.religious.integrations.index') }}" class="{{ $isActive('admin.religious.integrations') }}">
                    <span><i class="menu-icon bi bi-link-45deg"></i> التكاملات</span>
                </a>
                @endcanany
                @can('religious.reports')
                <a href="{{ route('admin.religious.reports.trips') }}" class="{{ $isActive('admin.religious.reports') }}">
                    <span><i class="menu-icon bi bi-file-bar-graph"></i> حصر الرحلات</span>
                </a>
                @endcan
                @can('exchange_rates.view')
                <a href="{{ route('admin.religious.exchange_rates.index') }}" class="{{ $isActive('admin.religious.exchange_rates') }}">
                    <span><i class="menu-icon bi bi-currency-exchange"></i> أسعار الصرف</span>
                </a>
                @endcan
            </div>
        </div>
        @endcanany

        @canany(['leads.view', 'opportunities.view'])
        <div class="menu-group {{ str_starts_with($routeName, 'admin.crm') ? 'open' : '' }}">
            <a href="#" class="menu-group-toggle {{ str_starts_with($routeName, 'admin.crm') ? 'active' : '' }}"
               onclick="this.parentElement.classList.toggle('open'); return false;">
                <span><i class="menu-icon bi bi-person-lines-fill"></i> CRM</span>
                <i class="bi bi-chevron-down chev"></i>
            </a>
            <div class="submenu">
                @can('leads.view')
                <a href="{{ route('admin.crm.leads.index') }}" class="{{ request()->routeIs('admin.crm.leads.index') || request()->routeIs('admin.crm.leads.show') || request()->routeIs('admin.crm.leads.create') || request()->routeIs('admin.crm.leads.edit') ? 'active' : '' }}">
                    <span><i class="menu-icon bi bi-table"></i> العملاء المحتملون</span>
                </a>
                <a href="{{ route('admin.crm.leads.kanban') }}" class="{{ request()->routeIs('admin.crm.leads.kanban') ? 'active' : '' }}">
                    <span><i class="menu-icon bi bi-kanban"></i> قمع العملاء</span>
                </a>
                @endcan
                @can('opportunities.view')
                <a href="{{ route('admin.crm.opportunities.index') }}" class="{{ request()->routeIs('admin.crm.opportunities.index') || request()->routeIs('admin.crm.opportunities.show') || request()->routeIs('admin.crm.opportunities.create') || request()->routeIs('admin.crm.opportunities.edit') ? 'active' : '' }}">
                    <span><i class="menu-icon bi bi-briefcase"></i> الصفقات</span>
                </a>
                <a href="{{ route('admin.crm.opportunities.pipeline') }}" class="{{ request()->routeIs('admin.crm.opportunities.pipeline') ? 'active' : '' }}">
                    <span><i class="menu-icon bi bi-funnel"></i> قمع الصفقات</span>
                </a>
                @endcan
                @can('whatsapp.view_logs')
                <a href="{{ route('admin.crm.whatsapp.messages.index') }}" class="{{ request()->routeIs('admin.crm.whatsapp.messages.*') ? 'active' : '' }}">
                    <span><i class="menu-icon bi bi-whatsapp"></i> رسائل WhatsApp</span>
                </a>
                @endcan
                @can('whatsapp.manage_settings')
                <a href="{{ route('admin.crm.whatsapp.settings.edit') }}" class="{{ request()->routeIs('admin.crm.whatsapp.settings.*') ? 'active' : '' }}">
                    <span><i class="menu-icon bi bi-gear"></i> إعدادات WhatsApp</span>
                </a>
                @endcan
            </div>
        </div>
        @endcanany

        @canany(['domestic_programs.view', 'domestic_bookings.view'])
        <div class="menu-group {{ str_starts_with($routeName, 'admin.domestic') ? 'open' : '' }}">
            <a href="#" class="menu-group-toggle {{ str_starts_with($routeName, 'admin.domestic') ? 'active' : '' }}"
               onclick="this.parentElement.classList.toggle('open'); return false;">
                <span><i class="menu-icon bi bi-map"></i> السياحة الداخلية</span>
                <i class="bi bi-chevron-down chev"></i>
            </a>
            <div class="submenu">
                @can('domestic_bookings.view')
                <a href="{{ route('admin.domestic.bookings.index') }}" class="{{ $isActive('admin.domestic.bookings') }}">
                    <span><i class="menu-icon bi bi-journal-bookmark"></i> الحجوزات</span>
                </a>
                @endcan
                @can('domestic_programs.view')
                <a href="{{ route('admin.domestic.programs.index') }}" class="{{ $isActive('admin.domestic.programs') }}">
                    <span><i class="menu-icon bi bi-collection"></i> البرامج</span>
                </a>
                @endcan
                @can('domestic.reports')
                <a href="{{ route('admin.domestic.reports.pnl_by_program') }}" class="{{ $isActive('admin.domestic.reports') }}">
                    <span><i class="menu-icon bi bi-file-bar-graph"></i> الأرباح حسب البرنامج</span>
                </a>
                @endcan
            </div>
        </div>
        @endcanany

    

        @can('catalog.airlines.view')
        <a href="{{ route('admin.catalog.airlines.index') }}" class="{{ $isActive('admin.catalog.airlines') }}">
            <span><i class="menu-icon bi bi-airplane"></i> الطيران</span>
        </a>
        @endcan

        @can('catalog.visas.view')
        <a href="{{ route('admin.catalog.visas.index') }}" class="{{ $isActive('admin.catalog.visas') }}">
            <span><i class="menu-icon bi bi-passport"></i> التأشيرات</span>
        </a>
        @endcan

        @can('catalog.hotels.view')
        <a href="{{ route('admin.catalog.hotels.index') }}" class="{{ $isActive('admin.catalog.hotels') }}">
            <span><i class="menu-icon bi bi-building"></i> الفنادق</span>
        </a>
        @endcan

        @can('catalog.transport.view')
        <a href="{{ route('admin.catalog.transport.index') }}" class="{{ $isActive('admin.catalog.transport') }}">
            <span><i class="menu-icon bi bi-bus-front"></i> النقل</span>
        </a>
        @endcan

        @canany(['suppliers.view', 'supplier_invoices.view'])
        <div class="menu-group {{ str_starts_with($routeName, 'admin.supplier') ? 'open' : '' }}">
            <a href="#" class="menu-group-toggle {{ str_starts_with($routeName, 'admin.supplier') ? 'active' : '' }}"
               onclick="this.parentElement.classList.toggle('open'); return false;">
                <span><i class="menu-icon bi bi-building-add"></i> الموردون</span>
                <i class="bi bi-chevron-down chev"></i>
            </a>
            <div class="submenu">
                @can('suppliers.view')
                <a href="{{ route('admin.suppliers.index') }}" class="{{ $isActive('admin.suppliers') }}">
                    <span><i class="menu-icon bi bi-people"></i> قائمة الموردين</span>
                </a>
                @endcan
                @can('supplier_invoices.view')
                <a href="{{ route('admin.supplier_invoices.index') }}" class="{{ $isActive('admin.supplier_invoices') }}">
                    <span><i class="menu-icon bi bi-receipt"></i> فواتير الموردين</span>
                </a>
                @endcan
                @can('suppliers.reports')
                <a href="{{ route('admin.suppliers.statement') }}" class="{{ $isActive('admin.suppliers.statement') }}">
                    <span><i class="menu-icon bi bi-journal-arrow-down"></i> كشف حساب مورد</span>
                </a>
                <a href="{{ route('admin.suppliers.aging') }}" class="{{ $isActive('admin.suppliers.aging') }}">
                    <span><i class="menu-icon bi bi-hourglass-split"></i> أعمار الديون</span>
                </a>
                @endcan
            </div>
        </div>
        @endcanany

   

        @canany(['accounting.chart.view', 'accounting.journal.view', 'accounting.vouchers.view', 'accounting.reports.trial_balance'])
        <div class="menu-group {{ str_starts_with($routeName, 'admin.accounting') ? 'open' : '' }}">
            <a href="#" class="menu-group-toggle {{ str_starts_with($routeName, 'admin.accounting') ? 'active' : '' }}"
               onclick="this.parentElement.classList.toggle('open'); return false;">
                <span><i class="menu-icon bi bi-bank2"></i> المحاسبة</span>
                <i class="bi bi-chevron-down chev"></i>
            </a>
            <div class="submenu">
                @can('accounting.chart.view')
                <a href="{{ route('admin.accounting.accounts.index') }}" class="{{ $isActive('admin.accounting.accounts') }}">
                    <span><i class="menu-icon bi bi-diagram-3"></i> دليل الحسابات</span>
                </a>
                @endcan
                @can('accounting.journal.view')
                <a href="{{ route('admin.accounting.journal.index') }}" class="{{ $isActive('admin.accounting.journal') }}">
                    <span><i class="menu-icon bi bi-journal-text"></i> القيود اليومية</span>
                </a>
                @endcan
                @can('accounting.chart.view')
                <a href="{{ route('admin.accounting.cash.index') }}" class="{{ $isActive('admin.accounting.cash') }}">
                    <span><i class="menu-icon bi bi-piggy-bank"></i> الخزائن والبنوك</span>
                </a>
                @endcan
                @can('accounting.vouchers.view')
                <a href="{{ route('admin.accounting.vouchers.receipts.index') }}" class="{{ $isActive('admin.accounting.vouchers.receipts') }}">
                    <span><i class="menu-icon bi bi-arrow-down-circle"></i> سندات القبض</span>
                </a>
                <a href="{{ route('admin.accounting.vouchers.payments.index') }}" class="{{ $isActive('admin.accounting.vouchers.payments') }}">
                    <span><i class="menu-icon bi bi-arrow-up-circle"></i> سندات الصرف</span>
                </a>
                @endcan
                @can('accounting.reports.trial_balance')
                <a href="{{ route('admin.accounting.reports.trial_balance') }}" class="{{ $isActive('admin.accounting.reports.trial_balance') }}">
                    <span><i class="menu-icon bi bi-table"></i> ميزان المراجعة</span>
                </a>
                @endcan
                @can('accounting.reports.pnl')
                <a href="{{ route('admin.accounting.reports.pnl') }}" class="{{ $isActive('admin.accounting.reports.pnl') }}">
                    <span><i class="menu-icon bi bi-bar-chart-line"></i> قائمة الدخل</span>
                </a>
                @endcan
                @can('accounting.reports.general_ledger')
                <a href="{{ route('admin.accounting.reports.general_ledger') }}" class="{{ $isActive('admin.accounting.reports.general_ledger') }}">
                    <span><i class="menu-icon bi bi-book"></i> دفتر الأستاذ</span>
                </a>
                @endcan
            </div>
        </div>
        @endcanany

        @can('reports.view')
        <a href="{{ route('admin.reports.hub') }}" class="{{ $isActive('admin.reports') }}">
            <span><i class="menu-icon bi bi-graph-up-arrow"></i> التقارير</span>
        </a>
        @endcan

        @canany(['branches.view', 'departments.view', 'positions.view', 'employees.view', 'payroll.view'])
        <div class="menu-group {{ str_starts_with($routeName, 'admin.hr') ? 'open' : '' }}">
            <a href="#" class="menu-group-toggle {{ str_starts_with($routeName, 'admin.hr') ? 'active' : '' }}"
               onclick="this.parentElement.classList.toggle('open'); return false;">
                <span><i class="menu-icon bi bi-people"></i> الموارد البشرية</span>
                <i class="bi bi-chevron-down chev"></i>
            </a>
            <div class="submenu">
                @can('branches.view')
                <a href="{{ route('admin.hr.branches.index') }}" class="{{ $isActive('admin.hr.branches') }}">
                    <span><i class="menu-icon bi bi-buildings"></i> الفروع</span>
                </a>
                @endcan
                @can('departments.view')
                <a href="{{ route('admin.hr.departments.index') }}" class="{{ $isActive('admin.hr.departments') }}">
                    <span><i class="menu-icon bi bi-diagram-3"></i> الأقسام</span>
                </a>
                @endcan
                @can('positions.view')
                <a href="{{ route('admin.hr.positions.index') }}" class="{{ $isActive('admin.hr.positions') }}">
                    <span><i class="menu-icon bi bi-briefcase"></i> الوظائف</span>
                </a>
                @endcan
                @can('employees.view')
                <a href="{{ route('admin.hr.employees.index') }}" class="{{ $isActive('admin.hr.employees') }}">
                    <span><i class="menu-icon bi bi-person-badge"></i> الموظفون</span>
                </a>
                @endcan
                @can('payroll.view')
                <a href="{{ route('admin.hr.payroll.runs.index') }}" class="{{ $isActive('admin.hr.payroll') }}">
                    <span><i class="menu-icon bi bi-cash-stack"></i> الرواتب</span>
                </a>
                @endcan
            </div>
        </div>
        @endcanany

        @canany(['users.view','roles.view','settings.view'])
        @can('users.view')
        <a href="{{ route('admin.users.index') }}" class="{{ $isActive('admin.users') }}">
            <span><i class="menu-icon bi bi-person-badge"></i> المستخدمون</span>
        </a>
        @endcan
        @can('roles.view')
        <a href="{{ route('admin.roles.index') }}" class="{{ $isActive('admin.roles') }}">
            <span><i class="menu-icon bi bi-shield-lock"></i> الصلاحيات</span>
        </a>
        @endcan
        @can('settings.view')
        <a href="{{ route('admin.settings.index') }}" class="{{ $isActive('admin.settings') }}">
            <span><i class="menu-icon bi bi-gear"></i> الإعدادات</span>
        </a>
        @endcan
        @can('audit.view')
        <a href="{{ route('admin.audit.index') }}" class="{{ $isActive('admin.audit') }}">
            <span><i class="menu-icon bi bi-shield-check"></i> سجل التدقيق</span>
        </a>
        @endcan
        @endcanany
    </nav>

    {{-- Support card --}}
    <div class="support-card">
        <div class="support-icon"><i class="bi bi-headset"></i></div>
        <div class="support-text">
            <strong>الدعم والمساعدة</strong>
            <small>نحن هنا لمساعدتك</small>
        </div>
    </div>
</aside>
