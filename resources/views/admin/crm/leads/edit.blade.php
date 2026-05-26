@extends('layouts.master')

@section('title', 'تعديل ' . $lead->code)
@section('page_title', 'تعديل العميل المحتمل')
@section('page_subtitle', $lead->code . ' — ' . $lead->full_name)

@section('content')
<form action="{{ route('admin.crm.leads.update', $lead) }}" method="POST" novalidate>
    @csrf
    @method('PUT')
    @include('admin.crm.leads._form', ['lead' => $lead])
</form>
@endsection
