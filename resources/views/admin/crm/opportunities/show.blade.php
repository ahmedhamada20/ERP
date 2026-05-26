@extends('layouts.master')

@section('title', $opp->code . ' — ' . $opp->title)
@section('page_title', $opp->title)
@section('page_subtitle', 'صفقة ' . $opp->code . ' — ' . $opp->stage_label)

@push('styles')
<style>
    .info-card { background:#fff; border-radius:14px; box-shadow:0 1px 3px rgba(15,23,42,.04); border:1px solid var(--brand-border); overflow:hidden; margin-bottom:1rem; }
    .info-card .head { padding:.85rem 1.1rem; border-bottom:1px solid var(--brand-border); background:linear-gradient(180deg,#fafbff,#f1f5f9); display:flex; align-items:center; justify-content:space-between; }
    .info-card .head h6 { margin:0; color:var(--brand-navy); font-weight:800; }
    .info-card .body { padding:1.1rem; }
    .kv { display:flex; justify-content:space-between; padding:.45rem 0; border-bottom:1px dashed #e2e8f0; font-size:.86rem; }
    .kv:last-child { border-bottom:none; }
    .kv .k { color:#64748b; font-weight:600; }
    .kv .v { color:#0f172a; font-weight:700; text-align:end; }

    .opp-hero { background:linear-gradient(135deg, #4338ca 0%, #6b21a8 100%); color:#fff; border-radius:18px; padding:1.6rem; margin-bottom:1rem; box-shadow:0 10px 25px rgba(67, 56, 202, 0.2); }
    .opp-hero h3 { margin:0; font-weight:800; font-size:1.5rem; }
    .opp-hero .meta { display:flex; gap:1rem; margin-top:.85rem; flex-wrap:wrap; }
    .opp-hero .badge-mega { font-size:.78rem; padding:.4rem .85rem; border-radius:8px; font-weight:700; background:rgba(255,255,255,.18); color:#fff; }
    .opp-hero .value-tag { background:rgba(255,255,255,.95); color:#92400e; font-weight:800; padding:.55rem 1rem; border-radius:10px; font-size:1.1rem; text-align:center; }
    .opp-hero .value-tag small { display:block; font-size:.7rem; color:#64748b; font-weight:600; }

    /* Stage progress bar */
    .stage-bar { display:flex; gap:.3rem; margin:1rem 0; }
    .stage-step { flex:1; padding:.45rem .5rem; border-radius:6px; text-align:center; font-size:.72rem; font-weight:700; background:#f1f5f9; color:#94a3b8; }
    .stage-step.done    { background:#dcfce7; color:#15803d; }
    .stage-step.current { background:linear-gradient(135deg, #fef3c7, #fde68a); color:#92400e; box-shadow:0 4px 12px rgba(252,211,77,.3); }
    .stage-step.lost    { background:#fee2e2; color:#b91c1c; }

    .bg-success-soft   { background:#dcfce7 !important; color:#15803d !important; }
    .bg-warning-soft   { background:#fef3c7 !important; color:#b45309 !important; }
    .bg-info-soft      { background:#dbeafe !important; color:#1d4ed8 !important; }
    .bg-primary-soft   { background:#e0e7ff !important; color:#4338ca !important; }
    .bg-secondary-soft { background:#f1f5f9 !important; color:#475569 !important; }
    .bg-danger-soft    { background:#fee2e2 !important; color:#b91c1c !important; }
</style>
@endpush

@section('content')

{{-- Hero --}}
<div class="opp-hero">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
        <div>
            <h3>{{ $opp->title }}</h3>
            <div class="meta">
                <span class="badge-mega"><i class="bi bi-hash"></i> {{ $opp->code }}</span>
                <span class="badge-mega"><i class="bi bi-tag"></i> {{ $opp->booking_type_label }}</span>
                @if($opp->destination)
                    <span class="badge-mega"><i class="bi bi-geo-alt"></i> {{ $opp->destination }}</span>
                @endif
                <span class="badge-mega"><i class="bi bi-people"></i> {{ $opp->pax_count }} فرد</span>
                <span class="badge-mega" style="background:rgba(255,255,255,.3) !important;">{{ $opp->stage_label }}</span>
                @if($opp->assignee)
                    <span class="badge-mega"><i class="bi bi-person"></i> {{ $opp->assignee->name }}</span>
                @endif
            </div>
        </div>
        <div class="value-tag">
            <div>{{ number_format($opp->estimated_value, 0) }} ج.م</div>
            <small>مرجح: {{ number_format($opp->weighted_value, 0) }} ({{ $opp->probability }}%)</small>
        </div>
    </div>
</div>

{{-- Stage progress --}}
@php
    $stageOrder = ['prospecting', 'qualification', 'proposal', 'negotiation', 'closed_won'];
    $currentIdx = array_search($opp->stage, $stageOrder);
    $isLost = $opp->stage === 'closed_lost';
@endphp
<div class="info-card">
    <div class="body">
        <h6 class="mb-3"><i class="bi bi-diagram-3"></i> مراحل القمع</h6>
        <div class="stage-bar">
            @foreach(['prospecting'=>'استكشاف','qualification'=>'تأهيل','proposal'=>'عرض','negotiation'=>'تفاوض','closed_won'=>'فوز'] as $key => $label)
                @php
                    $idx = array_search($key, $stageOrder);
                    if ($isLost) { $cls = 'lost'; }
                    elseif ($idx < $currentIdx) { $cls = 'done'; }
                    elseif ($idx === $currentIdx) { $cls = 'current'; }
                    else { $cls = ''; }
                @endphp
                <div class="stage-step {{ $cls }}">{{ $label }}</div>
            @endforeach
        </div>

        {{-- Action buttons --}}
        <div class="d-flex gap-2 mt-3 flex-wrap">
            @if($opp->isConverted())
                @if($convertedBooking)
                    <a href="{{ route('admin.' . $opp->converted_booking_type . '.bookings.show', $convertedBooking) }}"
                       class="btn btn-success">
                        <i class="bi bi-check-circle-fill"></i> عرض الحجز المُحوّل ({{ $convertedBooking->booking_number }})
                    </a>
                @endif
            @elseif(!$opp->isClosed())
                @can('opportunities.convert')
                <a href="{{ route('admin.crm.opportunities.convert_form', $opp) }}" class="btn btn-warning">
                    <i class="bi bi-arrow-right-circle"></i> تحويل لحجز
                </a>
                @endcan
                @can('opportunities.update')
                <a href="{{ route('admin.crm.opportunities.edit', $opp) }}" class="btn btn-outline-primary">
                    <i class="bi bi-pencil"></i> تعديل
                </a>
                @endcan
            @endif

            @if($opp->lead)
                <a href="{{ route('admin.crm.leads.show', $opp->lead) }}" class="btn btn-outline-info">
                    <i class="bi bi-person-plus"></i> عرض الـ Lead
                </a>
            @endif

            @if($opp->customer)
                <a href="{{ route('admin.customers.show', $opp->customer) }}" class="btn btn-outline-secondary">
                    <i class="bi bi-person-check"></i> عرض العميل
                </a>
            @endif
        </div>

        @if($opp->stage === 'closed_lost' && $opp->lost_reason)
            <div class="alert alert-danger mt-3 mb-0">
                <strong><i class="bi bi-x-octagon"></i> سبب الخسارة:</strong> {{ $opp->lost_reason }}
            </div>
        @endif
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="info-card">
            <div class="head">
                <h6><i class="bi bi-info-circle"></i> تفاصيل الصفقة</h6>
            </div>
            <div class="body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="kv"><span class="k">الكود</span><span class="v"><code>{{ $opp->code }}</code></span></div>
                        <div class="kv"><span class="k">نوع الحجز</span><span class="v">{{ $opp->booking_type_label }}</span></div>
                        @if($opp->sub_type)
                        <div class="kv"><span class="k">النوع الفرعي</span><span class="v">{{ $opp->sub_type }}</span></div>
                        @endif
                        <div class="kv"><span class="k">الوجهة</span><span class="v">{{ $opp->destination ?? '—' }}</span></div>
                        <div class="kv"><span class="k">العدد</span><span class="v">{{ $opp->pax_count }} فرد</span></div>
                    </div>
                    <div class="col-md-6">
                        <div class="kv"><span class="k">القيمة المتوقعة</span><span class="v text-primary">{{ number_format($opp->estimated_value, 0) }} ج.م</span></div>
                        <div class="kv"><span class="k">القيمة المرجحة</span><span class="v text-success">{{ number_format($opp->weighted_value, 0) }} ج.م</span></div>
                        <div class="kv"><span class="k">احتمال الفوز</span><span class="v">{{ $opp->probability }}%</span></div>
                        <div class="kv"><span class="k">تاريخ السفر المتوقع</span><span class="v">{{ $opp->expected_trip_date?->format('Y-m-d') ?? '—' }}</span></div>
                        <div class="kv"><span class="k">تاريخ الإغلاق المتوقع</span><span class="v">{{ $opp->expected_close_date?->format('Y-m-d') ?? '—' }}</span></div>
                        @if($opp->actual_close_date)
                        <div class="kv"><span class="k">تاريخ الإغلاق الفعلي</span><span class="v">{{ $opp->actual_close_date->format('Y-m-d') }}</span></div>
                        @endif
                    </div>

                    @if($opp->notes)
                    <div class="col-12">
                        <div class="alert alert-light mb-0 border">
                            <strong><i class="bi bi-sticky"></i> ملاحظات:</strong>
                            <div class="mt-1">{{ $opp->notes }}</div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        @if($opp->lead || $opp->customer)
        <div class="info-card">
            <div class="head"><h6><i class="bi bi-person"></i> المصدر</h6></div>
            <div class="body">
                @if($opp->customer)
                    <div class="kv"><span class="k">العميل</span><span class="v">{{ $opp->customer->full_name }}</span></div>
                    <div class="kv"><span class="k">كود العميل</span><span class="v"><code>{{ $opp->customer->code }}</code></span></div>
                    <div class="kv"><span class="k">الهاتف</span><span class="v" dir="ltr">{{ $opp->customer->phone }}</span></div>
                @elseif($opp->lead)
                    <div class="kv"><span class="k">Lead</span><span class="v">{{ $opp->lead->full_name }}</span></div>
                    <div class="kv"><span class="k">كود Lead</span><span class="v"><code>{{ $opp->lead->code }}</code></span></div>
                    <div class="kv"><span class="k">الهاتف</span><span class="v" dir="ltr">{{ $opp->lead->phone }}</span></div>
                @endif
            </div>
        </div>
        @endif

        <div class="info-card">
            <div class="head"><h6><i class="bi bi-clock-history"></i> معلومات النظام</h6></div>
            <div class="body">
                <div class="kv"><span class="k">المنشئ</span><span class="v">{{ $opp->creator?->name ?? '—' }}</span></div>
                <div class="kv"><span class="k">المسؤول</span><span class="v">{{ $opp->assignee?->name ?? '—' }}</span></div>
                <div class="kv"><span class="k">تاريخ الإنشاء</span><span class="v">{{ $opp->created_at?->format('Y-m-d H:i') }}</span></div>
                <div class="kv"><span class="k">آخر تعديل</span><span class="v">{{ $opp->updated_at?->diffForHumans() }}</span></div>
            </div>
        </div>

        @can('opportunities.delete')
        @if(!$opp->isConverted())
        <button type="button" class="btn btn-outline-danger w-100 btn-delete-opp"
                data-url="{{ route('admin.crm.opportunities.destroy', $opp) }}">
            <i class="bi bi-trash"></i> حذف الصفقة
        </button>
        @endif
        @endcan
    </div>
</div>

@endsection

@push('scripts')
<script>
$(function () {
    $('.btn-delete-opp').on('click', function () {
        if (confirm('هل أنت متأكد من حذف الصفقة؟')) {
            CoreX.ajaxDelete($(this).data('url'), null, () => {
                window.location.href = '{{ route('admin.crm.opportunities.index') }}';
            });
        }
    });
});
</script>
@endpush
