@extends('layouts.master')

@section('title', 'الموردون')
@section('page_title', 'إدارة الموردين')
@section('page_subtitle', 'الفنادق، الطيران، النقل، التأشيرات — وأرصدتهم المستحقة')

@push('styles')
<style>
    .stat-mini { background:#fff; border-radius:12px; padding:1rem 1.25rem; border:1px solid #e5e7eb; display:flex; align-items:center; gap:.9rem; }
    .stat-mini .si { width:44px; height:44px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.3rem; }
    .stat-mini .sv { font-weight:800; font-size:1.35rem; color:#0f172a; line-height:1; }
    .stat-mini .sl { font-size:.78rem; color:#6b7280; margin-top:.25rem; }
    .btn-light-info { background:#dbeafe; color:#1e40af; border:none; }
    .btn-light-info:hover { background:#bfdbfe; color:#1e40af; }
    .btn-light-primary { background:#e0e7ff; color:#4338ca; border:none; }
    .btn-light-primary:hover { background:#c7d2fe; color:#4338ca; }
    .btn-light-danger { background:#fee2e2; color:#b91c1c; border:none; }
    .btn-light-danger:hover { background:#fecaca; color:#b91c1c; }
</style>
@endpush

@section('content')

<div class="row g-2 mb-3">
    <div class="col-6 col-md-3">
        <div class="stat-mini">
            <div class="si" style="background:#eef2ff; color:#4f46e5;"><i class="bi bi-building"></i></div>
            <div><div class="sv">{{ $counts['total'] }}</div><div class="sl">إجمالي الموردين</div></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-mini">
            <div class="si" style="background:#dcfce7; color:#15803d;"><i class="bi bi-check2-circle"></i></div>
            <div><div class="sv">{{ $counts['active'] }}</div><div class="sl">نشطون</div></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-mini">
            <div class="si" style="background:#dbeafe; color:#1e40af;"><i class="bi bi-bank2"></i></div>
            <div><div class="sv">{{ $counts['hotels'] }}</div><div class="sl">فنادق</div></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-mini">
            <div class="si" style="background:#fef3c7; color:#92400e;"><i class="bi bi-airplane"></i></div>
            <div><div class="sv">{{ $counts['airlines'] }}</div><div class="sl">طيران</div></div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h6 class="mb-0"><i class="bi bi-list-ul"></i> قائمة الموردين</h6>
        <div class="d-flex gap-2 flex-wrap">
            <select id="typeFilter" class="form-select form-select-sm" style="width:auto;">
                <option value="">— كل الأنواع —</option>
                <option value="hotel">فنادق</option>
                <option value="airline">طيران</option>
                <option value="transport">نقل</option>
                <option value="visa">تأشيرات</option>
                <option value="other">أخرى</option>
            </select>
            <select id="statusFilter" class="form-select form-select-sm" style="width:auto;">
                <option value="">— كل الحالات —</option>
                <option value="1">نشط</option>
                <option value="0">متوقف</option>
            </select>
            @can('suppliers.create')
            <a href="{{ route('admin.suppliers.create') }}" class="btn btn-sm btn-primary">
                <i class="bi bi-plus-lg"></i> مورد جديد
            </a>
            @endcan
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="suppliersTable" class="table pretty-table" style="width:100%;">
                <thead>
                    <tr>
                        <th width="120">الكود</th>
                        <th>الاسم</th>
                        <th>النوع</th>
                        <th>التواصل</th>
                        <th>الرصيد الافتتاحي</th>
                        <th>الحالة</th>
                        <th width="120">إجراءات</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    const t = $('#suppliersTable').DataTable({
        processing: true, serverSide: true, responsive: true,
        order: [[0, 'desc']],
        language: { url: 'https://cdn.datatables.net/plug-ins/2.0.7/i18n/ar.json' },
        ajax: {
            url: '{{ route('admin.suppliers.data') }}',
            data: d => {
                d.type   = $('#typeFilter').val();
                d.status = $('#statusFilter').val();
            },
        },
        columns: [
            { data: 'code',            name: 'code' },
            { data: 'name',            name: 'name' },
            { data: 'type',            name: 'type' },
            { data: 'contact',         orderable: false },
            { data: 'opening_balance', name: 'opening_balance', className: 'text-end' },
            { data: 'is_active',       name: 'is_active' },
            { data: 'actions',         orderable: false, searchable: false },
        ],
    });
    $('#typeFilter, #statusFilter').on('change', () => t.ajax.reload());

    $('#suppliersTable').on('click', '.btn-delete', async function () {
        if (!confirm('تأكيد حذف هذا المورد؟')) return;
        const url = $(this).data('url');
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
        try {
            const res = await fetch(url, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            });
            if (res.ok) t.ajax.reload();
            else {
                const body = await res.json().catch(() => ({}));
                alert(body.message || 'فشل الحذف');
            }
        } catch { alert('خطأ في الاتصال'); }
    });
});
</script>
@endpush
