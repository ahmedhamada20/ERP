@extends('layouts.master')

@section('title', 'تعديل صلاحية')
@section('page_title', 'تعديل الصلاحية: ' . $role->name)
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.roles.index') }}">الصلاحيات</a></li>
    <li class="breadcrumb-item active">تعديل</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <h5><i class="bi bi-pencil-square text-info me-1"></i> تعديل الصلاحية</h5>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.roles.update', $role) }}">
            @method('PUT')
            @include('admin.roles._form')
        </form>
    </div>
</div>
@endsection
