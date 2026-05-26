@extends('layouts.master')

@section('title', 'قسم جديد')
@section('page_title', 'إنشاء قسم جديد')
@section('page_subtitle', 'سجّل قسماً جديداً (يمكن ربطه بفرع أو يكون عاماً)')

@section('content')
<form action="{{ route('admin.hr.departments.store') }}" method="POST" novalidate>
    @csrf
    @include('admin.hr.departments._form')
</form>
@endsection
