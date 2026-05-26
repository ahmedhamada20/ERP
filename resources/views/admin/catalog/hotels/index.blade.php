@extends('layouts.master')

@section('title', 'الفنادق')
@section('page_title', 'إدارة الفنادق')
@section('page_subtitle', 'كتالوج الفنادق الشريكة في مكة والمدينة والقاهرة ومدن سياحية أخرى')

@push('styles')
<style>
    .hotel-thumb { width: 50px; height: 38px; border-radius: 8px; object-fit: cover; flex-shrink: 0; }
    .hotel-thumb-empty { background: linear-gradient(135deg,#f1f5f9,#e2e8f0); color: #94a3b8; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; }
    .bg-primary-soft { background: #dbeafe !important; color: #1e40af !important; }
    .bg-success-soft { background: #dcfce7 !important; color: #15803d !important; }
    .bg-secondary-soft { background: #f1f5f9 !important; color: #475569 !important; }
    .btn-light-primary { background: #dbeafe; color: #1e40af; border: none; }
    .btn-light-danger { background: #fee2e2; color: #b91c1c; border: none; }
</style>
@endpush

@section('content')
<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h6 class="mb-0"><i class="bi bi-building"></i> الفنادق</h6>
        <div class="d-flex gap-2 flex-wrap">
            <select id="cityFilter" class="form-select form-select-sm" style="width:auto;">
                <option value="">— كل المدن —</option>
                @foreach(\App\Models\Hotel::CITY_LABELS as $v => $l)
                    <option value="{{ $v }}">{{ $l }}</option>
                @endforeach
            </select>
            <select id="gradeFilter" class="form-select form-select-sm" style="width:auto;">
                <option value="">— كل الدرجات —</option>
                @foreach(\App\Models\Hotel::GRADE_LABELS as $v => $l)
                    <option value="{{ $v }}">{{ $l }}</option>
                @endforeach
            </select>
            <select id="statusFilter" class="form-select form-select-sm" style="width:auto;">
                <option value="">— كل الحالات —</option>
                <option value="1">نشط</option>
                <option value="0">متوقف</option>
            </select>
            @can('catalog.hotels.manage')
            <a href="{{ route('admin.catalog.hotels.create') }}" class="btn btn-sm btn-primary">
                <i class="bi bi-plus-lg"></i> إضافة فندق
            </a>
            @endcan
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="hotelsTable" class="table pretty-table" style="width:100%;">
                <thead>
                    <tr>
                        <th width="60">#</th>
                        <th>الفندق</th>
                        <th>المدينة</th>
                        <th>الدرجة</th>
                        <th>المسافة</th>
                        <th>السعر/ليلة</th>
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
    let filter = { city: '', grade: '', status: '' };
    const table = $('#hotelsTable').DataTable({
        processing: true, serverSide: true, responsive: true,
        autoWidth: false, language: window.dtArabic,
        order: [[0, 'desc']], pageLength: 25,
        ajax: {
            url: '{{ route('admin.catalog.hotels.data') }}',
            data: d => Object.assign(d, filter)
        },
        columns: [
            { data: 'id', name: 'id', visible: false },
            { data: 'hotel_info', name: 'name' },
            { data: 'city', name: 'city' },
            { data: 'grade', name: 'grade' },
            { data: 'distance', name: 'distance_meters' },
            { data: 'base_price_per_night', name: 'base_price_per_night' },
            { data: 'is_active', name: 'is_active' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ]
    });

    $('#cityFilter').on('change', function () { filter.city = this.value; table.ajax.reload(); });
    $('#gradeFilter').on('change', function () { filter.grade = this.value; table.ajax.reload(); });
    $('#statusFilter').on('change', function () { filter.status = this.value; table.ajax.reload(); });

    $(document).on('click', '.btn-delete', function () {
        CoreX.ajaxDelete($(this).data('url'), table);
    });
});
</script>
@endpush
