@extends('layouts.master')

@section('title', 'تعديل مورد')
@section('page_title', 'تعديل: ' . $supplier->name)
@section('page_subtitle', 'كود: ' . $supplier->code)

@section('content')
<form action="{{ route('admin.suppliers.update', $supplier) }}" method="POST">
    @include('admin.suppliers._form')

    <div class="d-flex gap-2 justify-content-end">
        <a href="{{ route('admin.suppliers.show', $supplier) }}" class="btn btn-outline-secondary"><i class="bi bi-x"></i> إلغاء</a>
        <button type="submit" class="btn btn-primary"><i class="bi bi-check2-circle"></i> حفظ التعديلات</button>
    </div>
</form>
@endsection
