@extends('layouts.master')

@section('title', 'تعديل الفندق')
@section('page_title', 'تعديل: ' . $hotel->name)
@section('page_subtitle', $hotel->code . ' • ' . $hotel->city_label)

@section('content')
<form action="{{ route('admin.catalog.hotels.update', $hotel) }}" method="POST" enctype="multipart/form-data" novalidate>
    @csrf @method('PUT')
    @include('admin.catalog.hotels._form')
</form>
@endsection
