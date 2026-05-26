@extends('layouts.master')

@section('title', 'فواتير الموردين')
@section('page_title', 'فواتير الموردين')
@section('page_subtitle', 'كل الفواتير الواردة من الموردين — تنشئ قيود تلقائياً عند الترحيل')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h6 class="mb-0"><i class="bi bi-receipt"></i> فواتير الموردين</h6>
        <div class="d-flex gap-2 flex-wrap">
            <select id="statusFilter" class="form-select form-select-sm" style="width:auto;">
                <option value="">— كل الحالات —</option>
                <option value="draft">مسودة</option>
                <option value="posted">مرحّلة</option>
                <option value="cancelled">ملغاة</option>
            </select>
            <div class="form-check form-switch mt-1">
                <input type="checkbox" id="overdueOnly" class="form-check-input">
                <label for="overdueOnly" class="form-check-label small">المتأخرة فقط</label>
            </div>
            <input type="date" id="fromFilter" class="form-control form-control-sm" style="width:auto;">
            <input type="date" id="toFilter"   class="form-control form-control-sm" style="width:auto;">
            @can('supplier_invoices.create')
            <a href="{{ route('admin.supplier_invoices.create') }}" class="btn btn-sm btn-primary">
                <i class="bi bi-plus-lg"></i> فاتورة جديدة
            </a>
            @endcan
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="invoicesTable" class="table pretty-table" style="width:100%;">
                <thead>
                    <tr>
                        <th>رقم الفاتورة</th>
                        <th>التاريخ</th>
                        <th>الاستحقاق</th>
                        <th>المورد</th>
                        <th>حساب المصروف</th>
                        <th>القيمة</th>
                        <th>الحالة</th>
                        <th width="80">إجراء</th>
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
    const t = $('#invoicesTable').DataTable({
        processing: true, serverSide: true, responsive: true,
        order: [[1, 'desc']],
        language: { url: 'https://cdn.datatables.net/plug-ins/2.0.7/i18n/ar.json' },
        ajax: {
            url: '{{ route('admin.supplier_invoices.data') }}',
            data: d => {
                d.status       = $('#statusFilter').val();
                d.from         = $('#fromFilter').val();
                d.to           = $('#toFilter').val();
                d.overdue_only = $('#overdueOnly').is(':checked') ? 1 : 0;
            },
        },
        columns: [
            { data: 'number',          name: 'number' },
            { data: 'invoice_date',    name: 'invoice_date' },
            { data: 'due_date',        name: 'due_date' },
            { data: 'supplier_label',  orderable: false },
            { data: 'expense_label',   orderable: false },
            { data: 'amount',          name: 'amount_egp', className: 'text-end' },
            { data: 'status',          name: 'status' },
            { data: 'actions',         orderable: false, searchable: false },
        ],
    });
    $('#statusFilter, #fromFilter, #toFilter, #overdueOnly').on('change', () => t.ajax.reload());
});
</script>
@endpush
