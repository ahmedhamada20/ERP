@extends('layouts.master')

@section('title', 'صفقة جديدة')
@section('page_title', 'إنشاء صفقة جديدة')
@section('page_subtitle', 'سجّل صفقة محتملة وحدد توقعاتها')

@section('content')
<form action="{{ route('admin.crm.opportunities.store') }}" method="POST" novalidate>
    @csrf
    @include('admin.crm.opportunities._form')
</form>
@endsection
