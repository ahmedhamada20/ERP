@extends('layouts.master')

@section('title', 'إضافة تأشيرة')
@section('page_title', 'إضافة نوع تأشيرة جديد')
@section('page_subtitle', 'سجّل تأشيرة جديدة بدولتها وأنواعها ورسومها')

@section('content')
<form action="{{ route('admin.catalog.visas.store') }}" method="POST" novalidate>
    @csrf
    @include('admin.catalog.visas._form')
</form>
@endsection
