@extends('layouts.master')

@section('title', 'تفاصيل سجل المزامنة')
@section('page_title', 'تفاصيل سجل المزامنة')
@section('page_subtitle', $log->provider_label . ' • ' . $log->action_label)

@push('styles')
<style>
    .info-card { background:#fff; border-radius:12px; box-shadow:0 1px 3px rgba(15,23,42,.04); border:1px solid var(--brand-border); margin-bottom:1rem; overflow:hidden; }
    .info-card .head { padding:.85rem 1.1rem; background:#f9fafb; border-bottom:1px solid var(--brand-border); }
    .info-card .head h6 { margin:0; color:var(--brand-navy); font-weight:800; display:inline-flex; align-items:center; gap:.5rem; }
    .info-card .body { padding:1.1rem; }

    .kv { display:flex; justify-content:space-between; padding:.55rem 0; border-bottom:1px dashed #e2e8f0; font-size:.88rem; }
    .kv:last-child { border-bottom:none; }
    .kv .k { color:#64748b; font-weight:600; }
    .kv .v { color:#0f172a; font-weight:700; }

    .json-block {
        background:#0f172a; color:#e2e8f0;
        padding:1rem; border-radius:10px;
        font-family:'Courier New',monospace; font-size:.78rem;
        direction:ltr; text-align:left;
        max-height:400px; overflow-y:auto;
        white-space:pre-wrap; word-break:break-word;
    }
    .error-block {
        background:#fef2f2; color:#991b1b;
        padding:1rem; border-radius:10px;
        border-right:4px solid #dc2626;
        font-size:.85rem; line-height:1.6;
    }

    .status-pill {
        display:inline-flex; align-items:center; gap:.4rem;
        padding:.45rem .9rem; border-radius:8px; font-weight:800; font-size:.85rem;
    }
    .status-pill.success { background:#dcfce7; color:#15803d; }
    .status-pill.failed  { background:#fee2e2; color:#b91c1c; }
    .status-pill.pending { background:#fef3c7; color:#92400e; }
</style>
@endpush

@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">
        <span class="status-pill {{ $log->status }}">
            <i class="bi bi-{{ $log->status === 'success' ? 'check-circle' : ($log->status === 'failed' ? 'x-circle' : 'clock') }}"></i>
            {{ $log->status_label }}
        </span>
    </h5>
    <a href="{{ route('admin.religious.integrations.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-right"></i> العودة للسجل
    </a>
</div>

<div class="row g-3">
    <div class="col-md-6">
        <div class="info-card">
            <div class="head"><h6><i class="bi bi-info-circle"></i> معلومات الاستدعاء</h6></div>
            <div class="body">
                <div class="kv"><span class="k">المزود</span><span class="v">{{ $log->provider_label }}</span></div>
                <div class="kv"><span class="k">العملية</span><span class="v">{{ $log->action_label }}</span></div>
                <div class="kv"><span class="k">التاريخ والوقت</span><span class="v">{{ $log->created_at?->format('Y-m-d H:i:s') }}</span></div>
                <div class="kv"><span class="k">المستخدم</span><span class="v">{{ $log->trigger?->name ?: 'النظام' }}</span></div>
                <div class="kv"><span class="k">المدة</span><span class="v">{{ $log->duration_ms ? $log->duration_ms . ' ms' : '—' }}</span></div>
                @if($log->booking)
                <div class="kv">
                    <span class="k">الحجز</span>
                    <span class="v">
                        <a href="{{ route('admin.religious.bookings.show', $log->booking) }}">
                            <code>{{ $log->booking->booking_number }}</code>
                        </a>
                    </span>
                </div>
                @endif
                <div class="kv"><span class="k">ملخص الطلب</span><span class="v">{{ $log->request_summary ?: '—' }}</span></div>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        @if($log->error_message)
        <div class="info-card">
            <div class="head"><h6 class="text-danger"><i class="bi bi-exclamation-triangle"></i> رسالة الخطأ</h6></div>
            <div class="body">
                <div class="error-block">{{ $log->error_message }}</div>
            </div>
        </div>
        @endif

        @if($log->request_payload)
        <div class="info-card">
            <div class="head"><h6><i class="bi bi-arrow-up-circle"></i> Payload الطلب (Request)</h6></div>
            <div class="body">
                <pre class="json-block">{{ json_encode($log->request_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            </div>
        </div>
        @endif
    </div>

    @if($log->response_payload)
    <div class="col-12">
        <div class="info-card">
            <div class="head"><h6><i class="bi bi-arrow-down-circle"></i> Payload الاستجابة (Response)</h6></div>
            <div class="body">
                <pre class="json-block">{{ json_encode($log->response_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            </div>
        </div>
    </div>
    @endif
</div>

@endsection
