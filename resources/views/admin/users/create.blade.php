@extends('layouts.master')

@section('title', 'إضافة مستخدم')
@section('page_title', 'إضافة مستخدم جديد')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">المستخدمون</a></li>
    <li class="breadcrumb-item active">إضافة</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <h5><i class="bi bi-person-plus text-primary me-1"></i> إضافة مستخدم</h5>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.users.store') }}" enctype="multipart/form-data">
            @include('admin.users._form')
        </form>
    </div>
</div>
@endsection
