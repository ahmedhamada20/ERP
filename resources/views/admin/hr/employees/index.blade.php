@extends('layouts.master')

@section('title', 'الموظفون')
@section('page_title', 'إدارة الموظفين')
@section('page_subtitle', 'كل من يعمل في الشركة — مع الرواتب والعمولات والإدارة')

@push('styles')
<style>
    .kpi-card { background:#fff; border-radius:14px; padding:1rem 1.1rem; box-shadow:0 1px 4px rgba(15,23,42,.04); display:flex; align-items:center; gap:.85rem; height:100%; }
    .kpi-icon { width:48px; height:48px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:1.35rem; flex-shrink:0; }
    .kpi-body .lbl { font-size:.78rem; color:#64748b; font-weight:500; margin-bottom:.15rem; }
    .kpi-body .val { font-size:1.45rem; font-weight:800; color:var(--brand-navy); line-height:1; }

    .kpi-i-navy   { background:#eef2ff; color:#1e3a8a; }
    .kpi-i-green  { background:#dcfce7; color:#15803d; }
    .kpi-i-info   { background:#dbeafe; color:#1d4ed8; }
    .kpi-i-red    { background:#fee2e2; color:#b91c1c; }
    .kpi-i-warn   { background:#fef3c7; color:#b45309; }

    .filter-bar { background:#fff; border-radius:12px; padding:1rem; box-shadow:0 1px 3px rgba(15,23,42,.04); border:1px solid var(--brand-border); margin-bottom:1rem; }
    .filter-chips { display:flex; gap:.5rem; flex-wrap:wrap; padding:.35rem 0; }
    .chip { display:inline-flex; align-items:center; gap:.35rem; padding:.4rem .85rem; background:#f1f5f9; color:#475569; border-radius:8px; font-size:.82rem; font-weight:600; cursor:pointer; }
    .chip.active { background:var(--brand-navy); color:#fff; }
    .chip .count { background:rgba(255,255,255,.25); font-size:.68rem; padding:1px 7px; border-radius:10px; font-weight:700; }
    .chip:not(.active) .count { background:#fff; color:var(--brand-navy); }

    .x-small { font-size:.7rem; }
    .bg-success-soft   { background:#dcfce7 !important; color:#15803d !important; }
    .bg-info-soft      { background:#dbeafe !important; color:#1d4ed8 !important; }
    .bg-danger-soft    { background:#fee2e2 !important; color:#b91c1c !important; }
    .bg-warning-soft   { background:#fef3c7 !important; color:#b45309 !important; }
    .bg-secondary-soft { background:#f1f5f9 !important; color:#475569 !important; }
    .btn-light-primary { background:#e0e7ff; color:#4338ca; border:none; }
    .btn-light-info    { background:#dbeafe; color:#1d4ed8; border:none; }
    .btn-light-danger  { background:#fee2e2; color:#b91c1c; border:none; }
</style>
@endpush

@section('content')

<div class="row g-3 mb-3">
    <div class="col-xl col-md-4 col-6">
        <div class="kpi-card">
            <div class="kpi-icon kpi-i-navy"><i class="bi bi-people"></i></div>
            <div class="kpi-body">
                <div class="lbl">إجمالي الموظفين</div>
                <div class="val">{{ number_format($stats['total']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-xl col-md-4 col-6">
        <div class="kpi-card">
            <div class="kpi-icon kpi-i-green"><i class="bi bi-check-circle"></i></div>
            <div class="kpi-body">
                <div class="lbl">نشط</div>
                <div class="val text-success">{{ number_format($stats['active']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-xl col-md-4 col-6">
        <div class="kpi-card">
            <div class="kpi-icon kpi-i-info"><i class="bi bi-airplane"></i></div>
            <div class="kpi-body">
                <div class="lbl">في إجازة</div>
                <div class="val">{{ number_format($stats['on_leave']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-xl col-md-4 col-6">
        <div class="kpi-card">
            <div class="kpi-icon kpi-i-warn"><i class="bi bi-pause-circle"></i></div>
            <div class="kpi-body">
                <div class="lbl">موقوف</div>
                <div class="val">{{ number_format($stats['suspended']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-xl col-md-4 col-6">
        <div class="kpi-card">
            <div class="kpi-icon kpi-i-red"><i class="bi bi-x-circle"></i></div>
            <div class="kpi-body">
                <div class="lbl">منتهي الخدمة</div>
                <div class="val">{{ number_format($stats['terminated']) }}</div>
            </div>
        </div>
    </div>
</div>

<div class="filter-bar">
    <div class="d-flex gap-2 flex-wrap align-items-center">
        <input type="search" id="quickSearch" class="form-control" style="max-width:280px;" placeholder="ابحث بالاسم، الكود، الرقم القومي، الهاتف...">

        <select id="branchFilter" class="form-select" style="max-width:200px;">
            <option value="">كل الفروع</option>
            @foreach($branches as $b)
                <option value="{{ $b->id }}">{{ $b->name }}</option>
            @endforeach
        </select>

        <select id="departmentFilter" class="form-select" style="max-width:200px;">
            <option value="">كل الأقسام</option>
            @foreach($departments as $d)
                <option value="{{ $d->id }}">{{ $d->name }}</option>
            @endforeach
        </select>

        <div class="ms-auto d-flex gap-2 flex-wrap">
            @can('employees.create')
            <a href="{{ route('admin.hr.employees.create') }}" class="btn btn-primary">
                <i class="bi bi-person-plus"></i> موظف جديد
            </a>
            @endcan
            <button type="button" class="btn btn-outline-secondary" onclick="window.location.reload()">
                <i class="bi bi-arrow-clockwise"></i>
            </button>
        </div>
    </div>

    <div class="filter-chips mt-3" id="filterChips">
        <span class="chip active" data-value="">الكل <span class="count">{{ $stats['total'] }}</span></span>
        <span class="chip" data-value="active">نشط <span class="count">{{ $stats['active'] }}</span></span>
        <span class="chip" data-value="on_leave">إجازة <span class="count">{{ $stats['on_leave'] }}</span></span>
        <span class="chip" data-value="suspended">موقوف <span class="count">{{ $stats['suspended'] }}</span></span>
        <span class="chip" data-value="terminated">منتهي <span class="count">{{ $stats['terminated'] }}</span></span>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table id="employees-table" class="table pretty-table" style="width:100%;">
                <thead>
                    <tr>
                        <th width="40">#</th>
                        <th>الموظف</th>
                        <th width="180">التواصل</th>
                        <th width="220">التنظيم</th>
                        <th width="120">الراتب</th>
                        <th width="120">تاريخ التعيين</th>
                        <th width="90">الحالة</th>
                        <th width="140">الإجراءات</th>
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
    let currentFilter = { q: '', status_filter: '', branch_filter: '', department_filter: '' };
    let searchDebounce = null;

    var table = $('#employees-table').DataTable({
        processing: true, serverSide: true, responsive: true,
        autoWidth: false, language: window.dtArabic,
        order: [[0, 'desc']], pageLength: 25,
        ajax: {
            url: '{{ route('admin.hr.employees.data') }}',
            data: d => Object.assign(d, currentFilter),
        },
        columns: [
            { data: 'id', name: 'id', visible: false },
            { data: 'emp_info', name: 'full_name' },
            { data: 'contact', name: 'phone', orderable: false },
            { data: 'org', name: 'position_id', orderable: false },
            { data: 'salary', name: 'basic_salary', orderable: false },
            { data: 'hire_date', name: 'hire_date' },
            { data: 'status', name: 'status' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false },
        ],
    });

    $('#quickSearch').on('input', function () {
        const v = $(this).val();
        clearTimeout(searchDebounce);
        searchDebounce = setTimeout(() => { currentFilter.q = v; table.ajax.reload(); }, 350);
    });

    $('#branchFilter').on('change', function () {
        currentFilter.branch_filter = $(this).val();
        table.ajax.reload();
    });

    $('#departmentFilter').on('change', function () {
        currentFilter.department_filter = $(this).val();
        table.ajax.reload();
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
