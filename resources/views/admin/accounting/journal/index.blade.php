@extends('layouts.master')

@section('title', 'القيود اليومية')
@section('page_title', 'القيود اليومية')
@section('page_subtitle', 'دفتر اليومية — كل الحركات المحاسبية في النظام')

@push('styles')
<style>
    .badge-soft-draft   { background:#fef3c7; color:#92400e; }
    .badge-soft-posted  { background:#dcfce7; color:#15803d; }
    .badge-soft-cancel  { background:#fee2e2; color:#b91c1c; }
    .btn-light-info     { background:#dbeafe; color:#1e40af; border:none; }
    .btn-light-info:hover{ background:#bfdbfe; color:#1e40af; }
    .btn-light-primary  { background:#e0e7ff; color:#4338ca; border:none; }
    .btn-light-primary:hover{ background:#c7d2fe; color:#4338ca; }
</style>
@endpush

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h6 class="mb-0"><i class="bi bi-journal-text"></i> دفتر اليومية</h6>
        <div class="d-flex gap-2 flex-wrap">
            <select id="statusFilter" class="form-select form-select-sm" style="width:auto;">
                <option value="">— كل الحالات —</option>
                <option value="draft">مسودة</option>
                <option value="posted">مرحّل</option>
                <option value="cancelled">ملغي</option>
            </select>
            <input type="date" id="fromFilter" class="form-control form-control-sm" placeholder="من" style="width:auto;">
            <input type="date" id="toFilter"   class="form-control form-control-sm" placeholder="إلى" style="width:auto;">
            @can('accounting.journal.create')
            <a href="{{ route('admin.accounting.journal.create') }}" class="btn btn-sm btn-primary">
                <i class="bi bi-plus-lg"></i> قيد جديد
            </a>
            @endcan
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="journalTable" class="table pretty-table" style="width:100%;">
                <thead>
                    <tr>
                        <th>رقم القيد</th>
                        <th>التاريخ</th>
                        <th>البيان</th>
                        <th>المرجع</th>
                        <th>المصدر</th>
                        <th>سطور</th>
                        <th>مدين</th>
                        <th>دائن</th>
                        <th>الحالة</th>
                        <th width="100">إجراء</th>
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
    const t = $('#journalTable').DataTable({
        processing: true, serverSide: true, responsive: true,
        order: [[1, 'desc']],
        language: { url: 'https://cdn.datatables.net/plug-ins/2.0.7/i18n/ar.json' },
        ajax: {
            url: '{{ route('admin.accounting.journal.data') }}',
            data: d => {
                d.status = $('#statusFilter').val();
                d.from   = $('#fromFilter').val();
                d.to     = $('#toFilter').val();
            },
        },
        columns: [
            { data: 'number',       name: 'number' },
            { data: 'date',         name: 'date' },
            { data: 'description',  name: 'description' },
            { data: 'reference',    name: 'reference', defaultContent: '—' },
            { data: 'source_label', name: 'source_type', orderable: false },
            { data: 'lines_count',  name: 'lines_count', orderable: false },
            { data: 'total_debit',  name: 'total_debit',  className: 'text-end' },
            { data: 'total_credit', name: 'total_credit', className: 'text-end' },
            { data: 'status',       name: 'status' },
            { data: 'actions',      orderable: false, searchable: false },
        ],
    });

    $('#statusFilter, #fromFilter, #toFilter').on('change', () => t.ajax.reload());
});
</script>
@endpush
