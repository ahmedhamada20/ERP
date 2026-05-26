@extends('layouts.master')

@section('title', $program->name)
@section('page_title', $program->name)
@section('page_subtitle', 'تفاصيل البرنامج الديني')

@push('styles')
<style>
    .info-card { background:#fff; border-radius:14px; box-shadow:0 1px 3px rgba(15,23,42,.04); border:1px solid var(--brand-border); overflow:hidden; margin-bottom:1rem; }
    .info-card .head { padding:.85rem 1.1rem; border-bottom:1px solid var(--brand-border); background:linear-gradient(180deg,#fafbff,#f1f5f9); }
    .info-card .head h6 { margin:0; color:var(--brand-navy); font-weight:800; display:inline-flex; align-items:center; gap:.5rem; }
    .info-card .body { padding:1.1rem; }
    .kv { display:flex; justify-content:space-between; padding:.5rem 0; border-bottom:1px dashed #e2e8f0; font-size:.88rem; }
    .kv:last-child { border-bottom:none; }
    .kv .k { color:#64748b; font-weight:600; }
    .kv .v { color:#0f172a; font-weight:700; }
    .badge-mega { font-size:.85rem; padding:.45rem .9rem; border-radius:8px; font-weight:700; }
    .cover-big { width:100%; max-height:280px; object-fit:cover; border-radius:14px; }
    .price-tag { font-size:2.2rem; font-weight:900; color:var(--brand-gold); line-height:1; }
    .price-tag small { font-size:.85rem; color:#64748b; font-weight:600; }
</style>
@endpush

@section('content')
<div class="row g-3">
    <div class="col-lg-8">
        <div class="info-card">
            <img src="{{ $program->cover_url }}" class="cover-big"
                 onerror="this.src='data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 600 280%22><rect width=%22100%25%22 height=%22100%25%22 fill=%22%23eef2ff%22/><text x=%2250%25%22 y=%2250%25%22 text-anchor=%22middle%22 dominant-baseline=%22middle%22 font-family=%22Cairo%22 font-size=%2220%22 fill=%22%231e3a8a%22>📿 {{ $program->name }}</text></svg>'">
            <div class="body">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                    <div>
                        <h4 class="mb-1">{{ $program->name }}</h4>
                        @if($program->name_en)
                            <div class="text-muted" dir="ltr">{{ $program->name_en }}</div>
                        @endif
                        <div class="mt-2">
                            @if($program->type === 'hajj')
                                <span class="badge bg-success-soft badge-mega"><i class="bi bi-mosque"></i> حج</span>
                            @else
                                <span class="badge bg-info-soft badge-mega"><i class="bi bi-moon-stars"></i> عمرة</span>
                            @endif
                            <span class="badge bg-light text-dark badge-mega"><i class="bi bi-clock"></i> {{ $program->duration_days }} يوم</span>
                            @if($program->season)
                                <span class="badge bg-primary-soft badge-mega"><i class="bi bi-calendar3"></i> {{ $program->season }}</span>
                            @endif
                            @if($program->is_active)
                                <span class="badge bg-success-soft badge-mega"><i class="bi bi-check-circle"></i> نشط</span>
                            @else
                                <span class="badge bg-secondary-soft badge-mega"><i class="bi bi-pause-circle"></i> متوقف</span>
                            @endif
                        </div>
                    </div>
                    <div class="text-end">
                        <div class="price-tag">
                            {{ number_format($program->base_price_per_person, 0) }}
                            <small>ج.م</small>
                        </div>
                        <small class="text-muted">السعر الأساسي للفرد</small>
                    </div>
                </div>

                @if($program->description)
                    <div class="mb-3">
                        <h6 class="text-muted"><i class="bi bi-card-text"></i> الوصف</h6>
                        <p class="mb-0">{{ $program->description }}</p>
                    </div>
                @endif

                <div class="row g-3">
                    @if($program->inclusions)
                    <div class="col-md-6">
                        <div class="info-card mb-0">
                            <div class="head"><h6><i class="bi bi-check-circle text-success"></i> يشمل</h6></div>
                            <div class="body">{!! nl2br(e($program->inclusions)) !!}</div>
                        </div>
                    </div>
                    @endif
                    @if($program->exclusions)
                    <div class="col-md-6">
                        <div class="info-card mb-0">
                            <div class="head"><h6><i class="bi bi-x-circle text-danger"></i> لا يشمل</h6></div>
                            <div class="body">{!! nl2br(e($program->exclusions)) !!}</div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="info-card">
            <div class="head"><h6><i class="bi bi-info-circle"></i> معلومات البرنامج</h6></div>
            <div class="body">
                <div class="kv"><span class="k">الكود</span><span class="v"><code>{{ $program->code }}</code></span></div>
                <div class="kv"><span class="k">تاريخ البداية</span><span class="v">{{ $program->start_date?->format('Y-m-d') ?? '—' }}</span></div>
                <div class="kv"><span class="k">تاريخ النهاية</span><span class="v">{{ $program->end_date?->format('Y-m-d') ?? '—' }}</span></div>
                <div class="kv"><span class="k">الحد الأدنى</span><span class="v">{{ $program->min_pilgrims }} فرد</span></div>
                <div class="kv"><span class="k">الحد الأقصى</span><span class="v">{{ $program->max_pilgrims }} فرد</span></div>
                <div class="kv"><span class="k">عدد الحجوزات</span><span class="v">{{ number_format($bookingsCount) }}</span></div>
            </div>
        </div>

        <div class="info-card">
            <div class="head"><h6><i class="bi bi-gear"></i> الإعدادات الافتراضية</h6></div>
            <div class="body">
                <div class="kv"><span class="k">نوع التأشيرة</span><span class="v">
                    @switch($program->default_visa_type)
                        @case('haram') حرم @break
                        @case('kaaba') كعبة @break
                        @default عادية
                    @endswitch
                </span></div>
                <div class="kv"><span class="k">مستوى السكن</span><span class="v">
                    @switch($program->default_accommodation_grade)
                        @case('5_stars') 5 نجوم @break
                        @case('4_stars') 4 نجوم @break
                        @default اقتصادي
                    @endswitch
                </span></div>
                <div class="kv"><span class="k">وسيلة النقل</span><span class="v">
                    @switch($program->default_transport_type)
                        @case('flight') طيران @break
                        @case('bus') باص @break
                        @case('train') قطار @break
                        @case('vip') VIP @break
                    @endswitch
                </span></div>
                <div class="kv"><span class="k">نظام الإقامة</span><span class="v">{{ strtoupper($program->default_meal_plan) }}</span></div>
                <div class="kv"><span class="k">مستوى المطوف</span><span class="v">
                    @switch($program->default_mutawif_grade)
                        @case('5_stars') 5 نجوم @break
                        @case('land') بري @break
                        @default اقتصادي
                    @endswitch
                </span></div>
            </div>
        </div>

        <div class="d-grid gap-2">
            @can('religious_programs.update')
            <a href="{{ route('admin.religious.programs.edit', $program) }}" class="btn btn-primary">
                <i class="bi bi-pencil"></i> تعديل البرنامج
            </a>
            @endcan
            <a href="{{ route('admin.religious.programs.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-right"></i> العودة للقائمة
            </a>
        </div>
    </div>
</div>
@endsection
