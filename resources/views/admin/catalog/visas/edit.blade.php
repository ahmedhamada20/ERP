@extends('layouts.master')

@section('title', 'تعديل التأشيرة')
@section('page_title', 'تعديل: ' . $visa->name)
@section('page_subtitle', $visa->code . ' • ' . $visa->country)

@section('content')
<form action="{{ route('admin.catalog.visas.update', $visa) }}" method="POST" novalidate>
    @csrf @method('PUT')
    @include('admin.catalog.visas._form')
</form>
@endsection
