@extends('layouts.master')

@section('title', 'التأشيرات')
@section('page_title', 'إدارة التأشيرات')
@section('page_subtitle', 'كتالوج أنواع التأشيرات والدول والرسوم')

@push('styles')
<style>
    .bg-info-soft { background: #dbeafe !important; color: #1e40af !important; }
    .bg-success-soft { background: #dcfce7 !important; color: #15803d !important; }
    .bg-secondary-soft { background: #f1f5f9 !important; color: #475569 !important; }
    .btn-light-primary { background: #dbeafe; color: #1e40af; border: none; }
    .btn-light-danger { background: #fee2e2; color: #b91c1c; border: none; }
</style>
@endpush

@section('content')
<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h6 class="mb-0"><i class="bi bi-passport"></i> أنواع التأشيرات</h6>
        <div class="d-flex gap-2 flex-wrap">
            <select id="typeFilter" class="form-select form-select-sm" style="width:auto;">
                <option value="">— كل الأنواع —</option>
                @foreach(\App\Models\VisaType::TYPE_LABELS as $v => $l)
                    <option value="{{ $v }}">{{ $l }}</option>
                @endforeach
            </select>
            <input type="text" id="countryFilter" class="form-control form-control-sm" placeholder="فلتر الدولة" style="width:140px;">
            <select id="statusFilter" class="form-select form-select-sm" style="width:auto;">
                <option value="">— كل الحالات —</option>
                <option value="1">نشط</option>
                <option value="0">متوقف</option>
            </select>
            @can('catalog.visas.manage')
            <a href="{{ route('admin.catalog.visas.create') }}" class="btn btn-sm btn-primary">
                <i class="bi bi-plus-lg"></i> إضافة تأشيرة
            </a>
            @endcan
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="visasTable" class="table pretty-table" style="width:100%;">
                <thead>
                    <tr>
                        <th width="60">#</th>
                        <th>التأشيرة</th>
                        <th>النوع</th>
                        <th>المدة والإصدار</th>
                        <th>دخول</th>
                        <th>الرسوم</th>
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
    let filter = { type: '', country: '', status: '' };
    const table = $('#visasTable').DataTable({
        processing: true, serverSide: true, responsive: true,
        autoWidth: false, language: window.dtArabic,
        order: [[0, 'desc']], pageLength: 25,
        ajax: {
            url: '{{ route('admin.catalog.visas.data') }}',
            data: d => Object.assign(d, filter)
        },
        columns: [
            { data: 'id', name: 'id', visible: false },
            { data: 'visa_info', name: 'name' },
            { data: 'type', name: 'type' },
            { data: 'duration_info', name: 'duration_days', orderable: false },
            { data: 'multiple_entry_label', name: 'multiple_entry' },
            { data: 'base_fee', name: 'base_fee' },
            { data: 'is_active', name: 'is_active' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ]
    });

    $('#typeFilter').on('change', function () { filter.type = this.value; table.ajax.reload(); });
    let countryTimer;
    $('#countryFilter').on('input', function () {
        clearTimeout(countryTimer);
        const v = $(this).val();
        countryTimer = setTimeout(() => { filter.country = v; table.ajax.reload(); }, 350);
    });
    $('#statusFilter').on('change', function () { filter.status = this.value; table.ajax.reload(); });

    $(document).on('click', '.btn-delete', function () {
        CoreX.ajaxDelete($(this).data('url'), table);
    });
});
</script>
@endpush
