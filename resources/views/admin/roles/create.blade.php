@extends('layouts.master')

@section('title', 'إضافة صلاحية')
@section('page_title', 'إضافة صلاحية جديدة')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.roles.index') }}">الصلاحيات</a></li>
    <li class="breadcrumb-item active">إضافة</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <h5><i class="bi bi-plus-circle text-primary me-1"></i> إضافة صلاحية</h5>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.roles.store') }}">
            @include('admin.roles._form')
        </form>
    </div>
</div>
@endsection
