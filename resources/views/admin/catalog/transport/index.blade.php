@extends('layouts.master')

@section('title', 'شركات النقل')
@section('page_title', 'إدارة شركات النقل')
@section('page_subtitle', 'كتالوج شركات النقل البري والقطارات وخدمات VIP')

@push('styles')
<style>
    .bg-warning-soft { background: #fef3c7 !important; color: #92400e !important; }
    .bg-success-soft { background: #dcfce7 !important; color: #15803d !important; }
    .bg-secondary-soft { background: #f1f5f9 !important; color: #475569 !important; }
    .btn-light-primary { background: #dbeafe; color: #1e40af; border: none; }
    .btn-light-danger { background: #fee2e2; color: #b91c1c; border: none; }
</style>
@endpush

@section('content')
<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h6 class="mb-0"><i class="bi bi-bus-front"></i> شركات النقل</h6>
        <div class="d-flex gap-2 flex-wrap">
            <select id="typeFilter" class="form-select form-select-sm" style="width:auto;">
                <option value="">— كل الأنواع —</option>
                @foreach(\App\Models\TransportProvider::TYPE_LABELS as $v => $l)
                    <option value="{{ $v }}">{{ $l }}</option>
                @endforeach
            </select>
            <select id="countryFilter" class="form-select form-select-sm" style="width:auto;">
                <option value="">— كل الدول —</option>
                @foreach(\App\Models\TransportProvider::COUNTRY_LABELS as $v => $l)
                    <option value="{{ $v }}">{{ $l }}</option>
                @endforeach
            </select>
            <select id="statusFilter" class="form-select form-select-sm" style="width:auto;">
                <option value="">— كل الحالات —</option>
                <option value="1">نشط</option>
                <option value="0">متوقف</option>
            </select>
            @can('catalog.transport.manage')
            <a href="{{ route('admin.catalog.transport.create') }}" class="btn btn-sm btn-primary">
                <i class="bi bi-plus-lg"></i> إضافة شركة
            </a>
            @endcan
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="transportTable" class="table pretty-table" style="width:100%;">
                <thead>
                    <tr>
                        <th width="60">#</th>
                        <th>الشركة</th>
                        <th>النوع</th>
                        <th>الأسطول</th>
                        <th>السعر</th>
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
    const table = $('#transportTable').DataTable({
        processing: true, serverSide: true, responsive: true,
        autoWidth: false, language: window.dtArabic,
        order: [[0, 'desc']], pageLength: 25,
        ajax: {
            url: '{{ route('admin.catalog.transport.data') }}',
            data: d => Object.assign(d, filter)
        },
        columns: [
            { data: 'id', name: 'id', visible: false },
            { data: 'provider_info', name: 'name' },
            { data: 'type', name: 'type' },
            { data: 'fleet', name: 'vehicle_count', orderable: false },
            { data: 'base_price_per_pax', name: 'base_price_per_pax' },
            { data: 'is_active', name: 'is_active' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ]
    });

    $('#typeFilter').on('change', function () { filter.type = this.value; table.ajax.reload(); });
    $('#countryFilter').on('change', function () { filter.country = this.value; table.ajax.reload(); });
    $('#statusFilter').on('change', function () { filter.status = this.value; table.ajax.reload(); });

    $(document).on('click', '.btn-delete', function () {
        CoreX.ajaxDelete($(this).data('url'), table);
    });
});
</script>
@endpush
