@extends('layouts.master')

@section('title', 'البرامج السياحية الداخلية')
@section('page_title', 'البرامج السياحية الداخلية')
@section('page_subtitle', 'إدارة قوالب الرحلات الداخلية - باكدجات، حجوزات فندقية، رحلات نيلية، مخيمات')

@push('styles')
<style>
    .kpi-card { background:#fff; border-radius:14px; padding:1rem 1.1rem; box-shadow:0 1px 4px rgba(15,23,42,.04); display:flex; align-items:center; gap:.85rem; height:100%; transition:transform .15s, box-shadow .15s; }
    .kpi-card:hover { transform:translateY(-2px); box-shadow:0 6px 16px rgba(15,23,42,.07); }
    .kpi-icon { width:48px; height:48px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:1.35rem; flex-shrink:0; }
    .kpi-body .lbl { font-size:.78rem; color:var(--text-muted); font-weight:500; margin-bottom:.15rem; }
    .kpi-body .val { font-size:1.45rem; font-weight:800; color:var(--brand-navy); line-height:1; }

    .kpi-i-navy   { background:#eef2ff; color:#1e3a8a; }
    .kpi-i-green  { background:#dcfce7; color:#15803d; }
    .kpi-i-gold   { background:#fef3c7; color:#b45309; }
    .kpi-i-blue   { background:#dbeafe; color:#1d4ed8; }
    .kpi-i-purple { background:#f3e8ff; color:#6b21a8; }
    .kpi-i-teal   { background:#ccfbf1; color:#0f766e; }

    .filter-bar { background:#fff; border-radius:12px; padding:1rem; box-shadow:0 1px 3px rgba(15,23,42,.04); border:1px solid var(--brand-border); margin-bottom:1rem; }
    .filter-bar .search-wrap { position:relative; flex:1; min-width:240px; }
    .filter-bar .search-wrap .form-control { padding-right:2.5rem; height:44px; font-size:.9rem; border-radius:10px; background:#f8f9fc; border:1px solid var(--brand-border); }
    .filter-bar .search-wrap i.s-ico { position:absolute; right:.9rem; top:50%; transform:translateY(-50%); color:var(--text-muted); }

    .filter-chips { display:flex; gap:.5rem; flex-wrap:wrap; padding:.35rem 0; }
    .chip { display:inline-flex; align-items:center; gap:.35rem; padding:.4rem .85rem; background:#f1f5f9; color:#475569; border-radius:8px; font-size:.82rem; font-weight:600; cursor:pointer; border:1px solid transparent; transition:all .15s; user-select:none; }
    .chip:hover { background:#e2e8f0; }
    .chip.active { background:var(--brand-navy); color:#fff; border-color:var(--brand-navy); }
    .chip .count { background:rgba(255,255,255,.25); font-size:.68rem; padding:1px 7px; border-radius:10px; font-weight:700; }
    .chip:not(.active) .count { background:#fff; color:var(--brand-navy); }

    .cust-cell { display:flex; align-items:center; gap:.65rem; }
    .cust-avatar { width:48px; height:48px; border-radius:10px; object-fit:cover; flex-shrink:0; border:2px solid #fff; box-shadow:0 0 0 1.5px var(--brand-border); }
    .cust-name { font-weight:700; color:var(--brand-navy); font-size:.9rem; }
    .cust-code { font-size:.72rem; color:var(--text-muted); font-family:'Cairo',monospace; }
    .cust-sub  { font-size:.72rem; color:#64748b; }

    .bg-success-soft   { background:#dcfce7 !important; color:#15803d !important; }
    .bg-warning-soft   { background:#fef3c7 !important; color:#b45309 !important; }
    .bg-info-soft      { background:#dbeafe !important; color:#1d4ed8 !important; }
    .bg-primary-soft   { background:#e0e7ff !important; color:#4338ca !important; }
    .bg-secondary-soft { background:#f1f5f9 !important; color:#475569 !important; }
    .bg-danger-soft    { background:#fee2e2 !important; color:#b91c1c !important; }

    .btn-light-primary { background:#e0e7ff; color:#4338ca; border:none; }
    .btn-light-info    { background:#dbeafe; color:#1d4ed8; border:none; }
    .btn-light-danger  { background:#fee2e2; color:#b91c1c; border:none; }
</style>
@endpush

@section('content')

<div class="row g-3 mb-3">
    <div class="col-xl-3 col-md-6 col-6">
        <div class="kpi-card">
            <div class="kpi-icon kpi-i-navy"><i class="bi bi-collection-fill"></i></div>
            <div class="kpi-body">
                <div class="lbl">إجمالي البرامج</div>
                <div class="val">{{ number_format($stats['total']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 col-6">
        <div class="kpi-card">
            <div class="kpi-icon kpi-i-blue"><i class="bi bi-bag-check"></i></div>
            <div class="kpi-body">
                <div class="lbl">باكدجات كاملة</div>
                <div class="val">{{ number_format($stats['package_total']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 col-6">
        <div class="kpi-card">
            <div class="kpi-icon kpi-i-teal"><i class="bi bi-building"></i></div>
            <div class="kpi-body">
                <div class="lbl">إقامات فندقية</div>
                <div class="val">{{ number_format($stats['hotel_total']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 col-6">
        <div class="kpi-card">
            <div class="kpi-icon kpi-i-gold"><i class="bi bi-broadcast"></i></div>
            <div class="kpi-body">
                <div class="lbl">منشورة للحجز</div>
                <div class="val">{{ number_format($stats['published']) }}</div>
            </div>
        </div>
    </div>
</div>

<div class="filter-bar">
    <div class="d-flex gap-2 flex-wrap align-items-center">
        <div class="search-wrap">
            <i class="bi bi-search s-ico"></i>
            <input type="search" id="quickSearch" class="form-control" placeholder="ابحث بالاسم، الكود، الموسم، المدينة...">
        </div>

        <div class="ms-auto d-flex gap-2 flex-wrap">
            @can('domestic_programs.create')
            <a href="{{ route('admin.domestic.programs.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> إضافة برنامج
            </a>
            @endcan
            <button type="button" class="btn btn-outline-secondary" onclick="window.location.reload()">
                <i class="bi bi-arrow-clockwise"></i>
            </button>
        </div>
    </div>

    <div class="filter-chips mt-3" id="filterChips">
        <span class="chip active" data-filter="all">
            <i class="bi bi-collection"></i> الكل <span class="count">{{ $stats['total'] }}</span>
        </span>
        <span class="chip" data-filter="type" data-value="package">
            <i class="bi bi-bag-check"></i> باكدج <span class="count">{{ $stats['package_total'] }}</span>
        </span>
        <span class="chip" data-filter="type" data-value="hotel_only">
            <i class="bi bi-building"></i> إقامة فندقية <span class="count">{{ $stats['hotel_total'] }}</span>
        </span>
        <span class="chip" data-filter="type" data-value="cruise">
            <i class="bi bi-water"></i> رحلة نيلية/بحرية <span class="count">{{ $stats['cruise_total'] }}</span>
        </span>
        <span class="chip" data-filter="status" data-value="active">
            <i class="bi bi-check-circle"></i> نشط <span class="count">{{ $stats['active'] }}</span>
        </span>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table id="programs-table" class="table pretty-table" style="width:100%;">
                <thead>
                    <tr>
                        <th width="60">#</th>
                        <th>البرنامج</th>
                        <th width="130">النوع</th>
                        <th width="160">الوجهة</th>
                        <th width="120">المدة</th>
                        <th width="140">السعر للفرد</th>
                        <th width="130">الطاقة الاستيعابية</th>
                        <th width="100">الحالة</th>
                        <th width="100">تاريخ الإضافة</th>
                        <th width="160">الإجراءات</th>
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
    let currentFilter = { q: '', type_filter: '', status_filter: '', city_filter: '' };
    let searchDebounce = null;

    var table = $('#programs-table').DataTable({
        processing: true, serverSide: true, responsive: true,
        autoWidth: false, language: window.dtArabic,
        order: [[0, 'desc']], pageLength: 25,
        ajax: {
            url: '{{ route('admin.domestic.programs.data') }}',
            data: d => Object.assign(d, currentFilter)
        },
        columns: [
            { data: 'id', name: 'id', visible: false },
            { data: 'program_info', name: 'name' },
            { data: 'type', name: 'type' },
            { data: 'destination', name: 'destination_city' },
            { data: 'duration_days', name: 'duration_days' },
            { data: 'base_price_per_person', name: 'base_price_per_person' },
            { data: 'capacity', name: 'capacity', orderable: false },
            { data: 'is_active', name: 'is_active' },
            { data: 'created_at', name: 'created_at' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ]
    });

    $('#quickSearch').on('input', function () {
        const v = $(this).val();
        clearTimeout(searchDebounce);
        searchDebounce = setTimeout(() => { currentFilter.q = v; table.ajax.reload(); }, 350);
    });

    $('#filterChips').on('click', '.chip', function () {
        $('#filterChips .chip').removeClass('active');
        $(this).addClass('active');

        currentFilter.type_filter = '';
        currentFilter.status_filter = '';

        const filter = $(this).data('filter');
        const value  = $(this).data('value') || '';
        if (filter === 'type')   currentFilter.type_filter   = value;
        if (filter === 'status') currentFilter.status_filter = value;

        table.ajax.reload();
    });

    $(document).on('click', '.btn-delete', function () {
        CoreX.ajaxDelete($(this).data('url'), table);
    });
});
</script>
@endpush
