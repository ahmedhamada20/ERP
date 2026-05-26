@extends('layouts.master')

@section('title', 'إضافة شركة نقل')
@section('page_title', 'إضافة شركة نقل')
@section('page_subtitle', 'سجّل شركة نقل جديدة بأسطولها ومساراتها وأسعارها')

@section('content')
<form action="{{ route('admin.catalog.transport.store') }}" method="POST" novalidate>
    @csrf
    @include('admin.catalog.transport._form')
</form>
@endsection
