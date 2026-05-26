@extends('layouts.master')

@section('title', 'تعديل شركة طيران')
@section('page_title', 'تعديل: ' . $airline->airline_name)
@section('page_subtitle', $airline->code . ' • ' . $airline->route)

@section('content')
<form action="{{ route('admin.catalog.airlines.update', $airline) }}" method="POST" novalidate>
    @csrf @method('PUT')
    @include('admin.catalog.airlines._form')
</form>
@endsection
