@extends('layouts.master')

@section('title', 'تعديل ' . $opp->code)
@section('page_title', 'تعديل صفقة')
@section('page_subtitle', $opp->code . ' — ' . $opp->title)

@section('content')
<form action="{{ route('admin.crm.opportunities.update', $opp) }}" method="POST" novalidate>
    @csrf
    @method('PUT')
    @include('admin.crm.opportunities._form', ['opp' => $opp])
</form>
@endsection
