@extends('layouts.master')

@section('title', 'تعديل البرنامج')
@section('page_title', 'تعديل برنامج سياحة داخلية')
@section('page_subtitle', $program->name)

@section('content')
<form action="{{ route('admin.domestic.programs.update', $program) }}" method="POST" enctype="multipart/form-data" novalidate>
    @csrf
    @method('PUT')
    @include('admin.domestic.programs._form', ['program' => $program])
</form>
@endsection
