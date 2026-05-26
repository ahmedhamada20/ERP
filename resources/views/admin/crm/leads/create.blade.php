@extends('layouts.master')

@section('title', 'Lead جديد')
@section('page_title', 'إنشاء عميل محتمل جديد')
@section('page_subtitle', 'سجّل بيانات أول تواصل مع العميل')

@section('content')
<form action="{{ route('admin.crm.leads.store') }}" method="POST" novalidate>
    @csrf
    @include('admin.crm.leads._form')
</form>
@endsection
