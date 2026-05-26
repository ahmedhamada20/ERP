@extends('layouts.master')

@section('title', 'الصفقات (Opportunities)')
@section('page_title', 'الصفقات')
@section('page_subtitle', 'إدارة الصفقات المفتوحة وتتبع قمع المبيعات')

@push('styles')
<style>
    .kpi-card { background:#fff; border-radius:14px; padding:1rem 1.1rem; box-shadow:0 1px 4px rgba(15,23,42,.04); display:flex; align-items:center; gap:.85rem; height:100%; }
    .kpi-icon { width:48px; height:48px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:1.35rem; flex-shrink:0; }
    .kpi-body .lbl { font-size:.78rem; color:#64748b; font-weight:500; margin-bottom:.15rem; }
    .kpi-body .val { font-size:1.4rem; font-weight:800; color:var(--brand-navy); line-height:1; }
    .kpi-body .sub { font-size:.7rem; color:#94a3b8; }
    .kpi-i-navy   { background:#eef2ff; color:#1e3a8a; }
    .kpi-i-blue   { background:#dbeafe; color:#1d4ed8; }
    .kpi-i-purple { background:#f3e8ff; color:#6b21a8; }
    .kpi-i-green  { background:#dcfce7; color:#15803d; }

    .filter-bar { background:#fff; border-radius:12px; padding:1rem; box-shadow:0 1px 3px rgba(15,23,42,.04); border:1px solid var(--brand-border); margin-bottom:1rem; }
    .filter-bar .search-wrap { position:relative; flex:1; min-width:240px; }
    .filter-bar .search-wrap .form-control { padding-right:2.5rem; height:44px; font-size:.9rem; border-radius:10px; background:#f8f9fc; border:1px solid var(--brand-border); }
    .filter-bar .search-wrap i.s-ico { position:absolute; right:.9rem; top:50%; transform:translateY(-50%); color:#64748b; }

    .view-toggle { display:flex; background:#f1f5f9; padding:3px; border-radius:9px; }
    .view-toggle a { padding:.45rem .85rem; border-radius:7px; font-size:.82rem; font-weight:700; color:#64748b; text-decoration:none; transition:all .15s; }
    .view-toggle a.active { background:#fff; color:var(--brand-navy); box-shadow:0 1px 3px rgba(15,23,42,.1); }

    .filter-chips { display:flex; gap:.5rem; flex-wrap:wrap; padding:.35rem 0; }
    .chip { display:inline-flex; align-items:center; gap:.35rem; padding:.4rem .85rem; background:#f1f5f9; color:#475569; border-radius:8px; font-size:.82rem; font-weight:600; cursor:pointer; }
    .chip.active { background:var(--brand-navy); color:#fff; }
    .chip .count { background:rgba(255,255,255,.25); font-size:.68rem; padding:1px 7px; border-radius:10px; font-weight:700; }
    .chip:not(.active) .count { background:#fff; color:var(--brand-navy); }
    .x-small { font-size:.7rem; }

    .bg-success-soft { background:#dcfce7 !important; color:#15803d !important; }
    .bg-warning-soft { background:#fef3c7 !important; color:#b45309 !important; }
    .bg-info-soft    { background:#dbeafe !important; color:#1d4ed8 !important; }
    .bg-primary-soft { background:#e0e7ff !important; color:#4338ca !important; }
    .bg-secondary-soft { background:#f1f5f9 !important; color:#475569 !important; }
    .bg-danger-soft  { background:#fee2e2 !important; color:#b91c1c !important; }
    .btn-light-primary { background:#e0e7ff; color:#4338ca; border:none; }
    .btn-light-info    { background:#dbeafe; color:#1d4ed8; border:none; }
    .btn-light-danger  { background:#fee2e2; color:#b91c1c; border:none; }
</style>
@endpush

@section('content')

<div class="row g-3 mb-3">
    <div class="col-xl-3 col-md-6 col-6">
        <div class="kpi-card">
            <div class="kpi-icon kpi-i-navy"><i class="bi bi-briefcase"></i></div>
            <div class="kpi-body">
                <div class="lbl">إجمالي الصفقات</div>
                <div class="val">{{ number_format($stats['total']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 col-6">
        <div class="kpi-card">
            <div class="kpi-icon kpi-i-blue"><i class="bi bi-bar-chart-line"></i></div>
            <div class="kpi-body">
                <div class="lbl">قيمة القمع المفتوحة</div>
                <div class="val">{{ number_format($stats['pipeline_value'], 0) }}</div>
                <div class="sub">ج.م — مرجح: {{ number_format($stats['weighted_pipeline'], 0) }}</div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 col-6">
        <div class="kpi-card">
            <div class="kpi-icon kpi-i-green"><i class="bi bi-trophy"></i></div>
            <div class="kpi-body">
                <div class="lbl">معدل الفوز</div>
                <div class="val">{{ $stats['win_rate'] }}<small>%</small></div>
                <div class="sub">{{ $stats['won'] }} فائز / {{ $stats['won'] + $stats['lost'] }} مغلق</div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 col-6">
        <div class="kpi-card">
            <div class="kpi-icon kpi-i-purple"><i class="bi bi-cash-stack"></i></div>
            <div class="kpi-body">
                <div class="lbl">إيرادات الفوز</div>
                <div class="val text-success">{{ number_format($stats['won_value'], 0) }}</div>
                <div class="sub">ج.م</div>
            </div>
        </div>
    </div>
</div>

<div class="filter-bar">
    <div class="d-flex gap-2 flex-wrap align-items-center">
        <div class="search-wrap">
            <i class="bi bi-search s-ico"></i>
            <input type="search" id="quickSearch" class="form-control" placeholder="ابحث بالعنوان، الكود، الوجهة، أو اسم العميل...">
        </div>

        <div class="view-toggle">
            <a href="{{ route('admin.crm.opportunities.index') }}" class="active"><i class="bi bi-table"></i> جدول</a>
            <a href="{{ route('admin.crm.opportunities.pipeline') }}"><i class="bi bi-kanban"></i> قمع</a>
        </div>

        <div class="ms-auto d-flex gap-2 flex-wrap">
            @can('opportunities.create')
            <a href="{{ route('admin.crm.opportunities.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> صفقة جديدة
            </a>
            @endcan
            <button type="button" class="btn btn-outline-secondary" onclick="window.location.reload()">
                <i class="bi bi-arrow-clockwise"></i>
            </button>
        </div>
    </div>

    <div class="filter-chips mt-3" id="filterChips">
        <span class="chip active" data-filter="all">
            الكل <span class="count">{{ $stats['total'] }}</span>
        </span>
        <span class="chip" data-filter="stage" data-value="prospecting">
            استكشاف <span class="count">{{ $stats['prospecting'] }}</span>
        </span>
        <span class="chip" data-filter="stage" data-value="qualification">
            تأهيل <span class="count">{{ $stats['qualification'] }}</span>
        </span>
        <span class="chip" data-filter="stage" data-value="proposal">
            عرض <span class="count">{{ $stats['proposal'] }}</span>
        </span>
        <span class="chip" data-filter="stage" data-value="negotiation">
            تفاوض <span class="count">{{ $stats['negotiation'] }}</span>
        </span>
        <span class="chip" data-filter="stage" data-value="closed_won">
            فوز <span class="count">{{ $stats['won'] }}</span>
        </span>
        <span class="chip" data-filter="stage" data-value="closed_lost">
            خسارة <span class="count">{{ $stats['lost'] }}</span>
        </span>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table id="opps-table" class="table pretty-table" style="width:100%;">
                <thead>
                    <tr>
                        <th width="40">#</th>
                        <th>الصفقة</th>
                        <th>العميل / Lead</th>
                        <th width="110">نوع الحجز</th>
                        <th width="140">الوجهة</th>
                        <th width="100">المرحلة</th>
                        <th width="140">القيمة</th>
                        <th width="80">عدد</th>
                        <th width="140">المسؤول</th>
                        <th width="100">منذ</th>
                        <th width="140">إجراءات</th>
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
    let currentFilter = { q: '', stage_filter: '', booking_type_filter: '', assignee_id: '' };
    let searchDebounce = null;

    var table = $('#opps-table').DataTable({
        processing: true, serverSide: true, responsive: true,
        autoWidth: false, language: window.dtArabic,
        order: [[0, 'desc']], pageLength: 25,
        ajax: {
            url: '{{ route('admin.crm.opportunities.data') }}',
            data: d => Object.assign(d, currentFilter),
        },
        columns: [
            { data: 'id', name: 'id', visible: false },
            { data: 'opp_info', name: 'title' },
            { data: 'source_party', name: 'lead.full_name', orderable: false },
            { data: 'booking_type', name: 'booking_type' },
            { data: 'destination', name: 'destination' },
            { data: 'stage', name: 'stage' },
            { data: 'estimated_value', name: 'estimated_value' },
            { data: 'pax', name: 'pax_count' },
            { data: 'assignee_name', name: 'assignee.name', orderable: false },
            { data: 'created_at', name: 'created_at' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false },
        ],
    });

    $('#quickSearch').on('input', function () {
        const v = $(this).val();
        clearTimeout(searchDebounce);
        searchDebounce = setTimeout(() => { currentFilter.q = v; table.ajax.reload(); }, 350);
    });

    $('#filterChips').on('click', '.chip', function () {
        $('#filterChips .chip').removeClass('active');
        $(this).addClass('active');
        currentFilter.stage_filter = $(this).data('value') || '';
        table.ajax.reload();
    });

    $(document).on('click', '.btn-delete', function () {
        CoreX.ajaxDelete($(this).data('url'), table);
    });
});
</script>
@endpush
