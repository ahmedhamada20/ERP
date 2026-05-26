@extends('layouts.master')

@section('title', 'تعديل قيد')
@section('page_title', 'تعديل القيد ' . $entry->number)
@section('page_subtitle', 'تعديل مسودة قيد — لن يؤثر على الترصيد حتى يتم الترحيل')

@section('content')
<form action="{{ route('admin.accounting.journal.update', $entry) }}" method="POST">
    <div class="card">
        <div class="card-body">
            @include('admin.accounting.journal._form')
        </div>
        <div class="card-footer d-flex justify-content-between">
            <a href="{{ route('admin.accounting.journal.show', $entry) }}" class="btn btn-outline-secondary">
                <i class="bi bi-x"></i> إلغاء
            </a>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check2-circle"></i> حفظ التعديلات
            </button>
        </div>
    </div>
</form>
@endsection
