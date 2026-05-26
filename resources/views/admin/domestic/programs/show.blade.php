@extends('layouts.master')

@section('title', $program->name)
@section('page_title', $program->name)
@section('page_subtitle', 'تفاصيل البرنامج السياحي الداخلي')

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

    .bg-success-soft   { background:#dcfce7 !important; color:#15803d !important; }
    .bg-warning-soft   { background:#fef3c7 !important; color:#b45309 !important; }
    .bg-info-soft      { background:#dbeafe !important; color:#1d4ed8 !important; }
    .bg-primary-soft   { background:#e0e7ff !important; color:#4338ca !important; }
    .bg-secondary-soft { background:#f1f5f9 !important; color:#475569 !important; }
    .bg-danger-soft    { background:#fee2e2 !important; color:#b91c1c !important; }
</style>
@endpush

@section('content')
<div class="row g-3">
    <div class="col-lg-8">
        <div class="info-card">
            <img src="{{ $program->cover_url }}" class="cover-big"
                 onerror="this.src='data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 600 280%22><rect width=%22100%25%22 height=%22100%25%22 fill=%22%23eef2ff%22/><text x=%2250%25%22 y=%2250%25%22 text-anchor=%22middle%22 dominant-baseline=%22middle%22 font-family=%22Cairo%22 font-size=%2220%22 fill=%22%231e3a8a%22>🏖️ {{ $program->name }}</text></svg>'">
            <div class="body">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                    <div>
                        <h4 class="mb-1">{{ $program->name }}</h4>
                        @if($program->name_en)
                            <div class="text-muted" dir="ltr">{{ $program->name_en }}</div>
                        @endif
                        <div class="mt-2">
                            @php
                                $typeColors = [
                                    'hotel_only'=>'info','package'=>'primary','day_trip'=>'warning',
                                    'cruise'=>'success','camp'=>'secondary','event'=>'danger',
                                ];
                                $typeIcons = [
                                    'hotel_only'=>'building','package'=>'bag-check','day_trip'=>'sun',
                                    'cruise'=>'water','camp'=>'tree','event'=>'calendar-event',
                                ];
                            @endphp
                            <span class="badge bg-{{ $typeColors[$program->type] ?? 'secondary' }}-soft badge-mega">
                                <i class="bi bi-{{ $typeIcons[$program->type] ?? 'compass' }}"></i>
                                {{ $program->type_label }}
                            </span>
                            <span class="badge bg-light text-dark badge-mega">
                                <i class="bi bi-geo-alt"></i> {{ $program->destination_city }}
                            </span>
                            <span class="badge bg-light text-dark badge-mega">
                                <i class="bi bi-clock"></i> {{ $program->duration_days }} يوم
                                @if($program->duration_nights) / {{ $program->duration_nights }} ليلة @endif
                            </span>
                            @if($program->season)
                                <span class="badge bg-primary-soft badge-mega"><i class="bi bi-calendar3"></i> {{ $program->season }}</span>
                            @endif
                            @if($program->is_active)
                                <span class="badge bg-success-soft badge-mega"><i class="bi bi-check-circle"></i> نشط</span>
                            @else
                                <span class="badge bg-secondary-soft badge-mega"><i class="bi bi-pause-circle"></i> متوقف</span>
                            @endif
                            @if($program->is_published)
                                <span class="badge bg-warning-soft badge-mega"><i class="bi bi-broadcast"></i> منشور</span>
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
                <div class="kv"><span class="k">الدولة</span><span class="v">{{ $program->destination_country }}</span></div>
                <div class="kv"><span class="k">المدينة</span><span class="v">{{ $program->destination_city }}</span></div>
                @if($program->destination_area)
                <div class="kv"><span class="k">المنطقة</span><span class="v">{{ $program->destination_area }}</span></div>
                @endif
                <div class="kv"><span class="k">تاريخ البداية</span><span class="v">{{ $program->start_date?->format('Y-m-d') ?? '—' }}</span></div>
                <div class="kv"><span class="k">تاريخ النهاية</span><span class="v">{{ $program->end_date?->format('Y-m-d') ?? '—' }}</span></div>
                <div class="kv"><span class="k">الحد الأدنى</span><span class="v">{{ $program->min_guests }} فرد</span></div>
                <div class="kv"><span class="k">الحد الأقصى</span><span class="v">{{ $program->max_guests }} فرد</span></div>
                <div class="kv"><span class="k">عدد الحجوزات</span><span class="v">{{ number_format($bookingsCount) }}</span></div>
            </div>
        </div>

        <div class="info-card">
            <div class="head"><h6><i class="bi bi-gear"></i> الإعدادات الافتراضية</h6></div>
            <div class="body">
                <div class="kv"><span class="k">مستوى السكن</span><span class="v">{{ $program->grade_label }}</span></div>
                <div class="kv"><span class="k">وسيلة النقل</span><span class="v">{{ $program->transport_label }}</span></div>
                <div class="kv"><span class="k">نظام الإقامة</span><span class="v">{{ $program->meal_plan_label }}</span></div>
            </div>
        </div>

        <div class="d-grid gap-2">
            @can('domestic_programs.update')
            <a href="{{ route('admin.domestic.programs.edit', $program) }}" class="btn btn-primary">
                <i class="bi bi-pencil"></i> تعديل البرنامج
            </a>
            @endcan
            <a href="{{ route('admin.domestic.programs.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-right"></i> العودة للقائمة
            </a>
        </div>
    </div>
</div>
@endsection
