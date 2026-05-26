@extends('layouts.master')

@section('title', $customer->full_name)
@section('page_title', 'الملف الشخصي للعميل')
@section('page_subtitle', 'عرض شامل لجميع بيانات العميل وسجل تعاملاته')

@push('styles')
<style>
    /* ════════════════════════════════════════════════════════════
       Customer Profile — professional ERP layout (HubSpot/Salesforce style)
       ════════════════════════════════════════════════════════════ */

    /* ── Page breadcrumb bar ────────────────────────────────── */
    .cust-breadcrumb {
        display: flex; align-items: center; justify-content: space-between;
        gap: .75rem; flex-wrap: wrap;
        padding: .85rem 1.15rem;
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 1px 3px rgba(15,23,42,.04);
        margin-bottom: 1rem;
    }
    .cust-breadcrumb .crumb-trail {
        display: flex; align-items: center; gap: .35rem;
        font-size: .82rem; color: var(--text-muted); flex-wrap: wrap;
    }
    .cust-breadcrumb .crumb-trail a {
        color: var(--text-muted); text-decoration: none; display: inline-flex; align-items: center; gap: .25rem;
    }
    .cust-breadcrumb .crumb-trail a:hover { color: var(--brand-navy); }
    .cust-breadcrumb .crumb-trail .sep { color: #cbd5e1; }
    .cust-breadcrumb .crumb-trail .current {
        color: var(--brand-navy); font-weight: 700;
        background: #f1f5f9; padding: .15rem .55rem; border-radius: 6px;
        font-family: 'Cairo', monospace; font-size: .76rem;
    }
    .cust-breadcrumb .crumb-actions { display: flex; gap: .5rem; flex-wrap: wrap; }
    .cust-breadcrumb .crumb-actions .btn {
        font-size: .82rem; padding: .45rem .9rem;
        display: inline-flex; align-items: center; gap: .35rem;
    }

    /* ── Identity Card (clean, no overlap tricks) ───────────── */
    .cust-identity {
        background: #fff;
        border-radius: 14px;
        box-shadow: 0 1px 4px rgba(15,23,42,.05);
        padding: 1.5rem 1.5rem 1.25rem;
        margin-bottom: 1rem;
        position: relative;
        overflow: hidden;
    }
    .cust-identity::before {
        content: '';
        position: absolute;
        top: 0; right: 0; left: 0;
        height: 92px;
        background: linear-gradient(135deg, #0c2461 0%, #1e3a8a 55%, #1e40af 100%);
        z-index: 0;
    }
    .cust-identity::after {
        content: '';
        position: absolute;
        top: 0; right: 0; left: 0;
        height: 92px;
        background:
            radial-gradient(circle at 88% 25%, rgba(212,164,55,.25) 0%, transparent 50%),
            radial-gradient(circle at 12% 75%, rgba(255,255,255,.08) 0%, transparent 45%);
        z-index: 1;
    }
    .cust-identity-row {
        position: relative; z-index: 2;
        display: flex; align-items: flex-end; gap: 1.5rem;
        flex-wrap: wrap;
    }
    .cust-avatar {
        width: 110px; height: 110px;
        border-radius: 50%;
        object-fit: cover;
        border: 4px solid #fff;
        box-shadow: 0 0 0 3px var(--brand-gold), 0 6px 18px rgba(0,0,0,.18);
        background: #f1f5f9;
        flex-shrink: 0;
        margin-top: 12px;
    }
    .cust-main { flex: 1; min-width: 240px; padding-top: 56px; }
    .cust-main h2 {
        margin: 0 0 .25rem;
        font-size: 1.55rem; font-weight: 800;
        color: var(--brand-navy);
        line-height: 1.2;
    }
    .cust-main .en-name {
        color: var(--text-muted);
        font-size: .85rem;
        margin-bottom: .55rem;
        direction: ltr; text-align: right;
        font-weight: 500;
    }
    .cust-main .id-row {
        display: flex; gap: .4rem; flex-wrap: wrap; align-items: center;
    }
    .cust-main .code-chip {
        display: inline-flex; align-items: center; gap: .3rem;
        background: #f8f9fc;
        border: 1px solid var(--brand-border);
        padding: .3rem .65rem;
        border-radius: 7px;
        font-family: 'Cairo', monospace;
        font-size: .78rem;
        color: var(--brand-navy);
        font-weight: 700;
    }
    .cust-main .id-row .badge {
        font-size: .72rem; padding: .35rem .6rem; font-weight: 600;
        display: inline-flex; align-items: center; gap: .3rem;
    }

    /* Quick action pills */
    .cust-quick-actions {
        display: flex; gap: .45rem; flex-wrap: wrap;
        padding-top: 56px;
    }
    .quick-btn {
        display: inline-flex; align-items: center; gap: .4rem;
        padding: .55rem .85rem;
        border-radius: 9px;
        font-weight: 600;
        font-size: .8rem;
        text-decoration: none;
        transition: all .15s;
        border: 1px solid transparent;
    }
    .quick-btn.whatsapp { background: #dcfce7; color: #15803d; border-color: #bbf7d0; }
    .quick-btn.whatsapp:hover { background: #bbf7d0; color: #14532d; transform: translateY(-1px); }
    .quick-btn.call     { background: #dbeafe; color: #1d4ed8; border-color: #bfdbfe; }
    .quick-btn.call:hover { background: #bfdbfe; color: #1e3a8a; transform: translateY(-1px); }
    .quick-btn.email    { background: #f3e8ff; color: #6b21a8; border-color: #e9d5ff; }
    .quick-btn.email:hover { background: #e9d5ff; color: #581c87; transform: translateY(-1px); }

    /* ── KPI stats row ──────────────────────────────────────── */
    .kpi-row {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: .85rem;
        margin-bottom: 1rem;
    }
    @media (max-width: 991.98px) { .kpi-row { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 480px)    { .kpi-row { grid-template-columns: 1fr; } }

    .kpi-card {
        background: #fff;
        border-radius: 12px;
        padding: 1rem 1.15rem;
        display: flex; align-items: center; gap: .85rem;
        box-shadow: 0 1px 3px rgba(15,23,42,.04);
        border-right: 3px solid;
        transition: all .15s;
    }
    .kpi-card:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(15,23,42,.07); }
    .kpi-card.k-blue   { border-color: #1d4ed8; }
    .kpi-card.k-green  { border-color: #15803d; }
    .kpi-card.k-gold   { border-color: #c9a227; }
    .kpi-card.k-red    { border-color: #b91c1c; }

    .kpi-icon {
        width: 44px; height: 44px;
        border-radius: 11px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.25rem; flex-shrink: 0;
    }
    .kpi-body { flex: 1; min-width: 0; }
    .kpi-body .lbl { font-size: .74rem; color: var(--text-muted); margin-bottom: 3px; font-weight: 500; }
    .kpi-body .val {
        font-size: 1.3rem; font-weight: 800; color: var(--brand-navy); line-height: 1;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .kpi-body .val small { font-size: .7rem; font-weight: 500; color: var(--text-muted); }

    /* ── Two-column layout ──────────────────────────────────── */
    .cust-layout { display: grid; grid-template-columns: minmax(0, 1fr) 320px; gap: 1rem; align-items: start; }
    @media (max-width: 1199.98px) { .cust-layout { grid-template-columns: 1fr; } }

    /* ── Tab nav ─────────────────────────────────────────────── */
    .show-tabs {
        display: flex; gap: .25rem;
        background: #fff;
        border-radius: 12px;
        padding: .35rem;
        box-shadow: 0 1px 3px rgba(15,23,42,.04);
        margin-bottom: 1rem;
        overflow-x: auto;
        flex-wrap: nowrap;
    }
    .show-tabs button {
        background: transparent; border: none;
        padding: .65rem 1.1rem;
        color: var(--text-muted);
        font-weight: 600;
        font-size: .87rem;
        border-radius: 8px;
        cursor: pointer;
        white-space: nowrap;
        transition: all .15s;
        display: inline-flex; align-items: center; gap: .4rem;
    }
    .show-tabs button:hover { color: var(--brand-navy); background: #f8fafc; }
    .show-tabs button.active {
        background: var(--brand-navy);
        color: #fff;
        box-shadow: 0 2px 8px rgba(15,23,42,.15);
    }
    .show-tabs button .tab-count {
        background: rgba(255,255,255,.18);
        padding: 0 .45rem; border-radius: 999px;
        font-size: .7rem; font-weight: 700;
        min-width: 18px; text-align: center;
    }
    .show-tabs button:not(.active) .tab-count {
        background: #f1f5f9; color: var(--brand-navy);
    }
    .show-pane { display: none; animation: fadeIn .2s; }
    .show-pane.active { display: block; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(4px); } to { opacity: 1; transform: none; } }

    /* Booking sub-tabs (Religious / Domestic) */
    .book-subpane { display: none; animation: fadeIn .2s; }
    .book-subpane.active { display: block; }

    /* ── Info list (key-value pairs) ────────────────────────── */
    .info-list { list-style: none; padding: 0; margin: 0; }
    .info-list li {
        display: flex; gap: .85rem;
        padding: .8rem 0;
        border-bottom: 1px dashed #f1f5f9;
        font-size: .9rem;
    }
    .info-list li:last-child { border-bottom: none; }
    .info-list .ic {
        width: 36px; height: 36px;
        background: #f8f9fc;
        border-radius: 9px;
        display: inline-flex; align-items: center; justify-content: center;
        flex-shrink: 0;
        color: var(--brand-navy);
        font-size: 1rem;
    }
    .info-list .lbl {
        color: var(--text-muted);
        font-size: .76rem;
        margin-bottom: 2px;
        font-weight: 500;
    }
    .info-list .val {
        color: var(--text-primary);
        font-weight: 700;
        font-size: .9rem;
        word-break: break-word;
    }
    .info-list .val.empty { color: #cbd5e1; font-weight: 500; }

    /* ── Sidebar card (right column) ────────────────────────── */
    .side-card {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 1px 3px rgba(15,23,42,.04);
        margin-bottom: 1rem;
        overflow: hidden;
    }
    .side-card .side-head {
        padding: .85rem 1.1rem;
        border-bottom: 1px solid var(--brand-border);
        display: flex; align-items: center; gap: .5rem;
        color: var(--brand-navy); font-weight: 700; font-size: .9rem;
    }
    .side-card .side-head i { color: var(--brand-gold); font-size: 1.05rem; }
    .side-card .side-body { padding: 1rem 1.1rem; }
    .side-card .side-body.compact { padding: .35rem 1.1rem 1rem; }

    .side-meta {
        display: grid; grid-template-columns: 1fr; gap: .65rem;
    }
    .side-meta-item {
        display: flex; align-items: flex-start; gap: .65rem;
        padding: .55rem 0;
        border-bottom: 1px dashed #f1f5f9;
    }
    .side-meta-item:last-child { border-bottom: none; }
    .side-meta-item .ic-sm {
        width: 32px; height: 32px;
        border-radius: 8px;
        background: #f8f9fc;
        display: flex; align-items: center; justify-content: center;
        color: var(--brand-navy); font-size: .92rem;
        flex-shrink: 0;
    }
    .side-meta-item .meta-text { flex: 1; min-width: 0; }
    .side-meta-item .meta-text .lbl { font-size: .72rem; color: var(--text-muted); margin-bottom: 1px; }
    .side-meta-item .meta-text .val { font-size: .85rem; font-weight: 700; color: var(--text-primary); word-break: break-word; }
    .side-meta-item .meta-text .val.empty { color: #cbd5e1; font-weight: 500; }

    /* Passport status banner */
    .pass-status {
        padding: 1rem 1.25rem;
        border-radius: 12px;
        display: flex; align-items: center; gap: 1rem;
        margin-bottom: 1rem;
    }
    .pass-status.ok      { background: #dcfce7; color: #14532d; }
    .pass-status.warn    { background: #fef3c7; color: #78350f; }
    .pass-status.danger  { background: #fee2e2; color: #7f1d1d; }
    .pass-status .ps-icon {
        width: 44px; height: 44px;
        background: rgba(255,255,255,.55);
        border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.3rem;
        flex-shrink: 0;
    }
    .pass-status .ps-msg { font-weight: 700; font-size: .95rem; }
    .pass-status .ps-sub { font-size: .78rem; opacity: .85; }

    /* attachment cards */
    .doc-card {
        background: #fff;
        border: 1px solid var(--brand-border);
        border-radius: 12px;
        overflow: hidden;
        transition: all .15s;
    }
    .doc-card:hover { transform: translateY(-2px); box-shadow: 0 6px 18px rgba(15,23,42,.07); }
    .doc-card img {
        width: 100%; aspect-ratio: 4/3; object-fit: cover;
        background: #f1f5f9;
        display: block;
    }
    .doc-card .doc-foot {
        padding: .65rem .85rem;
        display: flex; justify-content: space-between; align-items: center;
        background: #fafbff;
        border-top: 1px solid var(--brand-border);
        font-size: .82rem;
        font-weight: 600;
        color: var(--brand-navy);
    }
    .doc-empty {
        text-align: center;
        padding: 3rem 1rem;
        color: var(--text-muted);
    }
    .doc-empty i { font-size: 3rem; opacity: .35; }

    /* Placeholder box */
    .placeholder-box {
        text-align: center;
        padding: 3rem 1.5rem;
        background: #fafbff;
        border: 2px dashed var(--brand-border);
        border-radius: 14px;
    }
    .placeholder-box i { font-size: 3rem; color: #cbd5e1; }
    .placeholder-box h6 { font-weight: 800; color: var(--brand-navy); margin: 1rem 0 .35rem; }
    .placeholder-box p { color: var(--text-muted); font-size: .85rem; margin: 0; }

    /* Mini stat (inside booking pane) */
    .mini-stat-box {
        background: #fff; border-radius: 10px; padding: .85rem;
        text-align: center; box-shadow: 0 1px 3px rgba(15,23,42,.04);
    }
    .mini-stat-box .lbl { font-size: .72rem; color: var(--text-muted); margin-bottom: .35rem; }
    .mini-stat-box .val { font-size: 1.25rem; font-weight: 800; line-height: 1; color: var(--brand-navy); }

    /* Print friendly */
    @media print {
        .topbar, .sidebar, .cust-breadcrumb, .cust-quick-actions, .show-tabs { display: none !important; }
        .cust-identity::before, .cust-identity::after { display: none; }
        .cust-identity { padding: 1rem !important; }
        .cust-main { padding-top: 0 !important; }
        .cust-avatar { margin-top: 0 !important; }
        .show-pane { display: block !important; page-break-inside: avoid; }
        .cust-layout { grid-template-columns: 1fr !important; }
    }

    /* ── Responsive ─────────────────────────────────────────── */
    @media (max-width: 991.98px) {
        .cust-identity { padding: 1.25rem 1.15rem 1rem; }
        .cust-identity-row { gap: 1rem; }
        .cust-main { padding-top: 48px; }
        .cust-quick-actions { padding-top: 0; }
        .cust-avatar { width: 90px; height: 90px; }
        .cust-main h2 { font-size: 1.3rem; }
    }
    @media (max-width: 767.98px) {
        .cust-identity::before, .cust-identity::after { height: 82px; }
        .cust-identity-row { flex-direction: column; align-items: stretch; text-align: center; }
        .cust-avatar { margin: 12px auto 0; width: 100px; height: 100px; }
        .cust-main { padding-top: 0; text-align: center; }
        .cust-main .en-name { text-align: center; }
        .cust-main .id-row { justify-content: center; }
        .cust-quick-actions { justify-content: center; padding-top: 0; }
        .quick-btn { padding: .5rem .75rem; font-size: .78rem; }

        .cust-breadcrumb { padding: .75rem 1rem; }
        .cust-breadcrumb .crumb-actions .btn { font-size: .76rem; padding: .4rem .7rem; }
        .cust-breadcrumb .crumb-actions .btn span { display: none; }
        .cust-breadcrumb .crumb-actions .btn i { margin: 0 !important; }

        .kpi-card { padding: .85rem; gap: .65rem; }
        .kpi-icon { width: 38px; height: 38px; font-size: 1.1rem; }
        .kpi-body .lbl { font-size: .7rem; }
        .kpi-body .val { font-size: 1.1rem; }

        .show-tabs { padding: .25rem; gap: .15rem; }
        .show-tabs button { padding: .55rem .75rem; font-size: .8rem; gap: .3rem; }
    }
    @media (max-width: 575.98px) {
        .cust-main h2 { font-size: 1.15rem; }
        .cust-main .id-row .badge { font-size: .68rem; padding: .25rem .5rem; }
        .quick-btn { flex: 1; min-width: 0; justify-content: center; }
        .info-list li { padding: .65rem 0; gap: .65rem; font-size: .85rem; }
        .info-list .ic { width: 32px; height: 32px; font-size: .9rem; }
    }
</style>
@endpush

@section('content')

{{-- ════════════════════════════════════════════════════════════
     1) Top Breadcrumb Bar — slim, useful, replaces the empty hero
     ════════════════════════════════════════════════════════════ --}}
<div class="cust-breadcrumb">
    <div class="crumb-trail">
        <a href="{{ route('admin.dashboard') }}"><i class="bi bi-house-door"></i> الرئيسية</a>
        <span class="sep">/</span>
        <a href="{{ route('admin.customers.index') }}"><i class="bi bi-people"></i> العملاء</a>
        <span class="sep">/</span>
        <span class="current">{{ $customer->code }}</span>
    </div>
    <div class="crumb-actions">
        @can('customers.update')
        <a href="{{ route('admin.customers.edit', $customer) }}" class="btn btn-gold btn-sm">
            <i class="bi bi-pencil-square"></i> <span>تعديل البيانات</span>
        </a>
        @endcan
        <button class="btn btn-outline-secondary btn-sm" onclick="window.print()">
            <i class="bi bi-printer"></i> <span>طباعة</span>
        </button>
        <a href="{{ route('admin.customers.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-right"></i> <span>العودة</span>
        </a>
    </div>
</div>

{{-- ════════════════════════════════════════════════════════════
     2) Identity Card — avatar, name, badges, quick contact actions
     ════════════════════════════════════════════════════════════ --}}
<div class="cust-identity">
    <div class="cust-identity-row">
        <img src="{{ $customer->photo_url }}" class="cust-avatar" alt=""
             onerror="this.onerror=null;this.src='data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%22110%22 height=%22110%22><rect width=%22100%25%22 height=%22100%25%22 fill=%22%23eef0f5%22/><text x=%2250%25%22 y=%2255%25%22 text-anchor=%22middle%22 font-family=%22Arial%22 font-size=%2240%22 fill=%22%2394a3b8%22>{{ mb_substr($customer->full_name, 0, 1) }}</text></svg>';">

        <div class="cust-main">
            <h2>{{ $customer->full_name }}</h2>
            @if($customer->full_name_en)
                <div class="en-name">{{ $customer->full_name_en }}</div>
            @endif
            <div class="id-row">
                <span class="code-chip"><i class="bi bi-hash"></i> {{ $customer->code }}</span>
                <span class="badge bg-{{ $customer->status_badge }}-soft">
                    <i class="bi bi-circle-fill" style="font-size:.5rem;"></i> {{ $customer->status_label }}
                </span>
                <span class="badge type-{{ $customer->type }}">
                    <i class="bi bi-{{ $customer->type === 'agency' ? 'briefcase' : ($customer->type === 'group' ? 'people' : 'person') }}"></i>
                    {{ $customer->type_label }}
                </span>
                @if($customer->nationality)
                    <span class="badge bg-secondary-soft"><i class="bi bi-flag"></i> {{ $customer->nationality }}</span>
                @endif
                @if($customer->city)
                    <span class="badge bg-secondary-soft"><i class="bi bi-geo-alt"></i> {{ $customer->city }}</span>
                @endif
            </div>
        </div>

        <div class="cust-quick-actions">
            @if($customer->whatsapp)
                <a class="quick-btn whatsapp" href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $customer->whatsapp) }}" target="_blank">
                    <i class="bi bi-whatsapp"></i> واتساب
                </a>
            @endif
            @if($customer->phone)
                <a class="quick-btn call" href="tel:{{ $customer->phone }}">
                    <i class="bi bi-telephone"></i> اتصال
                </a>
            @endif
            @if($customer->email)
                <a class="quick-btn email" href="mailto:{{ $customer->email }}">
                    <i class="bi bi-envelope"></i> بريد
                </a>
            @endif
        </div>
    </div>
</div>

{{-- ════════════════════════════════════════════════════════════
     3) KPI Stats Row
     ════════════════════════════════════════════════════════════ --}}
@php
    // ── Combined stats across Religious + Domestic bookings ─────────
    $religiousBookings = \App\Models\ReligiousBooking::query()
        ->where('customer_id', $customer->id)
        ->with(['program:id,name,type'])
        ->latest('trip_date')
        ->limit(100)
        ->get();

    $domesticBookings = \App\Models\DomesticBooking::query()
        ->where('customer_id', $customer->id)
        ->with(['program:id,name'])
        ->latest('trip_date')
        ->limit(100)
        ->get();

    $allBookings  = $religiousBookings->count() + $domesticBookings->count();
    $allCompleted = $religiousBookings->where('status','completed')->count()
                  + $domesticBookings->where('status','completed')->count();

    $religiousRevenue = $religiousBookings->where('status','!=','cancelled')->sum('selling_price');
    $domesticRevenue  = $domesticBookings->where('status','!=','cancelled')->sum('selling_price');
    $totalSpent       = $customer->total_spent ?? ($religiousRevenue + $domesticRevenue);
    $balance          = $customer->balance ?? 0;

    $totalBookings  = $customer->bookings_count ?? $allBookings;
    $completedTrips = $customer->completed_trips_count ?? $allCompleted;
@endphp
<div class="kpi-row">
    <div class="kpi-card k-blue">
        <div class="kpi-icon" style="background:#dbeafe;color:#1d4ed8;"><i class="bi bi-calendar-check"></i></div>
        <div class="kpi-body">
            <div class="lbl">إجمالي الحجوزات</div>
            <div class="val">{{ number_format($totalBookings) }}</div>
        </div>
    </div>
    <div class="kpi-card k-green">
        <div class="kpi-icon" style="background:#dcfce7;color:#15803d;"><i class="bi bi-airplane"></i></div>
        <div class="kpi-body">
            <div class="lbl">رحلات مكتملة</div>
            <div class="val">{{ number_format($completedTrips) }}</div>
        </div>
    </div>
    <div class="kpi-card k-gold">
        <div class="kpi-icon" style="background:#fef3c7;color:#b45309;"><i class="bi bi-cash-stack"></i></div>
        <div class="kpi-body">
            <div class="lbl">إجمالي المبالغ</div>
            <div class="val">{{ number_format($totalSpent) }} <small>ر.س</small></div>
        </div>
    </div>
    <div class="kpi-card k-{{ $balance >= 0 ? 'green' : 'red' }}">
        <div class="kpi-icon" style="background:{{ $balance >= 0 ? '#dcfce7' : '#fee2e2' }};color:{{ $balance >= 0 ? '#15803d' : '#b91c1c' }};">
            <i class="bi bi-wallet2"></i>
        </div>
        <div class="kpi-body">
            <div class="lbl">الرصيد الحالي</div>
            <div class="val">{{ number_format($balance) }} <small>ر.س</small></div>
        </div>
    </div>
</div>

{{-- ════════════════════════════════════════════════════════════
     4) Two-column layout: main tabs + sidebar
     ════════════════════════════════════════════════════════════ --}}
<div class="cust-layout">

    {{-- ── MAIN COLUMN (Tabs + Panes) ─────────────────────── --}}
    <div class="cust-main-col">

        {{-- Tabs --}}
        @php
            $attachmentsCount = ($customer->photo ? 1 : 0) + ($customer->passport_image ? 1 : 0) + ($customer->national_id_image ? 1 : 0);
        @endphp
        <div class="show-tabs" id="showTabs">
            <button class="active" data-tab="ov"><i class="bi bi-person-lines-fill"></i> نظرة عامة</button>
            <button data-tab="pass"><i class="bi bi-passport"></i> جواز السفر</button>
            <button data-tab="docs"><i class="bi bi-images"></i> المرفقات
                @if($attachmentsCount) <span class="tab-count">{{ $attachmentsCount }}</span> @endif
            </button>
            <button data-tab="book"><i class="bi bi-calendar-check"></i> الحجوزات
                @if($allBookings) <span class="tab-count">{{ $allBookings }}</span> @endif
            </button>
            <button data-tab="notes"><i class="bi bi-sticky"></i> الملاحظات</button>
        </div>

        {{-- ─── Pane 1: Overview (Personal data + Contact priority) ─── --}}
        <div class="show-pane active" id="pane-ov">
            <div class="row g-3">
                {{-- Personal data --}}
                <div class="col-12">
                    <div class="card">
                        <div class="card-header"><h6><i class="bi bi-person-vcard text-primary me-1"></i> البيانات الشخصية</h6></div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <ul class="info-list">
                                        <li>
                                            <span class="ic"><i class="bi bi-translate"></i></span>
                                            <div class="flex-grow-1">
                                                <div class="lbl">الاسم بالإنجليزية</div>
                                                <div class="val {{ $customer->full_name_en ? '' : 'empty' }}" dir="ltr">{{ $customer->full_name_en ?: '— غير محدد —' }}</div>
                                            </div>
                                        </li>
                                        <li>
                                            <span class="ic"><i class="bi bi-card-text"></i></span>
                                            <div class="flex-grow-1">
                                                <div class="lbl">الرقم القومي</div>
                                                <div class="val {{ $customer->national_id ? '' : 'empty' }}"><code>{{ $customer->national_id ?: '— غير محدد —' }}</code></div>
                                            </div>
                                        </li>
                                        <li>
                                            <span class="ic"><i class="bi bi-gender-{{ $customer->gender === 'female' ? 'female' : 'male' }}"></i></span>
                                            <div class="flex-grow-1">
                                                <div class="lbl">الجنس</div>
                                                <div class="val">{{ $customer->gender_label }}</div>
                                            </div>
                                        </li>
                                        <li>
                                            <span class="ic"><i class="bi bi-cake"></i></span>
                                            <div class="flex-grow-1">
                                                <div class="lbl">تاريخ الميلاد</div>
                                                <div class="val {{ $customer->birth_date ? '' : 'empty' }}">
                                                    {{ $customer->birth_date?->format('Y-m-d') ?: '— غير محدد —' }}
                                                    @if($customer->birth_date)
                                                        <small class="text-muted">({{ $customer->birth_date->age }} عام)</small>
                                                    @endif
                                                </div>
                                            </div>
                                        </li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <ul class="info-list">
                                        <li>
                                            <span class="ic"><i class="bi bi-flag"></i></span>
                                            <div class="flex-grow-1">
                                                <div class="lbl">الجنسية</div>
                                                <div class="val {{ $customer->nationality ? '' : 'empty' }}">{{ $customer->nationality ?: '— غير محدد —' }}</div>
                                            </div>
                                        </li>
                                        <li>
                                            <span class="ic"><i class="bi bi-moon-stars"></i></span>
                                            <div class="flex-grow-1">
                                                <div class="lbl">الديانة</div>
                                                <div class="val {{ $customer->religion ? '' : 'empty' }}">{{ $customer->religion ?: '— غير محدد —' }}</div>
                                            </div>
                                        </li>
                                        <li>
                                            <span class="ic"><i class="bi bi-heart"></i></span>
                                            <div class="flex-grow-1">
                                                <div class="lbl">الحالة الاجتماعية</div>
                                                <div class="val {{ $customer->marital_status ? '' : 'empty' }}">{{ $customer->marital_status ?: '— غير محدد —' }}</div>
                                            </div>
                                        </li>
                                        <li>
                                            <span class="ic"><i class="bi bi-person-plus"></i></span>
                                            <div class="flex-grow-1">
                                                <div class="lbl">أضيف بواسطة</div>
                                                <div class="val">
                                                    {{ $customer->creator?->name ?? 'غير معروف' }}
                                                    <small class="text-muted d-block">{{ $customer->created_at?->format('Y-m-d H:i') }}</small>
                                                </div>
                                            </div>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Contact + Address --}}
                <div class="col-12">
                    <div class="card">
                        <div class="card-header"><h6><i class="bi bi-telephone-fill text-success me-1"></i> الاتصال والعنوان</h6></div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <ul class="info-list">
                                        <li>
                                            <span class="ic" style="background:#dcfce7;color:#15803d;"><i class="bi bi-telephone"></i></span>
                                            <div class="flex-grow-1">
                                                <div class="lbl">الهاتف الرئيسي</div>
                                                <div class="val" dir="ltr">{{ $customer->phone }}</div>
                                            </div>
                                        </li>
                                        <li>
                                            <span class="ic" style="background:#dbeafe;color:#1d4ed8;"><i class="bi bi-phone"></i></span>
                                            <div class="flex-grow-1">
                                                <div class="lbl">رقم الجوال</div>
                                                <div class="val {{ $customer->mobile ? '' : 'empty' }}" dir="ltr">{{ $customer->mobile ?: '— غير محدد —' }}</div>
                                            </div>
                                        </li>
                                        <li>
                                            <span class="ic" style="background:#dcfce7;color:#15803d;"><i class="bi bi-whatsapp"></i></span>
                                            <div class="flex-grow-1">
                                                <div class="lbl">واتساب</div>
                                                <div class="val {{ $customer->whatsapp ? '' : 'empty' }}" dir="ltr">{{ $customer->whatsapp ?: '— غير محدد —' }}</div>
                                            </div>
                                        </li>
                                        <li>
                                            <span class="ic" style="background:#f3e8ff;color:#6b21a8;"><i class="bi bi-envelope"></i></span>
                                            <div class="flex-grow-1">
                                                <div class="lbl">البريد الإلكتروني</div>
                                                <div class="val {{ $customer->email ? '' : 'empty' }}">
                                                    @if($customer->email)
                                                        <a href="mailto:{{ $customer->email }}" class="text-decoration-none" dir="ltr">{{ $customer->email }}</a>
                                                    @else
                                                        — غير محدد —
                                                    @endif
                                                </div>
                                            </div>
                                        </li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <ul class="info-list">
                                        <li>
                                            <span class="ic" style="background:#fef3c7;color:#b45309;"><i class="bi bi-geo-alt"></i></span>
                                            <div class="flex-grow-1">
                                                <div class="lbl">العنوان</div>
                                                <div class="val {{ $customer->address ? '' : 'empty' }}">{{ $customer->address ?: '— غير محدد —' }}</div>
                                            </div>
                                        </li>
                                        <li>
                                            <span class="ic" style="background:#e0e7ff;color:#4338ca;"><i class="bi bi-buildings"></i></span>
                                            <div class="flex-grow-1">
                                                <div class="lbl">المدينة</div>
                                                <div class="val {{ $customer->city ? '' : 'empty' }}">{{ $customer->city ?: '— غير محدد —' }}</div>
                                            </div>
                                        </li>
                                        <li>
                                            <span class="ic" style="background:#e0e7ff;color:#4338ca;"><i class="bi bi-map"></i></span>
                                            <div class="flex-grow-1">
                                                <div class="lbl">المحافظة</div>
                                                <div class="val {{ $customer->governorate ? '' : 'empty' }}">{{ $customer->governorate ?: '— غير محدد —' }}</div>
                                            </div>
                                        </li>
                                        <li>
                                            <span class="ic" style="background:#e0e7ff;color:#4338ca;"><i class="bi bi-globe"></i></span>
                                            <div class="flex-grow-1">
                                                <div class="lbl">الدولة</div>
                                                <div class="val {{ $customer->country ? '' : 'empty' }}">{{ $customer->country ?: '— غير محدد —' }}</div>
                                            </div>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ─── Pane 2: Passport ─── --}}
        <div class="show-pane" id="pane-pass">
            @php
                $passStatus = null;
                if ($customer->passport_expiry_date) {
                    $days = now()->diffInDays($customer->passport_expiry_date, false);
                    if ($days < 0)        $passStatus = ['danger', 'الجواز منتهي الصلاحية', 'انتهى منذ ' . abs((int)$days) . ' يوم — يجب التجديد فوراً قبل أي حجز'];
                    elseif ($days <= 180) $passStatus = ['warn', 'الجواز يقترب من الانتهاء', 'متبقي ' . (int)$days . ' يوم — قد لا يُقبل في الحجوزات الدولية'];
                    else                  $passStatus = ['ok', 'الجواز ساري المفعول', 'متبقي ' . (int)$days . ' يوم على الانتهاء'];
                }
            @endphp

            @if(!$customer->passport_number)
                <div class="card">
                    <div class="card-body">
                        <div class="placeholder-box">
                            <i class="bi bi-passport"></i>
                            <h6>لم تُدخل بيانات جواز السفر بعد</h6>
                            <p>أضف بيانات الجواز لتتمكن من حجز الرحلات الدولية</p>
                            @can('customers.update')
                            <a href="{{ route('admin.customers.edit', $customer) }}" class="btn btn-primary btn-sm mt-3">
                                <i class="bi bi-plus-circle ms-1"></i> إضافة بيانات الجواز
                            </a>
                            @endcan
                        </div>
                    </div>
                </div>
            @else
                @if($passStatus)
                    <div class="pass-status {{ $passStatus[0] }}">
                        <div class="ps-icon">
                            <i class="bi bi-{{ $passStatus[0] === 'ok' ? 'shield-check' : ($passStatus[0] === 'warn' ? 'exclamation-circle' : 'x-octagon') }}"></i>
                        </div>
                        <div>
                            <div class="ps-msg">{{ $passStatus[1] }}</div>
                            <div class="ps-sub">{{ $passStatus[2] }}</div>
                        </div>
                    </div>
                @endif

                <div class="card">
                    <div class="card-header"><h6><i class="bi bi-passport text-warning me-1"></i> تفاصيل جواز السفر</h6></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <ul class="info-list">
                                    <li>
                                        <span class="ic" style="background:#fef3c7;color:#b45309;"><i class="bi bi-hash"></i></span>
                                        <div class="flex-grow-1">
                                            <div class="lbl">رقم الجواز</div>
                                            <div class="val"><code style="font-size:1rem;">{{ $customer->passport_number }}</code></div>
                                        </div>
                                    </li>
                                    <li>
                                        <span class="ic"><i class="bi bi-calendar-plus"></i></span>
                                        <div class="flex-grow-1">
                                            <div class="lbl">تاريخ الإصدار</div>
                                            <div class="val {{ $customer->passport_issue_date ? '' : 'empty' }}">{{ $customer->passport_issue_date?->format('Y-m-d') ?: '— غير محدد —' }}</div>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <ul class="info-list">
                                    <li>
                                        <span class="ic"><i class="bi bi-calendar-x"></i></span>
                                        <div class="flex-grow-1">
                                            <div class="lbl">تاريخ الانتهاء</div>
                                            <div class="val {{ $customer->passport_expiry_date ? '' : 'empty' }}">{{ $customer->passport_expiry_date?->format('Y-m-d') ?: '— غير محدد —' }}</div>
                                        </div>
                                    </li>
                                    <li>
                                        <span class="ic"><i class="bi bi-geo"></i></span>
                                        <div class="flex-grow-1">
                                            <div class="lbl">مكان الإصدار</div>
                                            <div class="val {{ $customer->passport_issue_place ? '' : 'empty' }}">{{ $customer->passport_issue_place ?: '— غير محدد —' }}</div>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- ─── Pane 3: Attachments ─── --}}
        <div class="show-pane" id="pane-docs">
            @if(!$customer->photo && !$customer->passport_image && !$customer->national_id_image)
                <div class="card">
                    <div class="card-body">
                        <div class="doc-empty">
                            <i class="bi bi-images"></i>
                            <h6 class="mt-3 fw-bold">لا توجد مرفقات</h6>
                            <p class="small">لم تُرفع أي صور لهذا العميل بعد</p>
                            @can('customers.update')
                            <a href="{{ route('admin.customers.edit', $customer) }}" class="btn btn-primary btn-sm mt-2">
                                <i class="bi bi-cloud-upload ms-1"></i> رفع المرفقات
                            </a>
                            @endcan
                        </div>
                    </div>
                </div>
            @else
                <div class="row g-3">
                    @if($customer->photo)
                    <div class="col-md-4 col-sm-6">
                        <div class="doc-card">
                            <a href="{{ $customer->photo_url }}" target="_blank">
                                <img src="{{ $customer->photo_url }}" alt="صورة شخصية">
                            </a>
                            <div class="doc-foot">
                                <span><i class="bi bi-person-circle text-primary me-1"></i> الصورة الشخصية</span>
                                <a href="{{ $customer->photo_url }}" target="_blank" class="btn btn-icon btn-sm btn-light"><i class="bi bi-box-arrow-up-right"></i></a>
                            </div>
                        </div>
                    </div>
                    @endif

                    @if($customer->passport_image)
                    <div class="col-md-4 col-sm-6">
                        <div class="doc-card">
                            <a href="{{ asset('storage/'.$customer->passport_image) }}" target="_blank">
                                <img src="{{ asset('storage/'.$customer->passport_image) }}" alt="صورة الجواز">
                            </a>
                            <div class="doc-foot">
                                <span><i class="bi bi-passport text-warning me-1"></i> صورة الجواز</span>
                                <a href="{{ asset('storage/'.$customer->passport_image) }}" target="_blank" class="btn btn-icon btn-sm btn-light"><i class="bi bi-box-arrow-up-right"></i></a>
                            </div>
                        </div>
                    </div>
                    @endif

                    @if($customer->national_id_image)
                    <div class="col-md-4 col-sm-6">
                        <div class="doc-card">
                            <a href="{{ asset('storage/'.$customer->national_id_image) }}" target="_blank">
                                <img src="{{ asset('storage/'.$customer->national_id_image) }}" alt="صورة البطاقة">
                            </a>
                            <div class="doc-foot">
                                <span><i class="bi bi-card-text text-info me-1"></i> الرقم القومي</span>
                                <a href="{{ asset('storage/'.$customer->national_id_image) }}" target="_blank" class="btn btn-icon btn-sm btn-light"><i class="bi bi-box-arrow-up-right"></i></a>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            @endif
        </div>

        {{-- ─── Pane 4: Bookings (Religious + Domestic) ─── --}}
        <div class="show-pane" id="pane-book">
            @php
                $relActive    = $religiousBookings->whereIn('status', ['pending','confirmed','in_progress'])->count();
                $relCompleted = $religiousBookings->where('status','completed')->count();
                $domActive    = $domesticBookings->whereIn('status', ['pending','confirmed','in_progress'])->count();
                $domCompleted = $domesticBookings->where('status','completed')->count();
            @endphp

            {{-- Combined mini-stats --}}
            <div class="row g-2 mb-3">
                <div class="col-md-3 col-6">
                    <div class="mini-stat-box">
                        <div class="lbl">إجمالي الحجوزات</div>
                        <div class="val">{{ $allBookings }}</div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="mini-stat-box">
                        <div class="lbl">نشطة</div>
                        <div class="val text-primary">{{ $relActive + $domActive }}</div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="mini-stat-box">
                        <div class="lbl">مكتملة</div>
                        <div class="val text-success">{{ $relCompleted + $domCompleted }}</div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="mini-stat-box">
                        <div class="lbl">إجمالي الإنفاق</div>
                        <div class="val" style="color:var(--brand-gold);font-size:1.05rem;">{{ number_format($religiousRevenue + $domesticRevenue) }} <small>ج.م</small></div>
                    </div>
                </div>
            </div>

            {{-- New booking dropdown --}}
            @canany(['religious_bookings.create', 'domestic_bookings.create'])
            <div class="d-flex justify-content-end mb-3 gap-2">
                @can('religious_bookings.create')
                <a href="{{ route('admin.religious.bookings.create', ['customer_id' => $customer->id]) }}" class="btn btn-primary btn-sm">
                    <i class="bi bi-mosque"></i> حجز ديني جديد
                </a>
                @endcan
                @can('domestic_bookings.create')
                <a href="{{ route('admin.domestic.bookings.create', ['customer_id' => $customer->id]) }}" class="btn btn-gold btn-sm">
                    <i class="bi bi-geo-alt"></i> حجز داخلي جديد
                </a>
                @endcan
            </div>
            @endcanany

            {{-- Sub-tabs: Religious / Domestic --}}
            <div class="show-tabs mb-3" id="bookSubTabs" style="background:#f1f5f9;">
                <button class="active" data-subtab="rel">
                    <i class="bi bi-mosque"></i> السياحة الدينية
                    @if($religiousBookings->count()) <span class="tab-count">{{ $religiousBookings->count() }}</span> @endif
                </button>
                <button data-subtab="dom">
                    <i class="bi bi-geo-alt"></i> السياحة الداخلية
                    @if($domesticBookings->count()) <span class="tab-count">{{ $domesticBookings->count() }}</span> @endif
                </button>
            </div>

            {{-- ── Religious Bookings ── --}}
            <div class="book-subpane active" id="subpane-rel">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="bi bi-mosque text-primary me-1"></i> حجوزات السياحة الدينية</h6>
                    </div>
                    <div class="card-body p-0">
                        @if($religiousBookings->isEmpty())
                            <div class="placeholder-box">
                                <i class="bi bi-mosque"></i>
                                <h6>لا توجد حجوزات دينية بعد</h6>
                                <p>هذا العميل لم يقم بأي حجز عمرة أو حج حتى الآن</p>
                            </div>
                        @else
                        <div class="table-responsive">
                            <table class="table mb-0" style="font-size:.88rem;">
                                <thead style="background:#f8fafc;">
                                    <tr>
                                        <th class="px-3">رقم الحجز</th>
                                        <th>النوع / البرنامج</th>
                                        <th>تاريخ السفر</th>
                                        <th>المدة</th>
                                        <th>الأفراد</th>
                                        <th>السعر</th>
                                        <th>الحالة</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($religiousBookings as $b)
                                    <tr>
                                        <td class="px-3"><code>{{ $b->booking_number }}</code></td>
                                        <td>
                                            <span class="badge bg-light text-dark"><i class="bi bi-{{ $b->type === 'hajj' ? 'mosque' : 'moon-stars' }}"></i> {{ $b->type_label }}</span>
                                            <div class="text-muted small mt-1">{{ $b->program?->name ?: '—' }}</div>
                                        </td>
                                        <td>{{ $b->trip_date?->format('Y-m-d') }}</td>
                                        <td>{{ $b->duration_days }} يوم</td>
                                        <td>{{ $b->adults_count + $b->children_count }}</td>
                                        <td><strong>{{ number_format($b->selling_price, 0) }}</strong> <small>ج.م</small></td>
                                        <td>
                                            <span class="badge bg-{{ $b->status_badge === 'success' ? 'success' : ($b->status_badge === 'danger' ? 'danger' : ($b->status_badge === 'warning' ? 'warning' : 'info')) }}">
                                                {{ $b->status_label }}
                                            </span>
                                        </td>
                                        <td>
                                            <a href="{{ route('admin.religious.bookings.show', $b) }}" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- ── Domestic Bookings ── --}}
            <div class="book-subpane" id="subpane-dom">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="bi bi-geo-alt-fill text-warning me-1"></i> حجوزات السياحة الداخلية</h6>
                    </div>
                    <div class="card-body p-0">
                        @if($domesticBookings->isEmpty())
                            <div class="placeholder-box">
                                <i class="bi bi-geo-alt"></i>
                                <h6>لا توجد حجوزات داخلية بعد</h6>
                                <p>هذا العميل لم يقم بأي حجز داخلي (فنادق، رحلات، باكدج، إلخ) حتى الآن</p>
                            </div>
                        @else
                        <div class="table-responsive">
                            <table class="table mb-0" style="font-size:.88rem;">
                                <thead style="background:#f8fafc;">
                                    <tr>
                                        <th class="px-3">رقم الحجز</th>
                                        <th>النوع / الوجهة</th>
                                        <th>تاريخ السفر</th>
                                        <th>المدة</th>
                                        <th>الأفراد</th>
                                        <th>السعر</th>
                                        <th>الحالة</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($domesticBookings as $b)
                                    <tr>
                                        <td class="px-3"><code>{{ $b->booking_number }}</code></td>
                                        <td>
                                            <span class="badge bg-light text-dark"><i class="bi bi-tag"></i> {{ $b->type_label }}</span>
                                            <div class="text-muted small mt-1">
                                                @if($b->destination_city)<i class="bi bi-geo-alt-fill"></i> {{ $b->destination_city }}@endif
                                                @if($b->program?->name) — {{ $b->program->name }}@endif
                                            </div>
                                        </td>
                                        <td>{{ $b->trip_date?->format('Y-m-d') }}</td>
                                        <td>{{ $b->duration_days }} يوم</td>
                                        <td>{{ $b->adults_count + $b->children_count }}</td>
                                        <td><strong>{{ number_format($b->selling_price, 0) }}</strong> <small>ج.م</small></td>
                                        <td>
                                            <span class="badge bg-{{ $b->status_badge }}">
                                                {{ $b->status_label }}
                                            </span>
                                        </td>
                                        <td>
                                            <a href="{{ route('admin.domestic.bookings.show', $b) }}" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- ─── Pane 5: Notes ─── --}}
        <div class="show-pane" id="pane-notes">
            <div class="card">
                <div class="card-header"><h6><i class="bi bi-sticky text-secondary me-1"></i> ملاحظات داخلية</h6></div>
                <div class="card-body">
                    @if($customer->notes)
                        <div style="background:#fffbeb;border-right:4px solid #f59e0b;padding:1rem 1.25rem;border-radius:10px;line-height:1.8;color:#78350f;">
                            {!! nl2br(e($customer->notes)) !!}
                        </div>
                    @else
                        <div class="doc-empty">
                            <i class="bi bi-sticky"></i>
                            <h6 class="mt-3 fw-bold">لا توجد ملاحظات</h6>
                            <p class="small">يمكنك إضافة ملاحظات داخلية عن العميل من شاشة التعديل</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

    </div>

    {{-- ── SIDEBAR COLUMN ─────────────────────────────────── --}}
    <aside class="cust-side-col">
        {{-- Quick contact summary --}}
        <div class="side-card">
            <div class="side-head"><i class="bi bi-info-circle"></i> ملخص العميل</div>
            <div class="side-body compact">
                <div class="side-meta">
                    <div class="side-meta-item">
                        <div class="ic-sm"><i class="bi bi-telephone"></i></div>
                        <div class="meta-text">
                            <div class="lbl">هاتف رئيسي</div>
                            <div class="val" dir="ltr">{{ $customer->phone ?: '—' }}</div>
                        </div>
                    </div>
                    <div class="side-meta-item">
                        <div class="ic-sm" style="background:#dcfce7;color:#15803d;"><i class="bi bi-whatsapp"></i></div>
                        <div class="meta-text">
                            <div class="lbl">واتساب</div>
                            <div class="val {{ $customer->whatsapp ? '' : 'empty' }}" dir="ltr">{{ $customer->whatsapp ?: '— غير محدد —' }}</div>
                        </div>
                    </div>
                    <div class="side-meta-item">
                        <div class="ic-sm" style="background:#f3e8ff;color:#6b21a8;"><i class="bi bi-envelope"></i></div>
                        <div class="meta-text">
                            <div class="lbl">البريد الإلكتروني</div>
                            <div class="val {{ $customer->email ? '' : 'empty' }}" dir="ltr">{{ $customer->email ?: '— غير محدد —' }}</div>
                        </div>
                    </div>
                    <div class="side-meta-item">
                        <div class="ic-sm" style="background:#fef3c7;color:#b45309;"><i class="bi bi-geo-alt"></i></div>
                        <div class="meta-text">
                            <div class="lbl">العنوان</div>
                            <div class="val {{ $customer->address ? '' : 'empty' }}">{{ $customer->address ?: '— غير محدد —' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Account meta --}}
        <div class="side-card">
            <div class="side-head"><i class="bi bi-clock-history"></i> معلومات الحساب</div>
            <div class="side-body compact">
                <div class="side-meta">
                    <div class="side-meta-item">
                        <div class="ic-sm"><i class="bi bi-tag"></i></div>
                        <div class="meta-text">
                            <div class="lbl">نوع العميل</div>
                            <div class="val">{{ $customer->type_label }}</div>
                        </div>
                    </div>
                    <div class="side-meta-item">
                        <div class="ic-sm"><i class="bi bi-shield-check"></i></div>
                        <div class="meta-text">
                            <div class="lbl">الحالة</div>
                            <div class="val"><span class="badge bg-{{ $customer->status_badge }}-soft">{{ $customer->status_label }}</span></div>
                        </div>
                    </div>
                    <div class="side-meta-item">
                        <div class="ic-sm"><i class="bi bi-person-plus"></i></div>
                        <div class="meta-text">
                            <div class="lbl">أضيف بواسطة</div>
                            <div class="val">{{ $customer->creator?->name ?? 'غير معروف' }}</div>
                        </div>
                    </div>
                    <div class="side-meta-item">
                        <div class="ic-sm"><i class="bi bi-calendar-plus"></i></div>
                        <div class="meta-text">
                            <div class="lbl">تاريخ الإضافة</div>
                            <div class="val">{{ $customer->created_at?->format('Y-m-d H:i') }}</div>
                        </div>
                    </div>
                    @if($customer->updated_at && $customer->updated_at->ne($customer->created_at))
                    <div class="side-meta-item">
                        <div class="ic-sm"><i class="bi bi-pencil"></i></div>
                        <div class="meta-text">
                            <div class="lbl">آخر تعديل</div>
                            <div class="val">{{ $customer->updated_at?->diffForHumans() }}</div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Passport quick view --}}
        @if($customer->passport_number)
        <div class="side-card">
            <div class="side-head"><i class="bi bi-passport"></i> جواز السفر</div>
            <div class="side-body compact">
                <div class="side-meta">
                    <div class="side-meta-item">
                        <div class="ic-sm" style="background:#fef3c7;color:#b45309;"><i class="bi bi-hash"></i></div>
                        <div class="meta-text">
                            <div class="lbl">رقم الجواز</div>
                            <div class="val"><code>{{ $customer->passport_number }}</code></div>
                        </div>
                    </div>
                    @if($customer->passport_expiry_date)
                    @php
                        $days = (int) now()->diffInDays($customer->passport_expiry_date, false);
                        $cls  = $days < 0 ? 'text-danger' : ($days <= 180 ? 'text-warning' : 'text-success');
                    @endphp
                    <div class="side-meta-item">
                        <div class="ic-sm"><i class="bi bi-calendar-x"></i></div>
                        <div class="meta-text">
                            <div class="lbl">تاريخ الانتهاء</div>
                            <div class="val {{ $cls }}">
                                {{ $customer->passport_expiry_date->format('Y-m-d') }}
                                <small class="d-block fw-normal">
                                    @if($days < 0) منتهي منذ {{ abs($days) }} يوم
                                    @else متبقي {{ $days }} يوم
                                    @endif
                                </small>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        @endif

        {{-- Notes preview --}}
        @if($customer->notes)
        <div class="side-card">
            <div class="side-head"><i class="bi bi-sticky"></i> ملاحظة سريعة</div>
            <div class="side-body" style="background:#fffbeb;border-top:3px solid #f59e0b;">
                <p class="mb-0" style="color:#78350f;font-size:.85rem;line-height:1.7;">
                    {{ \Illuminate\Support\Str::limit($customer->notes, 220) }}
                </p>
            </div>
        </div>
        @endif
    </aside>

</div>

@endsection

@push('scripts')
<script>
(function () {
    // ── Main tabs ─────────────────────────────────
    const tabs  = document.querySelectorAll('#showTabs button');
    const panes = document.querySelectorAll('.show-pane');
    tabs.forEach(t => t.addEventListener('click', () => {
        const target = t.dataset.tab;
        tabs.forEach(x => x.classList.toggle('active', x === t));
        panes.forEach(p => p.classList.toggle('active', p.id === 'pane-' + target));
        try { history.replaceState(null, '', '#' + target); } catch (e) {}
    }));
    const h = (location.hash || '').replace('#','');
    if (h) {
        const btn = document.querySelector('#showTabs button[data-tab="' + h + '"]');
        if (btn) btn.click();
    }

    // ── Booking sub-tabs (Religious / Domestic) ──
    const subTabs   = document.querySelectorAll('#bookSubTabs button');
    const subPanes  = document.querySelectorAll('.book-subpane');
    subTabs.forEach(t => t.addEventListener('click', () => {
        const target = t.dataset.subtab;
        subTabs.forEach(x => x.classList.toggle('active', x === t));
        subPanes.forEach(p => p.classList.toggle('active', p.id === 'subpane-' + target));
    }));
})();
</script>
@endpush
