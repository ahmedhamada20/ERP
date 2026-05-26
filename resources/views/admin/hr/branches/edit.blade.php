@extends('layouts.master')

@section('title', 'تعديل ' . $branch->name)
@section('page_title', 'تعديل الفرع')
@section('page_subtitle', $branch->code . ' — ' . $branch->name)

@section('content')
<form action="{{ route('admin.hr.branches.update', $branch) }}" method="POST" novalidate>
    @csrf
    @method('PUT')
    @include('admin.hr.branches._form', ['branch' => $branch])
</form>
@endsection
