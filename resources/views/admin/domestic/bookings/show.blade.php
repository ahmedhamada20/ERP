@extends('layouts.master')

@section('title', 'حجز ' . $booking->booking_number)
@section('page_title', 'حجز سياحة داخلية - ' . $booking->booking_number)
@section('page_subtitle', $booking->customer?->full_name . ' — ' . $booking->destination_city)

@push('styles')
<style>
    .info-card { background:#fff; border-radius:14px; box-shadow:0 1px 3px rgba(15,23,42,.04); border:1px solid var(--brand-border); overflow:hidden; margin-bottom:1rem; }
    .info-card .head { padding:.85rem 1.1rem; border-bottom:1px solid var(--brand-border); background:linear-gradient(180deg,#fafbff,#f1f5f9); display:flex; align-items:center; justify-content:space-between; }
    .info-card .head h6 { margin:0; color:var(--brand-navy); font-weight:800; display:inline-flex; align-items:center; gap:.5rem; }
    .info-card .body { padding:1.1rem; }
    .kv { display:flex; justify-content:space-between; padding:.45rem 0; border-bottom:1px dashed #e2e8f0; font-size:.85rem; }
    .kv:last-child { border-bottom:none; }
    .kv .k { color:#64748b; font-weight:600; }
    .kv .v { color:#0f172a; font-weight:700; text-align:end; }

    .summary-card { background: linear-gradient(135deg, #1e3a8a 0%, #312e81 100%); color:#fff; border-radius:18px; padding:1.5rem; margin-bottom:1rem; box-shadow: 0 10px 25px rgba(30, 58, 138, 0.2); }
    .summary-card h3 { margin:0; font-weight:800; font-size:1.5rem; }
    .summary-card .lbl { font-size:.78rem; opacity:.85; margin-bottom:.25rem; }
    .summary-card .val { font-size:1.6rem; font-weight:900; }
    .summary-card .meta { display:flex; gap:1.5rem; margin-top:1rem; flex-wrap:wrap; }
    .summary-card .badge-mega { font-size:.78rem; padding:.4rem .85rem; border-radius:8px; font-weight:700; background:rgba(255,255,255,.18); color:#fff; }

    .money-row { display:grid; grid-template-columns: repeat(4, 1fr); gap:.85rem; }
    .money-tile { background:#fff; border:1px solid #f1f5f9; border-radius:12px; padding:.95rem; text-align:center; }
    .money-tile .lbl { font-size:.72rem; color:#64748b; font-weight:600; margin-bottom:.35rem; }
    .money-tile .val { font-size:1.35rem; font-weight:900; }
    .money-tile.t-revenue .val { color: var(--brand-navy); }
    .money-tile.t-paid    .val { color: #15803d; }
    .money-tile.t-out     .val { color: #b45309; }
    .money-tile.t-profit  .val { color: #4338ca; }
    .money-tile .val small { font-size:.7rem; color:#94a3b8; font-weight:600; }

    .workflow-bar { display:flex; gap:.4rem; margin:1rem 0; flex-wrap:wrap; }
    .stage-pill { display:flex; align-items:center; gap:.4rem; padding:.5rem .85rem; border-radius:8px; font-size:.78rem; font-weight:700; background:#f1f5f9; color:#64748b; border:1px solid #e2e8f0; }
    .stage-pill.done { background:#dcfce7; color:#15803d; border-color:#86efac; }
    .stage-pill.current { background: linear-gradient(135deg, #fef3c7, #fde68a); color:#92400e; border-color:#fcd34d; box-shadow: 0 4px 12px rgba(252,211,77,.3); }

    .bg-success-soft   { background:#dcfce7 !important; color:#15803d !important; }
    .bg-warning-soft   { background:#fef3c7 !important; color:#b45309 !important; }
    .bg-info-soft      { background:#dbeafe !important; color:#1d4ed8 !important; }
    .bg-primary-soft   { background:#e0e7ff !important; color:#4338ca !important; }
    .bg-secondary-soft { background:#f1f5f9 !important; color:#475569 !important; }
    .bg-danger-soft    { background:#fee2e2 !important; color:#b91c1c !important; }

    @media (max-width: 768px) {
        .money-row { grid-template-columns: 1fr 1fr; }
    }
</style>
@endpush

@section('content')

{{-- Summary banner --}}
<div class="summary-card">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
        <div>
            <h3>{{ $booking->booking_number }}</h3>
            <div class="meta">
                <span class="badge-mega"><i class="bi bi-tag"></i> {{ $booking->type_label }}</span>
                <span class="badge-mega"><i class="bi bi-geo-alt"></i> {{ $booking->destination_city }}</span>
                <span class="badge-mega"><i class="bi bi-calendar-event"></i> {{ $booking->trip_date?->format('Y-m-d') }}</span>
                <span class="badge-mega"><i class="bi bi-people"></i> {{ $booking->adults_count + $booking->children_count }} فرد</span>
                <span class="badge-mega"><i class="bi bi-clock"></i> {{ $booking->duration_days }} يوم</span>
            </div>
        </div>
        <div class="text-end">
            <div class="lbl">سعر البيع</div>
            <div class="val">{{ number_format($booking->selling_price, 0) }} <small>ج.م</small></div>
        </div>
    </div>
</div>

{{-- Workflow stages --}}
@php
    $stages = [
        'sales'           => ['المبيعات', 'bi-cart'],
        'manager_review'  => ['مراجعة المدير', 'bi-person-check'],
        'operations'      => ['العمليات', 'bi-gear'],
        'finance'         => ['المالية', 'bi-cash-coin'],
        'closed'          => ['مُقفل', 'bi-lock'],
    ];
    $stageOrder = array_keys($stages);
    $currentIdx = array_search($booking->workflow_stage, $stageOrder);
@endphp
<div class="info-card">
    <div class="body">
        <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
            <h6 class="mb-0"><i class="bi bi-diagram-3"></i> مراحل سير العمل</h6>
            <span class="badge bg-{{ $booking->status_badge }}-soft">{{ $booking->status_label }}</span>
        </div>
        <div class="workflow-bar">
            @foreach($stages as $key => $info)
                @php
                    $idx = array_search($key, $stageOrder);
                    $class = $idx < $currentIdx ? 'done' : ($idx === $currentIdx ? 'current' : '');
                @endphp
                <div class="stage-pill {{ $class }}">
                    <i class="{{ $info[1] }}"></i> {{ $info[0] }}
                </div>
            @endforeach
        </div>

        {{-- Action buttons --}}
        @if($booking->status !== 'cancelled' && $booking->workflow_stage !== 'closed')
            <div class="d-flex gap-2 mt-3 flex-wrap">
                @if($booking->status === 'pending')
                    @can('domestic_bookings.approve')
                    <form action="{{ route('admin.domestic.bookings.transition', $booking) }}" method="POST" class="d-inline">
                        @csrf <input type="hidden" name="action" value="approve">
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-circle"></i> اعتماد الحجز
                        </button>
                    </form>
                    @endcan
                @endif

                @if($booking->status === 'confirmed')
                    @can('domestic_bookings.update')
                    <form action="{{ route('admin.domestic.bookings.transition', $booking) }}" method="POST" class="d-inline">
                        @csrf <input type="hidden" name="action" value="start_operations">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-play-circle"></i> بدء التنفيذ
                        </button>
                    </form>
                    @endcan
                @endif

                @if(in_array($booking->workflow_stage, ['operations']) && $booking->status === 'in_progress')
                    @can('domestic_bookings.update')
                    <form action="{{ route('admin.domestic.bookings.transition', $booking) }}" method="POST" class="d-inline">
                        @csrf <input type="hidden" name="action" value="send_to_finance">
                        <button type="submit" class="btn btn-info">
                            <i class="bi bi-arrow-right-circle"></i> تحويل للمالية
                        </button>
                    </form>
                    @endcan
                @endif

                @if(in_array($booking->workflow_stage, ['finance', 'operations']))
                    @can('domestic_bookings.close')
                    <form action="{{ route('admin.domestic.bookings.transition', $booking) }}" method="POST" class="d-inline"
                          onsubmit="return confirm('سيتم إقفال الحجز نهائياً وقفل بنود التكلفة. هل أنت متأكد؟');">
                        @csrf <input type="hidden" name="action" value="close">
                        <button type="submit" class="btn btn-dark">
                            <i class="bi bi-lock"></i> إقفال الحجز
                        </button>
                    </form>
                    @endcan
                @endif

                @can('domestic_bookings.cancel')
                <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#cancelModal">
                    <i class="bi bi-x-circle"></i> إلغاء الحجز
                </button>
                @endcan
            </div>
        @endif

        @if($booking->status === 'cancelled')
            <div class="alert alert-danger mt-3 mb-0">
                <strong><i class="bi bi-x-octagon"></i> هذا الحجز ملغي</strong>
                @if($booking->cancellation_reason) — {{ $booking->cancellation_reason }} @endif
                <div class="small">بتاريخ {{ $booking->cancelled_at?->format('Y-m-d H:i') }} بواسطة {{ $booking->canceller?->name }}</div>
            </div>
        @endif
    </div>
</div>

{{-- Money summary --}}
<div class="money-row mb-3">
    <div class="money-tile t-revenue">
        <div class="lbl">سعر البيع</div>
        <div class="val">{{ number_format($booking->selling_price, 0) }} <small>ج.م</small></div>
    </div>
    <div class="money-tile t-paid">
        <div class="lbl">المسدد</div>
        <div class="val">{{ number_format($totals['paid'], 0) }} <small>ج.م</small></div>
    </div>
    <div class="money-tile t-out">
        <div class="lbl">المتبقي</div>
        <div class="val">{{ number_format($totals['outstanding'], 0) }} <small>ج.م</small></div>
    </div>
    <div class="money-tile t-profit">
        <div class="lbl">صافي الربح <span class="text-muted small">({{ $totals['profit_pct'] }}%)</span></div>
        <div class="val">{{ number_format($booking->net_profit, 0) }} <small>ج.م</small></div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        {{-- Booking details --}}
        <div class="info-card">
            <div class="head">
                <h6><i class="bi bi-info-circle"></i> تفاصيل الحجز</h6>
                @can('domestic_bookings.update')
                @if($booking->status !== 'cancelled')
                <a href="{{ route('admin.domestic.bookings.edit', $booking) }}" class="btn btn-sm btn-light-info">
                    <i class="bi bi-pencil"></i> تعديل
                </a>
                @endif
                @endcan
            </div>
            <div class="body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="kv"><span class="k">رقم الحجز</span><span class="v"><code>{{ $booking->booking_number }}</code></span></div>
                        <div class="kv"><span class="k">رقم العقد</span><span class="v">{{ $booking->contract_number ?? '—' }}</span></div>
                        <div class="kv"><span class="k">نوع الرحلة</span><span class="v">{{ $booking->type_label }}</span></div>
                        @if($booking->program)
                        <div class="kv"><span class="k">البرنامج</span><span class="v">{{ $booking->program->name }}</span></div>
                        @endif
                        <div class="kv"><span class="k">الدولة / المدينة</span><span class="v">مصر / {{ $booking->destination_city }}</span></div>
                        @if($booking->destination_area)
                        <div class="kv"><span class="k">المنطقة</span><span class="v">{{ $booking->destination_area }}</span></div>
                        @endif
                        @if($booking->hotel)
                        <div class="kv"><span class="k">الفندق</span><span class="v"><i class="bi bi-building"></i> {{ $booking->hotel->name }}</span></div>
                        @endif
                    </div>
                    <div class="col-md-6">
                        <div class="kv"><span class="k">تاريخ الحجز</span><span class="v">{{ $booking->booking_date?->format('Y-m-d') }}</span></div>
                        <div class="kv"><span class="k">تاريخ السفر</span><span class="v">{{ $booking->trip_date?->format('Y-m-d') }}</span></div>
                        <div class="kv"><span class="k">تاريخ المغادرة</span><span class="v">{{ $booking->return_date?->format('Y-m-d') ?? '—' }}</span></div>
                        <div class="kv"><span class="k">المدة</span><span class="v">{{ $booking->duration_days }} يوم / {{ $booking->duration_nights }} ليلة</span></div>
                        <div class="kv"><span class="k">عدد الضيوف</span><span class="v">{{ $booking->adults_count }} بالغ
                            @if($booking->children_count) + {{ $booking->children_count }} طفل @endif
                            @if($booking->infants_count) + {{ $booking->infants_count }} رضيع @endif
                        </span></div>
                    </div>

                    <div class="col-md-6">
                        <div class="kv"><span class="k">نوع الغرفة</span><span class="v">{{ $booking->accommodation_label }} × {{ $booking->rooms_count }}</span></div>
                        <div class="kv"><span class="k">مستوى السكن</span><span class="v">{{ $booking->grade_label }}</span></div>
                    </div>
                    <div class="col-md-6">
                        <div class="kv"><span class="k">وسيلة النقل</span><span class="v">{{ $booking->transport_label }}</span></div>
                        <div class="kv"><span class="k">نظام الإقامة</span><span class="v">{{ $booking->meal_plan_label }}</span></div>
                    </div>

                    @if($booking->notes)
                    <div class="col-12">
                        <div class="alert alert-light mb-0 border">
                            <strong><i class="bi bi-sticky"></i> ملاحظات:</strong>
                            <div class="mt-1">{{ $booking->notes }}</div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Costs section --}}
        @include('admin.domestic.bookings._costs_section', ['booking' => $booking])

        {{-- Payments section --}}
        @include('admin.domestic.bookings._payments_section', ['booking' => $booking])
    </div>

    <div class="col-lg-4">
        {{-- Customer --}}
        <div class="info-card">
            <div class="head"><h6><i class="bi bi-person"></i> العميل</h6></div>
            <div class="body">
                @if($booking->customer)
                    <div class="kv"><span class="k">الاسم</span><span class="v">{{ $booking->customer->full_name }}</span></div>
                    <div class="kv"><span class="k">الهاتف</span><span class="v" dir="ltr">{{ $booking->customer->phone }}</span></div>
                    <div class="kv"><span class="k">الكود</span><span class="v"><code>{{ $booking->customer->code }}</code></span></div>
                @else
                    <span class="text-muted">— لم يتم تحديد عميل —</span>
                @endif
            </div>
        </div>

        {{-- Responsibility --}}
        <div class="info-card">
            <div class="head"><h6><i class="bi bi-person-workspace"></i> المسؤولية</h6></div>
            <div class="body">
                <div class="kv"><span class="k">الموظف</span><span class="v">{{ $booking->employee?->name ?? '—' }}</span></div>
                <div class="kv"><span class="k">المدير</span><span class="v">{{ $booking->manager?->name ?? '—' }}</span></div>
                <div class="kv"><span class="k">أنشأ</span><span class="v">{{ $booking->creator?->name ?? '—' }}</span></div>
                <div class="kv"><span class="k">تاريخ الإنشاء</span><span class="v">{{ $booking->created_at?->format('Y-m-d H:i') }}</span></div>
            </div>
        </div>

        {{-- Quick actions --}}
        <div class="d-grid gap-2">
            @can('domestic_bookings.create')
            <form action="{{ route('admin.domestic.bookings.duplicate', $booking) }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-outline-primary w-100">
                    <i class="bi bi-files"></i> نسخ هذا الحجز
                </button>
            </form>
            @endcan
            <a href="{{ route('admin.domestic.bookings.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-right"></i> العودة للقائمة
            </a>
        </div>
    </div>
</div>

{{-- Cost + Payment modals --}}
@include('admin.domestic.bookings._cost_modal',    ['booking' => $booking])
@include('admin.domestic.bookings._payment_modal', ['booking' => $booking])

{{-- Cancel modal --}}
@can('domestic_bookings.cancel')
<div class="modal fade" id="cancelModal" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content" action="{{ route('admin.domestic.bookings.transition', $booking) }}" method="POST">
            @csrf
            <input type="hidden" name="action" value="cancel">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-x-octagon text-danger"></i> إلغاء الحجز {{ $booking->booking_number }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle"></i> سيتم تغيير الحالة إلى "ملغي" ولن يمكن تعديله بعد ذلك.
                </div>
                <label class="form-label">سبب الإلغاء <span class="text-danger">*</span></label>
                <textarea name="reason" rows="3" class="form-control" required placeholder="مثال: طلب العميل / تأجيل / تعارض في المواعيد..."></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">تراجع</button>
                <button type="submit" class="btn btn-danger"><i class="bi bi-x-circle"></i> تأكيد الإلغاء</button>
            </div>
        </form>
    </div>
</div>
@endcan

@endsection
