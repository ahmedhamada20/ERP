@extends('layouts.master')

@section('title', 'تعديل مستخدم')
@section('page_title', 'تعديل المستخدم: ' . $user->name)
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">المستخدمون</a></li>
    <li class="breadcrumb-item active">تعديل</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <h5><i class="bi bi-pencil-square text-info me-1"></i> تعديل المستخدم</h5>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.users.update', $user) }}" enctype="multipart/form-data">
            @method('PUT')
            @include('admin.users._form')
        </form>
    </div>
</div>
@endsection
