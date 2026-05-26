@extends('layouts.master')

@section('title', 'موظف جديد')
@section('page_title', 'إضافة موظف جديد')
@section('page_subtitle', 'سجّل بيانات الموظف بالكامل')

@section('content')
<form action="{{ route('admin.hr.employees.store') }}" method="POST" enctype="multipart/form-data" novalidate>
    @csrf
    @include('admin.hr.employees._form')
</form>
@endsection
