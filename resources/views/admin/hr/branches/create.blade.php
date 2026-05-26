@extends('layouts.master')

@section('title', 'فرع جديد')
@section('page_title', 'إنشاء فرع جديد')
@section('page_subtitle', 'سجّل فرعاً جديداً للشركة')

@section('content')
<form action="{{ route('admin.hr.branches.store') }}" method="POST" novalidate>
    @csrf
    @include('admin.hr.branches._form')
</form>
@endsection
