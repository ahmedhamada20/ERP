@extends('layouts.master')

@section('title', 'المستخدمون')
@section('page_title', 'قائمة المستخدمين')
@section('breadcrumb')
    <li class="breadcrumb-item active">المستخدمون</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5><i class="bi bi-people text-primary me-1"></i> قائمة المستخدمين</h5>
        @can('users.create')
        <a href="{{ route('admin.users.create') }}" class="btn btn-primary">
            <i class="bi bi-person-plus ms-1"></i> إضافة مستخدم
        </a>
        @endcan
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="users-table" class="table pretty-table" style="width:100%;">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>الصورة</th>
                        <th>الاسم</th>
                        <th>البريد</th>
                        <th>الهاتف</th>
                        <th>الصلاحيات</th>
                        <th>الحالة</th>
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
    var table = CoreX.initDataTable('#users-table', {
        ajax: '{{ route('admin.users.data') }}',
        columns: [
            { data: 'id', name: 'id', width: '60px' },
            { data: 'avatar', name: 'avatar', orderable: false, searchable: false, width: '70px' },
            { data: 'name', name: 'name' },
            { data: 'email', name: 'email' },
            { data: 'phone', name: 'phone' },
            { data: 'roles_list', name: 'roles', orderable: false, searchable: false },
            { data: 'is_active', name: 'is_active', width: '100px' },
            { data: 'created_at', name: 'created_at', width: '140px' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false, width: '120px' }
        ]
    });

    $(document).on('click', '.btn-delete', function () {
        CoreX.ajaxDelete($(this).data('url'), table);
    });
});
</script>
@endpush
