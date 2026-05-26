@extends('layouts.master')

@section('title', 'مركز التقارير')
@section('page_title', 'مركز التقارير')
@section('page_subtitle', 'كل التقارير التحليلية والمالية والتشغيلية في النظام — موحدة في مكان واحد')

@push('styles')
<style>
    .report-categories { display: grid; gap: 1rem; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); }

    .report-cat {
        background: #fff; border-radius: 16px; overflow: hidden;
        border: 1px solid #f1f5f9;
        box-shadow: 0 2px 8px rgba(15,23,42,.04);
        transition: all .25s cubic-bezier(.4,0,.2,1);
        display: flex; flex-direction: column;
    }
    .report-cat:hover { transform: translateY(-3px); box-shadow: 0 12px 28px rgba(15,23,42,.08); }

    .report-cat .cat-head {
        padding: 1.1rem 1.25rem;
        display: flex; align-items: center; gap: .85rem;
        border-bottom: 1px solid #f1f5f9;
        position: relative; overflow: hidden;
    }
    .report-cat .cat-head::before {
        content: ''; position: absolute;
        right: -30px; bottom: -30px;
        width: 110px; height: 110px;
        background: var(--cat-accent, #e0e7ff);
        border-radius: 50%; opacity: .25;
    }
    .report-cat .cat-icon {
        width: 54px; height: 54px; border-radius: 14px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.6rem; color: #fff;
        background: var(--cat-icon, #4338ca);
        box-shadow: 0 6px 14px var(--cat-shadow, rgba(67,56,202,.25));
        position: relative; z-index: 1;
    }
    .report-cat .cat-title {
        font-weight: 800; color: var(--brand-navy, #1e293b);
        font-size: 1rem; position: relative; z-index: 1;
    }
    .report-cat .cat-desc {
        font-size: .76rem; color: #64748b;
        margin-top: .15rem; position: relative; z-index: 1;
    }
    .report-cat .cat-count {
        margin-right: auto; background: rgba(255,255,255,.85);
        padding: .25rem .6rem; border-radius: 8px;
        font-size: .7rem; font-weight: 800; color: #475569;
        position: relative; z-index: 1;
    }

    .report-cat .reports-list { padding: .5rem; flex: 1; }
    .report-cat .report-link {
        display: flex; align-items: center; gap: .75rem;
        padding: .75rem .85rem; border-radius: 10px;
        text-decoration: none; color: #1f2937;
        transition: all .15s; font-size: .88rem;
        font-weight: 600;
    }
    .report-cat .report-link:hover {
        background: linear-gradient(135deg, #fffbeb, #fef3c7);
        color: #92400e; transform: translateX(-3px);
    }
    .report-cat .report-link i.bi-chevron-left {
        margin-right: auto; color: #cbd5e1; transition: all .2s;
    }
    .report-cat .report-link:hover i.bi-chevron-left { color: #d4a437; transform: translateX(-3px); }
    .report-cat .report-link .r-ico {
        width: 36px; height: 36px; border-radius: 9px;
        display: flex; align-items: center; justify-content: center;
        background: #f8fafc; color: #64748b; font-size: 1.05rem;
        flex-shrink: 0;
    }
    .report-cat .report-link:hover .r-ico { background: #fff; color: #d4a437; }
    .report-cat .report-link .r-text { line-height: 1.25; }
    .report-cat .report-link .r-text .r-desc {
        font-size: .7rem; color: #94a3b8; font-weight: 500;
        margin-top: .1rem;
    }

    .cat-analytics { --cat-icon: linear-gradient(135deg,#6366f1,#4338ca); --cat-shadow: rgba(99,102,241,.30); --cat-accent: #e0e7ff; }
    .cat-religious { --cat-icon: linear-gradient(135deg,#d4a437,#b45309); --cat-shadow: rgba(212,164,55,.30); --cat-accent: #fef3c7; }
    .cat-domestic  { --cat-icon: linear-gradient(135deg,#0ea5e9,#0369a1); --cat-shadow: rgba(14,165,233,.30); --cat-accent: #e0f2fe; }
    .cat-finance   { --cat-icon: linear-gradient(135deg,#16a34a,#15803d); --cat-shadow: rgba(21,128,61,.30); --cat-accent: #dcfce7; }
    .cat-suppliers { --cat-icon: linear-gradient(135deg,#f97316,#c2410c); --cat-shadow: rgba(249,115,22,.30); --cat-accent: #fed7aa; }
    .cat-customers { --cat-icon: linear-gradient(135deg,#ec4899,#be185d); --cat-shadow: rgba(236,72,153,.30); --cat-accent: #fce7f3; }
    .cat-staff     { --cat-icon: linear-gradient(135deg,#a855f7,#7c3aed); --cat-shadow: rgba(168,85,247,.30); --cat-accent: #f3e8ff; }
    .cat-ops       { --cat-icon: linear-gradient(135deg,#475569,#1e293b); --cat-shadow: rgba(71,85,105,.30); --cat-accent: #e2e8f0; }
    .cat-catalog   { --cat-icon: linear-gradient(135deg,#06b6d4,#0e7490); --cat-shadow: rgba(6,182,212,.30); --cat-accent: #cffafe; }
</style>
@endpush

@section('content')

<div class="report-categories">

    {{-- ── 1. التقارير التحليلية ─────────────────────── --}}
    <div class="report-cat cat-analytics">
        <div class="cat-head">
            <div class="cat-icon"><i class="bi bi-bar-chart-line-fill"></i></div>
            <div>
                <div class="cat-title">التقارير التحليلية</div>
                <div class="cat-desc">رؤى متقاطعة لاتخاذ القرار</div>
            </div>
            <span class="cat-count">7</span>
        </div>
        <div class="reports-list">
            <a href="{{ route('admin.reports.analytics.monthly_profitability') }}" class="report-link">
                <div class="r-ico"><i class="bi bi-graph-up-arrow"></i></div>
                <div class="r-text">
                    <div>الربحية الشهرية</div>
                    <div class="r-desc">إيراد، تكلفة، صافي ربح لكل شهر</div>
                </div>
                <i class="bi bi-chevron-left"></i>
            </a>
            <a href="{{ route('admin.reports.analytics.top_programs') }}" class="report-link">
                <div class="r-ico"><i class="bi bi-trophy"></i></div>
                <div class="r-text">
                    <div>البرامج الأعلى مبيعاً</div>
                    <div class="r-desc">ترتيب البرامج حسب الإيراد والهامش</div>
                </div>
                <i class="bi bi-chevron-left"></i>
            </a>
            <a href="{{ route('admin.reports.analytics.top_customers') }}" class="report-link">
                <div class="r-ico"><i class="bi bi-people-fill"></i></div>
                <div class="r-text">
                    <div>العملاء الأكثر حجزاً</div>
                    <div class="r-desc">أعلى 50 عميل في الفترة</div>
                </div>
                <i class="bi bi-chevron-left"></i>
            </a>
            <a href="{{ route('admin.reports.analytics.new_customers') }}" class="report-link">
                <div class="r-ico"><i class="bi bi-person-plus"></i></div>
                <div class="r-text">
                    <div>العملاء الجدد</div>
                    <div class="r-desc">من سجّل حديثاً + من حجز فعلاً</div>
                </div>
                <i class="bi bi-chevron-left"></i>
            </a>
            <a href="{{ route('admin.reports.analytics.sales_performance') }}" class="report-link">
                <div class="r-ico"><i class="bi bi-person-workspace"></i></div>
                <div class="r-text">
                    <div>أداء البائعين</div>
                    <div class="r-desc">ترتيب الموظفين حسب الإيراد والربح</div>
                </div>
                <i class="bi bi-chevron-left"></i>
            </a>
            <a href="{{ route('admin.reports.analytics.commissions') }}" class="report-link">
                <div class="r-ico"><i class="bi bi-cash-coin"></i></div>
                <div class="r-text">
                    <div>كشف العمولات</div>
                    <div class="r-desc">العمولات الفعلية من كشوف الرواتب</div>
                </div>
                <i class="bi bi-chevron-left"></i>
            </a>
            <a href="{{ route('admin.reports.analytics.outstanding_payments') }}" class="report-link">
                <div class="r-ico"><i class="bi bi-exclamation-octagon"></i></div>
                <div class="r-text">
                    <div>المدفوعات المتأخرة</div>
                    <div class="r-desc">حجوزات بها رصيد مستحق على العميل</div>
                </div>
                <i class="bi bi-chevron-left"></i>
            </a>
        </div>
    </div>

    {{-- ── 2. التقارير المحاسبية ─────────────────────── --}}
    @canany(['accounting.reports.trial_balance', 'accounting.reports.pnl', 'accounting.reports.general_ledger', 'accounting.chart.view'])
    <div class="report-cat cat-finance">
        <div class="cat-head">
            <div class="cat-icon"><i class="bi bi-bank2"></i></div>
            <div>
                <div class="cat-title">التقارير المحاسبية</div>
                <div class="cat-desc">دفاتر GL ومراجعة وأرصدة</div>
            </div>
            <span class="cat-count">4</span>
        </div>
        <div class="reports-list">
            @can('accounting.reports.trial_balance')
            <a href="{{ route('admin.accounting.reports.trial_balance') }}" class="report-link">
                <div class="r-ico"><i class="bi bi-list-columns-reverse"></i></div>
                <div class="r-text">
                    <div>ميزان المراجعة</div>
                    <div class="r-desc">أرصدة كل الحسابات في تاريخ معين</div>
                </div>
                <i class="bi bi-chevron-left"></i>
            </a>
            @endcan
            @can('accounting.reports.pnl')
            <a href="{{ route('admin.accounting.reports.pnl') }}" class="report-link">
                <div class="r-ico"><i class="bi bi-clipboard-data"></i></div>
                <div class="r-text">
                    <div>قائمة الأرباح والخسائر (P&L)</div>
                    <div class="r-desc">الإيرادات ناقص المصروفات</div>
                </div>
                <i class="bi bi-chevron-left"></i>
            </a>
            @endcan
            @can('accounting.reports.general_ledger')
            <a href="{{ route('admin.accounting.reports.general_ledger') }}" class="report-link">
                <div class="r-ico"><i class="bi bi-journal-text"></i></div>
                <div class="r-text">
                    <div>دفتر الأستاذ العام</div>
                    <div class="r-desc">كل الحركات لكل حساب بالتفصيل</div>
                </div>
                <i class="bi bi-chevron-left"></i>
            </a>
            @endcan
            @can('accounting.chart.view')
            <a href="{{ route('admin.accounting.cash.index') }}" class="report-link">
                <div class="r-ico"><i class="bi bi-cash-stack"></i></div>
                <div class="r-text">
                    <div>أرصدة الخزن والبنوك</div>
                    <div class="r-desc">حالة كل حساب نقدي/بنكي مباشرة</div>
                </div>
                <i class="bi bi-chevron-left"></i>
            </a>
            @endcan
        </div>
    </div>
    @endcanany

    {{-- ── 3. تقارير الموردين ─────────────────────── --}}
    @can('suppliers.reports')
    <div class="report-cat cat-suppliers">
        <div class="cat-head">
            <div class="cat-icon"><i class="bi bi-truck"></i></div>
            <div>
                <div class="cat-title">تقارير الموردين</div>
                <div class="cat-desc">كشوف حسابات وأعمار ديون</div>
            </div>
            <span class="cat-count">2</span>
        </div>
        <div class="reports-list">
            <a href="{{ route('admin.suppliers.statement') }}" class="report-link">
                <div class="r-ico"><i class="bi bi-receipt-cutoff"></i></div>
                <div class="r-text">
                    <div>كشف حساب مورد</div>
                    <div class="r-desc">الحركات والرصيد الجاري لمورد محدد</div>
                </div>
                <i class="bi bi-chevron-left"></i>
            </a>
            <a href="{{ route('admin.suppliers.aging') }}" class="report-link">
                <div class="r-ico"><i class="bi bi-hourglass-split"></i></div>
                <div class="r-text">
                    <div>أعمار الديون</div>
                    <div class="r-desc">الفواتير المعلّقة موزّعة حسب العمر (30/60/90+)</div>
                </div>
                <i class="bi bi-chevron-left"></i>
            </a>
        </div>
    </div>
    @endcan

    {{-- ── 4. تقارير السياحة الدينية ─────────────────────── --}}
    @canany(['religious.reports', 'exchange_rates.view', 'religious.alerts.view'])
    <div class="report-cat cat-religious">
        <div class="cat-head">
            <div class="cat-icon" style="font-family:'Apple Color Emoji','Segoe UI Emoji',sans-serif;">🕋</div>
            <div>
                <div class="cat-title">السياحة الدينية</div>
                <div class="cat-desc">حصر رحلات حج وعمرة</div>
            </div>
            <span class="cat-count">3</span>
        </div>
        <div class="reports-list">
            @can('religious.reports')
            <a href="{{ route('admin.religious.reports.trips') }}" class="report-link">
                <div class="r-ico"><i class="bi bi-list-ul"></i></div>
                <div class="r-text">
                    <div>حصر الرحلات الدينية</div>
                    <div class="r-desc">غرف، أفراد، تأشيرات، مبيعات، أرباح</div>
                </div>
                <i class="bi bi-chevron-left"></i>
            </a>
            @endcan
            @can('exchange_rates.view')
            <a href="{{ route('admin.religious.exchange_rates.index') }}" class="report-link">
                <div class="r-ico"><i class="bi bi-currency-exchange"></i></div>
                <div class="r-text">
                    <div>سجل أسعار الصرف</div>
                    <div class="r-desc">تاريخ SAR/USD مقابل الجنيه</div>
                </div>
                <i class="bi bi-chevron-left"></i>
            </a>
            @endcan
            @can('religious.alerts.view')
            <a href="{{ route('admin.religious.alerts.index') }}" class="report-link">
                <div class="r-ico"><i class="bi bi-bell-fill"></i></div>
                <div class="r-text">
                    <div>التنبيهات الذكية</div>
                    <div class="r-desc">جوازات تنتهي، تأشيرات، دفعات متأخرة</div>
                </div>
                <i class="bi bi-chevron-left"></i>
            </a>
            @endcan
        </div>
    </div>
    @endcanany

    {{-- ── 5. تقارير السياحة الداخلية ─────────────────────── --}}
    @can('domestic.reports')
    <div class="report-cat cat-domestic">
        <div class="cat-head">
            <div class="cat-icon"><i class="bi bi-geo-alt-fill"></i></div>
            <div>
                <div class="cat-title">السياحة الداخلية</div>
                <div class="cat-desc">حصر رحلات داخلية</div>
            </div>
            <span class="cat-count">1</span>
        </div>
        <div class="reports-list">
            <a href="{{ route('admin.domestic.reports.pnl_by_program') }}" class="report-link">
                <div class="r-ico"><i class="bi bi-pie-chart"></i></div>
                <div class="r-text">
                    <div>أرباح كل برنامج داخلي</div>
                    <div class="r-desc">إيراد، تكلفة، ربح موزّع على البرامج</div>
                </div>
                <i class="bi bi-chevron-left"></i>
            </a>
        </div>
    </div>
    @endcan

    {{-- ── 6. تقارير العملاء ─────────────────────── --}}
    @can('customers.view')
    <div class="report-cat cat-customers">
        <div class="cat-head">
            <div class="cat-icon"><i class="bi bi-person-vcard"></i></div>
            <div>
                <div class="cat-title">قواعد بيانات العملاء</div>
                <div class="cat-desc">قوائم وفلاتر شاملة</div>
            </div>
            <span class="cat-count">1</span>
        </div>
        <div class="reports-list">
            <a href="{{ route('admin.customers.index') }}" class="report-link">
                <div class="r-ico"><i class="bi bi-person-lines-fill"></i></div>
                <div class="r-text">
                    <div>كل العملاء</div>
                    <div class="r-desc">قائمة مع فلاتر بحث متقدمة</div>
                </div>
                <i class="bi bi-chevron-left"></i>
            </a>
        </div>
    </div>
    @endcan

    {{-- ── 7. تقارير الموظفين ─────────────────────── --}}
    @canany(['users.view', 'employees.view'])
    <div class="report-cat cat-staff">
        <div class="cat-head">
            <div class="cat-icon"><i class="bi bi-person-badge"></i></div>
            <div>
                <div class="cat-title">الموظفون</div>
                <div class="cat-desc">سجلات وقوائم</div>
            </div>
        </div>
        <div class="reports-list">
            @can('users.view')
            <a href="{{ route('admin.users.index') }}" class="report-link">
                <div class="r-ico"><i class="bi bi-people"></i></div>
                <div class="r-text">
                    <div>مستخدمو النظام</div>
                    <div class="r-desc">كل من له صلاحية دخول</div>
                </div>
                <i class="bi bi-chevron-left"></i>
            </a>
            @endcan
            @can('employees.view')
            <a href="{{ route('admin.hr.employees.index') }}" class="report-link">
                <div class="r-ico"><i class="bi bi-briefcase"></i></div>
                <div class="r-text">
                    <div>قاعدة الموظفين</div>
                    <div class="r-desc">كل الموظفين، الأقسام، المناصب</div>
                </div>
                <i class="bi bi-chevron-left"></i>
            </a>
            @endcan
        </div>
    </div>
    @endcanany

    {{-- ── 8. التقارير التشغيلية ─────────────────────── --}}
    @canany(['audit.view', 'religious_bookings.sync_safa', 'religious_bookings.sync_umrah_portal'])
    <div class="report-cat cat-ops">
        <div class="cat-head">
            <div class="cat-icon"><i class="bi bi-gear-fill"></i></div>
            <div>
                <div class="cat-title">التشغيل والمراجعة</div>
                <div class="cat-desc">سجلات النشاط والتكاملات</div>
            </div>
        </div>
        <div class="reports-list">
            @can('audit.view')
            <a href="{{ route('admin.audit.index') }}" class="report-link">
                <div class="r-ico"><i class="bi bi-clock-history"></i></div>
                <div class="r-text">
                    <div>سجل النشاط (Audit Log)</div>
                    <div class="r-desc">كل التغييرات التي حدثت في النظام</div>
                </div>
                <i class="bi bi-chevron-left"></i>
            </a>
            @endcan
            @canany(['religious_bookings.sync_safa', 'religious_bookings.sync_umrah_portal'])
            <a href="{{ route('admin.religious.integrations.index') }}" class="report-link">
                <div class="r-ico"><i class="bi bi-link-45deg"></i></div>
                <div class="r-text">
                    <div>سجل التكاملات</div>
                    <div class="r-desc">مزامنة صفا وبوابة العمرة</div>
                </div>
                <i class="bi bi-chevron-left"></i>
            </a>
            @endcanany
        </div>
    </div>
    @endcanany

    {{-- ── 9. الكتالوج (المرجع) ─────────────────────── --}}
    @canany(['catalog.airlines.view', 'catalog.hotels.view', 'catalog.visas.view', 'catalog.transport.view'])
    <div class="report-cat cat-catalog">
        <div class="cat-head">
            <div class="cat-icon"><i class="bi bi-collection-fill"></i></div>
            <div>
                <div class="cat-title">قواعد الكتالوج</div>
                <div class="cat-desc">طيران، فنادق، تأشيرات، نقل</div>
            </div>
        </div>
        <div class="reports-list">
            @can('catalog.airlines.view')
            <a href="{{ route('admin.catalog.airlines.index') }}" class="report-link">
                <div class="r-ico"><i class="bi bi-airplane"></i></div>
                <div class="r-text"><div>شركات الطيران</div></div>
                <i class="bi bi-chevron-left"></i>
            </a>
            @endcan
            @can('catalog.hotels.view')
            <a href="{{ route('admin.catalog.hotels.index') }}" class="report-link">
                <div class="r-ico"><i class="bi bi-building"></i></div>
                <div class="r-text"><div>الفنادق</div></div>
                <i class="bi bi-chevron-left"></i>
            </a>
            @endcan
            @can('catalog.visas.view')
            <a href="{{ route('admin.catalog.visas.index') }}" class="report-link">
                <div class="r-ico"><i class="bi bi-passport"></i></div>
                <div class="r-text"><div>التأشيرات</div></div>
                <i class="bi bi-chevron-left"></i>
            </a>
            @endcan
            @can('catalog.transport.view')
            <a href="{{ route('admin.catalog.transport.index') }}" class="report-link">
                <div class="r-ico"><i class="bi bi-bus-front"></i></div>
                <div class="r-text"><div>شركات النقل</div></div>
                <i class="bi bi-chevron-left"></i>
            </a>
            @endcan
        </div>
    </div>
    @endcanany

</div>

@endsection
