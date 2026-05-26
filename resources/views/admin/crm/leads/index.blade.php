@extends('layouts.master')

@section('title', 'العملاء المحتملون')
@section('page_title', 'العملاء المحتملون')
@section('page_subtitle', 'إدارة الـ Leads ومتابعة قمع المبيعات')

@push('styles')
<style>
    .kpi-card { background:#fff; border-radius:14px; padding:1rem 1.1rem; box-shadow:0 1px 4px rgba(15,23,42,.04); display:flex; align-items:center; gap:.85rem; height:100%; transition:transform .15s, box-shadow .15s; }
    .kpi-card:hover { transform:translateY(-2px); box-shadow:0 6px 16px rgba(15,23,42,.07); }
    .kpi-icon { width:48px; height:48px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:1.35rem; flex-shrink:0; }
    .kpi-body .lbl { font-size:.78rem; color:#64748b; font-weight:500; margin-bottom:.15rem; }
    .kpi-body .val { font-size:1.45rem; font-weight:800; color:var(--brand-navy); line-height:1; }
    .kpi-body .sub { font-size:.7rem; color:#94a3b8; }
    .kpi-i-navy   { background:#eef2ff; color:#1e3a8a; }
    .kpi-i-green  { background:#dcfce7; color:#15803d; }
    .kpi-i-gold   { background:#fef3c7; color:#b45309; }
    .kpi-i-blue   { background:#dbeafe; color:#1d4ed8; }
    .kpi-i-red    { background:#fee2e2; color:#b91c1c; }
    .kpi-i-purple { background:#f3e8ff; color:#6b21a8; }

    .filter-bar { background:#fff; border-radius:12px; padding:1rem; box-shadow:0 1px 3px rgba(15,23,42,.04); border:1px solid var(--brand-border); margin-bottom:1rem; }
    .filter-bar .search-wrap { position:relative; flex:1; min-width:240px; }
    .filter-bar .search-wrap .form-control { padding-right:2.5rem; height:44px; font-size:.9rem; border-radius:10px; background:#f8f9fc; border:1px solid var(--brand-border); }
    .filter-bar .search-wrap i.s-ico { position:absolute; right:.9rem; top:50%; transform:translateY(-50%); color:#64748b; }

    .filter-chips { display:flex; gap:.5rem; flex-wrap:wrap; padding:.35rem 0; }
    .chip { display:inline-flex; align-items:center; gap:.35rem; padding:.4rem .85rem; background:#f1f5f9; color:#475569; border-radius:8px; font-size:.82rem; font-weight:600; cursor:pointer; border:1px solid transparent; transition:all .15s; user-select:none; }
    .chip:hover { background:#e2e8f0; }
    .chip.active { background:var(--brand-navy); color:#fff; border-color:var(--brand-navy); }
    .chip .count { background:rgba(255,255,255,.25); font-size:.68rem; padding:1px 7px; border-radius:10px; font-weight:700; }
    .chip:not(.active) .count { background:#fff; color:var(--brand-navy); }

    .view-toggle { display:flex; background:#f1f5f9; padding:3px; border-radius:9px; }
    .view-toggle a { padding:.45rem .85rem; border-radius:7px; font-size:.82rem; font-weight:700; color:#64748b; text-decoration:none; transition:all .15s; }
    .view-toggle a.active { background:#fff; color:var(--brand-navy); box-shadow:0 1px 3px rgba(15,23,42,.1); }

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

{{-- KPI cards --}}
<div class="row g-3 mb-3">
    <div class="col-xl-3 col-md-6 col-6">
        <div class="kpi-card">
            <div class="kpi-icon kpi-i-navy"><i class="bi bi-person-lines-fill"></i></div>
            <div class="kpi-body">
                <div class="lbl">إجمالي الـ Leads</div>
                <div class="val">{{ number_format($stats['total']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 col-6">
        <div class="kpi-card">
            <div class="kpi-icon kpi-i-gold"><i class="bi bi-cash-coin"></i></div>
            <div class="kpi-body">
                <div class="lbl">قيمة قمع المبيعات</div>
                <div class="val">{{ number_format($stats['pipeline_value'], 0) }}</div>
                <div class="sub">ج.م مفتوحة (غير مغلقة)</div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 col-6">
        <div class="kpi-card">
            <div class="kpi-icon kpi-i-green"><i class="bi bi-trophy"></i></div>
            <div class="kpi-body">
                <div class="lbl">معدل التحويل</div>
                <div class="val">{{ $stats['conversion_rate'] }}<small>%</small></div>
                <div class="sub">{{ $stats['won'] }} فائز / {{ $stats['won'] + $stats['lost'] }} مغلق</div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 col-6">
        <div class="kpi-card">
            <div class="kpi-icon kpi-i-purple"><i class="bi bi-check2-circle"></i></div>
            <div class="kpi-body">
                <div class="lbl">قيمة الصفقات الفائزة</div>
                <div class="val text-success">{{ number_format($stats['won_value'], 0) }}</div>
                <div class="sub">ج.م</div>
            </div>
        </div>
    </div>
</div>

{{-- Filter bar --}}
<div class="filter-bar">
    <div class="d-flex gap-2 flex-wrap align-items-center">
        <div class="search-wrap">
            <i class="bi bi-search s-ico"></i>
            <input type="search" id="quickSearch" class="form-control" placeholder="ابحث بالاسم، الهاتف، البريد، أو الكود...">
        </div>

        <div class="view-toggle">
            <a href="{{ route('admin.crm.leads.index') }}" class="active"><i class="bi bi-table"></i> جدول</a>
            <a href="{{ route('admin.crm.leads.kanban') }}"><i class="bi bi-kanban"></i> قمع</a>
        </div>

        <div class="ms-auto d-flex gap-2 flex-wrap">
            @can('leads.create')
            <a href="{{ route('admin.crm.leads.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Lead جديد
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
        <span class="chip" data-filter="status" data-value="new">
            <i class="bi bi-stars"></i> جديد <span class="count">{{ $stats['new'] }}</span>
        </span>
        <span class="chip" data-filter="status" data-value="contacted">
            <i class="bi bi-telephone"></i> تم التواصل <span class="count">{{ $stats['contacted'] }}</span>
        </span>
        <span class="chip" data-filter="status" data-value="qualified">
            <i class="bi bi-check-square"></i> مؤهل <span class="count">{{ $stats['qualified'] }}</span>
        </span>
        <span class="chip" data-filter="status" data-value="proposal">
            <i class="bi bi-file-earmark-text"></i> عرض مقدم <span class="count">{{ $stats['proposal'] }}</span>
        </span>
        <span class="chip" data-filter="status" data-value="won">
            <i class="bi bi-trophy"></i> فائز <span class="count">{{ $stats['won'] }}</span>
        </span>
        <span class="chip" data-filter="status" data-value="lost">
            <i class="bi bi-x-circle"></i> خاسر <span class="count">{{ $stats['lost'] }}</span>
        </span>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table id="leads-table" class="table pretty-table" style="width:100%;">
                <thead>
                    <tr>
                        <th width="40">#</th>
                        <th>العميل المحتمل</th>
                        <th>التواصل</th>
                        <th width="110">المصدر</th>
                        <th width="110">الاهتمام</th>
                        <th width="110">الحالة</th>
                        <th width="120">القيمة</th>
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
    let currentFilter = { q: '', status_filter: '', source_filter: '', interest_filter: '', assignee_id: '' };
    let searchDebounce = null;

    var table = $('#leads-table').DataTable({
        processing: true, serverSide: true, responsive: true,
        autoWidth: false, language: window.dtArabic,
        order: [[0, 'desc']], pageLength: 25,
        ajax: {
            url: '{{ route('admin.crm.leads.data') }}',
            data: d => Object.assign(d, currentFilter)
        },
        columns: [
            { data: 'id', name: 'id', visible: false },
            { data: 'lead_info', name: 'full_name' },
            { data: 'contact', name: 'phone', orderable: false },
            { data: 'source', name: 'source' },
            { data: 'interest_type', name: 'interest_type' },
            { data: 'status', name: 'status' },
            { data: 'estimated_value', name: 'estimated_value' },
            { data: 'assignee_name', name: 'assignee.name', orderable: false },
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
        currentFilter.status_filter = $(this).data('value') || '';
        table.ajax.reload();
    });

    $(document).on('click', '.btn-delete', function () {
        CoreX.ajaxDelete($(this).data('url'), table);
    });
});
</script>
@endpush
