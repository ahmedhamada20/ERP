@extends('layouts.master')

@section('title', 'مورد جديد')
@section('page_title', 'إضافة مورد جديد')
@section('page_subtitle', 'فندق، شركة طيران، نقل، أو أي مورد آخر للشركة')

@section('content')
<form action="{{ route('admin.suppliers.store') }}" method="POST">
    @include('admin.suppliers._form')

    <div class="d-flex gap-2 justify-content-end">
        <a href="{{ route('admin.suppliers.index') }}" class="btn btn-outline-secondary"><i class="bi bi-x"></i> إلغاء</a>
        <button type="submit" class="btn btn-primary"><i class="bi bi-check2-circle"></i> حفظ المورد</button>
    </div>
</form>
@endsection
