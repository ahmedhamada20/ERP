@extends('layouts.master')

@section('title', 'إضافة عميل')
@section('page_title', 'إضافة عميل جديد')
@section('page_subtitle', 'أدخل بيانات العميل بدقة — البيانات الصحيحة تضمن سلامة الحجوزات وعمليات السفر')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="d-flex align-items-center gap-3">
            <div style="width:44px;height:44px;border-radius:12px;background:#eef2ff;color:#1e3a8a;display:flex;align-items:center;justify-content:center;font-size:1.3rem;">
                <i class="bi bi-person-plus-fill"></i>
            </div>
            <div>
                <h5 class="mb-1">إضافة عميل جديد</h5>
                <p class="text-muted small mb-0">املأ الحقول المطلوبة على الأقل وانتقل بين التبويبات</p>
            </div>
        </div>
        <a href="{{ route('admin.customers.index') }}" class="btn btn-light btn-sm">
            <i class="bi bi-arrow-right ms-1"></i> العودة للقائمة
        </a>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.customers.store') }}" enctype="multipart/form-data" id="customerForm">
            @include('admin.customers._form')
        </form>
    </div>
</div>
@endsection
