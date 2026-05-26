@extends('layouts.master')

@section('title', 'تعديل شركة النقل')
@section('page_title', 'تعديل: ' . $transport->name)
@section('page_subtitle', $transport->code . ' • ' . $transport->type_label)

@section('content')
<form action="{{ route('admin.catalog.transport.update', $transport) }}" method="POST" novalidate>
    @csrf @method('PUT')
    @include('admin.catalog.transport._form')
</form>
@endsection
