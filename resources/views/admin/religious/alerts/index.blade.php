@extends('layouts.master')

@section('title', 'تنبيهات السياحة الدينية')
@section('page_title', 'التنبيهات الذكية')
@section('page_subtitle', 'تنبيهات تلقائية بمشاكل الحجوزات - جوازات منتهية، تأشيرات متأخرة، دفعات متأخرة، ربحية منخفضة')

@push('styles')
<style>
    /* ── KPI cards ─────────────────────────────────────────── */
    .alert-kpis { display: grid; gap: .85rem; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); margin-bottom: 1.1rem; }
    .alert-kpi {
        background: #fff; border-radius: 16px; padding: 1.1rem 1.25rem;
        display: flex; align-items: center; gap: 1rem;
        border: 1px solid #f1f5f9;
        box-shadow: 0 2px 8px rgba(15,23,42,.04);
        transition: all .25s cubic-bezier(.4,0,.2,1);
        position: relative; overflow: hidden;
    }
    .alert-kpi:hover { transform: translateY(-3px); box-shadow: 0 10px 24px rgba(15,23,42,.08); }
    .alert-kpi::before {
        content: ''; position: absolute;
        left: 0; top: 0; bottom: 0; width: 4px;
        background: var(--accent-color, #cbd5e1);
        transition: width .25s;
    }
    .alert-kpi:hover::before { width: 6px; }
    .alert-kpi.k-critical { --accent-color: #dc2626; }
    .alert-kpi.k-warning  { --accent-color: #f59e0b; }
    .alert-kpi.k-info     { --accent-color: #3b82f6; }
    .alert-kpi .kpi-icon {
        width: 54px; height: 54px; border-radius: 14px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.5rem; flex-shrink: 0;
    }
    .alert-kpi.k-critical .kpi-icon { background: linear-gradient(135deg,#fee2e2,#fecaca); color:#b91c1c; }
    .alert-kpi.k-warning .kpi-icon  { background: linear-gradient(135deg,#fef3c7,#fde68a); color:#b45309; }
    .alert-kpi.k-info .kpi-icon     { background: linear-gradient(135deg,#dbeafe,#bfdbfe); color:#1d4ed8; }
    .alert-kpi .kpi-meta { flex: 1; min-width: 0; }
    .alert-kpi .kpi-label { font-size: .8rem; color: #64748b; font-weight: 600; }
    .alert-kpi .kpi-value { font-size: 1.8rem; font-weight: 900; color: var(--brand-navy); line-height: 1.1; margin-top: .1rem; }
    .alert-kpi .kpi-trend { font-size: .7rem; color: #94a3b8; margin-top: .15rem; }

    /* ── Filter toolbar ────────────────────────────────────── */
    .alert-toolbar {
        background: #fff; border-radius: 14px; padding: .85rem 1rem;
        border: 1px solid #f1f5f9;
        box-shadow: 0 1px 3px rgba(15,23,42,.04);
        display: flex; justify-content: space-between; align-items: center;
        gap: 1rem; flex-wrap: wrap; margin-bottom: 1rem;
    }
    .alert-pills { display: flex; gap: .35rem; flex-wrap: wrap; }
    .alert-pills .pill {
        display: inline-flex; align-items: center; gap: .35rem;
        padding: .45rem .9rem; border-radius: 10px;
        font-size: .82rem; font-weight: 700;
        background: #f8fafc; color: #475569;
        border: 1.5px solid transparent;
        text-decoration: none;
        transition: all .2s;
    }
    .alert-pills .pill:hover { background: #f1f5f9; color: var(--brand-navy); transform: translateY(-1px); }
    .alert-pills .pill.active {
        background: linear-gradient(135deg, var(--brand-navy), #1e293b);
        color: #fff;
        box-shadow: 0 4px 12px rgba(15,23,42,.20);
    }
    .alert-pills .pill.active.p-critical { background: linear-gradient(135deg,#dc2626,#991b1b); box-shadow: 0 4px 12px rgba(220,38,38,.30); }
    .alert-pills .pill.active.p-warning  { background: linear-gradient(135deg,#f59e0b,#b45309); box-shadow: 0 4px 12px rgba(245,158,11,.30); }
    .alert-pills .pill .count {
        background: rgba(255,255,255,.25); color: inherit;
        padding: 0 .45rem; border-radius: 6px;
        font-size: .72rem; font-weight: 800;
    }
    .alert-pills .pill:not(.active) .count { background: #e2e8f0; color: #64748b; }

    /* ── Alert cards ───────────────────────────────────────── */
    .alert-card {
        background: #fff; border-radius: 14px;
        border: 1px solid #f1f5f9;
        padding: 1rem 1.25rem; margin-bottom: .85rem;
        display: flex; align-items: center; gap: 1rem;
        position: relative; overflow: hidden;
        box-shadow: 0 1px 4px rgba(15,23,42,.03);
        transition: all .25s cubic-bezier(.4,0,.2,1);
    }
    .alert-card:hover {
        transform: translateX(3px);
        box-shadow: 0 8px 22px rgba(15,23,42,.08);
    }
    .alert-card::before {
        content: ''; position: absolute;
        right: 0; top: 0; bottom: 0; width: 5px;
    }
    .alert-card.acknowledged { opacity: .72; background: #fafbfc; }
    .alert-card.acknowledged:hover { opacity: 1; }

    .alert-card.critical::before { background: linear-gradient(180deg, #dc2626, #991b1b); }
    .alert-card.warning::before  { background: linear-gradient(180deg, #f59e0b, #b45309); }
    .alert-card.info::before     { background: linear-gradient(180deg, #3b82f6, #1d4ed8); }

    .alert-card.critical { background: linear-gradient(95deg, #fef2f2 0%, #fff 40%); }
    .alert-card.warning  { background: linear-gradient(95deg, #fef3c7 0%, #fff 40%); }
    .alert-card.info     { background: linear-gradient(95deg, #dbeafe 0%, #fff 40%); }

    .alert-icon {
        width: 54px; height: 54px; border-radius: 14px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.6rem; flex-shrink: 0;
        box-shadow: 0 3px 8px rgba(0,0,0,.06);
    }
    .alert-icon.critical { background: linear-gradient(135deg,#dc2626,#991b1b); color: #fff; }
    .alert-icon.warning  { background: linear-gradient(135deg,#f59e0b,#b45309); color: #fff; }
    .alert-icon.info     { background: linear-gradient(135deg,#3b82f6,#1d4ed8); color: #fff; }

    .alert-body { flex: 1; min-width: 0; }
    .alert-header-row {
        display: flex; align-items: center; gap: .5rem; flex-wrap: wrap;
        margin-bottom: .25rem;
    }
    .alert-title {
        font-weight: 800; color: var(--brand-navy);
        font-size: 1rem;
    }
    .alert-type-badge {
        font-size: .65rem; font-weight: 700;
        padding: .15rem .55rem; border-radius: 999px;
        background: #f1f5f9; color: #475569;
        text-transform: uppercase; letter-spacing: .02em;
    }
    .alert-type-badge.critical { background: #fee2e2; color: #991b1b; }
    .alert-type-badge.warning  { background: #fef3c7; color: #92400e; }
    .alert-type-badge.info     { background: #dbeafe; color: #1e40af; }
    .alert-msg { color: #475569; font-size: .87rem; line-height: 1.5; }
    .alert-meta {
        display: flex; align-items: center; gap: .85rem;
        font-size: .72rem; color: #94a3b8; margin-top: .55rem;
        flex-wrap: wrap;
    }
    .alert-meta a {
        color: #1d4ed8; text-decoration: none; font-weight: 700;
        display: inline-flex; align-items: center; gap: .25rem;
    }
    .alert-meta a:hover { text-decoration: underline; }
    .alert-meta .ack-info {
        color: #15803d; font-weight: 600;
        display: inline-flex; align-items: center; gap: .25rem;
    }

    .alert-actions { display: flex; gap: .5rem; flex-shrink: 0; }
    .alert-actions .btn-ack {
        background: linear-gradient(135deg, #16a34a, #15803d);
        color: #fff; border: none; padding: .55rem 1rem;
        border-radius: 10px; font-weight: 700; font-size: .82rem;
        display: inline-flex; align-items: center; gap: .35rem;
        cursor: pointer; transition: all .2s;
        box-shadow: 0 2px 6px rgba(21,128,61,.20);
    }
    .alert-actions .btn-ack:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 14px rgba(21,128,61,.30);
    }

    /* ── Empty state ───────────────────────────────────────── */
    .alerts-empty {
        background: linear-gradient(135deg, #f0fdf4 0%, #fff 60%);
        border: 1px solid #bbf7d0; border-radius: 16px;
        padding: 3rem 1.5rem; text-align: center;
    }
    .alerts-empty .empty-icon {
        width: 88px; height: 88px; border-radius: 50%;
        background: linear-gradient(135deg, #22c55e, #15803d);
        color: #fff; font-size: 3rem;
        display: flex; align-items: center; justify-content: center;
        margin: 0 auto 1.25rem;
        box-shadow: 0 12px 28px rgba(21,128,61,.25);
        animation: emptyPulse 2.5s ease-in-out infinite;
    }
    @keyframes emptyPulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.06); }
    }
    .alerts-empty h5 { color: var(--brand-navy); font-weight: 900; margin-bottom: .5rem; }
    .alerts-empty p { color: #64748b; margin-bottom: 1.25rem; }

    @media (max-width: 575.98px) {
        .alert-card { padding: .85rem; flex-direction: row; align-items: flex-start; gap: .75rem; }
        .alert-icon { width: 44px; height: 44px; font-size: 1.3rem; }
        .alert-title { font-size: .92rem; }
        .alert-actions { width: 100%; margin-top: .65rem; }
    }
</style>
@endpush

@section('content')

{{-- ── KPI Summary ───────────────────────────────────────── --}}
<div class="alert-kpis">
    <div class="alert-kpi k-critical">
        <div class="kpi-icon"><i class="bi bi-exclamation-octagon-fill"></i></div>
        <div class="kpi-meta">
            <div class="kpi-label">تنبيهات حرجة</div>
            <div class="kpi-value">{{ $counts['critical'] ?? 0 }}</div>
            <div class="kpi-trend">تتطلب تدخّل فوري</div>
        </div>
    </div>
    <div class="alert-kpi k-warning">
        <div class="kpi-icon"><i class="bi bi-exclamation-triangle-fill"></i></div>
        <div class="kpi-meta">
            <div class="kpi-label">تحذيرات</div>
            <div class="kpi-value">{{ $counts['warning'] ?? 0 }}</div>
            <div class="kpi-trend">راجعها قبل تصبح حرجة</div>
        </div>
    </div>
    <div class="alert-kpi k-info">
        <div class="kpi-icon"><i class="bi bi-info-circle-fill"></i></div>
        <div class="kpi-meta">
            <div class="kpi-label">تنبيهات معلوماتية</div>
            <div class="kpi-value">{{ $counts['info'] ?? 0 }}</div>
            <div class="kpi-trend">للمتابعة فقط</div>
        </div>
    </div>
</div>

{{-- ── Toolbar (filters + scan) ─────────────────────────── --}}
<div class="alert-toolbar">
    <div class="alert-pills">
        @php
            $activeAll = request('active', '1') === '1' && !request('severity');
            $totalActive = ($counts['critical'] ?? 0) + ($counts['warning'] ?? 0) + ($counts['info'] ?? 0);
        @endphp
        <a href="{{ route('admin.religious.alerts.index') }}"
           class="pill {{ $activeAll ? 'active' : '' }}">
            <i class="bi bi-list-ul"></i> الكل النشط
            <span class="count">{{ $totalActive }}</span>
        </a>
        <a href="{{ route('admin.religious.alerts.index', ['severity' => 'critical']) }}"
           class="pill p-critical {{ request('severity') === 'critical' ? 'active' : '' }}">
            <i class="bi bi-exclamation-octagon"></i> حرجة
            <span class="count">{{ $counts['critical'] ?? 0 }}</span>
        </a>
        <a href="{{ route('admin.religious.alerts.index', ['severity' => 'warning']) }}"
           class="pill p-warning {{ request('severity') === 'warning' ? 'active' : '' }}">
            <i class="bi bi-exclamation-triangle"></i> تحذير
            <span class="count">{{ $counts['warning'] ?? 0 }}</span>
        </a>
        <a href="{{ route('admin.religious.alerts.index', ['active' => 0]) }}"
           class="pill {{ request('active') === '0' ? 'active' : '' }}">
            <i class="bi bi-archive"></i> المؤرشفة
        </a>
    </div>
    <form method="POST" action="{{ route('admin.religious.alerts.scan') }}">
        @csrf
        <button class="btn btn-warning fw-bold" style="background:linear-gradient(135deg,#d4a437,#b8860b); border:none; color:#fff;">
            <i class="bi bi-arrow-clockwise"></i> فحص التنبيهات الآن
        </button>
    </form>
</div>

{{-- ── Alert cards ──────────────────────────────────────── --}}
@forelse($alerts as $alert)
    <div class="alert-card {{ $alert->severity }} {{ $alert->is_acknowledged ? 'acknowledged' : '' }}">
        <div class="alert-icon {{ $alert->severity }}">
            @switch($alert->type)
                @case('passport_expiring')   <i class="bi bi-passport-fill"></i> @break
                @case('visa_overdue')        <i class="bi bi-card-checklist"></i> @break
                @case('payment_overdue')     <i class="bi bi-cash-coin"></i> @break
                @case('profit_low')          <i class="bi bi-graph-down-arrow"></i> @break
                @case('trip_imminent')       <i class="bi bi-airplane-fill"></i> @break
                @case('safa_sync_failed')    <i class="bi bi-cloud-slash"></i> @break
                @case('umrah_portal_failed') <i class="bi bi-broadcast-pin"></i> @break
                @default                     <i class="bi bi-exclamation-circle-fill"></i>
            @endswitch
        </div>

        <div class="alert-body">
            <div class="alert-header-row">
                <span class="alert-title">{{ $alert->title }}</span>
                <span class="alert-type-badge {{ $alert->severity }}">
                    {{ $alert->type_label }}
                </span>
            </div>
            <div class="alert-msg">{{ $alert->message }}</div>
            <div class="alert-meta">
                <span><i class="bi bi-clock"></i> {{ $alert->created_at?->diffForHumans() }}</span>
                @if($alert->booking)
                    <a href="{{ route('admin.religious.bookings.show', $alert->booking) }}">
                        <i class="bi bi-link-45deg"></i> {{ $alert->booking->booking_number }}
                    </a>
                @endif
                @if($alert->is_acknowledged)
                    <span class="ack-info">
                        <i class="bi bi-check-circle-fill"></i>
                        تم الاستلام بواسطة {{ $alert->acknowledger?->name ?? '—' }}
                        @if($alert->acknowledged_at)
                            • {{ $alert->acknowledged_at->format('Y-m-d H:i') }}
                        @endif
                    </span>
                @endif
            </div>
        </div>

        @if(!$alert->is_acknowledged)
            @can('religious.alerts.acknowledge')
            <div class="alert-actions">
                <form method="POST" action="{{ route('admin.religious.alerts.acknowledge', $alert) }}">
                    @csrf
                    <button class="btn-ack" type="submit">
                        <i class="bi bi-check2-all"></i> استلام
                    </button>
                </form>
            </div>
            @endcan
        @endif
    </div>
@empty
    <div class="alerts-empty">
        <div class="empty-icon"><i class="bi bi-check-lg"></i></div>
        <h5>لا توجد تنبيهات نشطة</h5>
        <p>كل شيء يسير بشكل ممتاز — جميع الحجوزات على المسار الصحيح.</p>
        <form method="POST" action="{{ route('admin.religious.alerts.scan') }}" style="display:inline;">
            @csrf
            <button class="btn btn-outline-success">
                <i class="bi bi-arrow-clockwise"></i> فحص مرة أخرى
            </button>
        </form>
    </div>
@endforelse

{{ $alerts->links() }}

@endsection
