@extends('layouts.master')

@section('title', 'تعديل البرنامج')
@section('page_title', 'تعديل برنامج ديني')
@section('page_subtitle', $program->name)

@section('content')
<form action="{{ route('admin.religious.programs.update', $program) }}" method="POST" enctype="multipart/form-data" novalidate>
    @csrf
    @method('PUT')
    @include('admin.religious.programs._form', ['program' => $program])
</form>
@endsection
