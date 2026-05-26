@extends('layouts.master')

@section('title', 'تعديل ' . $position->title)
@section('page_title', 'تعديل الوظيفة')
@section('page_subtitle', $position->code . ' — ' . $position->title)

@section('content')
<form action="{{ route('admin.hr.positions.update', $position) }}" method="POST" novalidate>
    @csrf
    @method('PUT')
    @include('admin.hr.positions._form', ['position' => $position])
</form>
@endsection
