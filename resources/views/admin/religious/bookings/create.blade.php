@extends('layouts.master')

@section('title', 'حجز جديد')
@section('page_title', 'إنشاء حجز ديني جديد')
@section('page_subtitle', 'إضافة حجز حج أو عمرة - يمكنك إضافة المعتمرين والتكاليف بعد الحفظ')

@section('content')
<form action="{{ route('admin.religious.bookings.store') }}" method="POST" novalidate>
    @csrf
    @include('admin.religious.bookings._form')
</form>
@endsection
