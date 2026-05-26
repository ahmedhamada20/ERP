@extends('layouts.master')

@section('title', 'تعديل الحجز ' . $booking->booking_number)
@section('page_title', 'تعديل حجز سياحة داخلية')
@section('page_subtitle', $booking->booking_number . ' — ' . $booking->customer?->full_name)

@section('content')
<form action="{{ route('admin.domestic.bookings.update', $booking) }}" method="POST" novalidate>
    @csrf
    @method('PUT')
    @include('admin.domestic.bookings._form', ['booking' => $booking])
</form>
@endsection
