@extends('layouts.master')

@section('title', 'إضافة برنامج ديني')
@section('page_title', 'إضافة برنامج ديني جديد')
@section('page_subtitle', 'تعريف قالب جديد لرحلة حج أو عمرة')

@section('content')
<form action="{{ route('admin.religious.programs.store') }}" method="POST" enctype="multipart/form-data" novalidate>
    @csrf
    @include('admin.religious.programs._form')
</form>
@endsection
