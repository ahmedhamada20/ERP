@extends('layouts.master')

@section('title', 'سجل التدقيق')
@section('page_title', 'سجل تدقيق النظام')
@section('page_subtitle', 'تتبع كل عملية حساسة على بيانات العملاء والمستخدمين (مَن فعل ماذا ومتى)')

@push('styles')
<style>
    .bg-success-soft   { background: #dcfce7 !important; color: #15803d !important; }
    .bg-warning-soft   { background: #fef3c7 !important; color: #b45309 !important; }
    .bg-danger-soft    { background: #fee2e2 !important; color: #b91c1c !important; }
    .bg-secondary-soft { background: #f1f5f9 !important; color: #475569 !important; }
    .bg-info-soft      { background: #dbeafe !important; color: #1d4ed8 !important; }
    .bg-primary-soft   { background: #e0e7ff !important; color: #4338ca !important; }

    .audit-filter {
        background: #fff;
        border-radius: 12px;
        padding: 1rem;
        box-shadow: 0 1px 3px rgba(15,23,42,.04);
        border: 1px solid var(--brand-border);
        margin-bottom: 1rem;
    }
    .audit-filter .form-label { font-size: .78rem; font-weight: 700; color: #475569; margin-bottom: .35rem; }
    .audit-filter .form-control, .audit-filter .form-select {
        height: 40px; font-size: .88rem; border-radius: 9px;
        background: #fafbff; border: 1px solid var(--brand-border);
    }

    #audit-table tbody td { padding: .85rem .65rem; vertical-align: top; font-size: .85rem; }
</style>
@endpush

@section('content')

<div class="audit-filter">
    <div class="row g-3 align-items-end">
        <div class="col-sm-6 col-md-3">
            <label class="form-label"><i class="bi bi-collection text-primary"></i> الموديل</label>
            <select id="f_log_name" class="form-select">
                <option value="">— الكل —</option>
                <option value="customer">العملاء</option>
                <option value="user">المستخدمون</option>
            </select>
        </div>
        <div class="col-sm-6 col-md-3">
            <label class="form-label"><i class="bi bi-arrow-repeat text-info"></i> نوع العملية</label>
            <select id="f_event" class="form-select">
                <option value="">— الكل —</option>
                <option value="created">إنشاء</option>
                <option value="updated">تعديل</option>
                <option value="deleted">حذف</option>
            </select>
        </div>
        <div class="col-sm-6 col-md-3">
            <label class="form-label"><i class="bi bi-calendar3"></i> من تاريخ</label>
            <input type="date" id="f_date_from" class="form-control">
        </div>
        <div class="col-sm-6 col-md-3">
            <label class="form-label"><i class="bi bi-calendar3"></i> إلى تاريخ</label>
            <input type="date" id="f_date_to" class="form-control">
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5><i class="bi bi-shield-check text-primary me-1"></i> سجل العمليات</h5>
        <p class="text-muted small mb-0">يتم تسجيل كل عملية إنشاء وتعديل وحذف على البيانات الحساسة تلقائياً</p>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="audit-table" class="table pretty-table" style="width:100%;">
                <thead>
                    <tr>
                        <th width="100">العملية</th>
                        <th>التفاصيل</th>
                        <th width="100">الموديل</th>
                        <th>التغييرات</th>
                        <th width="160">المستخدم</th>
                        <th width="130">التاريخ</th>
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
    let filters = { log_name: '', event: '', date_from: '', date_to: '' };

    var table = $('#audit-table').DataTable({
        processing: true, serverSide: true, responsive: true,
        autoWidth: false, language: window.dtArabic,
        order: [[5, 'desc']], pageLength: 25,
        ajax: {
            url: '{{ route('admin.audit.data') }}',
            data: d => Object.assign(d, filters)
        },
        columns: [
            { data: 'event', name: 'event' },
            { data: 'description', name: 'description' },
            { data: 'log_name', name: 'log_name' },
            { data: 'changes', name: 'changes', orderable: false, searchable: false },
            { data: 'causer_name', name: 'causer_id', orderable: false, searchable: false },
            { data: 'created_at', name: 'created_at' }
        ]
    });

    $('#f_log_name, #f_event, #f_date_from, #f_date_to').on('change input', function () {
        filters = {
            log_name:  $('#f_log_name').val(),
            event:     $('#f_event').val(),
            date_from: $('#f_date_from').val(),
            date_to:   $('#f_date_to').val(),
        };
        table.ajax.reload();
    });
});
</script>
@endpush
