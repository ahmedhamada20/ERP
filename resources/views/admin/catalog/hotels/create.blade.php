@extends('layouts.master')

@section('title', 'إضافة فندق')
@section('page_title', 'إضافة فندق جديد')
@section('page_subtitle', 'سجّل فندق جديد مع تفاصيله ودرجته وأسعاره')

@section('content')
<form action="{{ route('admin.catalog.hotels.store') }}" method="POST" enctype="multipart/form-data" novalidate>
    @csrf
    @include('admin.catalog.hotels._form')
</form>
@endsection
