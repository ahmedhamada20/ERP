@extends('layouts.master')

@section('title', 'قيد جديد')
@section('page_title', 'إضافة قيد يومية جديد')
@section('page_subtitle', 'كل قيد لازم يكون متوازن: إجمالي المدين = إجمالي الدائن')

@section('content')
<form action="{{ route('admin.accounting.journal.store') }}" method="POST">
    <div class="card">
        <div class="card-body">
            @include('admin.accounting.journal._form')
        </div>
        <div class="card-footer d-flex justify-content-between">
            <a href="{{ route('admin.accounting.journal.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-x"></i> إلغاء
            </a>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check2-circle"></i> حفظ القيد
            </button>
        </div>
    </div>
</form>
@endsection
