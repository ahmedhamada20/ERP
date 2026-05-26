@extends('layouts.master')

@section('title', 'سجل رسائل WhatsApp')
@section('page_title', 'سجل رسائل WhatsApp')
@section('page_subtitle', 'كل الرسائل الصادرة والواردة عبر Meta Cloud API')

@push('styles')
<style>
    .kpi-card { background:#fff; border-radius:14px; padding:1rem 1.1rem; box-shadow:0 1px 4px rgba(15,23,42,.04); display:flex; align-items:center; gap:.85rem; height:100%; }
    .kpi-icon { width:48px; height:48px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:1.35rem; flex-shrink:0; }
    .kpi-body .lbl { font-size:.78rem; color:#64748b; font-weight:500; }
    .kpi-body .val { font-size:1.45rem; font-weight:800; color:var(--brand-navy); line-height:1; }

    .kpi-i-navy   { background:#eef2ff; color:#1e3a8a; }
    .kpi-i-info   { background:#dbeafe; color:#1d4ed8; }
    .kpi-i-blue   { background:#dbeafe; color:#1d4ed8; }
    .kpi-i-green  { background:#dcfce7; color:#15803d; }
    .kpi-i-red    { background:#fee2e2; color:#b91c1c; }

    .filter-bar { background:#fff; border-radius:12px; padding:1rem; box-shadow:0 1px 3px rgba(15,23,42,.04); border:1px solid var(--brand-border); margin-bottom:1rem; }

    .config-warning {
        background:linear-gradient(135deg, #fef3c7, #fde68a); color:#92400e;
        border-radius:12px; padding:1rem; margin-bottom:1rem;
        display:flex; align-items:center; gap:.75rem;
    }

    .x-small { font-size:.7rem; }

    .bg-success-soft { background:#dcfce7 !important; color:#15803d !important; }
    .bg-warning-soft { background:#fef3c7 !important; color:#b45309 !important; }
    .bg-info-soft    { background:#dbeafe !important; color:#1d4ed8 !important; }
    .bg-primary-soft { background:#e0e7ff !important; color:#4338ca !important; }
    .bg-secondary-soft { background:#f1f5f9 !important; color:#475569 !important; }
    .bg-danger-soft  { background:#fee2e2 !important; color:#b91c1c !important; }
    .btn-light-primary { background:#e0e7ff; color:#4338ca; border:none; }
</style>
@endpush

@section('content')

@if(!$isConfigured)
<div class="config-warning">
    <i class="bi bi-exclamation-triangle-fill" style="font-size:1.5rem;"></i>
    <div class="flex-fill">
        <strong>WhatsApp غير مُكوَّن</strong>
        <div class="small">لن تستطيع إرسال أي رسائل حتى تضبط الاعتمادات.</div>
    </div>
    @can('whatsapp.manage_settings')
    <a href="{{ route('admin.crm.whatsapp.settings.edit') }}" class="btn btn-warning">
        <i class="bi bi-gear"></i> ضبط الإعدادات
    </a>
    @endcan
</div>
@endif

<div class="row g-3 mb-3">
    <div class="col-xl-3 col-md-6 col-6">
        <div class="kpi-card">
            <div class="kpi-icon kpi-i-navy"><i class="bi bi-whatsapp"></i></div>
            <div class="kpi-body">
                <div class="lbl">إجمالي الرسائل</div>
                <div class="val">{{ number_format($stats['total']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 col-6">
        <div class="kpi-card">
            <div class="kpi-icon kpi-i-green"><i class="bi bi-check-all"></i></div>
            <div class="kpi-body">
                <div class="lbl">تم التسليم</div>
                <div class="val text-success">{{ number_format($stats['delivered']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 col-6">
        <div class="kpi-card">
            <div class="kpi-icon kpi-i-info"><i class="bi bi-inbox"></i></div>
            <div class="kpi-body">
                <div class="lbl">رسائل واردة</div>
                <div class="val">{{ number_format($stats['inbound']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 col-6">
        <div class="kpi-card">
            <div class="kpi-icon kpi-i-red"><i class="bi bi-x-octagon"></i></div>
            <div class="kpi-body">
                <div class="lbl">فشل الإرسال</div>
                <div class="val text-danger">{{ number_format($stats['failed']) }}</div>
            </div>
        </div>
    </div>
</div>

<div class="filter-bar">
    <div class="d-flex gap-2 flex-wrap align-items-center">
        <input type="search" id="quickSearch" class="form-control" style="max-width:300px;" placeholder="ابحث بالرقم، النص، اسم القالب...">

        <select id="statusFilter" class="form-select form-select-sm" style="max-width:160px;">
            <option value="">كل الحالات</option>
            <option value="queued">في الانتظار</option>
            <option value="sent">مُرسلة</option>
            <option value="delivered">تم التسليم</option>
            <option value="read">تمت القراءة</option>
            <option value="failed">فشل</option>
        </select>

        <select id="directionFilter" class="form-select form-select-sm" style="max-width:140px;">
            <option value="">صادر + وارد</option>
            <option value="outbound">صادر</option>
            <option value="inbound">وارد</option>
        </select>

        <select id="typeFilter" class="form-select form-select-sm" style="max-width:140px;">
            <option value="">كل الأنواع</option>
            <option value="text">نص</option>
            <option value="template">قالب</option>
            <option value="image">صورة</option>
            <option value="document">مستند</option>
        </select>

        <div class="ms-auto d-flex gap-2">
            @can('whatsapp.manage_settings')
            <a href="{{ route('admin.crm.whatsapp.settings.edit') }}" class="btn btn-outline-primary">
                <i class="bi bi-gear"></i> الإعدادات
            </a>
            @endcan
            <button type="button" class="btn btn-outline-secondary" onclick="window.location.reload()">
                <i class="bi bi-arrow-clockwise"></i>
            </button>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table id="messages-table" class="table pretty-table" style="width:100%;">
                <thead>
                    <tr>
                        <th width="40">#</th>
                        <th width="140">إلى</th>
                        <th width="100">الاتجاه</th>
                        <th width="120">النوع</th>
                        <th>المحتوى</th>
                        <th width="140">الحالة</th>
                        <th width="120">منذ</th>
                        <th width="60">عرض</th>
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
    let currentFilter = { q: '', status_filter: '', direction_filter: '', type_filter: '' };
    let searchDebounce = null;

    var table = $('#messages-table').DataTable({
        processing: true, serverSide: true, responsive: true,
        autoWidth: false, language: window.dtArabic,
        order: [[0, 'desc']], pageLength: 25,
        ajax: {
            url: '{{ route('admin.crm.whatsapp.messages.data') }}',
            data: d => Object.assign(d, currentFilter),
        },
        columns: [
            { data: 'id', name: 'id', visible: false },
            { data: 'to_phone', name: 'to_phone' },
            { data: 'direction', name: 'direction' },
            { data: 'message_type', name: 'message_type' },
            { data: 'body', name: 'body', orderable: false },
            { data: 'status', name: 'status' },
            { data: 'created_at', name: 'created_at' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false },
        ],
    });

    $('#quickSearch').on('input', function () {
        const v = $(this).val();
        clearTimeout(searchDebounce);
        searchDebounce = setTimeout(() => { currentFilter.q = v; table.ajax.reload(); }, 350);
    });

    $('#statusFilter').on('change', function () { currentFilter.status_filter = $(this).val(); table.ajax.reload(); });
    $('#directionFilter').on('change', function () { currentFilter.direction_filter = $(this).val(); table.ajax.reload(); });
    $('#typeFilter').on('change', function () { currentFilter.type_filter = $(this).val(); table.ajax.reload(); });
});
</script>
@endpush
