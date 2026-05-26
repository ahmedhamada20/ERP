@extends('layouts.master')

@section('title', $supplier->name)
@section('page_title', $supplier->name)
@section('page_subtitle', $supplier->code . ' — ' . $supplier->type_label)

@push('styles')
<style>
    .info-card { background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:1.25rem; height:100%; }
    .info-card h6 { color:#6b7280; font-size:.78rem; font-weight:600; margin-bottom:.3rem; text-transform:uppercase; }
    .info-card .v { font-weight:700; color:#0f172a; font-size:1rem; }

    .balance-card {
        background:linear-gradient(135deg, #eef2ff, #fff); border:1px solid #c7d2fe;
        border-radius:14px; padding:1.5rem; height:100%; display:flex;
        flex-direction:column; justify-content:center; align-items:center; text-align:center;
    }
    .balance-card .lbl { color:#4338ca; font-weight:700; font-size:.85rem; }
    .balance-card .val { color:#4338ca; font-weight:800; font-size:1.8rem; line-height:1; margin-top:.4rem; }
    .balance-card .sub { color:#6b7280; font-size:.8rem; margin-top:.5rem; }

    .type-badge { padding:.3rem .85rem; border-radius:8px; font-weight:700; font-size:.85rem; }
    .t-hotel     { background:#dbeafe; color:#1e40af; }
    .t-airline   { background:#e0e7ff; color:#3730a3; }
    .t-transport { background:#fef3c7; color:#92400e; }
    .t-visa      { background:#dcfce7; color:#15803d; }
    .t-other     { background:#f1f5f9; color:#475569; }
</style>
@endpush

@section('content')

<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="balance-card">
            <div class="lbl"><i class="bi bi-cash-coin"></i> الرصيد الافتتاحي</div>
            <div class="val">{{ number_format($supplier->opening_balance, 2) }}</div>
            <div class="sub">{{ $supplier->currency }}</div>
            @if($supplier->opening_balance_date)
                <div class="sub">{{ $supplier->opening_balance_date->format('Y-m-d') }}</div>
            @endif
        </div>
    </div>
    <div class="col-md-9">
        <div class="row g-3 h-100">
            <div class="col-md-4"><div class="info-card">
                <h6>التصنيف</h6>
                <div class="v"><span class="type-badge t-{{ $supplier->type }}">{{ $supplier->type_label }}</span></div>
            </div></div>
            <div class="col-md-4"><div class="info-card">
                <h6>الحالة</h6>
                <div class="v">
                    @if($supplier->is_active)
                        <span class="badge bg-success px-3 py-2">نشط</span>
                    @else
                        <span class="badge bg-secondary px-3 py-2">متوقف</span>
                    @endif
                </div>
            </div></div>
            <div class="col-md-4"><div class="info-card">
                <h6>مهلة السداد</h6>
                <div class="v">{{ $supplier->payment_terms_days }} يوم</div>
            </div></div>
            <div class="col-md-4"><div class="info-card">
                <h6>الهاتف</h6>
                <div class="v" dir="ltr">{{ $supplier->phone ?: '—' }}</div>
            </div></div>
            <div class="col-md-4"><div class="info-card">
                <h6>الجوال</h6>
                <div class="v" dir="ltr">{{ $supplier->mobile ?: '—' }}</div>
            </div></div>
            <div class="col-md-4"><div class="info-card">
                <h6>البريد الإلكتروني</h6>
                <div class="v" dir="ltr">{{ $supplier->email ?: '—' }}</div>
            </div></div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-6"><div class="info-card">
        <h6>المسؤول</h6>
        <div class="v">{{ $supplier->contact_person ?: '—' }}</div>
    </div></div>
    <div class="col-md-6"><div class="info-card">
        <h6>العنوان</h6>
        <div class="v">{{ trim(($supplier->address ?: '') . ', ' . ($supplier->city ?: '') . ', ' . ($supplier->country ?: ''), ', ') ?: '—' }}</div>
    </div></div>

    <div class="col-md-6"><div class="info-card">
        <h6>الرقم الضريبي</h6>
        <div class="v" dir="ltr">{{ $supplier->tax_number ?: '—' }}</div>
    </div></div>
    <div class="col-md-6"><div class="info-card">
        <h6>السجل التجاري</h6>
        <div class="v" dir="ltr">{{ $supplier->commercial_register ?: '—' }}</div>
    </div></div>
</div>

@if($supplier->notes)
<div class="card mb-3">
    <div class="card-header"><h6 class="mb-0"><i class="bi bi-sticky"></i> ملاحظات</h6></div>
    <div class="card-body">{{ $supplier->notes }}</div>
</div>
@endif

{{-- Quick link to subsidiary ledger --}}
@can('suppliers.reports')
<div class="card mb-3" style="background:linear-gradient(135deg, #eef2ff, #fff); border:1px solid #c7d2fe;">
    <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h6 class="mb-1"><i class="bi bi-journal-arrow-down text-primary"></i> كشف حساب المورد</h6>
            <small class="text-muted">عرض كل الفواتير والسدادات + الرصيد المتراكم لأي فترة</small>
        </div>
        <a href="{{ route('admin.suppliers.statement', ['supplier_id' => $supplier->id, 'from' => now()->subYear()->format('Y-m-d'), 'to' => now()->format('Y-m-d')]) }}"
           class="btn btn-primary">
            <i class="bi bi-list-ul"></i> عرض كشف الحساب
        </a>
    </div>
</div>
@endcan

<div class="card">
    <div class="card-body d-flex justify-content-between flex-wrap gap-2">
        <a href="{{ route('admin.suppliers.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-right"></i> رجوع للقائمة
        </a>
        <div class="d-flex gap-2 flex-wrap">
            @can('supplier_invoices.create')
            <a href="{{ route('admin.supplier_invoices.create', ['supplier_id' => $supplier->id]) }}" class="btn btn-outline-info">
                <i class="bi bi-receipt"></i> فاتورة جديدة
            </a>
            @endcan
            @can('accounting.vouchers.create')
            <a href="{{ route('admin.accounting.vouchers.payments.create', ['supplier_id' => $supplier->id]) }}" class="btn btn-outline-warning">
                <i class="bi bi-cash-stack"></i> سداد للمورد
            </a>
            @endcan
            @can('suppliers.update')
            <a href="{{ route('admin.suppliers.edit', $supplier) }}" class="btn btn-primary">
                <i class="bi bi-pencil"></i> تعديل
            </a>
            @endcan
        </div>
    </div>
</div>

@if($supplier->creator)
    <div class="text-muted small text-center mt-3">
        أُضيف بواسطة: <strong>{{ $supplier->creator->name }}</strong> — {{ $supplier->created_at?->format('Y-m-d H:i') }}
    </div>
@endif

@endsection
