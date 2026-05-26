@extends('layouts.master')

@section('title', 'وظيفة جديدة')
@section('page_title', 'إنشاء وظيفة جديدة')
@section('page_subtitle', 'حدّد الراتب الافتراضي وأساس العمولة')

@section('content')
<form action="{{ route('admin.hr.positions.store') }}" method="POST" novalidate>
    @csrf
    @include('admin.hr.positions._form')
</form>
@endsection
