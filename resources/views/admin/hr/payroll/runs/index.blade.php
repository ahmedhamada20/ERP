@extends('layouts.master')

@section('title', 'دورات الرواتب')
@section('page_title', 'إدارة الرواتب — الدورات الشهرية')
@section('page_subtitle', 'احتساب، اعتماد، وترحيل رواتب الموظفين شهرياً لكل فرع')

@push('styles')
<style>
    .kpi-card { background:#fff; border-radius:14px; padding:1rem 1.1rem; box-shadow:0 1px 4px rgba(15,23,42,.04); display:flex; align-items:center; gap:.85rem; height:100%; }
    .kpi-icon { width:48px; height:48px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:1.35rem; flex-shrink:0; }
    .kpi-body .lbl { font-size:.78rem; color:#64748b; font-weight:500; margin-bottom:.15rem; }
    .kpi-body .val { font-size:1.45rem; font-weight:800; color:var(--brand-navy); line-height:1; }
    .kpi-i-navy   { background:#eef2ff; color:#1e3a8a; }
    .kpi-i-grey   { background:#f1f5f9; color:#475569; }
    .kpi-i-info   { background:#dbeafe; color:#1d4ed8; }
    .kpi-i-purple { background:#ede9fe; color:#6d28d9; }
    .kpi-i-green  { background:#dcfce7; color:#15803d; }
    .kpi-i-gold   { background:#fef3c7; color:#b45309; }

    .filter-bar { background:#fff; border-radius:12px; padding:1rem; box-shadow:0 1px 3px rgba(15,23,42,.04); border:1px solid var(--brand-border); margin-bottom:1rem; }
    .x-small { font-size:.7rem; }

    .bg-secondary-soft { background:#f1f5f9 !important; color:#475569 !important; }
    .bg-info-soft      { background:#dbeafe !important; color:#1e40af !important; }
    .bg-primary-soft   { background:#ede9fe !important; color:#6d28d9 !important; }
    .bg-success-soft   { background:#dcfce7 !important; color:#15803d !important; }
    .bg-danger-soft    { background:#fee2e2 !important; color:#b91c1c !important; }

    .btn-light-primary { background:#e0e7ff; color:#4338ca; border:none; }
    .btn-light-danger  { background:#fee2e2; color:#b91c1c; border:none; }
</style>
@endpush

@section('content')

<div class="row g-3 mb-3">
    <div class="col-xl-3 col-md-6 col-6">
        <div class="kpi-card">
            <div class="kpi-icon kpi-i-navy"><i class="bi bi-cash-stack"></i></div>
            <div class="kpi-body">
                <div class="lbl">إجمالي الدورات</div>
                <div class="val">{{ number_format($stats['total']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 col-6">
        <div class="kpi-card">
            <div class="kpi-icon kpi-i-info"><i class="bi bi-calculator"></i></div>
            <div class="kpi-body">
                <div class="lbl">محسوبة (تحت المراجعة)</div>
                <div class="val">{{ number_format($stats['calculated'] + $stats['approved']) }}</div>
                <div class="sub x-small text-muted">منها {{ $stats['approved'] }} معتمدة</div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 col-6">
        <div class="kpi-card">
            <div class="kpi-icon kpi-i-green"><i class="bi bi-check-all"></i></div>
            <div class="kpi-body">
                <div class="lbl">دورات مرحّلة</div>
                <div class="val text-success">{{ number_format($stats['posted']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 col-6">
        <div class="kpi-card">
            <div class="kpi-icon kpi-i-gold"><i class="bi bi-coin"></i></div>
            <div class="kpi-body">
                <div class="lbl">إجمالي المدفوع</div>
                <div class="val" style="font-size:1.1rem;">{{ number_format((float) $stats['total_paid'], 0) }} <small class="text-muted">ج.م</small></div>
            </div>
        </div>
    </div>
</div>

<div class="filter-bar">
    <div class="d-flex gap-2 flex-wrap align-items-center">
        <input type="search" id="quickSearch" class="form-control" style="max-width:240px;" placeholder="ابحث برقم الدورة...">

        <select id="branchFilter" class="form-select" style="max-width:200px;">
            <option value="">كل الفروع</option>
            @foreach($branches as $b)
                <option value="{{ $b->id }}">{{ $b->name }}</option>
            @endforeach
        </select>

        <select id="yearFilter" class="form-select" style="max-width:130px;">
            <option value="">كل السنوات</option>
            @foreach($years as $y)
                <option value="{{ $y }}">{{ $y }}</option>
            @endforeach
        </select>

        <select id="statusFilter" class="form-select" style="max-width:160px;">
            <option value="">كل الحالات</option>
            <option value="draft">مسودة</option>
            <option value="calculated">تم الحساب</option>
            <option value="approved">معتمدة</option>
            <option value="posted">مرحّلة</option>
            <option value="cancelled">ملغاة</option>
        </select>

        <div class="ms-auto">
            @can('payroll.process')
            <a href="{{ route('admin.hr.payroll.runs.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> دورة رواتب جديدة
            </a>
            @endcan
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table id="runsTable" class="table table-hover align-middle" style="width:100%">
                <thead class="table-light">
                    <tr>
                        <th>الدورة</th>
                        <th>الفرع</th>
                        <th>الموظفون</th>
                        <th class="text-end">إجمالي المستحق</th>
                        <th class="text-end">صافي الرواتب</th>
                        <th>تاريخ الصرف</th>
                        <th>الحالة</th>
                        <th>إجراءات</th>
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
    const table = $('#runsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: @json(route('admin.hr.payroll.runs.data')),
            data: function (d) {
                d.q             = $('#quickSearch').val();
                d.branch_filter = $('#branchFilter').val();
                d.year_filter   = $('#yearFilter').val();
                d.status_filter = $('#statusFilter').val();
            }
        },
        columns: [
            { data: 'run_info',        name: 'run_code' },
            { data: 'branch_name',     name: 'branch.name', orderable: false },
            { data: 'employees_count', name: 'employees_count', className: 'text-center' },
            { data: 'total_earnings',  name: 'total_earnings' },
            { data: 'total_net',       name: 'total_net' },
            { data: 'payment_date',    name: 'payment_date' },
            { data: 'status',          name: 'status' },
            { data: 'actions',         orderable: false, searchable: false, className: 'text-center' },
        ],
        order: [[0, 'desc']],
        language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/ar.json' },
        pageLength: 25,
    });

    let typingTimer;
    $('#quickSearch').on('keyup', function () {
        clearTimeout(typingTimer);
        typingTimer = setTimeout(() => table.ajax.reload(), 350);
    });
    $('#branchFilter, #yearFilter, #statusFilter').on('change', () => table.ajax.reload());

    // Delete handler
    $(document).on('click', '.btn-delete', function () {
        if (! confirm('هل تريد حذف دورة الرواتب نهائياً؟ هذا متاح فقط للمسودات.')) return;
        const url = $(this).data('url');
        $.ajax({
            url: url,
            method: 'DELETE',
            data: { _token: @json(csrf_token()) },
            success: () => { table.ajax.reload(); },
            error: (xhr) => alert(xhr.responseJSON?.message ?? 'حدث خطأ.'),
        });
    });
});
</script>
@endpush
