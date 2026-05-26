@extends('layouts.master')

@section('title', 'سندات الصرف')
@section('page_title', 'سندات الصرف')
@section('page_subtitle', 'كل الأموال المنصرفة من الشركة — تُسجّل تلقائياً في دفتر اليومية')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h6 class="mb-0"><i class="bi bi-arrow-up-circle-fill text-danger"></i> سندات الصرف</h6>
        <div class="d-flex gap-2 flex-wrap">
            <select id="statusFilter" class="form-select form-select-sm" style="width:auto;">
                <option value="">— كل الحالات —</option>
                <option value="posted">مرحّل</option>
                <option value="cancelled">ملغي</option>
            </select>
            <input type="date" id="fromFilter" class="form-control form-control-sm" style="width:auto;">
            <input type="date" id="toFilter" class="form-control form-control-sm" style="width:auto;">
            @can('accounting.vouchers.create')
            <a href="{{ route('admin.accounting.vouchers.payments.create') }}" class="btn btn-sm btn-danger">
                <i class="bi bi-plus-lg"></i> سند صرف جديد
            </a>
            @endcan
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="paymentsTable" class="table pretty-table" style="width:100%;">
                <thead>
                    <tr>
                        <th>رقم السند</th>
                        <th>التاريخ</th>
                        <th>الخزينة/البنك</th>
                        <th>الحساب المقابل</th>
                        <th>المستفيد</th>
                        <th>المبلغ</th>
                        <th>الحالة</th>
                        <th width="120">إجراء</th>
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
    const t = $('#paymentsTable').DataTable({
        processing: true, serverSide: true, responsive: true,
        order: [[1, 'desc']],
        language: { url: 'https://cdn.datatables.net/plug-ins/2.0.7/i18n/ar.json' },
        ajax: {
            url: '{{ route('admin.accounting.vouchers.payments.data') }}',
            data: d => {
                d.status = $('#statusFilter').val();
                d.from   = $('#fromFilter').val();
                d.to     = $('#toFilter').val();
            },
        },
        columns: [
            { data: 'number', name: 'number' },
            { data: 'date',   name: 'date' },
            { data: 'cash_label',    orderable: false },
            { data: 'counter_label', orderable: false },
            { data: 'party_name',    name: 'party_name' },
            { data: 'amount',        name: 'amount_egp', className: 'text-end' },
            { data: 'status',        name: 'status' },
            { data: 'actions', orderable: false, searchable: false },
        ],
    });
    $('#statusFilter, #fromFilter, #toFilter').on('change', () => t.ajax.reload());
});
</script>
@endpush
