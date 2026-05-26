@extends('layouts.master')

@section('title', 'شركات الطيران')
@section('page_title', 'إدارة شركات الطيران')
@section('page_subtitle', 'كتالوج شركات الطيران ومسارات الرحلات وأسعار التذاكر')

@push('styles')
<style>
    .route-chip {
        display: inline-block; padding: .25rem .65rem;
        background: linear-gradient(135deg, #dbeafe, #bfdbfe);
        color: #1e40af; font-weight: 800; font-size: .82rem;
        border-radius: 8px; font-family: 'JetBrains Mono', monospace;
    }
    .bg-info-soft { background: #dbeafe !important; color: #1e40af !important; }
    .bg-success-soft { background: #dcfce7 !important; color: #15803d !important; }
    .bg-secondary-soft { background: #f1f5f9 !important; color: #475569 !important; }
    .btn-light-primary { background: #dbeafe; color: #1e40af; border: none; }
    .btn-light-primary:hover { background: #bfdbfe; color: #1e40af; }
    .btn-light-danger { background: #fee2e2; color: #b91c1c; border: none; }
    .btn-light-danger:hover { background: #fecaca; color: #b91c1c; }
</style>
@endpush

@section('content')
<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h6 class="mb-0"><i class="bi bi-airplane"></i> شركات الطيران</h6>
        <div class="d-flex gap-2">
            <select id="cabinFilter" class="form-select form-select-sm" style="width:auto;">
                <option value="">— كل الدرجات —</option>
                <option value="economy">اقتصادي</option>
                <option value="business">رجال أعمال</option>
                <option value="first">أولى</option>
            </select>
            <select id="statusFilter" class="form-select form-select-sm" style="width:auto;">
                <option value="">— كل الحالات —</option>
                <option value="1">نشط</option>
                <option value="0">متوقف</option>
            </select>
            @can('catalog.airlines.manage')
            <a href="{{ route('admin.catalog.airlines.create') }}" class="btn btn-sm btn-primary">
                <i class="bi bi-plus-lg"></i> إضافة شركة
            </a>
            @endcan
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="airlinesTable" class="table pretty-table" style="width:100%;">
                <thead>
                    <tr>
                        <th width="60">#</th>
                        <th>الشركة</th>
                        <th>المسار</th>
                        <th>الدرجة</th>
                        <th>السعر/الراكب</th>
                        <th>المقاعد</th>
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
    let filter = { cabin: '', status: '' };
    const table = $('#airlinesTable').DataTable({
        processing: true, serverSide: true, responsive: true,
        autoWidth: false, language: window.dtArabic,
        order: [[0, 'desc']], pageLength: 25,
        ajax: {
            url: '{{ route('admin.catalog.airlines.data') }}',
            data: d => Object.assign(d, filter)
        },
        columns: [
            { data: 'id', name: 'id', visible: false },
            { data: 'airline_info', name: 'airline_name' },
            { data: 'route_chip', name: 'route' },
            { data: 'cabin_class', name: 'cabin_class' },
            { data: 'base_price_per_pax', name: 'base_price_per_pax' },
            { data: 'seats', name: 'available_seats', orderable: false },
            { data: 'is_active', name: 'is_active' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ]
    });

    $('#cabinFilter').on('change', function () { filter.cabin = this.value; table.ajax.reload(); });
    $('#statusFilter').on('change', function () { filter.status = this.value; table.ajax.reload(); });

    $(document).on('click', '.btn-delete', function () {
        CoreX.ajaxDelete($(this).data('url'), table);
    });
});
</script>
@endpush
