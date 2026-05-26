@extends('layouts.master')

@section('title', 'حجز جديد - سياحة داخلية')
@section('page_title', 'إنشاء حجز سياحة داخلية')
@section('page_subtitle', 'بيانات الحجز الأساسية - التكاليف والمدفوعات تُضاف بعد الحفظ')

@section('content')
<form action="{{ route('admin.domestic.bookings.store') }}" method="POST" novalidate>
    @csrf
    @include('admin.domestic.bookings._form')
</form>
@endsection
