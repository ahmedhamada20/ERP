@extends('layouts.master')

@section('title', 'تعديل عميل')
@section('page_title', 'تعديل بيانات: ' . $customer->full_name)
@section('page_subtitle', 'كود العميل: ' . $customer->code . ' — تاريخ الإضافة: ' . $customer->created_at?->format('Y-m-d'))

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="d-flex align-items-center gap-3">
            <img src="{{ $customer->photo_url }}" alt=""
                 style="width:48px;height:48px;border-radius:50%;object-fit:cover;border:2px solid #fff;box-shadow:0 0 0 2px var(--brand-gold);"
                 onerror="this.onerror=null;this.src='data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%2248%22 height=%2248%22><rect width=%22100%25%22 height=%22100%25%22 fill=%22%23eef0f5%22/></svg>';">
            <div>
                <h5 class="mb-1"><i class="bi bi-pencil-square text-info me-1"></i> تعديل العميل</h5>
                <p class="text-muted small mb-0">
                    <span class="badge bg-{{ $customer->status_badge }}-soft">{{ $customer->status_label }}</span>
                    <span class="badge type-{{ $customer->type }}">{{ $customer->type_label }}</span>
                    <span class="ms-2 text-muted">{{ $customer->code }}</span>
                </p>
            </div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.customers.show', $customer) }}" class="btn btn-light btn-sm">
                <i class="bi bi-eye ms-1"></i> عرض الملف
            </a>
            <a href="{{ route('admin.customers.index') }}" class="btn btn-light btn-sm">
                <i class="bi bi-arrow-right ms-1"></i> العودة للقائمة
            </a>
        </div>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.customers.update', $customer) }}" enctype="multipart/form-data" id="customerForm">
            @method('PUT')
            @include('admin.customers._form')
        </form>
    </div>
</div>
@endsection
