@extends('layouts.master')

@section('title', 'إضافة شركة طيران')
@section('page_title', 'إضافة شركة طيران')
@section('page_subtitle', 'سجّل شركة طيران جديدة بمساراتها وأسعارها')

@section('content')
<form action="{{ route('admin.catalog.airlines.store') }}" method="POST" novalidate>
    @csrf
    @include('admin.catalog.airlines._form')
</form>
@endsection
