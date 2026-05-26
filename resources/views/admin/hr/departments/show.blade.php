@extends('layouts.master')

@section('title', $department->name)
@section('page_title', $department->name)
@section('page_subtitle', 'قسم ' . $department->code)

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

    .dept-hero { background:linear-gradient(135deg, #4338ca 0%, #6d28d9 100%); color:#fff; border-radius:18px; padding:1.6rem; margin-bottom:1rem; box-shadow:0 10px 25px rgba(67, 56, 202, 0.2); }
    .dept-hero h3 { margin:0; font-weight:800; font-size:1.5rem; }
    .dept-hero .meta { display:flex; gap:1rem; margin-top:.85rem; flex-wrap:wrap; }
    .dept-hero .badge-mega { font-size:.78rem; padding:.4rem .85rem; border-radius:8px; font-weight:700; background:rgba(255,255,255,.18); color:#fff; }

    .txn-grid { display:grid; grid-template-columns:repeat(2, 1fr); gap:.85rem; }
    .txn-tile { background:#fff; border:1px solid #f1f5f9; border-radius:12px; padding:.95rem; text-align:center; }
    .txn-tile .lbl { font-size:.72rem; color:#64748b; font-weight:600; margin-bottom:.35rem; }
    .txn-tile .val { font-size:1.5rem; font-weight:900; color:var(--brand-navy); }
    .txn-tile .ic { font-size:1.5rem; color:#94a3b8; margin-bottom:.35rem; }
</style>
@endpush

@section('content')

<div class="dept-hero">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
        <div>
            <h3>{{ $department->name }}</h3>
            <div class="meta">
                <span class="badge-mega"><i class="bi bi-hash"></i> {{ $department->code }}</span>
                @if($department->is_active)
                    <span class="badge-mega"><i class="bi bi-check-circle"></i> نشط</span>
                @else
                    <span class="badge-mega"><i class="bi bi-pause-circle"></i> متوقف</span>
                @endif
                @if($department->branch)
                    <span class="badge-mega"><i class="bi bi-buildings"></i> {{ $department->branch->name }}</span>
                @else
                    <span class="badge-mega"><i class="bi bi-globe"></i> قسم عام</span>
                @endif
                @if($department->manager)
                    <span class="badge-mega"><i class="bi bi-person-badge"></i> {{ $department->manager->full_name }}</span>
                @endif
            </div>
        </div>
        <div class="d-flex gap-2">
            @can('departments.update')
            <a href="{{ route('admin.hr.departments.edit', $department) }}" class="btn btn-light">
                <i class="bi bi-pencil"></i> تعديل
            </a>
            @endcan
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
                        <div class="kv"><span class="k">الكود</span><span class="v"><code>{{ $department->code }}</code></span></div>
                        <div class="kv"><span class="k">الاسم</span><span class="v">{{ $department->name }}</span></div>
                        @if($department->name_en)
                        <div class="kv"><span class="k">بالإنجليزية</span><span class="v" dir="ltr">{{ $department->name_en }}</span></div>
                        @endif
                    </div>
                    <div class="col-md-6">
                        <div class="kv">
                            <span class="k">الفرع</span>
                            <span class="v">{{ $department->branch?->name ?? 'قسم عام (لكل الفروع)' }}</span>
                        </div>
                        <div class="kv">
                            <span class="k">المدير</span>
                            <span class="v">
                                @if($department->manager)
                                    {{ $department->manager->full_name }} ({{ $department->manager->code }})
                                @else
                                    —
                                @endif
                            </span>
                        </div>
                        <div class="kv"><span class="k">تاريخ الإنشاء</span><span class="v">{{ $department->created_at?->format('Y-m-d') }}</span></div>
                    </div>
                </div>

                @if($department->description)
                <div class="alert alert-light mt-3 mb-0 border">
                    <strong><i class="bi bi-info-circle"></i> الوصف:</strong>
                    <div class="mt-1">{{ $department->description }}</div>
                </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="info-card">
            <div class="head"><h6><i class="bi bi-bar-chart"></i> النشاط</h6></div>
            <div class="body">
                <div class="txn-grid">
                    <div class="txn-tile">
                        <div class="ic"><i class="bi bi-people"></i></div>
                        <div class="lbl">الموظفين</div>
                        <div class="val">{{ number_format($department->employees_count) }}</div>
                    </div>
                    <div class="txn-tile">
                        <div class="ic"><i class="bi bi-briefcase"></i></div>
                        <div class="lbl">الوظائف</div>
                        <div class="val">{{ number_format($department->positions_count) }}</div>
                    </div>
                </div>
            </div>
        </div>

        <a href="{{ route('admin.hr.departments.index') }}" class="btn btn-outline-secondary w-100">
            <i class="bi bi-arrow-right"></i> العودة للقائمة
        </a>
    </div>
</div>

@endsection
