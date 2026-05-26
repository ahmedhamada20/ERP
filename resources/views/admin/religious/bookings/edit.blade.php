@extends('layouts.master')

@section('title', 'تعديل الحجز')
@section('page_title', 'تعديل بيانات الحجز')
@section('page_subtitle', $booking->booking_number . ' - ' . ($booking->customer->full_name ?? '—'))

@section('content')
<form action="{{ route('admin.religious.bookings.update', $booking) }}" method="POST" novalidate>
    @csrf
    @method('PUT')
    @include('admin.religious.bookings._form', ['booking' => $booking])
</form>
@endsection
