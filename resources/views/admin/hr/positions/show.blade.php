@extends('layouts.master')

@section('title', $position->title)
@section('page_title', $position->title)
@section('page_subtitle', 'وظيفة ' . $position->code)

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

    .position-hero { background:linear-gradient(135deg, #b45309 0%, #ea580c 100%); color:#fff; border-radius:18px; padding:1.6rem; margin-bottom:1rem; box-shadow:0 10px 25px rgba(234, 88, 12, 0.2); }
    .position-hero h3 { margin:0; font-weight:800; font-size:1.5rem; }
    .position-hero .meta { display:flex; gap:1rem; margin-top:.85rem; flex-wrap:wrap; }
    .position-hero .badge-mega { font-size:.78rem; padding:.4rem .85rem; border-radius:8px; font-weight:700; background:rgba(255,255,255,.18); color:#fff; }

    .salary-row { display:flex; justify-content:space-between; padding:.65rem 0; border-bottom:1px dashed #e2e8f0; font-size:.9rem; }
    .salary-row:last-child { border-bottom:none; padding-top:.85rem; border-top:2px solid #15803d; font-size:1.05rem; font-weight:900; color:#15803d; }
    .salary-row .k { color:#475569; }
    .salary-row .v { font-weight:700; color:#0f172a; }
</style>
@endpush

@section('content')

<div class="position-hero">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
        <div>
            <h3>{{ $position->title }}</h3>
            <div class="meta">
                <span class="badge-mega"><i class="bi bi-hash"></i> {{ $position->code }}</span>
                @if($position->is_active)
                    <span class="badge-mega"><i class="bi bi-check-circle"></i> نشطة</span>
                @else
                    <span class="badge-mega"><i class="bi bi-pause-circle"></i> متوقفة</span>
                @endif
                @if($position->department)
                    <span class="badge-mega"><i class="bi bi-diagram-3"></i> {{ $position->department->name }}</span>
                @endif
                @if((float) $position->commission_rate > 0)
                    <span class="badge-mega"><i class="bi bi-percent"></i> {{ number_format($position->commission_rate, 2) }}%</span>
                @endif
            </div>
        </div>
        <div class="d-flex gap-2">
            @can('positions.update')
            <a href="{{ route('admin.hr.positions.edit', $position) }}" class="btn btn-light">
                <i class="bi bi-pencil"></i> تعديل
            </a>
            @endcan
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="info-card">
            <div class="head"><h6><i class="bi bi-info-circle"></i> التفاصيل</h6></div>
            <div class="body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="kv"><span class="k">الكود</span><span class="v"><code>{{ $position->code }}</code></span></div>
                        <div class="kv"><span class="k">المسمى</span><span class="v">{{ $position->title }}</span></div>
                        @if($position->title_en)
                        <div class="kv"><span class="k">بالإنجليزية</span><span class="v" dir="ltr">{{ $position->title_en }}</span></div>
                        @endif
                    </div>
                    <div class="col-md-6">
                        <div class="kv"><span class="k">القسم</span><span class="v">{{ $position->department?->name ?? '—' }}</span></div>
                        <div class="kv"><span class="k">عدد الموظفين</span><span class="v">{{ $position->employees_count }}</span></div>
                        <div class="kv"><span class="k">تاريخ الإنشاء</span><span class="v">{{ $position->created_at?->format('Y-m-d') }}</span></div>
                    </div>
                </div>

                @if($position->description)
                <div class="alert alert-light mt-3 mb-0 border">
                    <strong><i class="bi bi-info-circle"></i> الوصف:</strong>
                    <div class="mt-1">{{ $position->description }}</div>
                </div>
                @endif
            </div>
        </div>

        <div class="info-card">
            <div class="head"><h6><i class="bi bi-percent"></i> العمولة</h6></div>
            <div class="body">
                @if((float) $position->commission_rate > 0)
                    <div class="kv"><span class="k">نسبة العمولة</span><span class="v text-warning">{{ number_format($position->commission_rate, 2) }}%</span></div>
                    <div class="kv"><span class="k">أساس الاحتساب</span><span class="v">{{ $position->commission_basis_label }}</span></div>
                    <div class="alert alert-warning mt-2 mb-0 small">
                        <i class="bi bi-info-circle"></i>
                        كل موظف في هذه الوظيفة سيأخذ <strong>{{ number_format($position->commission_rate, 2) }}%</strong>
                        من {{ $position->commission_basis_label }} لكل حجز يتم البيع منه — ما لم يُحدَّد له نسبة خاصة.
                    </div>
                @else
                    <div class="text-muted text-center py-3">
                        <i class="bi bi-info-circle fs-3 d-block mb-2"></i>
                        لم تُحدَّد نسبة عمولة افتراضية لهذه الوظيفة.
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="info-card">
            <div class="head"><h6><i class="bi bi-cash-coin"></i> الراتب الافتراضي</h6></div>
            <div class="body">
                <div class="salary-row"><span class="k">الراتب الأساسي</span><span class="v">{{ number_format((float) $position->default_basic_salary, 2) }} ج.م</span></div>
                <div class="salary-row"><span class="k">بدل السكن</span><span class="v">{{ number_format((float) $position->default_housing_allowance, 2) }} ج.م</span></div>
                <div class="salary-row"><span class="k">بدل الانتقال</span><span class="v">{{ number_format((float) $position->default_transport_allowance, 2) }} ج.م</span></div>
                <div class="salary-row"><span class="k">بدلات أخرى</span><span class="v">{{ number_format((float) $position->default_other_allowances, 2) }} ج.م</span></div>
                <div class="salary-row"><span class="k">الإجمالي</span><span class="v">{{ number_format($position->total_default_salary, 2) }} ج.م</span></div>
            </div>
        </div>

        <a href="{{ route('admin.hr.positions.index') }}" class="btn btn-outline-secondary w-100">
            <i class="bi bi-arrow-right"></i> العودة للقائمة
        </a>
    </div>
</div>

@endsection
