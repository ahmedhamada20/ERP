@extends('layouts.master')

@section('title', $booking->booking_number)
@section('page_title', $booking->booking_number)
@section('page_subtitle', $booking->customer?->full_name . ' • ' . $booking->type_label . ' • ' . $booking->trip_date?->format('Y-m-d'))

@push('styles')
<style>
    .summary-card { background:linear-gradient(135deg,#0f172a 0%,#1e293b 100%); color:#fff; border-radius:16px; padding:1.5rem; box-shadow:0 6px 24px rgba(15,23,42,.16); margin-bottom:1rem; }
    .summary-card .booking-no { font-family:'Cairo',monospace; font-size:1.5rem; font-weight:800; color:#d4a437; letter-spacing:1px; }
    .summary-card .meta { font-size:.85rem; color:rgba(255,255,255,.85); }
    .summary-card .badges-row { display:flex; gap:.4rem; flex-wrap:wrap; margin-top:.75rem; }
    .summary-card .badges-row .badge { font-size:.75rem; padding:.4rem .7rem; border-radius:7px; font-weight:700; }

    .money-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:.85rem; margin-top:1rem; }
    .money-tile { background:rgba(255,255,255,.08); border-radius:10px; padding:.75rem .9rem; backdrop-filter:blur(8px); }
    .money-tile .lbl { font-size:.72rem; color:rgba(255,255,255,.75); margin-bottom:.2rem; }
    .money-tile .val { font-size:1.1rem; font-weight:800; }
    .money-tile .val.profit { color:#86efac; }
    .money-tile .val.cost   { color:#fca5a5; }
    .money-tile .val.gold   { color:#fcd34d; }

    .tabs-wrap { background:#fff; border-radius:14px; box-shadow:0 1px 3px rgba(15,23,42,.04); border:1px solid var(--brand-border); overflow:hidden; }
    .nav-tabs.show-tabs { background:#f8fafc; border:none; padding:.5rem .5rem 0; }
    .nav-tabs.show-tabs .nav-link {
        border:none; border-radius:8px 8px 0 0; padding:.75rem 1.1rem;
        color:#475569; font-weight:600; font-size:.88rem; margin-left:2px;
        position:relative;
    }
    .nav-tabs.show-tabs .nav-link.active {
        background:#fff; color:var(--brand-navy);
        border-bottom:3px solid var(--brand-gold);
    }
    .nav-tabs.show-tabs .nav-link .count {
        background:#e0e7ff; color:#4338ca; font-size:.68rem;
        padding:1px 7px; border-radius:9px; margin-right:.35rem;
        font-weight:700;
    }
    .nav-tabs.show-tabs .nav-link.active .count { background:#fef3c7; color:#92400e; }

    .tab-pane-body { padding:1.5rem; }

    .data-table-mini { width:100%; }
    .data-table-mini th { background:#f9fafb; color:#475569; font-weight:700; font-size:.78rem; padding:.65rem .55rem; border-bottom:1px solid var(--brand-border); }
    .data-table-mini td { padding:.65rem .55rem; border-bottom:1px solid #f3f4f6; font-size:.85rem; vertical-align:middle; }
    .data-table-mini tbody tr:hover { background:#fafbff; }

    .badge-soft-green { background:#dcfce7; color:#15803d; font-size:.7rem; padding:.25rem .55rem; border-radius:6px; font-weight:700; }
    .badge-soft-blue { background:#dbeafe; color:#1d4ed8; font-size:.7rem; padding:.25rem .55rem; border-radius:6px; font-weight:700; }
    .badge-soft-yellow { background:#fef3c7; color:#92400e; font-size:.7rem; padding:.25rem .55rem; border-radius:6px; font-weight:700; }
    .badge-soft-red { background:#fee2e2; color:#b91c1c; font-size:.7rem; padding:.25rem .55rem; border-radius:6px; font-weight:700; }
    .badge-soft-gray { background:#f1f5f9; color:#64748b; font-size:.7rem; padding:.25rem .55rem; border-radius:6px; font-weight:700; }

    /* ── Professional Workflow Tracker ─────────────── */
    .workflow-container {
        background: #fff; border-radius: 16px;
        padding: 1.25rem 1.5rem; margin-bottom: 1.25rem;
        box-shadow: 0 2px 8px rgba(15,23,42,.05);
        border: 1px solid #f1f5f9;
    }
    .workflow-pro {
        display: flex; align-items: stretch; gap: 0;
    }
    .wf-step {
        flex: 1; display: flex; flex-direction: column; align-items: center;
        text-align: center; position: relative;
        padding: .5rem .25rem;
    }
    .wf-circle {
        width: 48px; height: 48px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.25rem; margin-bottom: .55rem;
        background: #f1f5f9; color: #94a3b8;
        border: 3px solid #f1f5f9;
        transition: all .3s; position: relative; z-index: 2;
    }
    .wf-step.done .wf-circle {
        background: #15803d; border-color: #15803d; color: #fff;
        box-shadow: 0 4px 12px rgba(21,128,61,.25);
    }
    .wf-step.current .wf-circle {
        background: linear-gradient(135deg, #1e3a8a, #1d4ed8); color: #fff;
        border-color: #d4a437; box-shadow: 0 0 0 6px rgba(212,164,55,.15);
        animation: wfPulse 2s ease-in-out infinite;
        transform: scale(1.05);
    }
    @keyframes wfPulse {
        0%, 100% { box-shadow: 0 0 0 6px rgba(212,164,55,.15); }
        50%      { box-shadow: 0 0 0 10px rgba(212,164,55,.05); }
    }
    .wf-step.future .wf-circle { opacity: .5; }

    .wf-label { font-weight: 800; font-size: .82rem; color: var(--brand-navy); margin-bottom: 2px; }
    .wf-step.future .wf-label { color: #94a3b8; }
    .wf-step.done .wf-label { color: #15803d; }
    .wf-step.current .wf-label { color: #1d4ed8; }
    .wf-desc { font-size: .68rem; color: #94a3b8; line-height: 1.3; }

    .wf-connector {
        flex: 0 0 30px;
        height: 3px; background: #f1f5f9;
        margin-top: 22px; border-radius: 2px;
        position: relative;
    }
    .wf-connector.done {
        background: #15803d;
    }
    .wf-connector.done::after {
        content: ''; position: absolute;
        right: 0; top: -2px;
        width: 7px; height: 7px; border-radius: 50%;
        background: #15803d;
    }

    .wf-quick-action {
        margin-top: 1.25rem; padding: 1rem 1.25rem;
        background: linear-gradient(135deg, #fffbeb, #fef3c7);
        border: 1px solid #fde68a; border-radius: 12px;
        display: flex; justify-content: space-between; align-items: center;
        gap: 1rem; flex-wrap: wrap;
    }
    .wf-action-info { display: flex; align-items: center; gap: .75rem; }
    .wf-action-info > i {
        width: 38px; height: 38px; border-radius: 10px;
        background: #fbbf24; color: #fff;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.25rem; flex-shrink: 0;
    }

    .workflow-cancelled {
        background: linear-gradient(135deg, #fee2e2, #fecaca);
        border: 1px solid #fca5a5; border-radius: 12px;
        padding: 1rem 1.25rem; display: flex; align-items: center; gap: 1rem;
        color: #991b1b;
    }
    .workflow-cancelled > i {
        font-size: 2rem; color: #dc2626; flex-shrink: 0;
    }

    @media (max-width: 768px) {
        .workflow-pro { flex-direction: column; gap: .5rem; }
        .wf-step { flex-direction: row; gap: .75rem; text-align: right; padding: .35rem; }
        .wf-circle { margin-bottom: 0; width: 38px; height: 38px; font-size: 1rem; }
        .wf-content { flex: 1; }
        .wf-connector { display: none; }
    }

    .alert-strip { background:#fef3c7; border-right:4px solid #f59e0b; padding:.75rem 1rem; border-radius:8px; margin-bottom:1rem; }

    .empty-state { text-align:center; padding:2.5rem 1rem; color:#94a3b8; }
    .empty-state i { font-size:2.5rem; opacity:.5; display:block; margin-bottom:.5rem; }

    @media (max-width:768px) {
        .summary-card { padding:1.1rem; }
        .summary-card .booking-no { font-size:1.2rem; }
        .money-tile .val { font-size:.95rem; }
        .nav-tabs.show-tabs .nav-link { padding:.6rem .75rem; font-size:.8rem; }
        .tab-pane-body { padding:1rem; }
        .workflow-step { padding:.55rem .35rem; font-size:.65rem; }
    }
</style>
@endpush

@section('content')

{{-- ════════════════════════════════════════════════════════════
     Summary Card
     ════════════════════════════════════════════════════════════ --}}
<div class="summary-card">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
        <div>
            <div class="booking-no"><i class="bi bi-{{ $booking->type === 'hajj' ? 'mosque' : 'moon-stars' }}"></i> {{ $booking->booking_number }}</div>
            <div class="meta mt-1">
                <i class="bi bi-person"></i> {{ $booking->customer?->full_name ?? '—' }}
                @if($booking->customer?->phone)
                    • <span dir="ltr">{{ $booking->customer->phone }}</span>
                @endif
            </div>
            <div class="meta">
                <i class="bi bi-calendar-event"></i> سفر: {{ $booking->trip_date?->format('Y-m-d') }}
                • <i class="bi bi-clock"></i> {{ $booking->duration_days }} يوم
                • <i class="bi bi-people"></i> {{ $booking->adults_count + $booking->children_count }} فرد
            </div>
            <div class="badges-row">
                <span class="badge bg-{{ $booking->status_badge }}-soft">{{ $booking->status_label }}</span>
                <span class="badge bg-light text-dark">{{ $booking->workflow_label }}</span>
                <span class="badge bg-light text-dark">{{ $booking->type_label }}</span>
                <span class="badge bg-light text-dark">{{ $booking->visa_type_label }}</span>
                <span class="badge bg-light text-dark">{{ $booking->accommodation_label }}</span>
                @if($booking->safa_barcode)
                    <span class="badge bg-success-soft"><i class="bi bi-qr-code"></i> {{ $booking->safa_barcode }}</span>
                @endif
            </div>
        </div>

        <div class="d-flex gap-2 flex-wrap">
            {{-- Print menu --}}
            <div class="dropdown">
                <button class="btn btn-warning btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                    <i class="bi bi-printer"></i> طباعة
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="{{ route('admin.religious.bookings.print.contract', $booking) }}" target="_blank">
                        <i class="bi bi-file-earmark-text"></i> عقد الحجز (PDF)
                    </a></li>
                    <li><a class="dropdown-item" href="{{ route('admin.religious.bookings.print.manifest', $booking) }}" target="_blank">
                        <i class="bi bi-people"></i> قائمة المعتمرين (PDF)
                    </a></li>
                </ul>
            </div>

            @can('religious_bookings.create')
            <form method="POST" action="{{ route('admin.religious.bookings.duplicate', $booking) }}"
                  onsubmit="return confirm('سيتم إنشاء نسخة من هذا الحجز كحجز جديد. هل تريد المتابعة؟');">
                @csrf
                <button class="btn btn-info btn-sm text-white">
                    <i class="bi bi-files"></i> نسخ
                </button>
            </form>
            @endcan

            @can('religious_bookings.update')
            @if($booking->status !== 'cancelled')
            <a href="{{ route('admin.religious.bookings.edit', $booking) }}" class="btn btn-light btn-sm">
                <i class="bi bi-pencil"></i> تعديل
            </a>
            @endif
            @endcan
            <a href="{{ route('admin.religious.bookings.index') }}" class="btn btn-outline-light btn-sm">
                <i class="bi bi-arrow-right"></i> العودة
            </a>
        </div>
    </div>

    <div class="money-grid">
        <div class="money-tile">
            <div class="lbl">سعر البيع</div>
            <div class="val gold">{{ number_format($booking->selling_price, 2) }}</div>
        </div>
        <div class="money-tile">
            <div class="lbl">إجمالي التكلفة</div>
            <div class="val cost">{{ number_format($booking->total_cost, 2) }}</div>
        </div>
        <div class="money-tile">
            <div class="lbl">صافي الربح</div>
            <div class="val profit">{{ number_format($booking->net_profit, 2) }}</div>
        </div>
        <div class="money-tile">
            <div class="lbl">هامش الربح</div>
            <div class="val profit">{{ $totals['profit_pct'] }}%</div>
        </div>
        <div class="money-tile">
            <div class="lbl">إجمالي المدفوع</div>
            <div class="val">{{ number_format($totals['paid'], 2) }}</div>
        </div>
        <div class="money-tile">
            <div class="lbl">المتبقي</div>
            <div class="val {{ $totals['outstanding'] > 0 ? 'cost' : 'profit' }}">{{ number_format($totals['outstanding'], 2) }}</div>
        </div>
    </div>
</div>

{{-- Professional Workflow Tracker --}}
@php
    $stages = ['sales','manager_review','operations','finance','closed'];
    $stageLabels = ['sales'=>'المبيعات','manager_review'=>'مراجعة المدير','operations'=>'العمليات','finance'=>'المالية','closed'=>'مُقفل'];
    $stageIcons  = ['sales'=>'cart-check','manager_review'=>'shield-check','operations'=>'gear-wide-connected','finance'=>'cash-coin','closed'=>'lock-fill'];
    $stageDescs  = ['sales'=>'الموظف ينشئ الحجز','manager_review'=>'المدير يعتمد التكاليف','operations'=>'تنفيذ التأشيرات والسكن','finance'=>'مراجعة مالية وتحصيل','closed'=>'حجز مكتمل ومُقفل'];
    $currentIdx  = $booking->status === 'cancelled' ? -1 : array_search($booking->workflow_stage, $stages);

    // Define what action transitions to next stage
    $nextActions = [
        'sales' => ['action' => 'approve', 'label' => 'اعتماد المدير', 'permission' => 'religious_bookings.approve', 'btn' => 'success', 'icon' => 'check-circle', 'condition' => $booking->status === 'pending'],
        'manager_review' => ['action' => 'approve', 'label' => 'اعتماد', 'permission' => 'religious_bookings.approve', 'btn' => 'success', 'icon' => 'check-circle', 'condition' => $booking->status === 'pending'],
        'operations' => ['action' => 'send_to_finance', 'label' => 'تحويل للمالية', 'permission' => 'religious_bookings.update', 'btn' => 'warning', 'icon' => 'arrow-left-circle', 'condition' => true],
        'finance' => ['action' => 'close', 'label' => 'إقفال نهائي', 'permission' => 'religious_bookings.close', 'btn' => 'dark', 'icon' => 'lock', 'condition' => true],
    ];
    $nextAction = $nextActions[$booking->workflow_stage] ?? null;
@endphp
<div class="workflow-container">
    @if($booking->status === 'cancelled')
        <div class="workflow-cancelled">
            <i class="bi bi-x-octagon-fill"></i>
            <div>
                <strong>هذا الحجز ملغي</strong>
                <div class="small">السبب: {{ $booking->cancellation_reason ?: '—' }} • {{ $booking->cancelled_at?->diffForHumans() }}</div>
            </div>
        </div>
    @else
    <div class="workflow-pro">
        @foreach($stages as $i => $stage)
            @php
                $isDone    = $i < $currentIdx;
                $isCurrent = $i === $currentIdx;
                $isFuture  = $i > $currentIdx;
            @endphp
            <div class="wf-step {{ $isDone ? 'done' : '' }} {{ $isCurrent ? 'current' : '' }} {{ $isFuture ? 'future' : '' }}">
                <div class="wf-circle">
                    @if($isDone)
                        <i class="bi bi-check2"></i>
                    @else
                        <i class="bi bi-{{ $stageIcons[$stage] }}"></i>
                    @endif
                </div>
                <div class="wf-content">
                    <div class="wf-label">{{ $stageLabels[$stage] }}</div>
                    <div class="wf-desc">{{ $stageDescs[$stage] }}</div>
                </div>
            </div>
            @if($i < count($stages) - 1)
                <div class="wf-connector {{ $i < $currentIdx ? 'done' : '' }}"></div>
            @endif
        @endforeach
    </div>

    {{-- Quick action bar --}}
    @if($nextAction && $nextAction['condition'])
        @can($nextAction['permission'])
        <div class="wf-quick-action">
            <div class="wf-action-info">
                <i class="bi bi-arrow-up-circle-fill"></i>
                <div>
                    <div class="small text-muted">الإجراء التالي المتاح</div>
                    <strong>{{ $nextAction['label'] }}</strong>
                </div>
            </div>
            <form method="POST" action="{{ route('admin.religious.bookings.transition', $booking) }}"
                  @if($nextAction['action'] === 'close') onsubmit="return confirm('سيتم إقفال الحجز نهائياً وقفل بنوده المالية. هل أنت متأكد؟');" @endif>
                @csrf
                <input type="hidden" name="action" value="{{ $nextAction['action'] }}">
                <button class="btn btn-{{ $nextAction['btn'] }} btn-sm">
                    <i class="bi bi-{{ $nextAction['icon'] }}"></i> {{ $nextAction['label'] }}
                </button>
            </form>
        </div>
        @endcan
    @endif
    @endif
</div>

{{-- Active alerts strip --}}
@if($booking->alerts->isNotEmpty())
    @foreach($booking->alerts as $alert)
    <div class="alert-strip">
        <strong><i class="bi bi-exclamation-triangle text-warning"></i> {{ $alert->title }}</strong>
        <span class="text-muted ms-2">{{ $alert->message }}</span>
    </div>
    @endforeach
@endif

{{-- ════════════════════════════════════════════════════════════
     Tabs
     ════════════════════════════════════════════════════════════ --}}
<div class="tabs-wrap">
    <ul class="nav nav-tabs show-tabs" id="bookingTabs" role="tablist">
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-pilgrims"><i class="bi bi-people"></i> المعتمرون <span class="count">{{ $totals['pilgrims_n'] }}</span></button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-costs"><i class="bi bi-receipt"></i> التكاليف <span class="count">{{ $totals['costs_count'] }}</span></button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-payments"><i class="bi bi-cash-stack"></i> المدفوعات <span class="count">{{ $booking->payments->count() }}</span></button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-accom"><i class="bi bi-building"></i> السكن <span class="count">{{ $booking->accommodations->count() }}</span></button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-trans"><i class="bi bi-airplane"></i> النقل <span class="count">{{ $booking->transportation->count() }}</span></button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-documents"><i class="bi bi-folder"></i> الوثائق <span class="count">{{ $booking->documents->count() }}</span></button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-workflow"><i class="bi bi-diagram-3"></i> سير العمل</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-timeline"><i class="bi bi-clock-history"></i> السجل</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-integrations"><i class="bi bi-link-45deg"></i> التكاملات</button></li>
    </ul>

    <div class="tab-content">

        {{-- ── TAB: Pilgrims ──────────────────────────────── --}}
        <div class="tab-pane fade show active" id="tab-pilgrims">
            <div class="tab-pane-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="mb-0"><i class="bi bi-people-fill"></i> قائمة المعتمرين/الحجاج</h6>
                    @can('religious_bookings.update')
                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#pilgrimModal">
                        <i class="bi bi-plus-circle"></i> إضافة معتمر
                    </button>
                    @endcan
                </div>

                @if($booking->pilgrims->isEmpty())
                    <div class="empty-state">
                        <i class="bi bi-people"></i>
                        لم يتم إضافة معتمرين بعد. ابدأ بإضافة المعتمر الأول.
                    </div>
                @else
                <div class="table-responsive">
                    <table class="data-table-mini">
                        <thead>
                            <tr>
                                <th width="40">#</th>
                                <th>الاسم</th>
                                <th>الجنس / العمر</th>
                                <th>الرقم القومي</th>
                                <th>الجواز</th>
                                <th>التأشيرة</th>
                                <th width="120">الباركود</th>
                                <th width="100">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($booking->pilgrims as $i => $p)
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td>
                                    <strong>{{ $p->full_name }}</strong>
                                    @if($p->full_name_en)<div class="text-muted small" dir="ltr">{{ $p->full_name_en }}</div>@endif
                                </td>
                                <td>
                                    {{ $p->gender === 'female' ? 'أنثى' : 'ذكر' }}
                                    @if($p->age) <span class="text-muted">({{ $p->age }})</span>@endif
                                </td>
                                <td>{{ $p->national_id ?: '—' }}</td>
                                <td>
                                    {{ $p->passport_number ?: '—' }}
                                    @if($p->passport_expiry_date)
                                        <div class="small text-muted">ينتهي: {{ $p->passport_expiry_date->format('Y-m-d') }}</div>
                                    @endif
                                </td>
                                <td>
                                    @switch($p->visa_status)
                                        @case('issued')    <span class="badge-soft-green">صادرة</span> @break
                                        @case('requested') <span class="badge-soft-blue">مطلوبة</span> @break
                                        @case('rejected')  <span class="badge-soft-red">مرفوضة</span> @break
                                        @case('cancelled') <span class="badge-soft-gray">ملغية</span> @break
                                        @default           <span class="badge-soft-yellow">قيد الانتظار</span>
                                    @endswitch
                                    @if($p->visa_number)<div class="small text-muted">{{ $p->visa_number }}</div>@endif
                                </td>
                                <td>
                                    @if($p->safa_barcode)
                                        <code class="small">{{ $p->safa_barcode }}</code>
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>
                                <td>
                                    @can('religious_bookings.update')
                                    <button class="btn btn-icon btn-sm btn-light-info edit-pilgrim"
                                        data-pilgrim='@json($p)' title="تعديل">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-icon btn-sm btn-light-danger btn-delete"
                                        data-url="{{ route('admin.religious.bookings.pilgrims.destroy', [$booking, $p]) }}" title="حذف">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                    @endcan
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
        </div>

        {{-- ── TAB: Costs ──────────────────────────────────── --}}
        <div class="tab-pane fade" id="tab-costs">
            <div class="tab-pane-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="mb-0"><i class="bi bi-receipt"></i> بنود التكلفة</h6>
                    @can('religious_bookings.manage_costs')
                    @if($booking->workflow_stage !== 'closed')
                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#costModal">
                        <i class="bi bi-plus-circle"></i> إضافة بند
                    </button>
                    @endif
                    @endcan
                </div>

                @if($booking->costs->isEmpty())
                    <div class="empty-state">
                        <i class="bi bi-receipt"></i>
                        لا توجد بنود تكلفة بعد.
                    </div>
                @else
                <div class="table-responsive">
                    <table class="data-table-mini">
                        <thead>
                            <tr>
                                <th>البند</th>
                                <th>الوصف</th>
                                <th>المبلغ</th>
                                <th>الكمية</th>
                                <th>بعد التحويل (EGP)</th>
                                <th width="100">إجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($booking->costs as $cost)
                            <tr>
                                <td>
                                    <strong>{{ $cost->category_label }}</strong>
                                    @if($cost->is_revenue) <span class="badge-soft-green">إيراد</span> @endif
                                    @if($cost->is_locked) <i class="bi bi-lock text-muted" title="مقفل"></i> @endif
                                </td>
                                <td class="text-muted">{{ $cost->description ?: '—' }}</td>
                                <td>{{ number_format($cost->amount, 2) }} <small>{{ $cost->currency }}</small></td>
                                <td>{{ $cost->quantity }}</td>
                                <td><strong>{{ number_format($cost->amount_egp, 2) }}</strong> ج.م</td>
                                <td>
                                    @can('religious_bookings.manage_costs')
                                    @if(!$cost->is_locked && $booking->workflow_stage !== 'closed')
                                    <button class="btn btn-icon btn-sm btn-light-info edit-cost"
                                        data-cost='@json($cost)' title="تعديل">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-icon btn-sm btn-light-danger btn-delete"
                                        data-url="{{ route('admin.religious.bookings.costs.destroy', [$booking, $cost]) }}" title="حذف">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                    @endif
                                    @endcan
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr style="background:#f9fafb;font-weight:800;">
                                <td colspan="4" class="text-end">الإجمالي</td>
                                <td colspan="2"><strong>{{ number_format($booking->total_cost, 2) }}</strong> ج.م</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                @endif
            </div>
        </div>

        {{-- ── TAB: Payments ───────────────────────────────── --}}
        <div class="tab-pane fade" id="tab-payments">
            <div class="tab-pane-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="mb-0"><i class="bi bi-cash-stack"></i> المدفوعات والإيصالات</h6>
                    @can('religious_bookings.manage_payments')
                    @if($booking->workflow_stage !== 'closed')
                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#paymentModal">
                        <i class="bi bi-plus-circle"></i> تسجيل دفعة
                    </button>
                    @else
                    <span class="badge bg-secondary"><i class="bi bi-lock"></i> الحجز مُقفل — المدفوعات مقفلة</span>
                    @endif
                    @endcan
                </div>

                @if($booking->payments->isEmpty())
                    <div class="empty-state">
                        <i class="bi bi-cash-stack"></i>
                        لم يتم تسجيل أي دفعات بعد.
                    </div>
                @else
                <div class="table-responsive">
                    <table class="data-table-mini">
                        <thead>
                            <tr>
                                <th>الإيصال</th>
                                <th>التاريخ</th>
                                <th>النوع</th>
                                <th>الطريقة</th>
                                <th>المبلغ</th>
                                <th>المرجع</th>
                                <th width="100">إجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($booking->payments as $pay)
                            <tr>
                                <td><code>{{ $pay->receipt_number }}</code></td>
                                <td>{{ $pay->payment_date?->format('Y-m-d') }}</td>
                                <td>
                                    @switch($pay->payment_type)
                                        @case('deposit')     <span class="badge-soft-blue">مقدم</span> @break
                                        @case('installment') <span class="badge-soft-yellow">قسط</span> @break
                                        @case('final')       <span class="badge-soft-green">نهائي</span> @break
                                        @case('refund')      <span class="badge-soft-red">استرداد</span> @break
                                    @endswitch
                                    @if($pay->isRefund() && $pay->refund_status)
                                        @php
                                            $refundClass = match($pay->refund_status) {
                                                'pending'  => 'bg-warning text-dark',
                                                'approved' => 'bg-info text-white',
                                                'rejected' => 'bg-secondary text-white',
                                                'paid'     => 'bg-success text-white',
                                                default    => 'bg-light',
                                            };
                                        @endphp
                                        <span class="badge {{ $refundClass }} ms-1" title="{{ $pay->approval_notes }}">
                                            {{ $pay->refund_status_label }}
                                        </span>
                                    @endif
                                </td>
                                <td>{{ $pay->method_label }}</td>
                                <td><strong>{{ number_format($pay->amount, 2) }}</strong> {{ $pay->currency }}
                                    @if($pay->currency !== 'EGP')
                                        <div class="small text-muted">{{ number_format($pay->amount_egp, 2) }} ج.م</div>
                                    @endif
                                </td>
                                <td class="text-muted small">
                                    {{ $pay->transaction_reference ?: $pay->cheque_number ?: '—' }}
                                    @if($pay->isRefund() && $pay->refund_reason)
                                        <div class="text-muted" style="font-size:11px;"><i class="bi bi-chat-quote"></i> {{ Str::limit($pay->refund_reason, 60) }}</div>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('admin.religious.bookings.print.receipt', [$booking, $pay]) }}" target="_blank"
                                       class="btn btn-icon btn-sm btn-light-info" title="طباعة الإيصال">
                                        <i class="bi bi-printer"></i>
                                    </a>

                                    @if($pay->isRefund() && $booking->workflow_stage !== 'closed')
                                        @if($pay->refund_status === 'pending')
                                            @can('religious_bookings.approve_refund')
                                            <form action="{{ route('admin.religious.bookings.payments.approve_refund', [$booking, $pay]) }}"
                                                  method="POST" class="d-inline" onsubmit="return confirm('تأكيد الموافقة على الاسترداد؟');">
                                                @csrf
                                                <button class="btn btn-icon btn-sm btn-light-success" title="الموافقة على الاسترداد">
                                                    <i class="bi bi-check2-circle"></i>
                                                </button>
                                            </form>
                                            <button type="button" class="btn btn-icon btn-sm btn-light-warning"
                                                    title="رفض الاسترداد"
                                                    data-bs-toggle="modal" data-bs-target="#rejectRefundModal-{{ $pay->id }}">
                                                <i class="bi bi-x-circle"></i>
                                            </button>
                                            @endcan
                                        @elseif($pay->refund_status === 'approved')
                                            @can('religious_bookings.manage_payments')
                                            <form action="{{ route('admin.religious.bookings.payments.mark_refund_paid', [$booking, $pay]) }}"
                                                  method="POST" class="d-inline" onsubmit="return confirm('تأكيد صرف مبلغ الاسترداد للعميل؟');">
                                                @csrf
                                                <button class="btn btn-icon btn-sm btn-light-success" title="تم الصرف للعميل">
                                                    <i class="bi bi-cash-coin"></i>
                                                </button>
                                            </form>
                                            @endcan
                                        @endif
                                    @endif

                                    @can('religious_bookings.manage_payments')
                                    @if($booking->workflow_stage !== 'closed' && !($pay->isRefund() && in_array($pay->refund_status, ['approved','paid'])))
                                    <button class="btn btn-icon btn-sm btn-light-danger btn-delete"
                                        data-url="{{ route('admin.religious.bookings.payments.destroy', [$booking, $pay]) }}" title="حذف">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                    @endif
                                    @endcan
                                </td>
                            </tr>

                            {{-- Reject-refund modal (one per pending refund row) --}}
                            @if($pay->isRefund() && $pay->refund_status === 'pending' && $booking->workflow_stage !== 'closed')
                                @can('religious_bookings.approve_refund')
                                <div class="modal fade" id="rejectRefundModal-{{ $pay->id }}" tabindex="-1">
                                    <div class="modal-dialog">
                                        <form action="{{ route('admin.religious.bookings.payments.reject_refund', [$booking, $pay]) }}"
                                              method="POST" class="modal-content">
                                            @csrf
                                            <div class="modal-header bg-warning">
                                                <h5 class="modal-title"><i class="bi bi-x-circle"></i> رفض طلب الاسترداد</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p>طلب استرداد <strong>{{ number_format($pay->amount, 2) }} {{ $pay->currency }}</strong> — إيصال <code>{{ $pay->receipt_number }}</code></p>
                                                <label class="form-label">سبب الرفض *</label>
                                                <textarea name="approval_notes" rows="3" class="form-control" required></textarea>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">تراجع</button>
                                                <button type="submit" class="btn btn-warning">تأكيد الرفض</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                                @endcan
                            @endif
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr style="background:#f9fafb;font-weight:800;">
                                <td colspan="4" class="text-end">إجمالي المدفوع</td>
                                <td colspan="3"><strong>{{ number_format($totals['paid'], 2) }}</strong> ج.م</td>
                            </tr>
                            <tr style="background:#fef3c7;font-weight:800;">
                                <td colspan="4" class="text-end">المتبقي</td>
                                <td colspan="3"><strong>{{ number_format($totals['outstanding'], 2) }}</strong> ج.م</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                @endif
            </div>
        </div>

        {{-- ── TAB: Accommodations ─────────────────────────── --}}
        <div class="tab-pane fade" id="tab-accom">
            <div class="tab-pane-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="mb-0"><i class="bi bi-building"></i> السكن (مكة / المدينة)</h6>
                    @can('religious_bookings.update')
                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#accomModal">
                        <i class="bi bi-plus-circle"></i> إضافة سكن
                    </button>
                    @endcan
                </div>

                @if($booking->accommodations->isEmpty())
                    <div class="empty-state">
                        <i class="bi bi-building"></i>
                        لم يتم إضافة بيانات السكن بعد.
                    </div>
                @else
                <div class="row g-3">
                    @foreach($booking->accommodations as $accom)
                    <div class="col-md-6">
                        <div class="card border h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <span class="badge-soft-{{ $accom->city === 'mecca' ? 'green' : 'blue' }}">
                                            <i class="bi bi-geo-alt"></i> {{ $accom->city_label }}
                                        </span>
                                        <span class="badge-soft-yellow">
                                            @switch($accom->hotel_grade)
                                                @case('5_stars') 5 نجوم @break
                                                @case('4_stars') 4 نجوم @break
                                                @default اقتصادي
                                            @endswitch
                                        </span>
                                    </div>
                                    @can('religious_bookings.update')
                                    <button class="btn btn-icon btn-sm btn-light-danger btn-delete"
                                        data-url="{{ route('admin.religious.bookings.accommodations.destroy', [$booking, $accom]) }}">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                    @endcan
                                </div>
                                <h6 class="mb-2">{{ $accom->hotel_name }}</h6>
                                <div class="small text-muted mb-2">
                                    {{ $accom->check_in_date?->format('Y-m-d') }} → {{ $accom->check_out_date?->format('Y-m-d') }}
                                    • {{ $accom->nights }} ليلة
                                </div>
                                <div class="small mb-1"><strong>{{ $accom->rooms_count }}</strong> غرفة × {{ $accom->pax_per_room }} فرد</div>
                                <div class="small">سعر الليلة: <strong>{{ number_format($accom->room_price_per_night_sar, 2) }} SR</strong></div>
                                <div class="d-flex justify-content-between mt-2 pt-2 border-top">
                                    <span class="small text-muted">الإجمالي</span>
                                    <span><strong>{{ number_format($accom->total_cost_egp, 2) }}</strong> ج.م</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
        </div>

        {{-- ── TAB: Transportation ─────────────────────────── --}}
        <div class="tab-pane fade" id="tab-trans">
            <div class="tab-pane-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="mb-0"><i class="bi bi-airplane"></i> النقل (طيران / باص / VIP)</h6>
                    @can('religious_bookings.update')
                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#transModal">
                        <i class="bi bi-plus-circle"></i> إضافة وسيلة نقل
                    </button>
                    @endcan
                </div>

                @if($booking->transportation->isEmpty())
                    <div class="empty-state">
                        <i class="bi bi-airplane"></i>
                        لم يتم إضافة وسائل نقل بعد.
                    </div>
                @else
                <div class="table-responsive">
                    <table class="data-table-mini">
                        <thead>
                            <tr>
                                <th>النوع</th>
                                <th>الاتجاه</th>
                                <th>الناقل</th>
                                <th>من → إلى</th>
                                <th>التاريخ</th>
                                <th>الأفراد</th>
                                <th>التكلفة</th>
                                <th width="60"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($booking->transportation as $t)
                            <tr>
                                <td><strong>{{ $t->type_label }}</strong></td>
                                <td>
                                    @switch($t->direction)
                                        @case('outbound') ذهاب @break
                                        @case('inbound') عودة @break
                                        @default داخلي
                                    @endswitch
                                </td>
                                <td>{{ $t->carrier_name ?: '—' }} @if($t->reference)<div class="small text-muted">{{ $t->reference }}</div>@endif</td>
                                <td class="small">{{ $t->departure_location }} → {{ $t->arrival_location }}</td>
                                <td class="small">{{ $t->departure_at?->format('Y-m-d H:i') ?: '—' }}</td>
                                <td>{{ $t->pax_count }}</td>
                                <td><strong>{{ number_format($t->total_cost_egp, 2) }}</strong> ج.م</td>
                                <td>
                                    @can('religious_bookings.update')
                                    <button class="btn btn-icon btn-sm btn-light-danger btn-delete"
                                        data-url="{{ route('admin.religious.bookings.transportation.destroy', [$booking, $t]) }}">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                    @endcan
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
        </div>

        {{-- ── TAB: Workflow ───────────────────────────────── --}}
        <div class="tab-pane fade" id="tab-workflow">
            <div class="tab-pane-body">
                <style>
                    .action-card {
                        background: #fff; border: 2px solid #e2e8f0; border-radius: 14px;
                        padding: 1.1rem; transition: all .2s;
                        position: relative; overflow: hidden;
                    }
                    .action-card:hover { transform: translateY(-3px); box-shadow: 0 8px 20px rgba(15,23,42,.08); }
                    .action-card.disabled { opacity: .55; pointer-events: none; }
                    .action-card .ac-icon {
                        width: 52px; height: 52px; border-radius: 13px;
                        display: flex; align-items: center; justify-content: center;
                        font-size: 1.5rem; margin-bottom: .8rem;
                    }
                    .ac-icon.approve   { background: linear-gradient(135deg, #dcfce7, #bbf7d0); color: #15803d; }
                    .ac-icon.start     { background: linear-gradient(135deg, #dbeafe, #bfdbfe); color: #1d4ed8; }
                    .ac-icon.finance   { background: linear-gradient(135deg, #fef3c7, #fde68a); color: #b45309; }
                    .ac-icon.close     { background: linear-gradient(135deg, #1f2937, #374151); color: #d4a437; }
                    .ac-icon.cancel    { background: linear-gradient(135deg, #fee2e2, #fecaca); color: #b91c1c; }

                    .action-card h6 { color: var(--brand-navy); font-weight: 800; margin-bottom: .3rem; }
                    .action-card p  { font-size: .8rem; color: #64748b; margin-bottom: .85rem; }
                    .action-card .ac-badge {
                        position: absolute; top: 12px; left: 12px;
                        font-size: .65rem; font-weight: 700;
                        padding: 2px 8px; border-radius: 20px;
                    }
                    .ac-badge.available { background: #dcfce7; color: #15803d; }
                    .ac-badge.locked    { background: #f1f5f9; color: #94a3b8; }
                </style>

                <h6 class="mb-3"><i class="bi bi-diagram-3"></i> سير العمل والإجراءات</h6>

                <div class="row g-3">
                    {{-- Approve --}}
                    <div class="col-md-6 col-lg-3">
                        <div class="action-card {{ $booking->status !== 'pending' ? 'disabled' : '' }}">
                            <span class="ac-badge {{ $booking->status === 'pending' ? 'available' : 'locked' }}">
                                {{ $booking->status === 'pending' ? 'متاح' : 'تم' }}
                            </span>
                            <div class="ac-icon approve"><i class="bi bi-check-circle"></i></div>
                            <h6>اعتماد الحجز</h6>
                            <p>اعتماد الحجز وتأكيده وإرساله للعمليات</p>
                            @can('religious_bookings.approve')
                            @if($booking->status === 'pending')
                            <form method="POST" action="{{ route('admin.religious.bookings.transition', $booking) }}">
                                @csrf
                                <input type="hidden" name="action" value="approve">
                                <button class="btn btn-success btn-sm w-100">اعتماد ←</button>
                            </form>
                            @endif
                            @endcan
                        </div>
                    </div>

                    {{-- Start operations --}}
                    <div class="col-md-6 col-lg-3">
                        <div class="action-card {{ $booking->status !== 'confirmed' ? 'disabled' : '' }}">
                            <span class="ac-badge {{ $booking->status === 'confirmed' ? 'available' : 'locked' }}">
                                {{ $booking->status === 'confirmed' ? 'متاح' : ($booking->status === 'in_progress' ? 'تم' : '—') }}
                            </span>
                            <div class="ac-icon start"><i class="bi bi-airplane"></i></div>
                            <h6>بدء التنفيذ</h6>
                            <p>تأشيرات وسكن وطيران ونقل</p>
                            @can('religious_bookings.update')
                            @if($booking->status === 'confirmed')
                            <form method="POST" action="{{ route('admin.religious.bookings.transition', $booking) }}">
                                @csrf
                                <input type="hidden" name="action" value="start_operations">
                                <button class="btn btn-primary btn-sm w-100">بدء ←</button>
                            </form>
                            @endif
                            @endcan
                        </div>
                    </div>

                    {{-- Send to finance --}}
                    <div class="col-md-6 col-lg-3">
                        <div class="action-card {{ $booking->workflow_stage !== 'operations' ? 'disabled' : '' }}">
                            <span class="ac-badge {{ $booking->workflow_stage === 'operations' ? 'available' : 'locked' }}">
                                {{ $booking->workflow_stage === 'operations' ? 'متاح' : ($booking->workflow_stage === 'finance' || $booking->workflow_stage === 'closed' ? 'تم' : '—') }}
                            </span>
                            <div class="ac-icon finance"><i class="bi bi-cash-coin"></i></div>
                            <h6>تحويل للمالية</h6>
                            <p>للتحصيل والمراجعة المالية</p>
                            @can('religious_bookings.update')
                            @if($booking->workflow_stage === 'operations')
                            <form method="POST" action="{{ route('admin.religious.bookings.transition', $booking) }}">
                                @csrf
                                <input type="hidden" name="action" value="send_to_finance">
                                <button class="btn btn-warning btn-sm w-100">تحويل ←</button>
                            </form>
                            @endif
                            @endcan
                        </div>
                    </div>

                    {{-- Close --}}
                    <div class="col-md-6 col-lg-3">
                        <div class="action-card {{ $booking->workflow_stage !== 'finance' ? 'disabled' : '' }}">
                            <span class="ac-badge {{ $booking->workflow_stage === 'finance' ? 'available' : 'locked' }}">
                                {{ $booking->workflow_stage === 'finance' ? 'متاح' : ($booking->workflow_stage === 'closed' ? 'تم' : '—') }}
                            </span>
                            <div class="ac-icon close"><i class="bi bi-lock-fill"></i></div>
                            <h6>إقفال نهائي</h6>
                            <p>قفل بنود الحجز المالية</p>
                            @can('religious_bookings.close')
                            @if($booking->workflow_stage === 'finance')
                            <form method="POST" action="{{ route('admin.religious.bookings.transition', $booking) }}"
                                  onsubmit="return confirm('سيتم إقفال الحجز نهائياً. هل أنت متأكد؟');">
                                @csrf
                                <input type="hidden" name="action" value="close">
                                <button class="btn btn-dark btn-sm w-100">إقفال ←</button>
                            </form>
                            @endif
                            @endcan
                        </div>
                    </div>

                    {{-- Cancel (always at the bottom) --}}
                    @if($booking->status !== 'cancelled' && $booking->status !== 'completed')
                    @can('religious_bookings.cancel')
                    <div class="col-12">
                        <div class="action-card" style="background: linear-gradient(135deg, #fef2f2, #fff); border-color: #fecaca;">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="ac-icon cancel mb-0"><i class="bi bi-x-circle"></i></div>
                                    <div>
                                        <h6 class="mb-1">إلغاء الحجز</h6>
                                        <p class="mb-0 small">في حالة الإلغاء يلزم تسجيل السبب — هذا الإجراء لا يمكن التراجع عنه</p>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#cancelModal">
                                    <i class="bi bi-x-circle"></i> إلغاء الحجز
                                </button>
                            </div>
                        </div>
                    </div>
                    @endcan
                    @endif
                </div>
            </div>
        </div>

        {{-- ── TAB: Integrations ──────────────────────────── --}}
        <div class="tab-pane fade" id="tab-integrations">
            <div class="tab-pane-body">
                <h6 class="mb-3"><i class="bi bi-link-45deg"></i> التكاملات الخارجية</h6>

                <div class="row g-3">
                    {{-- Safa --}}
                    <div class="col-md-6">
                        <div class="card border h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="mb-0"><i class="bi bi-qr-code text-success"></i> صفا</h6>
                                    @if($booking->safa_synced_at)
                                        <span class="badge-soft-green">متزامن</span>
                                    @else
                                        <span class="badge-soft-gray">لم يتم</span>
                                    @endif
                                </div>
                                <div class="small mb-2">
                                    <strong>الباركود:</strong> {{ $booking->safa_barcode ?: '—' }}
                                </div>
                                <div class="small mb-2">
                                    <strong>رقم التأشيرة الجماعي:</strong> {{ $booking->safa_visa_group_number ?: '—' }}
                                </div>
                                <div class="small text-muted mb-3">
                                    آخر مزامنة: {{ $booking->safa_synced_at?->format('Y-m-d H:i') ?: 'لم يتم' }}
                                </div>
                                @can('religious_bookings.sync_safa')
                                <form method="POST" action="{{ route('admin.religious.bookings.sync_safa', $booking) }}">
                                    @csrf
                                    <button class="btn btn-success btn-sm w-100">
                                        <i class="bi bi-arrow-clockwise"></i> سحب من صفا
                                    </button>
                                </form>
                                @endcan
                            </div>
                        </div>
                    </div>

                    {{-- Umrah Portal --}}
                    <div class="col-md-6">
                        <div class="card border h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="mb-0"><i class="bi bi-globe-asia-australia text-primary"></i> بوابة العمرة</h6>
                                    @if($booking->umrah_portal_synced_at)
                                        <span class="badge-soft-green">متزامن</span>
                                    @else
                                        <span class="badge-soft-gray">لم يتم</span>
                                    @endif
                                </div>
                                <div class="small mb-2">
                                    <strong>الرقم المرجعي:</strong> {{ $booking->umrah_portal_ref ?: '—' }}
                                </div>
                                <div class="small text-muted mb-3">
                                    آخر مزامنة: {{ $booking->umrah_portal_synced_at?->format('Y-m-d H:i') ?: 'لم يتم' }}
                                </div>
                                @can('religious_bookings.sync_umrah_portal')
                                <form method="POST" action="{{ route('admin.religious.bookings.sync_portal', $booking) }}">
                                    @csrf
                                    <button class="btn btn-primary btn-sm w-100">
                                        <i class="bi bi-cloud-arrow-down"></i> مزامنة مع بوابة العمرة
                                    </button>
                                </form>
                                @endcan
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── TAB: Documents ──────────────────────────────── --}}
        <div class="tab-pane fade" id="tab-documents">
            <div class="tab-pane-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="mb-0"><i class="bi bi-folder"></i> مستودع الوثائق</h6>
                    @can('religious_bookings.update')
                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#documentModal">
                        <i class="bi bi-cloud-upload"></i> رفع وثيقة
                    </button>
                    @endcan
                </div>

                @if($booking->documents->isEmpty())
                    <div class="empty-state">
                        <i class="bi bi-folder2-open"></i>
                        لم يتم رفع أي وثيقة بعد. ابدأ برفع وثيقة (جواز، تأمين، تذكرة، إلخ).
                    </div>
                @else
                <div class="row g-3">
                    @foreach($booking->documents as $doc)
                    <div class="col-md-4 col-sm-6">
                        <div class="card border h-100">
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <span class="badge-soft-blue">{{ $doc->category_label }}</span>
                                    @can('religious_bookings.update')
                                    <button class="btn btn-icon btn-sm btn-light-danger btn-delete"
                                        data-url="{{ route('admin.religious.bookings.documents.destroy', [$booking, $doc]) }}"
                                        title="حذف">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                    @endcan
                                </div>

                                <div class="text-center mb-2" style="height:80px; display:flex; align-items:center; justify-content:center;">
                                    @if($doc->is_image)
                                        <img src="{{ $doc->file_url }}" style="max-height:80px; max-width:100%; border-radius:8px;" alt="">
                                    @elseif($doc->is_pdf)
                                        <i class="bi bi-file-earmark-pdf" style="font-size:3rem; color:#dc2626;"></i>
                                    @else
                                        <i class="bi bi-file-earmark-text" style="font-size:3rem; color:#6366f1;"></i>
                                    @endif
                                </div>

                                <div class="fw-bold small mb-1" style="color:var(--brand-navy); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                    {{ $doc->title }}
                                </div>

                                @if($doc->pilgrim)
                                <div class="small text-muted mb-1">
                                    <i class="bi bi-person"></i> {{ $doc->pilgrim->full_name }}
                                </div>
                                @endif

                                <div class="small text-muted mb-2">
                                    {{ $doc->file_size_human }} • {{ $doc->created_at?->diffForHumans() }}
                                </div>

                                @if($doc->expiry_date)
                                <div class="mb-2">
                                    @if($doc->is_expired)
                                        <span class="badge-soft-red"><i class="bi bi-x-circle"></i> منتهي ({{ $doc->expiry_date->format('Y-m-d') }})</span>
                                    @elseif($doc->is_expiring)
                                        <span class="badge-soft-yellow"><i class="bi bi-exclamation-triangle"></i> ينتهي {{ $doc->expiry_date->format('Y-m-d') }}</span>
                                    @else
                                        <span class="badge-soft-green"><i class="bi bi-check-circle"></i> سارٍ حتى {{ $doc->expiry_date->format('Y-m-d') }}</span>
                                    @endif
                                </div>
                                @endif

                                <div class="d-flex gap-1">
                                    <a href="{{ $doc->file_url }}" target="_blank" class="btn btn-sm btn-outline-primary flex-grow-1">
                                        <i class="bi bi-eye"></i> عرض
                                    </a>
                                    <a href="{{ route('admin.religious.bookings.documents.download', [$booking, $doc]) }}"
                                       class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-download"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
        </div>

        {{-- ── TAB: Timeline ───────────────────────────────── --}}
        <div class="tab-pane fade" id="tab-timeline">
            <div class="tab-pane-body">
                <h6 class="mb-3"><i class="bi bi-clock-history"></i> سجل النشاط للحجز</h6>

                @php
                    $activities = \Spatie\Activitylog\Models\Activity::query()
                        ->where('subject_type', \App\Models\ReligiousBooking::class)
                        ->where('subject_id', $booking->id)
                        ->orWhere(function ($q) use ($booking) {
                            $q->where('subject_type', \App\Models\BookingPilgrim::class)
                              ->whereIn('subject_id', $booking->pilgrims->pluck('id'));
                        })
                        ->with('causer:id,name')
                        ->latest()
                        ->limit(50)
                        ->get();
                @endphp

                @if($activities->isEmpty())
                    <div class="empty-state">
                        <i class="bi bi-clock"></i>
                        لا توجد سجلات نشاط بعد.
                    </div>
                @else
                <div style="position:relative; padding-right:30px;">
                    <div style="position:absolute; right:8px; top:0; bottom:0; width:2px; background:#e2e8f0;"></div>
                    @foreach($activities as $activity)
                    <div style="position:relative; padding:.65rem .85rem; margin-bottom:.65rem; background:#fafbff; border-radius:8px; border:1px solid #e2e8f0;">
                        <div style="position:absolute; right:-26px; top:.85rem; width:14px; height:14px; border-radius:50%; background:var(--brand-gold); border:3px solid #fff; box-shadow:0 0 0 2px var(--brand-gold);"></div>
                        <div class="small">
                            <strong>{{ $activity->description }}</strong>
                            @if($activity->causer)
                                <span class="text-muted">— بواسطة {{ $activity->causer->name }}</span>
                            @endif
                        </div>
                        <div class="text-muted" style="font-size:.7rem; margin-top:.15rem;">
                            <i class="bi bi-clock"></i> {{ $activity->created_at?->format('Y-m-d H:i:s') }}
                            • {{ $activity->created_at?->diffForHumans() }}
                        </div>
                        @if($activity->properties && $activity->properties->has('attributes'))
                            @php $attrs = $activity->properties->get('attributes'); $old = $activity->properties->get('old', []); @endphp
                            <div style="font-size:.75rem; margin-top:.4rem; padding-top:.4rem; border-top:1px dashed #cbd5e1; color:#475569;">
                                @foreach($attrs as $k => $v)
                                    @if(isset($old[$k]) && $old[$k] !== $v)
                                        <div><strong>{{ $k }}:</strong> <s class="text-muted">{{ is_scalar($old[$k]) ? $old[$k] : '...' }}</s> → {{ is_scalar($v) ? $v : '...' }}</div>
                                    @endif
                                @endforeach
                            </div>
                        @endif
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
        </div>

    </div>
</div>

{{-- Modals --}}
@include('admin.religious.bookings._modals')

@endsection

@push('scripts')
<script>
$(function () {
    // Generic delete handler
    $(document).on('click', '.btn-delete', function () {
        CoreX.ajaxDelete($(this).data('url'));
    });

    // Pre-fill edit modals
    $(document).on('click', '.edit-pilgrim', function () {
        const d = $(this).data('pilgrim');
        const $f = $('#pilgrimForm');
        $f.attr('action', '{{ url('admin/religious/bookings/' . $booking->id . '/pilgrims') }}/' + d.id);
        $('#pilgrimMethod').val('PUT');
        $('#pilgrimModalTitle').text('تعديل المعتمر');
        Object.keys(d).forEach(k => {
            const el = $f.find(`[name="${k}"]`);
            if (el.length) el.val(d[k]);
        });
        new bootstrap.Modal('#pilgrimModal').show();
    });

    $('#pilgrimModal').on('hidden.bs.modal', function () {
        $('#pilgrimForm')[0].reset();
        $('#pilgrimForm').attr('action', '{{ route('admin.religious.bookings.pilgrims.store', $booking) }}');
        $('#pilgrimMethod').val('POST');
        $('#pilgrimModalTitle').text('إضافة معتمر');
    });

    $(document).on('click', '.edit-cost', function () {
        const d = $(this).data('cost');
        const $f = $('#costForm');
        $f.attr('action', '{{ url('admin/religious/bookings/' . $booking->id . '/costs') }}/' + d.id);
        $('#costMethod').val('PUT');
        $('#costModalTitle').text('تعديل بند تكلفة');
        Object.keys(d).forEach(k => {
            const el = $f.find(`[name="${k}"]`);
            if (el.length) {
                if (el.attr('type') === 'checkbox') el.prop('checked', !!d[k]);
                else el.val(d[k]);
            }
        });
        new bootstrap.Modal('#costModal').show();
    });

    $('#costModal').on('hidden.bs.modal', function () {
        $('#costForm')[0].reset();
        $('#costForm').attr('action', '{{ route('admin.religious.bookings.costs.store', $booking) }}');
        $('#costMethod').val('POST');
        $('#costModalTitle').text('إضافة بند تكلفة');
    });
});
</script>
@endpush
