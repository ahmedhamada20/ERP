@extends('layouts.master')

@section('title', 'الحجوزات الدينية')
@section('page_title', 'حجوزات الحج والعمرة')
@section('page_subtitle', 'إدارة جميع حجوزات السياحة الدينية - من الإنشاء حتى الإقفال المالي')

@push('styles')
<style>
    .kpi-card { background:#fff; border-radius:14px; padding:1rem 1.1rem; box-shadow:0 1px 4px rgba(15,23,42,.04); display:flex; align-items:center; gap:.85rem; height:100%; transition:transform .15s, box-shadow .15s; }
    .kpi-card:hover { transform:translateY(-2px); box-shadow:0 6px 16px rgba(15,23,42,.07); }
    .kpi-icon { width:46px; height:46px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:1.3rem; flex-shrink:0; }
    .kpi-body .lbl { font-size:.76rem; color:var(--text-muted); font-weight:500; margin-bottom:.1rem; }
    .kpi-body .val { font-size:1.35rem; font-weight:800; color:var(--brand-navy); line-height:1; }
    .kpi-body .sub { font-size:.68rem; color:#94a3b8; margin-top:.15rem; }

    .kpi-i-navy   { background:#eef2ff; color:#1e3a8a; }
    .kpi-i-green  { background:#dcfce7; color:#15803d; }
    .kpi-i-gold   { background:#fef3c7; color:#b45309; }
    .kpi-i-blue   { background:#dbeafe; color:#1d4ed8; }
    .kpi-i-purple { background:#f3e8ff; color:#6b21a8; }
    .kpi-i-red    { background:#fee2e2; color:#b91c1c; }
    .kpi-i-teal   { background:#ccfbf1; color:#0f766e; }

    .filter-bar { background:#fff; border-radius:12px; padding:1rem; box-shadow:0 1px 3px rgba(15,23,42,.04); border:1px solid var(--brand-border); margin-bottom:1rem; }
    .filter-bar .search-wrap { position:relative; flex:1; min-width:240px; }
    .filter-bar .search-wrap .form-control { padding-right:2.5rem; height:42px; border-radius:10px; background:#f8f9fc; }
    .filter-bar .search-wrap i.s-ico { position:absolute; right:.9rem; top:50%; transform:translateY(-50%); color:var(--text-muted); }

    .filter-chips { display:flex; gap:.5rem; flex-wrap:wrap; padding:.35rem 0; }
    .chip { display:inline-flex; align-items:center; gap:.35rem; padding:.4rem .85rem; background:#f1f5f9; color:#475569; border-radius:8px; font-size:.82rem; font-weight:600; cursor:pointer; border:1px solid transparent; user-select:none; }
    .chip:hover { background:#e2e8f0; }
    .chip.active { background:var(--brand-navy); color:#fff; border-color:var(--brand-navy); }
    .chip .count { background:rgba(255,255,255,.25); font-size:.68rem; padding:1px 7px; border-radius:10px; font-weight:700; }
    .chip:not(.active) .count { background:#fff; color:var(--brand-navy); }

    .booking-cell { display:flex; align-items:center; gap:.65rem; }
    .booking-icon { width:42px; height:42px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.2rem; flex-shrink:0; }
    .booking-num { font-family:'Cairo',monospace; font-size:.85rem; color:var(--brand-navy); }

    .bg-success-soft   { background:#dcfce7 !important; color:#15803d !important; }
    .bg-warning-soft   { background:#fef3c7 !important; color:#b45309 !important; }
    .bg-danger-soft    { background:#fee2e2 !important; color:#b91c1c !important; }
    .bg-secondary-soft { background:#f1f5f9 !important; color:#475569 !important; }
    .bg-info-soft      { background:#dbeafe !important; color:#1d4ed8 !important; }
    .bg-primary-soft   { background:#e0e7ff !important; color:#4338ca !important; }

    .btn-light-primary { background:#e0e7ff; color:#4338ca; border:none; }
    .btn-light-info    { background:#dbeafe; color:#1d4ed8; border:none; }
    .btn-light-danger  { background:#fee2e2; color:#b91c1c; border:none; }
</style>
@endpush

@section('content')

{{-- KPI Cards --}}
<div class="row g-3 mb-3">
    <div class="col-xl-2 col-md-4 col-6">
        <div class="kpi-card">
            <div class="kpi-icon kpi-i-navy"><i class="bi bi-journal-bookmark-fill"></i></div>
            <div class="kpi-body">
                <div class="lbl">إجمالي الحجوزات</div>
                <div class="val">{{ number_format($stats['total']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <div class="kpi-card">
            <div class="kpi-icon kpi-i-green"><i class="bi bi-mosque"></i></div>
            <div class="kpi-body">
                <div class="lbl">حج</div>
                <div class="val">{{ number_format($stats['hajj_total']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <div class="kpi-card">
            <div class="kpi-icon kpi-i-blue"><i class="bi bi-moon-stars"></i></div>
            <div class="kpi-body">
                <div class="lbl">عمرة</div>
                <div class="val">{{ number_format($stats['umrah_total']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <div class="kpi-card">
            <div class="kpi-icon kpi-i-gold"><i class="bi bi-calendar-event"></i></div>
            <div class="kpi-body">
                <div class="lbl">رحلات قادمة (30 يوم)</div>
                <div class="val">{{ number_format($stats['upcoming_30']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <div class="kpi-card">
            <div class="kpi-icon kpi-i-teal"><i class="bi bi-cash-stack"></i></div>
            <div class="kpi-body">
                <div class="lbl">إجمالي المبيعات</div>
                <div class="val" style="font-size:1.05rem;">{{ number_format($stats['revenue'], 0) }}</div>
                <div class="sub">جنيه مصري</div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <div class="kpi-card">
            <div class="kpi-icon kpi-i-purple"><i class="bi bi-graph-up-arrow"></i></div>
            <div class="kpi-body">
                <div class="lbl">صافي الربح</div>
                <div class="val" style="font-size:1.05rem;">{{ number_format($stats['profit'], 0) }}</div>
                <div class="sub">جنيه مصري</div>
            </div>
        </div>
    </div>
</div>

{{-- Filter Bar --}}
<div class="filter-bar">
    <div class="d-flex gap-2 flex-wrap align-items-center">
        <div class="search-wrap">
            <i class="bi bi-search s-ico"></i>
            <input type="search" id="quickSearch" class="form-control" placeholder="ابحث برقم الحجز، العميل، الباركود...">
        </div>

        <div class="d-flex gap-2 flex-wrap">
            <input type="date" id="f_from" class="form-control" style="max-width:150px;height:42px;" placeholder="من تاريخ">
            <input type="date" id="f_to" class="form-control" style="max-width:150px;height:42px;" placeholder="إلى تاريخ">
        </div>

        <div class="ms-auto d-flex gap-2 flex-wrap">
            @can('religious_bookings.create')
            <a href="{{ route('admin.religious.bookings.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> حجز جديد
            </a>
            @endcan
            <button type="button" class="btn btn-outline-secondary" onclick="window.location.reload()">
                <i class="bi bi-arrow-clockwise"></i>
            </button>
        </div>
    </div>

    {{-- Quick chips --}}
    <div class="filter-chips mt-3" id="filterChips">
        <span class="chip active" data-filter="all">
            <i class="bi bi-collection"></i> الكل <span class="count">{{ $stats['total'] }}</span>
        </span>
        <span class="chip" data-filter="type" data-value="hajj">
            <i class="bi bi-mosque"></i> حج <span class="count">{{ $stats['hajj_total'] }}</span>
        </span>
        <span class="chip" data-filter="type" data-value="umrah">
            <i class="bi bi-moon-stars"></i> عمرة <span class="count">{{ $stats['umrah_total'] }}</span>
        </span>
        <span class="chip" data-filter="status" data-value="pending">
            <i class="bi bi-hourglass-split"></i> قيد الانتظار <span class="count">{{ $stats['pending'] }}</span>
        </span>
        <span class="chip" data-filter="status" data-value="confirmed">
            <i class="bi bi-check-circle"></i> مؤكد <span class="count">{{ $stats['confirmed'] }}</span>
        </span>
        <span class="chip" data-filter="status" data-value="in_progress">
            <i class="bi bi-airplane"></i> جارية <span class="count">{{ $stats['in_progress'] }}</span>
        </span>
        <span class="chip" data-filter="status" data-value="completed">
            <i class="bi bi-check-all"></i> مكتمل <span class="count">{{ $stats['completed'] }}</span>
        </span>
        <span class="chip" data-filter="status" data-value="cancelled">
            <i class="bi bi-x-circle"></i> ملغي <span class="count">{{ $stats['cancelled'] }}</span>
        </span>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table id="bookings-table" class="table pretty-table" style="width:100%;">
                <thead>
                    <tr>
                        <th width="60">#</th>
                        <th>الحجز</th>
                        <th>العميل</th>
                        <th width="160">الرحلة</th>
                        <th width="120">الأفراد</th>
                        <th width="160">المبيعات / الربح</th>
                        <th width="120">الحالة</th>
                        <th width="130">المرحلة</th>
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
    let currentFilter = { q:'', type_filter:'', status_filter:'', date_from:'', date_to:'' };
    let searchDebounce = null;

    var table = $('#bookings-table').DataTable({
        processing: true, serverSide: true, responsive: true,
        autoWidth: false, language: window.dtArabic,
        order: [[0,'desc']], pageLength: 25,
        ajax: {
            url: '{{ route('admin.religious.bookings.data') }}',
            data: d => Object.assign(d, currentFilter)
        },
        columns: [
            { data: 'id', name: 'id', visible: false },
            { data: 'booking_info', name: 'booking_number' },
            { data: 'customer_info', name: 'customer.full_name', orderable: false },
            { data: 'trip_info', name: 'trip_date' },
            { data: 'pax', name: 'adults_count', orderable: false },
            { data: 'money', name: 'selling_price' },
            { data: 'status', name: 'status' },
            { data: 'workflow_stage', name: 'workflow_stage' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ]
    });

    $('#quickSearch').on('input', function () {
        const v = $(this).val();
        clearTimeout(searchDebounce);
        searchDebounce = setTimeout(() => { currentFilter.q = v; table.ajax.reload(); }, 350);
    });

    $('#f_from, #f_to').on('change', function () {
        currentFilter.date_from = $('#f_from').val();
        currentFilter.date_to   = $('#f_to').val();
        table.ajax.reload();
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
