@extends('layouts.master')

@section('title', 'لوحة التحكم')
@section('page_title', 'لوحة تحكم إدارة السياحة والسفر')
@section('page_subtitle', 'إدارة متكاملة لجميع عمليات السياحة الدينية في منصة واحدة')

@push('styles')
<style>
    /* Hero overlay text — bottom-right (RTL: visually right side, away from Kaaba) */
    .hero-banner .hero-overlay {
        position: absolute; inset: 0;
        display: flex; flex-direction: column; justify-content: flex-end;
        padding: 1.5rem 1.75rem;
        color: #fff;
        z-index: 2;
    }
    .hero-banner .hero-overlay h2 {
        font-size: clamp(1rem, 1.8vw, 1.5rem);
        font-weight: 800;
        margin: 0 0 .35rem;
        text-shadow: 0 2px 12px rgba(0,0,0,.55);
    }
    .hero-banner .hero-overlay p {
        font-size: clamp(.78rem, 1vw, .95rem);
        margin: 0;
        opacity: .95;
        max-width: 520px;
        text-shadow: 0 1px 6px rgba(0,0,0,.6);
    }

    /* Donut center label */
    .donut-wrap { position: relative; width: 100%; max-width: 220px; margin: 0 auto; }
    .donut-center {
        position: absolute; inset: 0;
        display: flex; flex-direction: column; align-items: center; justify-content: center;
        pointer-events: none;
    }
    .donut-center .num   { font-size: 1.5rem; font-weight: 800; color: var(--brand-navy); line-height: 1; }
    .donut-center .lbl   { font-size: .72rem; color: var(--text-muted); margin-top: 4px; }

    /* Trip / alert list */
    .list-row {
        display: flex; align-items: center; gap: .75rem;
        padding: .65rem 0;
        border-bottom: 1px solid #f3f4f6;
    }
    .list-row:last-child { border-bottom: none; }
    .list-row .icon-tile {
        width: 42px; height: 42px; border-radius: 9px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.15rem; flex-shrink: 0;
    }
    .icon-tile.hajj  { background: #dcfce7; color: #15803d; }
    .icon-tile.umrah { background: #dbeafe; color: #1d4ed8; }
    .list-row .body { flex: 1; min-width: 0; }
    .list-row .body .title { font-weight: 700; font-size: .88rem; color: var(--brand-navy); margin: 0; }
    .list-row .body .sub   { font-size: .75rem; color: var(--text-muted); margin: 0; }
    .list-row .meta { text-align: left; flex-shrink: 0; }
    .list-row .meta .time { font-size: .82rem; font-weight: 700; color: var(--brand-navy); }
    .list-row .meta .when {
        font-size: .68rem; padding: 2px 7px;
        background: #fef3c7; color: #92400e; border-radius: 6px;
        font-weight: 700;
    }
    .list-row .meta .when.urgent { background: #fee2e2; color: #b91c1c; }

    .alert-dot {
        width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0;
        margin-top: 6px;
    }
    .alert-dot.danger  { background: #ef4444; }
    .alert-dot.warning { background: #f59e0b; }
    .alert-dot.success { background: #10b981; }

    /* Hotel occupancy gauge */
    .gauge-wrap { position: relative; width: 160px; height: 160px; margin: 0 auto; }
    .gauge-center {
        position: absolute; inset: 0;
        display: flex; align-items: center; justify-content: center;
        flex-direction: column;
    }
    .gauge-center .pct { font-size: 1.6rem; font-weight: 800; color: var(--brand-navy); line-height: 1; }
    .gauge-center .lbl { font-size: .72rem; color: var(--text-muted); margin-top: 4px; }

    .occ-row {
        display: flex; align-items: center; gap: .65rem;
        padding: .35rem 0;
        font-size: .85rem;
    }
    .occ-row .city { flex: 0 0 100px; color: var(--text-primary); font-weight: 600; }
    .occ-row .bar  { flex: 1; height: 6px; background: #f1f5f9; border-radius: 3px; overflow: hidden; }
    .occ-row .bar > i { display: block; height: 100%; background: linear-gradient(90deg, #1d4ed8, #3b82f6); border-radius: 3px; }
    .occ-row .val  { flex: 0 0 40px; text-align: left; font-weight: 700; color: var(--brand-navy); }

    /* Destination row */
    .dest-row {
        display: flex; align-items: center; gap: .75rem;
        padding: .55rem 0; border-bottom: 1px solid #f3f4f6;
    }
    .dest-row:last-child { border-bottom: none; }
    .dest-row .dest-icon {
        width: 44px; height: 44px; border-radius: 9px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.2rem; flex-shrink: 0;
    }
    .dest-icon.hajj  { background: #dcfce7; color: #15803d; }
    .dest-icon.umrah { background: #dbeafe; color: #1d4ed8; }
    .dest-row .body { flex: 1; min-width: 0; }
    .dest-row .title {
        font-weight: 700; font-size: .85rem; margin: 0; color: var(--brand-navy);
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .dest-row .booking-count { font-weight: 800; color: #16a34a; font-size: .82rem; }

    /* Payment summary row */
    .pay-row {
        display: flex; justify-content: space-between; align-items: center;
        padding: .85rem;
        background: #f9fafb;
        border-radius: 10px;
        margin-bottom: .55rem;
    }
    .pay-row:last-child { margin-bottom: 0; }
    .pay-row .label { display: flex; align-items: center; gap: .55rem; font-weight: 600; font-size: .85rem; color: #374151; }
    .pay-row .val { font-weight: 800; color: var(--brand-navy); font-size: .92rem; }
    .pay-row .pay-icon {
        width: 32px; height: 32px; border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        font-size: .95rem;
    }
    .pay-icon.bg-primary-soft { background: #dbeafe; color: #1d4ed8; }
    .pay-icon.bg-success-soft { background: #d1fae5; color: #059669; }
    .pay-icon.bg-warning-soft { background: #fef3c7; color: #b45309; }
    .pay-icon.bg-danger-soft  { background: #fee2e2; color: #b91c1c; }

    /* Top sellers */
    .seller-row { display:flex; justify-content:space-between; padding:.55rem 0; border-bottom:1px dashed #e2e8f0; font-size:.85rem; }
    .seller-row:last-child { border-bottom:none; }
    .seller-row .rank {
        background:var(--brand-gold); color:#fff; width:24px; height:24px;
        border-radius:50%; display:inline-flex; align-items:center; justify-content:center;
        font-weight:800; font-size:.75rem; margin-left:.5rem;
    }
    .seller-row .rank.rank-1 { background:#d97706; }
    .seller-row .rank.rank-2 { background:#64748b; }
    .seller-row .rank.rank-3 { background:#cd7c2f; }

    /* Bg soft colors used by status badges */
    .bg-success-soft   { background:#dcfce7 !important; color:#15803d !important; }
    .bg-warning-soft   { background:#fef3c7 !important; color:#b45309 !important; }
    .bg-info-soft      { background:#dbeafe !important; color:#1d4ed8 !important; }
    .bg-secondary-soft { background:#f1f5f9 !important; color:#475569 !important; }
    .bg-danger-soft    { background:#fee2e2 !important; color:#b91c1c !important; }
    .bg-primary-soft   { background:#e0e7ff !important; color:#4338ca !important; }

    /* ── Quick Actions bar ──────────────────────────────── */
    .quick-actions {
        display: grid;
        grid-template-columns: repeat(7, minmax(0, 1fr));
        gap: .65rem;
        margin-bottom: 1rem;
    }
    .quick-actions .qa-btn {
        position: relative;
        display: flex; flex-direction: column; align-items: center; justify-content: center;
        gap: .4rem;
        padding: .85rem .6rem;
        text-decoration: none;
        border-radius: 12px;
        background: #fff;
        border: 1px solid #e5e7eb;
        color: var(--brand-navy);
        font-size: .78rem;
        font-weight: 700;
        text-align: center;
        transition: transform .15s ease, box-shadow .15s ease, border-color .15s ease;
        min-height: 84px;
    }
    .quick-actions .qa-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(15,23,42,.08);
        border-color: var(--brand-gold);
        color: var(--brand-navy);
    }
    .quick-actions .qa-btn .qa-icon {
        width: 38px; height: 38px;
        border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.1rem;
        color: #fff;
    }
    .qa-icon.qa-umrah    { background: linear-gradient(135deg,#1d4ed8,#3b82f6); }
    .qa-icon.qa-domestic { background: linear-gradient(135deg,#14b8a6,#0d9488); }
    .qa-icon.qa-customer { background: linear-gradient(135deg,#f59e0b,#d97706); }
    .qa-icon.qa-receipt  { background: linear-gradient(135deg,#10b981,#059669); }
    .qa-icon.qa-payment  { background: linear-gradient(135deg,#ef4444,#dc2626); }
    .qa-icon.qa-journal  { background: linear-gradient(135deg,#8b5cf6,#6d28d9); }
    .qa-icon.qa-reports  { background: linear-gradient(135deg,#0ea5e9,#0369a1); }

    /* ── Module Pulse strip ─────────────────────────────── */
    .module-pulse {
        display: grid;
        grid-template-columns: repeat(7, minmax(0, 1fr));
        gap: .65rem;
        margin-bottom: 1rem;
    }
    .pulse-card {
        position: relative;
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: .75rem .85rem;
        text-decoration: none;
        color: inherit;
        display: flex; align-items: center; gap: .7rem;
        transition: border-color .15s ease, transform .15s ease;
        min-height: 70px;
    }
    .pulse-card:hover {
        border-color: var(--brand-gold);
        transform: translateY(-1px);
        color: inherit;
    }
    .pulse-card .pulse-icon {
        width: 36px; height: 36px; border-radius: 9px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1rem; flex-shrink: 0;
    }
    .pulse-card .pulse-body { min-width: 0; flex: 1; }
    .pulse-card .pulse-label {
        font-size: .68rem; color: var(--text-muted); margin: 0;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .pulse-card .pulse-value {
        font-size: 1.05rem; font-weight: 800; color: var(--brand-navy); line-height: 1.1;
    }
    .pulse-card .pulse-meta {
        font-size: .68rem; color: #475569; margin-top: 2px;
    }
    .pulse-icon.pi-religious { background: #dbeafe; color: #1d4ed8; }
    .pulse-icon.pi-domestic  { background: #ccfbf1; color: #0d9488; }
    .pulse-icon.pi-crm       { background: #fce7f3; color: #be185d; }
    .pulse-icon.pi-hr        { background: #ede9fe; color: #6d28d9; }
    .pulse-icon.pi-suppliers { background: #fee2e2; color: #b91c1c; }
    .pulse-icon.pi-accounting{ background: #fef3c7; color: #92400e; }
    .pulse-icon.pi-cash      { background: #dcfce7; color: #15803d; }

    /* ── Reports quick links card ───────────────────────── */
    .rep-link {
        display: flex; align-items: center; gap: .55rem;
        padding: .55rem .65rem;
        border-radius: 8px;
        text-decoration: none;
        color: var(--brand-navy);
        font-size: .82rem;
        font-weight: 600;
        background: #f8fafc;
        transition: background .15s ease, color .15s ease;
        margin-bottom: .45rem;
    }
    .rep-link:last-child { margin-bottom: 0; }
    .rep-link:hover { background: #fef3c7; color: var(--brand-navy); }
    .rep-link i { color: var(--brand-gold); flex-shrink: 0; }

    @media (max-width: 1199.98px) {
        .quick-actions, .module-pulse { grid-template-columns: repeat(4, minmax(0, 1fr)); }
    }
    @media (max-width: 767.98px) {
        .quick-actions, .module-pulse { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .quick-actions .qa-btn { min-height: 72px; font-size: .72rem; }
        .pulse-card { min-height: 60px; }
    }

    /* ── Responsive ─────────────────────────────────────── */
    @media (max-width: 991.98px) {
        .hero-banner .hero-overlay { padding: 1rem 1.15rem; }
        .donut-wrap { max-width: 180px; }
        .gauge-wrap { width: 140px; height: 140px; }
    }
    @media (max-width: 767.98px) {
        .hero-banner { height: 140px; border-radius: 14px; }
        .hero-banner .hero-overlay h2 { font-size: .95rem; }
        .hero-banner .hero-overlay p { font-size: .72rem; }
        .donut-center .num { font-size: 1.2rem; }
        .gauge-center .pct { font-size: 1.3rem; }
        .list-row { padding: .55rem 0; gap: .55rem; }
        .list-row .icon-tile { width: 36px; height: 36px; font-size: 1rem; }
        .list-row .body .title { font-size: .82rem; }
        .list-row .body .sub   { font-size: .7rem; }
        .occ-row .city { flex-basis: 80px; font-size: .8rem; }
        .pay-row { padding: .65rem .75rem; }
    }
    @media (max-width: 575.98px) {
        .hero-banner { height: 120px; }
    }
</style>
@endpush

@section('content')

{{-- ════════════════════════════════════════════════════════════
     Hero banner
     ════════════════════════════════════════════════════════════ --}}
<div class="hero-banner">
    <div class="hero-overlay">
        <h2>
            <i class="bi bi-stars text-warning"></i>
            من العمرة إلى السياحة الخارجية
        </h2>
        <p>إدارة متكاملة لكل عمليات شركتك السياحية في منصة واحدة</p>
    </div>
</div>

{{-- ════════════════════════════════════════════════════════════
     Quick Actions bar — fastest path to common operations
     ════════════════════════════════════════════════════════════ --}}
<div class="quick-actions">
    @can('religious.bookings.create')
    <a href="{{ route('admin.religious.bookings.create') }}" class="qa-btn">
        <div class="qa-icon qa-umrah"><i class="bi bi-moon-stars-fill"></i></div>
        <span>حجز عمرة/حج</span>
    </a>
    @endcan
    @can('domestic.bookings.create')
    <a href="{{ route('admin.domestic.bookings.create') }}" class="qa-btn">
        <div class="qa-icon qa-domestic"><i class="bi bi-airplane-fill"></i></div>
        <span>حجز سياحة داخلية</span>
    </a>
    @endcan
    @can('customers.create')
    <a href="{{ route('admin.customers.create') }}" class="qa-btn">
        <div class="qa-icon qa-customer"><i class="bi bi-person-plus-fill"></i></div>
        <span>عميل جديد</span>
    </a>
    @endcan
    @can('accounting.vouchers.create')
    <a href="{{ route('admin.accounting.vouchers.receipts.create') }}" class="qa-btn">
        <div class="qa-icon qa-receipt"><i class="bi bi-cash-coin"></i></div>
        <span>سند قبض</span>
    </a>
    <a href="{{ route('admin.accounting.vouchers.payments.create') }}" class="qa-btn">
        <div class="qa-icon qa-payment"><i class="bi bi-arrow-up-circle-fill"></i></div>
        <span>سند صرف</span>
    </a>
    @endcan
    @can('accounting.journal.create')
    <a href="{{ route('admin.accounting.journal.create') }}" class="qa-btn">
        <div class="qa-icon qa-journal"><i class="bi bi-journal-text"></i></div>
        <span>قيد محاسبي</span>
    </a>
    @endcan
    @can('reports.view')
    <a href="{{ route('admin.reports.hub') }}" class="qa-btn">
        <div class="qa-icon qa-reports"><i class="bi bi-bar-chart-fill"></i></div>
        <span>التقارير</span>
    </a>
    @endcan
</div>

{{-- ════════════════════════════════════════════════════════════
     Module Pulse strip — heartbeat of every subsystem
     ════════════════════════════════════════════════════════════ --}}
<div class="module-pulse">
    @can('religious.bookings.view')
    <a href="{{ route('admin.religious.bookings.index') }}" class="pulse-card">
        <div class="pulse-icon pi-religious"><i class="bi bi-moon-stars"></i></div>
        <div class="pulse-body">
            <div class="pulse-label">السياحة الدينية</div>
            <div class="pulse-value">{{ number_format(collect($statusBreakdown)->sum('value')) }}</div>
            <div class="pulse-meta">حجز ديني</div>
        </div>
    </a>
    @endcan
    @can('domestic.bookings.view')
    <a href="{{ route('admin.domestic.bookings.index') }}" class="pulse-card">
        <div class="pulse-icon pi-domestic"><i class="bi bi-globe-asia-australia"></i></div>
        <div class="pulse-body">
            <div class="pulse-label">السياحة الداخلية</div>
            <div class="pulse-value">{{ number_format($modulePulse['domesticCount']) }}</div>
            <div class="pulse-meta">{{ $modulePulse['domesticActive'] }} نشط</div>
        </div>
    </a>
    @endcan
    @can('crm.leads.view')
    <a href="{{ route('admin.crm.leads.index') }}" class="pulse-card">
        <div class="pulse-icon pi-crm"><i class="bi bi-people-fill"></i></div>
        <div class="pulse-body">
            <div class="pulse-label">إدارة العملاء (CRM)</div>
            <div class="pulse-value">{{ number_format($modulePulse['leadsOpen']) }}</div>
            <div class="pulse-meta">{{ $modulePulse['oppsOpen'] }} فرصة مفتوحة</div>
        </div>
    </a>
    @endcan
    @can('hr.employees.view')
    <a href="{{ route('admin.hr.employees.index') }}" class="pulse-card">
        <div class="pulse-icon pi-hr"><i class="bi bi-person-badge"></i></div>
        <div class="pulse-body">
            <div class="pulse-label">الموارد البشرية</div>
            <div class="pulse-value">{{ number_format($modulePulse['employeesCount']) }}</div>
            <div class="pulse-meta">موظف نشط</div>
        </div>
    </a>
    @endcan
    @can('suppliers.view')
    <a href="{{ route('admin.suppliers.index') }}" class="pulse-card">
        <div class="pulse-icon pi-suppliers"><i class="bi bi-truck"></i></div>
        <div class="pulse-body">
            <div class="pulse-label">الموردون</div>
            <div class="pulse-value">{{ number_format($modulePulse['suppliersCount']) }}</div>
            <div class="pulse-meta">مستحق: {{ number_format($modulePulse['apOutstanding'], 0) }} ج.م</div>
        </div>
    </a>
    @endcan
    @can('accounting.journal.view')
    <a href="{{ route('admin.accounting.journal.index') }}" class="pulse-card">
        <div class="pulse-icon pi-accounting"><i class="bi bi-journal-bookmark-fill"></i></div>
        <div class="pulse-body">
            <div class="pulse-label">المحاسبة</div>
            <div class="pulse-value">{{ number_format($modulePulse['journalThisMonth']) }}</div>
            <div class="pulse-meta">قيد هذا الشهر</div>
        </div>
    </a>
    @endcan
    @can('accounting.cash.view')
    <a href="{{ route('admin.accounting.cash.index') }}" class="pulse-card">
        <div class="pulse-icon pi-cash"><i class="bi bi-bank"></i></div>
        <div class="pulse-body">
            <div class="pulse-label">الخزائن والبنوك</div>
            <div class="pulse-value">{{ number_format($modulePulse['cashOnHand'], 0) }}</div>
            <div class="pulse-meta">رصيد بالـ ج.م</div>
        </div>
    </a>
    @endcan
</div>

{{-- ════════════════════════════════════════════════════════════
     KPI stat cards row
     ════════════════════════════════════════════════════════════ --}}
<div class="row g-3 mb-3">
    @foreach($kpis as $k)
    <div class="col-xl-2 col-md-4 col-6">
        <div class="stat-card">
            <div class="stat-head">
                <div>
                    <div class="stat-label">{{ $k['label'] }}</div>
                    <div class="stat-value">{{ $k['value'] }}</div>
                </div>
                <div class="stat-icon stat-icon-{{ $k['color'] }}">
                    <i class="bi {{ $k['icon'] }}"></i>
                </div>
            </div>
            <div class="stat-foot">
                @if($k['trend'])<span class="trend-up">{{ $k['trend'] }}</span>@endif
                <span>{{ $k['note'] }}</span>
            </div>
        </div>
    </div>
    @endforeach
</div>

{{-- ════════════════════════════════════════════════════════════
     Charts row: trend + donut + upcoming + alerts
     ════════════════════════════════════════════════════════════ --}}
<div class="row g-3 mb-3">

    {{-- Line chart: bookings + revenue trend --}}
    <div class="col-xl-4 col-lg-6">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6>اتجاه الحجوزات والإيرادات</h6>
                <span class="badge bg-light text-dark small">آخر 12 شهر</span>
            </div>
            <div class="card-body">
                <canvas id="bookingsTrend" height="200"></canvas>
            </div>
        </div>
    </div>

    {{-- Donut chart: status distribution --}}
    <div class="col-xl-3 col-lg-6">
        <div class="card h-100">
            <div class="card-header">
                <h6>توزيع الحجوزات حسب الحالة</h6>
            </div>
            <div class="card-body">
                <div class="donut-wrap">
                    <canvas id="bookingsStatus" height="200"></canvas>
                    <div class="donut-center">
                        <div class="num">{{ number_format(collect($statusBreakdown)->sum('value')) }}</div>
                        <div class="lbl">إجمالي الحجوزات</div>
                    </div>
                </div>
                <ul class="list-unstyled small mt-3 mb-0">
                    @foreach($statusBreakdown as $s)
                    <li class="d-flex justify-content-between align-items-center mb-1">
                        <span><i class="bi bi-circle-fill me-1" style="color: {{ $s['color'] }};"></i> {{ $s['label'] }}</span>
                        <span class="text-muted">{{ $s['pct'] }}% ({{ number_format($s['value']) }})</span>
                    </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>

    {{-- Upcoming trips --}}
    <div class="col-xl-3 col-lg-6">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6><i class="bi bi-airplane text-primary me-1"></i> الرحلات القادمة</h6>
                <a href="{{ route('admin.religious.bookings.index') }}" class="small text-muted">الكل ←</a>
            </div>
            <div class="card-body">
                @forelse($upcomingTrips as $t)
                <a href="{{ $t['url'] }}" class="text-decoration-none">
                    <div class="list-row">
                        <div class="icon-tile {{ $t['icon'] === 'mosque' ? 'hajj' : 'umrah' }}">
                            <i class="bi bi-{{ $t['icon'] }}"></i>
                        </div>
                        <div class="body">
                            <p class="title">{{ $t['customer'] }}</p>
                            <p class="sub">{{ $t['booking_no'] }} • {{ $t['destination'] }}</p>
                        </div>
                        <div class="meta">
                            <div class="time">{{ $t['date'] }}</div>
                            <div class="when {{ $t['urgent'] ? 'urgent' : '' }}">{{ $t['when'] }}</div>
                        </div>
                    </div>
                </a>
                @empty
                <div class="text-center text-muted py-3">
                    <i class="bi bi-calendar-x" style="font-size:2rem; opacity:.4;"></i>
                    <div class="mt-2 small">لا توجد رحلات قادمة</div>
                </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Alerts --}}
    <div class="col-xl-2 col-lg-6">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6><i class="bi bi-bell text-warning me-1"></i> التنبيهات</h6>
                @can('religious.alerts.view')
                <a href="{{ route('admin.religious.alerts.index') }}" class="small text-muted">الكل ←</a>
                @endcan
            </div>
            <div class="card-body">
                @forelse($alerts as $a)
                <div class="d-flex gap-2 mb-3">
                    <span class="alert-dot {{ $a['level'] }}"></span>
                    <div style="min-width:0;">
                        <div class="fw-bold small" style="color: var(--brand-navy);">{{ $a['title'] }}</div>
                        <div class="text-muted" style="font-size: .7rem; text-overflow:ellipsis; overflow:hidden; white-space:nowrap;">{{ $a['note'] }}</div>
                    </div>
                </div>
                @empty
                <div class="text-center text-muted py-3">
                    <i class="bi bi-check-circle text-success" style="font-size:1.8rem;"></i>
                    <div class="mt-2 small">لا توجد تنبيهات</div>
                </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

{{-- ════════════════════════════════════════════════════════════
     Bottom row: hotel occupancy + top programs + payments + latest bookings
     ════════════════════════════════════════════════════════════ --}}
<div class="row g-3">

    {{-- Hotel occupancy --}}
    <div class="col-xl-3 col-lg-6">
        <div class="card h-100">
            <div class="card-header"><h6><i class="bi bi-building text-primary me-1"></i> توزيع السكن</h6></div>
            <div class="card-body">
                <div class="gauge-wrap">
                    <canvas id="hotelGauge" height="160"></canvas>
                    <div class="gauge-center">
                        <div class="pct">{{ $avgOccupancy }}%</div>
                        <div class="lbl">متوسط الإشغال</div>
                    </div>
                </div>
                <div class="mt-3">
                    @forelse($hotelOccupancy as $h)
                    <div class="occ-row">
                        <div class="city">{{ $h['city'] }}</div>
                        <div class="bar"><i style="width: {{ $h['pct'] }}%;"></i></div>
                        <div class="val">{{ $h['pct'] }}%</div>
                    </div>
                    @empty
                    <div class="text-center text-muted py-3 small">لا توجد بيانات سكن بعد</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- Top programs --}}
    <div class="col-xl-3 col-lg-6">
        <div class="card h-100">
            <div class="card-header"><h6><i class="bi bi-trophy-fill text-warning me-1"></i> أفضل البرامج</h6></div>
            <div class="card-body">
                @forelse($topDestinations as $d)
                <div class="dest-row">
                    <div class="dest-icon {{ $d->type === 'hajj' ? 'hajj' : 'umrah' }}">
                        <i class="bi bi-{{ $d->type === 'hajj' ? 'mosque' : 'moon-stars' }}"></i>
                    </div>
                    <div class="body">
                        <p class="title">{{ $d->name }}</p>
                        <div class="text-muted small">
                            {{ number_format($d->bookings) }} <span class="badge bg-light text-dark">حجز</span>
                            <span class="booking-count" style="margin-right:.5rem;">{{ number_format($d->revenue / 1000, 1) }}k ج.م</span>
                        </div>
                    </div>
                </div>
                @empty
                <div class="text-center text-muted py-3 small">لا توجد برامج محجوزة بعد</div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Payments summary --}}
    <div class="col-xl-3 col-lg-6">
        <div class="card h-100">
            <div class="card-header"><h6><i class="bi bi-wallet2 text-warning me-1"></i> ملخص المدفوعات</h6></div>
            <div class="card-body">
                @foreach($payments as $p)
                <div class="pay-row">
                    <div class="label">
                        <span class="pay-icon bg-{{ $p['color'] }}-soft"><i class="bi {{ $p['icon'] }}"></i></span>
                        {{ $p['label'] }}
                    </div>
                    <div class="val">
                        {{ $p['value'] }} <small class="text-muted">ج.م</small>
                    </div>
                </div>
                @endforeach

                <div class="text-center mt-3">
                    @can('religious.reports')
                    <a href="{{ route('admin.religious.reports.trips') }}" class="text-primary text-decoration-none small fw-bold">
                        <i class="bi bi-arrow-left-short"></i> عرض التقارير الكاملة
                    </a>
                    @endcan
                </div>
            </div>
        </div>
    </div>

    {{-- Top sellers --}}
    <div class="col-xl-3 col-lg-6">
        <div class="card h-100">
            <div class="card-header">
                <h6><i class="bi bi-people text-success me-1"></i> أعلى البائعين (30 يوم)</h6>
            </div>
            <div class="card-body">
                @forelse($topSellers as $i => $seller)
                <div class="seller-row">
                    <div>
                        <span class="rank rank-{{ $i + 1 }}">{{ $i + 1 }}</span>
                        <strong>{{ $seller->name }}</strong>
                        <div class="small text-muted mt-1" style="padding-right:32px;">{{ $seller->bookings_count }} حجز</div>
                    </div>
                    <div class="text-end">
                        <strong>{{ number_format($seller->revenue, 0) }}</strong>
                        <div class="small text-muted">ج.م</div>
                    </div>
                </div>
                @empty
                <div class="text-center text-muted py-3 small">لا توجد بيانات بعد</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

{{-- Reports quick access + Latest bookings --}}
<div class="row g-3 mt-1">
    @can('reports.view')
    <div class="col-xl-4 col-lg-12">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="bi bi-bar-chart-fill text-warning me-1"></i> التقارير السريعة</h6>
                <a href="{{ route('admin.reports.hub') }}" class="small text-muted">مركز التقارير ←</a>
            </div>
            <div class="card-body">
                <a href="{{ route('admin.accounting.reports.pnl') }}" class="rep-link">
                    <i class="bi bi-graph-up-arrow"></i> قائمة الأرباح والخسائر (P&L)
                </a>
                <a href="{{ route('admin.accounting.reports.trial_balance') }}" class="rep-link">
                    <i class="bi bi-list-columns"></i> ميزان المراجعة
                </a>
                <a href="{{ route('admin.accounting.reports.general_ledger') }}" class="rep-link">
                    <i class="bi bi-book"></i> الأستاذ العام
                </a>
                @can('suppliers.view')
                <a href="{{ route('admin.suppliers.aging') }}" class="rep-link">
                    <i class="bi bi-hourglass-split"></i> أعمار ديون الموردين
                </a>
                @endcan
                @can('reports.view')
                <a href="{{ route('admin.reports.analytics.monthly_profitability') }}" class="rep-link">
                    <i class="bi bi-calendar-week"></i> الربحية الشهرية
                </a>
                <a href="{{ route('admin.reports.analytics.sales_performance') }}" class="rep-link">
                    <i class="bi bi-speedometer2"></i> أداء المبيعات
                </a>
                <a href="{{ route('admin.reports.analytics.outstanding_payments') }}" class="rep-link">
                    <i class="bi bi-cash-stack"></i> المستحقات المتأخرة
                </a>
                @endcan
            </div>
        </div>
    </div>
    @endcan

    <div class="@can('reports.view') col-xl-8 col-lg-12 @else col-12 @endcan">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="bi bi-calendar-check text-primary me-1"></i> أحدث الحجوزات</h6>
                <a href="{{ route('admin.religious.bookings.index') }}" class="small text-muted">عرض الكل ←</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table pretty-table mb-0">
                        <thead>
                            <tr>
                                <th>رقم الحجز</th>
                                <th>العميل</th>
                                <th>النوع / البرنامج</th>
                                <th>تاريخ السفر</th>
                                <th>الحالة</th>
                                <th class="text-end">المبلغ</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($latestBookings as $b)
                            <tr>
                                <td><a href="{{ $b['url'] }}"><code class="small">{{ $b['ref'] }}</code></a></td>
                                <td><strong class="small">{{ $b['customer'] }}</strong></td>
                                <td>
                                    <span class="badge bg-light text-dark">{{ $b['destination'] }}</span>
                                    <span class="small text-muted">{{ $b['service'] }}</span>
                                </td>
                                <td class="small">{{ $b['date'] }}</td>
                                <td><span class="badge bg-{{ $b['badge'] }}-soft">{{ $b['status'] }}</span></td>
                                <td class="fw-bold text-nowrap text-end">{{ $b['amount'] }} <small class="text-muted">ج.م</small></td>
                            </tr>
                            @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">لا توجد حجوزات بعد</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
$(function () {

    // ── Line chart: bookings trend (last 12 months) ──────────────
    new Chart(document.getElementById('bookingsTrend'), {
        type: 'line',
        data: {
            labels: @json($months),
            datasets: [
                {
                    label: 'الحجوزات',
                    data: @json($trend),
                    borderColor: '#1d4ed8',
                    backgroundColor: ctx => {
                        const g = ctx.chart.ctx.createLinearGradient(0, 0, 0, 220);
                        g.addColorStop(0, 'rgba(29,78,216,.20)');
                        g.addColorStop(1, 'rgba(29,78,216,0)');
                        return g;
                    },
                    borderWidth: 2.5,
                    tension: 0.35,
                    fill: true,
                    pointBackgroundColor: '#1d4ed8',
                    pointRadius: 3,
                    yAxisID: 'y'
                },
                {
                    label: 'الإيرادات (ألف ج.م)',
                    data: @json(array_map(fn($v) => round($v / 1000, 1), $revenueTrend)),
                    borderColor: '#d4a437',
                    borderWidth: 2,
                    tension: 0.35,
                    fill: false,
                    pointBackgroundColor: '#d4a437',
                    pointRadius: 2,
                    yAxisID: 'y1',
                    borderDash: [4, 4]
                }
            ]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: {
                legend: { display: true, position: 'bottom', labels: { font: { size: 11 } } },
                tooltip: { rtl: true, bodyAlign: 'right', titleAlign: 'right' }
            },
            scales: {
                y:  { position: 'right', grid: { color: '#f1f5f9' }, ticks: { color: '#1d4ed8', font: { size: 10 } } },
                y1: { position: 'left',  grid: { display: false   }, ticks: { color: '#d4a437', font: { size: 10 } } },
                x:  { grid: { display: false }, ticks: { color: '#6b7280', font: { size: 10 } } }
            }
        }
    });

    // ── Donut chart: bookings status ─────────────────────────────
    new Chart(document.getElementById('bookingsStatus'), {
        type: 'doughnut',
        data: {
            labels: @json(collect($statusBreakdown)->pluck('label')),
            datasets: [{
                data: @json(collect($statusBreakdown)->pluck('value')),
                backgroundColor: @json(collect($statusBreakdown)->pluck('color')),
                borderWidth: 0,
                cutout: '72%'
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: { rtl: true, bodyAlign: 'right', titleAlign: 'right' }
            }
        }
    });

    // ── Hotel gauge ──────────────────────────────────────────────
    const pct = {{ $avgOccupancy }};
    new Chart(document.getElementById('hotelGauge'), {
        type: 'doughnut',
        data: {
            datasets: [{
                data: [pct, 100 - pct],
                backgroundColor: ['#1d4ed8', '#e5e7eb'],
                borderWidth: 0,
                cutout: '78%',
                circumference: 360,
                rotation: -90
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false }, tooltip: { enabled: false } }
        }
    });
});
</script>
@endpush
