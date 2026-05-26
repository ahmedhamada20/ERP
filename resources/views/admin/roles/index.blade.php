@extends('layouts.master')

@section('title', 'الصلاحيات')
@section('page_title', 'قائمة الصلاحيات')
@section('breadcrumb')
    <li class="breadcrumb-item active">الصلاحيات</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5><i class="bi bi-shield-lock text-primary me-1"></i> قائمة الصلاحيات</h5>
        @can('roles.create')
        <a href="{{ route('admin.roles.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg ms-1"></i> إضافة صلاحية
        </a>
        @endcan
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="roles-table" class="table pretty-table" style="width:100%;">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>اسم الصلاحية</th>
                        <th>عدد الأذونات</th>
                        <th>عدد المستخدمين</th>
                        <th>تاريخ الإنشاء</th>
                        <th>الإجراءات</th>
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
    var table = CoreX.initDataTable('#roles-table', {
        ajax: '{{ route('admin.roles.data') }}',
        columns: [
            { data: 'id', name: 'id', width: '60px' },
            { data: 'name', name: 'name' },
            { data: 'permissions_badge', name: 'permissions_count', orderable: false, searchable: false },
            { data: 'users_badge', name: 'users_count', orderable: false, searchable: false },
            { data: 'created_at', name: 'created_at', width: '120px' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false, width: '120px' }
        ]
    });

    $(document).on('click', '.btn-delete', function () {
        CoreX.ajaxDelete($(this).data('url'), table);
    });
});
</script>
@endpush
