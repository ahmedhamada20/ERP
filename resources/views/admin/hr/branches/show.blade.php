@extends('layouts.master')

@section('title', $branch->name)
@section('page_title', $branch->name)
@section('page_subtitle', 'فرع ' . $branch->code)

@push('styles')
<style>
    .info-card { background:#fff; border-radius:14px; box-shadow:0 1px 3px rgba(15,23,42,.04); border:1px solid var(--brand-border); overflow:hidden; margin-bottom:1rem; }
    .info-card .head { padding:.85rem 1.1rem; border-bottom:1px solid var(--brand-border); background:linear-gradient(180deg,#fafbff,#f1f5f9); display:flex; align-items:center; justify-content:space-between; }
    .info-card .head h6 { margin:0; color:var(--brand-navy); font-weight:800; }
    .info-card .body { padding:1.1rem; }
    .kv { display:flex; justify-content:space-between; padding:.5rem 0; border-bottom:1px dashed #e2e8f0; font-size:.86rem; }
    .kv:last-child { border-bottom:none; }
    .kv .k { color:#64748b; font-weight:600; }
    .kv .v { color:#0f172a; font-weight:700; text-align:end; }

    .branch-hero { background:linear-gradient(135deg, #1e3a8a 0%, #4338ca 100%); color:#fff; border-radius:18px; padding:1.6rem; margin-bottom:1rem; box-shadow:0 10px 25px rgba(30, 58, 138, 0.2); }
    .branch-hero h3 { margin:0; font-weight:800; font-size:1.5rem; }
    .branch-hero .meta { display:flex; gap:1rem; margin-top:.85rem; flex-wrap:wrap; }
    .branch-hero .badge-mega { font-size:.78rem; padding:.4rem .85rem; border-radius:8px; font-weight:700; background:rgba(255,255,255,.18); color:#fff; }
    .branch-hero .badge-main { background:#fde68a !important; color:#78350f !important; }

    .txn-grid { display:grid; grid-template-columns:repeat(3, 1fr); gap:.85rem; margin-bottom:1rem; }
    .txn-tile { background:#fff; border:1px solid #f1f5f9; border-radius:12px; padding:.95rem; text-align:center; }
    .txn-tile .lbl { font-size:.72rem; color:#64748b; font-weight:600; margin-bottom:.35rem; }
    .txn-tile .val { font-size:1.5rem; font-weight:900; color:var(--brand-navy); }
    .txn-tile .ic { font-size:1.5rem; color:#94a3b8; margin-bottom:.35rem; }

    @media (max-width: 768px) { .txn-grid { grid-template-columns:1fr 1fr; } }
</style>
@endpush

@section('content')

<div class="branch-hero">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
        <div>
            <h3>{{ $branch->name }}</h3>
            <div class="meta">
                <span class="badge-mega"><i class="bi bi-hash"></i> {{ $branch->code }}</span>
                @if($branch->is_main)
                    <span class="badge-mega badge-main"><i class="bi bi-star-fill"></i> فرع رئيسي</span>
                @endif
                @if($branch->is_active)
                    <span class="badge-mega"><i class="bi bi-check-circle"></i> نشط</span>
                @endif
                @if($branch->city)
                    <span class="badge-mega"><i class="bi bi-geo-alt"></i> {{ $branch->city }}</span>
                @endif
                @if($branch->manager_name)
                    <span class="badge-mega"><i class="bi bi-person"></i> {{ $branch->manager_name }}</span>
                @endif
            </div>
        </div>
        <div class="d-flex gap-2">
            @can('branches.update')
            @if(!$branch->is_main)
            <form action="{{ route('admin.hr.branches.set_main', $branch) }}" method="POST" class="d-inline"
                  onsubmit="return confirm('تعيين هذا الفرع كرئيسي؟')">
                @csrf
                <button class="btn btn-warning"><i class="bi bi-star"></i> جعله رئيسي</button>
            </form>
            @endif
            <a href="{{ route('admin.hr.branches.edit', $branch) }}" class="btn btn-light">
                <i class="bi bi-pencil"></i> تعديل
            </a>
            @endcan
        </div>
    </div>
</div>

{{-- Transaction counts --}}
<div class="info-card">
    <div class="head"><h6><i class="bi bi-bar-chart"></i> النشاط المرتبط بهذا الفرع</h6></div>
    <div class="body">
        <div class="txn-grid">
            <div class="txn-tile">
                <div class="ic"><i class="bi bi-mosque"></i></div>
                <div class="lbl">حجوزات دينية</div>
                <div class="val">{{ number_format($txnCounts['religious_bookings']) }}</div>
            </div>
            <div class="txn-tile">
                <div class="ic"><i class="bi bi-map"></i></div>
                <div class="lbl">حجوزات داخلية</div>
                <div class="val">{{ number_format($txnCounts['domestic_bookings']) }}</div>
            </div>
            <div class="txn-tile">
                <div class="ic"><i class="bi bi-person-vcard"></i></div>
                <div class="lbl">عملاء</div>
                <div class="val">{{ number_format($txnCounts['customers']) }}</div>
            </div>
            <div class="txn-tile">
                <div class="ic"><i class="bi bi-building-add"></i></div>
                <div class="lbl">موردين</div>
                <div class="val">{{ number_format($txnCounts['suppliers']) }}</div>
            </div>
            <div class="txn-tile">
                <div class="ic"><i class="bi bi-receipt"></i></div>
                <div class="lbl">سندات</div>
                <div class="val">{{ number_format($txnCounts['vouchers']) }}</div>
            </div>
            <div class="txn-tile">
                <div class="ic"><i class="bi bi-people"></i></div>
                <div class="lbl">موظفين</div>
                <div class="val">{{ number_format($branch->employees_count) }}</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="info-card">
            <div class="head"><h6><i class="bi bi-info-circle"></i> التفاصيل</h6></div>
            <div class="body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="kv"><span class="k">الكود</span><span class="v"><code>{{ $branch->code }}</code></span></div>
                        <div class="kv"><span class="k">الاسم</span><span class="v">{{ $branch->name }}</span></div>
                        @if($branch->name_en)
                        <div class="kv"><span class="k">بالإنجليزية</span><span class="v" dir="ltr">{{ $branch->name_en }}</span></div>
                        @endif
                        <div class="kv"><span class="k">المدير</span><span class="v">{{ $branch->manager_name ?? '—' }}</span></div>
                    </div>
                    <div class="col-md-6">
                        <div class="kv"><span class="k">الهاتف</span><span class="v" dir="ltr">{{ $branch->phone ?? '—' }}</span></div>
                        <div class="kv"><span class="k">البريد</span><span class="v" dir="ltr">{{ $branch->email ?? '—' }}</span></div>
                        <div class="kv"><span class="k">الأقسام</span><span class="v">{{ $branch->departments_count }}</span></div>
                        <div class="kv"><span class="k">الموظفين</span><span class="v">{{ $branch->employees_count }}</span></div>
                    </div>
                </div>

                @if($branch->address)
                <div class="alert alert-light mt-3 mb-0 border">
                    <strong><i class="bi bi-geo-alt"></i> العنوان:</strong>
                    <div class="mt-1">{{ $branch->address }}</div>
                </div>
                @endif

                @if($branch->notes)
                <div class="alert alert-light mt-3 mb-0 border">
                    <strong><i class="bi bi-sticky"></i> ملاحظات:</strong>
                    <div class="mt-1">{{ $branch->notes }}</div>
                </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="info-card">
            <div class="head"><h6><i class="bi bi-geo-alt"></i> الموقع</h6></div>
            <div class="body">
                <div class="kv"><span class="k">الدولة</span><span class="v">{{ $branch->country }}</span></div>
                <div class="kv"><span class="k">المحافظة</span><span class="v">{{ $branch->governorate ?? '—' }}</span></div>
                <div class="kv"><span class="k">المدينة</span><span class="v">{{ $branch->city ?? '—' }}</span></div>
            </div>
        </div>

        <a href="{{ route('admin.hr.branches.index') }}" class="btn btn-outline-secondary w-100">
            <i class="bi bi-arrow-right"></i> العودة للقائمة
        </a>
    </div>
</div>

@endsection
