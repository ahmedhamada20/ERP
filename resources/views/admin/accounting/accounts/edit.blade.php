@extends('layouts.master')

@section('title', 'تعديل حساب')
@section('page_title', 'تعديل حساب: ' . $account->code . ' — ' . $account->name)
@section('page_subtitle', $account->is_system ? 'حساب نظام — بعض الحقول مقفلة' : 'تعديل بيانات الحساب')

@section('content')
<div class="card">
    <div class="card-body">
        <form action="{{ route('admin.accounting.accounts.update', $account) }}" method="POST">
            @include('admin.accounting.accounts._form')

            <hr class="my-4">
            <div class="d-flex gap-2 justify-content-end">
                <a href="{{ route('admin.accounting.accounts.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-x"></i> إلغاء
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check2-circle"></i> حفظ التعديلات
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
