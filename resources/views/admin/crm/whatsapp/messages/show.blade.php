@extends('layouts.master')

@section('title', 'تفاصيل رسالة WhatsApp')
@section('page_title', 'تفاصيل رسالة')
@section('page_subtitle', $message->whatsapp_message_id ?? $message->id)

@push('styles')
<style>
    .info-card { background:#fff; border-radius:14px; box-shadow:0 1px 3px rgba(15,23,42,.04); border:1px solid var(--brand-border); overflow:hidden; margin-bottom:1rem; }
    .info-card .head { padding:.85rem 1.1rem; border-bottom:1px solid var(--brand-border); background:linear-gradient(180deg,#fafbff,#f1f5f9); }
    .info-card .head h6 { margin:0; color:var(--brand-navy); font-weight:800; }
    .info-card .body { padding:1.25rem; }
    .kv { display:flex; justify-content:space-between; padding:.5rem 0; border-bottom:1px dashed #e2e8f0; font-size:.86rem; }
    .kv:last-child { border-bottom:none; }
    .kv .k { color:#64748b; font-weight:600; }
    .kv .v { color:#0f172a; font-weight:700; text-align:end; }

    .wa-bubble {
        max-width:75%; padding:.85rem 1.1rem; border-radius:14px;
        background:#dcfce7; color:#0f172a; position:relative; margin-bottom:.5rem;
        white-space:pre-wrap; word-wrap:break-word;
    }
    .wa-bubble.inbound { background:#fff; border:1px solid #e2e8f0; margin-right:auto; }
    .wa-bubble.outbound { background:#dcfce7; margin-left:auto; }
    .wa-bubble .meta { font-size:.7rem; color:#64748b; margin-top:.4rem; text-align:end; }

    /* Timeline */
    .status-track { display:flex; gap:.4rem; margin:1rem 0; }
    .status-step { flex:1; padding:.45rem; text-align:center; border-radius:6px; font-size:.72rem; font-weight:700; background:#f1f5f9; color:#94a3b8; }
    .status-step.done    { background:#dcfce7; color:#15803d; }
    .status-step.current { background:linear-gradient(135deg, #fef3c7, #fde68a); color:#92400e; }
    .status-step.failed  { background:#fee2e2; color:#b91c1c; }

    .bg-success-soft { background:#dcfce7 !important; color:#15803d !important; }
    .bg-warning-soft { background:#fef3c7 !important; color:#b45309 !important; }
    .bg-info-soft    { background:#dbeafe !important; color:#1d4ed8 !important; }
    .bg-primary-soft { background:#e0e7ff !important; color:#4338ca !important; }
    .bg-secondary-soft { background:#f1f5f9 !important; color:#475569 !important; }
    .bg-danger-soft  { background:#fee2e2 !important; color:#b91c1c !important; }
</style>
@endpush

@section('content')

<div class="row g-3">
    <div class="col-lg-8">
        <div class="info-card">
            <div class="head"><h6><i class="bi bi-chat-quote"></i> الرسالة</h6></div>
            <div class="body">
                <div class="wa-bubble {{ $message->direction }}">
                    @if($message->message_type === 'template')
                        <strong>[قالب: {{ $message->template_name }}]</strong>
                        @if($message->template_params)
                            <div class="small mt-2"><strong>المتغيرات:</strong>
                                <ol class="ps-3 mt-1 mb-0">
                                    @foreach($message->template_params as $i => $param)
                                        <li>{{ $param }}</li>
                                    @endforeach
                                </ol>
                            </div>
                        @endif
                        @if($message->body)
                            <div class="mt-2 small">{{ $message->body }}</div>
                        @endif
                    @else
                        {!! nl2br(e($message->body)) !!}
                    @endif
                    <div class="meta">
                        {{ $message->direction === 'outbound' ? 'إلى' : 'من' }}
                        <span dir="ltr">{{ $message->direction === 'outbound' ? $message->to_phone : $message->from_phone }}</span>
                        • {{ $message->created_at?->format('Y-m-d H:i') }}
                    </div>
                </div>
            </div>
        </div>

        {{-- Status timeline --}}
        @if($message->direction === 'outbound')
        <div class="info-card">
            <div class="head"><h6><i class="bi bi-bar-chart-steps"></i> حالة التسليم</h6></div>
            <div class="body">
                @php
                    $stages = ['queued', 'sent', 'delivered', 'read'];
                    $currentIdx = array_search($message->status, $stages);
                    $isFailed = $message->status === 'failed';
                @endphp
                <div class="status-track">
                    @foreach(['queued'=>'في الانتظار','sent'=>'مُرسلة','delivered'=>'تم التسليم','read'=>'تمت القراءة'] as $key => $label)
                        @php
                            $idx = array_search($key, $stages);
                            if ($isFailed) { $cls = 'failed'; }
                            elseif ($idx < $currentIdx) { $cls = 'done'; }
                            elseif ($idx === $currentIdx) { $cls = 'current'; }
                            else { $cls = ''; }
                        @endphp
                        <div class="status-step {{ $cls }}">{{ $label }}</div>
                    @endforeach
                </div>

                @if($isFailed && $message->error_message)
                    <div class="alert alert-danger mt-3 mb-0">
                        <strong><i class="bi bi-x-octagon"></i> سبب الفشل
                            @if($message->error_code) (كود {{ $message->error_code }})@endif:
                        </strong>
                        {{ $message->error_message }}
                    </div>
                @endif
            </div>
        </div>
        @endif
    </div>

    <div class="col-lg-4">
        <div class="info-card">
            <div class="head"><h6><i class="bi bi-info-circle"></i> التفاصيل</h6></div>
            <div class="body">
                <div class="kv"><span class="k">الاتجاه</span>
                    <span class="v">
                        @if($message->direction === 'outbound')
                            <span class="badge bg-info-soft"><i class="bi bi-send"></i> صادر</span>
                        @else
                            <span class="badge bg-success-soft"><i class="bi bi-inbox"></i> وارد</span>
                        @endif
                    </span>
                </div>
                <div class="kv"><span class="k">النوع</span><span class="v">{{ $message->type_label }}</span></div>
                <div class="kv"><span class="k">الحالة</span>
                    <span class="v"><span class="badge bg-{{ $message->status_badge }}-soft">{{ $message->status_label }}</span></span>
                </div>
                <div class="kv"><span class="k">إلى</span><span class="v" dir="ltr">{{ $message->to_phone }}</span></div>
                @if($message->from_phone)
                <div class="kv"><span class="k">من</span><span class="v" dir="ltr">{{ $message->from_phone }}</span></div>
                @endif
                <div class="kv"><span class="k">WAMID</span><span class="v" dir="ltr"><code class="x-small">{{ $message->whatsapp_message_id ?? '—' }}</code></span></div>

                <div class="kv"><span class="k">أُنشئت</span><span class="v">{{ $message->created_at?->format('Y-m-d H:i:s') }}</span></div>
                @if($message->sent_at)
                <div class="kv"><span class="k">أُرسلت</span><span class="v">{{ $message->sent_at->format('Y-m-d H:i:s') }}</span></div>
                @endif
                @if($message->delivered_at)
                <div class="kv"><span class="k">سُلّمت</span><span class="v">{{ $message->delivered_at->format('Y-m-d H:i:s') }}</span></div>
                @endif
                @if($message->read_at)
                <div class="kv"><span class="k">قُرئت</span><span class="v">{{ $message->read_at->format('Y-m-d H:i:s') }}</span></div>
                @endif
                @if($message->failed_at)
                <div class="kv"><span class="k">فشلت</span><span class="v text-danger">{{ $message->failed_at->format('Y-m-d H:i:s') }}</span></div>
                @endif
            </div>
        </div>

        @if($related)
        <div class="info-card">
            <div class="head"><h6><i class="bi bi-link-45deg"></i> المصدر المرتبط</h6></div>
            <div class="body">
                <div class="kv"><span class="k">النوع</span><span class="v">{{ $message->related_type }}</span></div>
                <div class="kv"><span class="k">معرّف</span><span class="v"><code class="x-small">{{ $related->code ?? $related->booking_number ?? $message->related_id }}</code></span></div>
                @if(method_exists($related, 'full_name') || isset($related->full_name))
                <div class="kv"><span class="k">الاسم</span><span class="v">{{ $related->full_name }}</span></div>
                @endif
            </div>
        </div>
        @endif

        <a href="{{ route('admin.crm.whatsapp.messages.index') }}" class="btn btn-outline-secondary w-100">
            <i class="bi bi-arrow-right"></i> العودة للسجل
        </a>
    </div>
</div>

@endsection
