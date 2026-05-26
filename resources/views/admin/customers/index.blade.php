@extends('layouts.master')

@section('title', 'العملاء')
@section('page_title', 'إدارة العملاء')
@section('page_subtitle', 'قاعدة بيانات شاملة لجميع عملاء الشركة من الأفراد والوكلاء والمجموعات')

@push('styles')
<style>
    /* ── KPI cards ──────────────────────────────────────── */
    .kpi-card {
        background: #fff;
        border-radius: 14px;
        padding: 1rem 1.1rem;
        box-shadow: 0 1px 4px rgba(15,23,42,.04);
        display: flex; align-items: center; gap: .85rem;
        height: 100%;
        transition: transform .15s, box-shadow .15s;
    }
    .kpi-card:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(15,23,42,.07); }
    .kpi-icon {
        width: 48px; height: 48px; border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.35rem; flex-shrink: 0;
    }
    .kpi-body .lbl { font-size: .78rem; color: var(--text-muted); font-weight: 500; margin-bottom: .15rem; }
    .kpi-body .val { font-size: 1.45rem; font-weight: 800; color: var(--brand-navy); line-height: 1; }

    .kpi-i-navy   { background: #eef2ff; color: #1e3a8a; }
    .kpi-i-green  { background: #dcfce7; color: #15803d; }
    .kpi-i-gold   { background: #fef3c7; color: #b45309; }
    .kpi-i-blue   { background: #dbeafe; color: #1d4ed8; }
    .kpi-i-red    { background: #fee2e2; color: #b91c1c; }
    .kpi-i-purple { background: #f3e8ff; color: #6b21a8; }

    /* ── Filter chips ───────────────────────────────────── */
    .filter-chips {
        display: flex; gap: .5rem; flex-wrap: wrap;
        padding: .35rem 0;
    }
    .chip {
        display: inline-flex; align-items: center; gap: .35rem;
        padding: .4rem .85rem;
        background: #f1f5f9;
        color: #475569;
        border-radius: 8px;
        font-size: .82rem;
        font-weight: 600;
        cursor: pointer;
        border: 1px solid transparent;
        transition: all .15s;
        user-select: none;
    }
    .chip:hover { background: #e2e8f0; }
    .chip.active {
        background: var(--brand-navy);
        color: #fff;
        border-color: var(--brand-navy);
        box-shadow: 0 2px 8px rgba(15,23,42,.18);
    }
    .chip .count {
        background: rgba(255,255,255,.25);
        font-size: .68rem;
        padding: 1px 7px;
        border-radius: 10px;
        font-weight: 700;
    }
    .chip:not(.active) .count { background: #fff; color: var(--brand-navy); }

    /* ── Search & Advanced Filter Bar ───────────────────── */
    .filter-bar {
        background: #fff;
        border-radius: 12px;
        padding: 1rem;
        box-shadow: 0 1px 3px rgba(15,23,42,.04);
        border: 1px solid var(--brand-border);
        margin-bottom: 1rem;
    }
    .filter-bar .search-wrap {
        position: relative;
        flex: 1; min-width: 240px;
    }
    .filter-bar .search-wrap .form-control {
        padding-right: 2.5rem;
        height: 44px;
        font-size: .9rem;
        border-radius: 10px;
        background: #f8f9fc;
        border: 1px solid var(--brand-border);
    }
    .filter-bar .search-wrap .form-control:focus { background: #fff; }
    .filter-bar .search-wrap i.s-ico {
        position: absolute;
        right: .9rem; top: 50%;
        transform: translateY(-50%);
        color: var(--text-muted);
        font-size: 1rem;
    }
    .filter-bar .search-wrap .clear-btn {
        position: absolute;
        left: .35rem; top: 50%;
        transform: translateY(-50%);
        background: transparent; border: none;
        color: #94a3b8;
        display: none;
        padding: 0 .4rem;
        cursor: pointer;
        font-size: 1.1rem;
    }
    .filter-bar .search-wrap.has-value .clear-btn { display: inline-flex; }

    .filter-bar .toggle-adv {
        height: 44px;
        background: #f8f9fc;
        border: 1px solid var(--brand-border);
        color: var(--brand-navy);
        border-radius: 10px;
        font-weight: 700;
        font-size: .85rem;
        padding: 0 1rem;
        display: inline-flex; align-items: center; gap: .4rem;
        position: relative;
    }
    .filter-bar .toggle-adv:hover { background: #eef0f5; }
    .filter-bar .toggle-adv.active {
        background: var(--brand-navy); color: #fff; border-color: var(--brand-navy);
    }
    .filter-bar .toggle-adv .badge-num {
        background: var(--brand-gold);
        color: #fff;
        font-size: .65rem;
        font-weight: 800;
        min-width: 18px; height: 18px;
        border-radius: 9px;
        display: inline-flex; align-items: center; justify-content: center;
        padding: 0 5px;
    }

    .filter-bar .quick-action {
        height: 44px;
        padding: 0 1rem;
        border-radius: 10px;
        font-weight: 700;
        font-size: .85rem;
        display: inline-flex; align-items: center; gap: .4rem;
    }

    /* ── Advanced filter panel (collapsible) ────────────── */
    .adv-panel {
        background: #fff;
        border-radius: 12px;
        padding: 1.25rem;
        box-shadow: 0 1px 3px rgba(15,23,42,.04);
        border: 1px solid var(--brand-border);
        margin-bottom: 1rem;
        max-height: 0;
        overflow: hidden;
        padding-top: 0; padding-bottom: 0;
        border-width: 0;
        transition: all .3s ease;
        opacity: 0;
    }
    .adv-panel.open {
        max-height: 600px;
        padding: 1.25rem;
        border-width: 1px;
        opacity: 1;
    }
    .adv-panel .adv-head {
        display: flex; justify-content: space-between; align-items: center;
        margin-bottom: 1rem;
        padding-bottom: .75rem;
        border-bottom: 1px solid var(--brand-border);
    }
    .adv-panel .adv-head h6 {
        margin: 0;
        color: var(--brand-navy);
        font-weight: 800;
        display: inline-flex; align-items: center; gap: .5rem;
    }
    .adv-panel label.form-label {
        font-size: .78rem;
        font-weight: 700;
        color: #475569;
        margin-bottom: .35rem;
    }
    .adv-panel .form-control,
    .adv-panel .form-select {
        height: 40px;
        font-size: .88rem;
        border-radius: 9px;
        background: #fafbff;
        border: 1px solid var(--brand-border);
    }
    .adv-panel .form-control:focus,
    .adv-panel .form-select:focus { background: #fff; }
    .adv-panel .adv-footer {
        margin-top: 1rem;
        padding-top: 1rem;
        border-top: 1px solid var(--brand-border);
        display: flex; justify-content: flex-end; gap: .5rem;
    }

    /* ── Active filter pills (after applying) ────────────── */
    .active-pills {
        display: flex; flex-wrap: wrap; gap: .4rem;
        margin-bottom: 1rem;
    }
    .pill {
        display: inline-flex; align-items: center; gap: .4rem;
        background: #eef2ff;
        color: #1e3a8a;
        border-radius: 8px;
        padding: .3rem .65rem;
        font-size: .78rem;
        font-weight: 600;
    }
    .pill .lbl { color: #6b7280; font-weight: 500; }
    .pill .x {
        cursor: pointer;
        color: #94a3b8;
        font-weight: 700;
        margin-right: .25rem;
    }
    .pill .x:hover { color: #b91c1c; }

    /* ── Cells in table ─────────────────────────────────── */
    .cust-cell { display: flex; align-items: center; gap: .65rem; }
    .cust-avatar {
        width: 40px; height: 40px;
        border-radius: 50%;
        object-fit: cover;
        flex-shrink: 0;
        border: 2px solid #fff;
        box-shadow: 0 0 0 1.5px var(--brand-border);
    }
    .cust-body { min-width: 0; }
    .cust-name { font-weight: 700; color: var(--brand-navy); font-size: .9rem; }
    .cust-code { font-size: .72rem; color: var(--text-muted); font-family: 'Cairo', monospace; }
    .cust-code i { font-size: .68rem; }
    .cust-sub  { font-size: .72rem; color: #64748b; }

    .contact-cell { font-size: .82rem; line-height: 1.6; }
    .contact-cell i { font-size: .85rem; margin-left: 4px; }

    .pass-cell { line-height: 1.5; font-size: .82rem; }
    .pass-cell .badge { font-size: .68rem; padding: .25rem .5rem; }

    .bg-success-soft   { background: #dcfce7 !important; color: #15803d !important; }
    .bg-warning-soft   { background: #fef3c7 !important; color: #b45309 !important; }
    .bg-danger-soft    { background: #fee2e2 !important; color: #b91c1c !important; }
    .bg-secondary-soft { background: #f1f5f9 !important; color: #475569 !important; }
    .bg-info-soft      { background: #dbeafe !important; color: #1d4ed8 !important; }
    .bg-primary-soft   { background: #e0e7ff !important; color: #4338ca !important; }

    .badge.type-individual { background: #e0e7ff; color: #4338ca; }
    .badge.type-agency     { background: #fef3c7; color: #b45309; }
    .badge.type-group      { background: #ccfbf1; color: #0f766e; }

    .btn-light-primary { background: #e0e7ff; color: #4338ca; border: none; }
    .btn-light-primary:hover { background: #c7d2fe; color: #312e81; }
    .btn-light-info { background: #dbeafe; color: #1d4ed8; border: none; }
    .btn-light-info:hover { background: #bfdbfe; color: #1e3a8a; }
    .btn-light-success { background: #dcfce7; color: #15803d; border: none; }
    .btn-light-success:hover { background: #bbf7d0; color: #14532d; }
    .btn-light-danger { background: #fee2e2; color: #b91c1c; border: none; }
    .btn-light-danger:hover { background: #fecaca; color: #7f1d1d; }

    #customers-table tbody td { padding: .75rem .65rem; vertical-align: middle; }

    /* ── Responsive ──────────────────────────────────────── */
    @media (max-width: 991.98px) {
        .hide-md { display: none !important; }
        .filter-bar { padding: .85rem; border-radius: 10px; }
        .filter-bar .search-wrap { min-width: 100%; flex: 1 1 100%; }
        .filter-bar .toggle-adv { flex: 1; justify-content: center; }
        .filter-bar .quick-action { flex: 1; justify-content: center; }
    }
    @media (max-width: 767.98px) {
        .kpi-card { padding: .75rem; gap: .65rem; }
        .kpi-icon { width: 40px; height: 40px; font-size: 1.1rem; }
        .kpi-body .lbl { font-size: .72rem; }
        .kpi-body .val { font-size: 1.15rem; }
        .filter-chips { gap: .35rem; }
        .chip { padding: .4rem .7rem; font-size: .76rem; }
        .chip .count { font-size: .62rem; padding: 0 5px; }
        /* Card view for table rows on mobile — DataTables responsive child rows */
        .cust-avatar { width: 36px; height: 36px; }
        .cust-name { font-size: .85rem; }
    }
    @media (max-width: 575.98px) {
        .filter-bar { padding: .75rem; }
        .filter-bar .toggle-adv,
        .filter-bar .quick-action { width: 100%; }
        .adv-panel { padding: 1rem; }
        .adv-panel.open { padding: 1rem; max-height: 1200px; }
        .pill { font-size: .72rem; padding: .25rem .5rem; }
        /* Filter chips: horizontal scroll instead of wrap on tiny screens */
        .filter-chips {
            flex-wrap: nowrap;
            overflow-x: auto;
            padding-bottom: .35rem;
            -webkit-overflow-scrolling: touch;
        }
        .filter-chips::-webkit-scrollbar { height: 4px; }
        .filter-chips::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 2px; }
        .chip { flex-shrink: 0; }
    }
</style>
@endpush

@section('content')

{{-- KPI Cards --}}
<div class="row g-3 mb-3">
    <div class="col-xl-2 col-md-4 col-6">
        <div class="kpi-card">
            <div class="kpi-icon kpi-i-navy"><i class="bi bi-people-fill"></i></div>
            <div class="kpi-body">
                <div class="lbl">إجمالي العملاء</div>
                <div class="val">{{ number_format($stats['total']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <div class="kpi-card">
            <div class="kpi-icon kpi-i-green"><i class="bi bi-person-check-fill"></i></div>
            <div class="kpi-body">
                <div class="lbl">عملاء نشطون</div>
                <div class="val">{{ number_format($stats['active']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <div class="kpi-card">
            <div class="kpi-icon kpi-i-gold"><i class="bi bi-briefcase-fill"></i></div>
            <div class="kpi-body">
                <div class="lbl">وكلاء</div>
                <div class="val">{{ number_format($stats['agencies']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <div class="kpi-card">
            <div class="kpi-icon kpi-i-blue"><i class="bi bi-person-plus-fill"></i></div>
            <div class="kpi-body">
                <div class="lbl">جدد هذا الشهر</div>
                <div class="val">{{ number_format($stats['new_month']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <div class="kpi-card">
            <div class="kpi-icon kpi-i-purple"><i class="bi bi-passport"></i></div>
            <div class="kpi-body">
                <div class="lbl">جواز ينتهي قريباً</div>
                <div class="val">{{ number_format($stats['expiring']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <div class="kpi-card">
            <div class="kpi-icon kpi-i-red"><i class="bi bi-shield-exclamation"></i></div>
            <div class="kpi-body">
                <div class="lbl">محظورون</div>
                <div class="val">{{ number_format($stats['blacklisted']) }}</div>
            </div>
        </div>
    </div>
</div>

{{-- ════════════════════════════════════════════════════════════
     Filter Bar (search + quick toggle + add)
     ════════════════════════════════════════════════════════════ --}}
<div class="filter-bar">
    <div class="d-flex gap-2 flex-wrap align-items-center">
        <div class="search-wrap" id="searchWrap">
            <i class="bi bi-search s-ico"></i>
            <input type="search" id="quickSearch" class="form-control"
                   placeholder="ابحث بالاسم، الكود، الهاتف، الجواز، الرقم القومي، البريد...">
            <button type="button" class="clear-btn" id="clearSearch" title="مسح"><i class="bi bi-x-circle-fill"></i></button>
        </div>

        <button type="button" class="toggle-adv" id="toggleAdv">
            <i class="bi bi-funnel"></i> فلتر متقدم
            <span class="badge-num" id="advCount" style="display:none;">0</span>
            <i class="bi bi-chevron-down" id="advChev"></i>
        </button>

        <div class="ms-auto d-flex gap-2 flex-wrap">
            @can('customers.create')
            <a href="{{ route('admin.customers.create') }}" class="btn btn-primary quick-action">
                <i class="bi bi-person-plus"></i> إضافة عميل
            </a>
            @endcan
            <button type="button" class="btn btn-outline-secondary quick-action" onclick="window.location.reload()">
                <i class="bi bi-arrow-clockwise"></i>
            </button>
        </div>
    </div>

    {{-- Quick chips --}}
    <div class="filter-chips mt-3" id="filterChips">
        <span class="chip active" data-filter="all">
            <i class="bi bi-collection"></i> الكل <span class="count">{{ $stats['total'] }}</span>
        </span>
        <span class="chip" data-filter="status" data-value="active">
            <i class="bi bi-check-circle"></i> نشط <span class="count">{{ $stats['active'] }}</span>
        </span>
        <span class="chip" data-filter="type" data-value="individual">
            <i class="bi bi-person"></i> أفراد
        </span>
        <span class="chip" data-filter="type" data-value="agency">
            <i class="bi bi-briefcase"></i> وكلاء <span class="count">{{ $stats['agencies'] }}</span>
        </span>
        <span class="chip" data-filter="type" data-value="group">
            <i class="bi bi-people"></i> مجموعات
        </span>
        <span class="chip" data-filter="passport" data-value="expiring">
            <i class="bi bi-exclamation-triangle"></i> جواز ينتهي <span class="count">{{ $stats['expiring'] }}</span>
        </span>
        <span class="chip" data-filter="status" data-value="blacklisted">
            <i class="bi bi-x-octagon"></i> محظور <span class="count">{{ $stats['blacklisted'] }}</span>
        </span>
    </div>
</div>

{{-- ════════════════════════════════════════════════════════════
     Advanced filter panel
     ════════════════════════════════════════════════════════════ --}}
<div class="adv-panel" id="advPanel">
    <div class="adv-head">
        <h6><i class="bi bi-sliders"></i> فلترة متقدمة</h6>
        <button type="button" class="btn-close" onclick="document.getElementById('toggleAdv').click()"></button>
    </div>

    <div class="row g-3" id="advFilters">
        <div class="col-sm-6 col-md-4 col-lg-3">
            <label class="form-label"><i class="bi bi-calendar3 text-primary"></i> من تاريخ</label>
            <input type="date" id="f_date_from" class="form-control" data-filter-key="date_from">
        </div>
        <div class="col-sm-6 col-md-4 col-lg-3">
            <label class="form-label"><i class="bi bi-calendar3 text-primary"></i> إلى تاريخ</label>
            <input type="date" id="f_date_to" class="form-control" data-filter-key="date_to">
        </div>
        <div class="col-sm-6 col-md-4 col-lg-3">
            <label class="form-label"><i class="bi bi-flag text-info"></i> الجنسية</label>
            <select id="f_nationality" class="form-select" data-filter-key="nationality">
                <option value="">— الكل —</option>
                @foreach($nationalities as $n)
                    <option value="{{ $n }}">{{ $n }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-sm-6 col-md-4 col-lg-3">
            <label class="form-label"><i class="bi bi-geo-alt text-warning"></i> المدينة</label>
            <select id="f_city" class="form-select" data-filter-key="city">
                <option value="">— الكل —</option>
                @foreach($cities as $c)
                    <option value="{{ $c }}">{{ $c }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-sm-6 col-md-4 col-lg-3">
            <label class="form-label"><i class="bi bi-gender-ambiguous text-secondary"></i> الجنس</label>
            <select id="f_gender" class="form-select" data-filter-key="gender">
                <option value="">— الكل —</option>
                <option value="male">ذكر</option>
                <option value="female">أنثى</option>
            </select>
        </div>
        <div class="col-sm-6 col-md-4 col-lg-3">
            <label class="form-label"><i class="bi bi-passport text-warning"></i> حالة الجواز</label>
            <select id="f_passport" class="form-select" data-filter-key="passport_filter">
                <option value="">— الكل —</option>
                <option value="valid">سارٍ (أكثر من 6 شهور)</option>
                <option value="expiring">يقترب من الانتهاء (خلال 6 شهور)</option>
                <option value="expired">منتهي</option>
                <option value="missing">بدون جواز</option>
            </select>
        </div>
        <div class="col-sm-6 col-md-4 col-lg-3">
            <label class="form-label"><i class="bi bi-check-circle text-success"></i> الحالة</label>
            <select id="f_status" class="form-select" data-filter-key="status_filter">
                <option value="">— الكل —</option>
                <option value="active">نشط</option>
                <option value="inactive">غير نشط</option>
                <option value="blacklisted">محظور</option>
            </select>
        </div>
        <div class="col-sm-6 col-md-4 col-lg-3">
            <label class="form-label"><i class="bi bi-tags text-primary"></i> نوع العميل</label>
            <select id="f_type" class="form-select" data-filter-key="type_filter">
                <option value="">— الكل —</option>
                <option value="individual">فرد</option>
                <option value="agency">وكيل</option>
                <option value="group">مجموعة</option>
            </select>
        </div>
    </div>

    <div class="adv-footer">
        <button type="button" class="btn btn-light" id="resetFilters">
            <i class="bi bi-arrow-counterclockwise"></i> إعادة تعيين
        </button>
        <button type="button" class="btn btn-primary" id="applyFilters">
            <i class="bi bi-funnel"></i> تطبيق الفلاتر
        </button>
    </div>
</div>

{{-- Active filters pills --}}
<div class="active-pills" id="activePills"></div>

{{-- ════════════════════════════════════════════════════════════
     Customers Table
     ════════════════════════════════════════════════════════════ --}}
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table id="customers-table" class="table pretty-table" style="width:100%;">
                <thead>
                    <tr>
                        <th width="60">#</th>
                        <th>العميل</th>
                        <th class="hide-md">الاتصال</th>
                        <th class="hide-md">جواز السفر</th>
                        <th width="90">النوع</th>
                        <th width="100">الحالة</th>
                        <th class="hide-md" width="120">تاريخ الإضافة</th>
                        <th width="180">الإجراءات</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    // ── State ───────────────────────────────────────────
    let currentFilter = {
        q: '',
        status_filter: '', type_filter: '', passport_filter: '',
        date_from: '', date_to: '',
        nationality: '', city: '', gender: '',
    };
    let searchDebounce = null;

    // Pretty labels for active pills
    const pillLabels = {
        q:               { lbl: 'بحث' },
        status_filter:   { lbl: 'الحالة', map: { active:'نشط', inactive:'غير نشط', blacklisted:'محظور' } },
        type_filter:     { lbl: 'النوع',  map: { individual:'فرد', agency:'وكيل', group:'مجموعة' } },
        passport_filter: { lbl: 'الجواز', map: { valid:'سارٍ', expiring:'يقترب الانتهاء', expired:'منتهي', missing:'بدون جواز' } },
        date_from:       { lbl: 'من' },
        date_to:         { lbl: 'إلى' },
        nationality:     { lbl: 'الجنسية' },
        city:            { lbl: 'المدينة' },
        gender:          { lbl: 'الجنس',  map: { male:'ذكر', female:'أنثى' } },
    };

    // ── DataTable ───────────────────────────────────────
    var table = $('#customers-table').DataTable({
        processing: true, serverSide: true, responsive: true,
        autoWidth: false, language: window.dtArabic,
        order: [[0, 'desc']], pageLength: 25,
        lengthMenu: [[10,25,50,100,-1],[10,25,50,100,'الكل']],
        ajax: {
            url: '{{ route('admin.customers.data') }}',
            data: d => Object.assign(d, currentFilter)
        },
        columns: [
            { data: 'id', name: 'id', visible: false },
            { data: 'customer_info', name: 'full_name' },
            { data: 'contact_info', name: 'phone', orderable: false, className: 'hide-md' },
            { data: 'passport_info', name: 'passport_number', orderable: false, className: 'hide-md' },
            { data: 'type', name: 'type' },
            { data: 'status', name: 'status' },
            { data: 'created_at', name: 'created_at', className: 'hide-md' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ]
    });

    // ── Quick search (debounced) ────────────────────────
    $('#quickSearch').on('input', function () {
        const v = $(this).val();
        $('#searchWrap').toggleClass('has-value', !!v);
        clearTimeout(searchDebounce);
        searchDebounce = setTimeout(() => {
            currentFilter.q = v;
            refreshTable();
        }, 350);
    });
    $('#clearSearch').on('click', () => {
        $('#quickSearch').val('').trigger('input').focus();
    });

    // ── Chips ───────────────────────────────────────────
    $('#filterChips').on('click', '.chip', function () {
        $('#filterChips .chip').removeClass('active');
        $(this).addClass('active');

        const filter = $(this).data('filter');
        const value  = $(this).data('value') || '';

        // Chips override the dropdowns of same kind
        currentFilter.status_filter = '';
        currentFilter.type_filter = '';
        currentFilter.passport_filter = '';

        if (filter === 'status')   currentFilter.status_filter   = value;
        if (filter === 'type')     currentFilter.type_filter     = value;
        if (filter === 'passport') currentFilter.passport_filter = value;

        // Sync dropdowns
        $('#f_status').val(currentFilter.status_filter);
        $('#f_type').val(currentFilter.type_filter);
        $('#f_passport').val(currentFilter.passport_filter);

        refreshTable();
    });

    // ── Advanced panel toggle ───────────────────────────
    $('#toggleAdv').on('click', function () {
        const panel = $('#advPanel');
        const open  = !panel.hasClass('open');
        panel.toggleClass('open', open);
        $(this).toggleClass('active', open);
        $('#advChev').toggleClass('bi-chevron-down', !open).toggleClass('bi-chevron-up', open);
    });

    // ── Apply / reset advanced ──────────────────────────
    $('#applyFilters').on('click', () => {
        $('#advFilters [data-filter-key]').each(function () {
            currentFilter[$(this).data('filter-key')] = $(this).val();
        });
        // If user picked a "status_filter" or "type_filter" or "passport_filter" from dropdown,
        // deactivate the matching chip and activate "all"
        if (currentFilter.status_filter || currentFilter.type_filter || currentFilter.passport_filter) {
            $('#filterChips .chip').removeClass('active');
            $('#filterChips .chip[data-filter="all"]').addClass('active');
        }
        refreshTable();
        $('#toggleAdv').click();   // close
    });

    $('#resetFilters').on('click', () => {
        $('#advFilters [data-filter-key]').val('');
        Object.keys(currentFilter).forEach(k => currentFilter[k] = '');
        $('#quickSearch').val(''); $('#searchWrap').removeClass('has-value');
        $('#filterChips .chip').removeClass('active');
        $('#filterChips .chip[data-filter="all"]').addClass('active');
        refreshTable();
    });

    // ── Helpers ─────────────────────────────────────────
    function refreshTable() {
        renderPills();
        renderAdvCount();
        table.ajax.reload();
    }

    function renderPills() {
        const pills = [];
        Object.entries(currentFilter).forEach(([k, v]) => {
            if (!v) return;
            const meta = pillLabels[k];
            if (!meta) return;
            const display = meta.map ? (meta.map[v] || v) : v;
            pills.push(`<span class="pill"><span class="lbl">${meta.lbl}:</span> ${display} <span class="x" data-clear="${k}">×</span></span>`);
        });
        $('#activePills').html(pills.join(''));
    }

    function renderAdvCount() {
        // Count advanced-only filters (exclude q, since it has its own field)
        const advKeys = ['date_from','date_to','nationality','city','gender','passport_filter','status_filter','type_filter'];
        const n = advKeys.filter(k => currentFilter[k]).length;
        $('#advCount').toggle(n > 0).text(n);
    }

    // ── Clear individual pill ───────────────────────────
    $('#activePills').on('click', '.x', function () {
        const k = $(this).data('clear');
        currentFilter[k] = '';
        // sync UI
        const input = $(`#advFilters [data-filter-key="${k}"]`);
        if (input.length) input.val('');
        if (k === 'q') { $('#quickSearch').val(''); $('#searchWrap').removeClass('has-value'); }
        if (['status_filter','type_filter','passport_filter'].includes(k)) {
            $('#filterChips .chip').removeClass('active');
            $('#filterChips .chip[data-filter="all"]').addClass('active');
        }
        refreshTable();
    });

    // ── Delete ──────────────────────────────────────────
    $(document).on('click', '.btn-delete', function () {
        CoreX.ajaxDelete($(this).data('url'), table);
    });
});
</script>
@endpush
