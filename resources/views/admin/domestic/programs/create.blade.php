@extends('layouts.master')

@section('title', 'إضافة برنامج سياحة داخلية')
@section('page_title', 'إضافة برنامج سياحة داخلية جديد')
@section('page_subtitle', 'تعريف قالب جديد لرحلة محلية: باكدج / إقامة فندقية / رحلة نيلية / مخيم')

@section('content')
<form action="{{ route('admin.domestic.programs.store') }}" method="POST" enctype="multipart/form-data" novalidate>
    @csrf
    @include('admin.domestic.programs._form')
</form>
@endsection
