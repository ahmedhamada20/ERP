@extends('layouts.master')

@section('title', 'تعديل ' . $department->name)
@section('page_title', 'تعديل القسم')
@section('page_subtitle', $department->code . ' — ' . $department->name)

@section('content')
<form action="{{ route('admin.hr.departments.update', $department) }}" method="POST" novalidate>
    @csrf
    @method('PUT')
    @include('admin.hr.departments._form', ['department' => $department])
</form>
@endsection
