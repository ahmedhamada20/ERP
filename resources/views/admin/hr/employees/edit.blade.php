@extends('layouts.master')

@section('title', 'تعديل ' . $employee->full_name)
@section('page_title', 'تعديل بيانات الموظف')
@section('page_subtitle', $employee->code . ' — ' . $employee->full_name)

@section('content')
<form action="{{ route('admin.hr.employees.update', $employee) }}" method="POST" enctype="multipart/form-data" novalidate>
    @csrf
    @method('PUT')
    @include('admin.hr.employees._form', ['employee' => $employee])
</form>
@endsection
