@extends('layouts.master')

@section('title', $lead->code . ' — ' . $lead->full_name)
@section('page_title', $lead->full_name)
@section('page_subtitle', 'Lead ' . $lead->code . ' — ' . $lead->status_label)

@push('styles')
<style>
    .info-card { background:#fff; border-radius:14px; box-shadow:0 1px 3px rgba(15,23,42,.04); border:1px solid var(--brand-border); overflow:hidden; margin-bottom:1rem; }
    .info-card .head { padding:.85rem 1.1rem; border-bottom:1px solid var(--brand-border); background:linear-gradient(180deg,#fafbff,#f1f5f9); display:flex; align-items:center; justify-content:space-between; }
    .info-card .head h6 { margin:0; color:var(--brand-navy); font-weight:800; display:inline-flex; align-items:center; gap:.5rem; }
    .info-card .body { padding:1.1rem; }
    .kv { display:flex; justify-content:space-between; padding:.45rem 0; border-bottom:1px dashed #e2e8f0; font-size:.86rem; }
    .kv:last-child { border-bottom:none; }
    .kv .k { color:#64748b; font-weight:600; }
    .kv .v { color:#0f172a; font-weight:700; text-align:end; }

    /* Hero summary */
    .lead-hero { background:linear-gradient(135deg, #1e3a8a 0%, #312e81 100%); color:#fff; border-radius:18px; padding:1.6rem; margin-bottom:1rem; box-shadow:0 10px 25px rgba(30, 58, 138, 0.2); }
    .lead-hero h3 { margin:0; font-weight:800; font-size:1.6rem; }
    .lead-hero .meta { display:flex; gap:1rem; margin-top:.85rem; flex-wrap:wrap; }
    .lead-hero .badge-mega { font-size:.78rem; padding:.4rem .85rem; border-radius:8px; font-weight:700; background:rgba(255,255,255,.18); color:#fff; }
    .lead-hero .value-tag { background:rgba(255,255,255,.95); color:#92400e; font-weight:800; padding:.55rem 1rem; border-radius:10px; font-size:1.1rem; }

    /* Timeline */
    .timeline { position:relative; padding-right:2rem; }
    .timeline::before { content:''; position:absolute; right:.7rem; top:.5rem; bottom:.5rem; width:2px; background:#e2e8f0; }
    .timeline-item { position:relative; padding-bottom:1.1rem; }
    .timeline-item:last-child { padding-bottom:0; }
    .timeline-icon {
        position:absolute; right:-1.7rem; top:.1rem;
        width:1.6rem; height:1.6rem; border-radius:50%;
        background:#fff; border:2px solid #cbd5e1;
        display:flex; align-items:center; justify-content:center;
        font-size:.7rem; color:#64748b; z-index:1;
    }
    .timeline-icon.t-call          { border-color:#1d4ed8; color:#1d4ed8; }
    .timeline-icon.t-whatsapp      { border-color:#15803d; color:#15803d; background:#dcfce7; }
    .timeline-icon.t-email         { border-color:#6b21a8; color:#6b21a8; }
    .timeline-icon.t-meeting       { border-color:#b45309; color:#b45309; }
    .timeline-icon.t-visit         { border-color:#0f766e; color:#0f766e; }
    .timeline-icon.t-note          { border-color:#475569; color:#475569; }
    .timeline-icon.t-status_change { border-color:#9333ea; color:#9333ea; }
    .timeline-icon.t-sms           { border-color:#0ea5e9; color:#0ea5e9; }

    .timeline-body {
        background:#f8fafc; border-radius:10px; padding:.75rem 1rem;
        border:1px solid #f1f5f9;
    }
    .timeline-head { display:flex; justify-content:space-between; align-items:center; margin-bottom:.3rem; font-size:.82rem; font-weight:700; color:var(--brand-navy); }
    .timeline-meta { font-size:.7rem; color:#94a3b8; }
    .timeline-body p { margin:0; font-size:.85rem; color:#334155; }
    .timeline-followup { margin-top:.4rem; font-size:.72rem; padding:.25rem .55rem; border-radius:6px; display:inline-block; background:#fef3c7; color:#92400e; font-weight:700; }
    .timeline-followup.done { background:#dcfce7; color:#15803d; }
    .timeline-followup.overdue { background:#fee2e2; color:#b91c1c; }

    .x-small { font-size:.7rem; }

    .bg-success-soft   { background:#dcfce7 !important; color:#15803d !important; }
    .bg-info-soft      { background:#dbeafe !important; color:#1d4ed8 !important; }
    .bg-warning-soft   { background:#fef3c7 !important; color:#b45309 !important; }
    .bg-primary-soft   { background:#e0e7ff !important; color:#4338ca !important; }
    .bg-secondary-soft { background:#f1f5f9 !important; color:#475569 !important; }
    .bg-danger-soft    { background:#fee2e2 !important; color:#b91c1c !important; }
</style>
@endpush

@section('content')

{{-- Hero --}}
<div class="lead-hero">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
        <div>
            <h3>{{ $lead->full_name }}</h3>
            <div class="meta">
                <span class="badge-mega"><i class="bi bi-hash"></i> {{ $lead->code }}</span>
                <span class="badge-mega"><i class="bi bi-tag"></i> {{ $lead->source_label }}</span>
                <span class="badge-mega"><i class="bi bi-bullseye"></i> {{ $lead->interest_label }}</span>
                <span class="badge-mega bg-{{ $lead->status_badge }}" style="background:rgba(255,255,255,.3) !important;">
                    {{ $lead->status_label }}
                </span>
                @if($lead->assignee)
                    <span class="badge-mega"><i class="bi bi-person"></i> {{ $lead->assignee->name }}</span>
                @endif
            </div>
        </div>
        @if($lead->estimated_value > 0)
        <div class="value-tag">
            <i class="bi bi-cash-coin"></i> {{ number_format($lead->estimated_value, 0) }} ج.م
        </div>
        @endif
    </div>
</div>

{{-- Action buttons --}}
<div class="info-card">
    <div class="body py-3">
        <div class="d-flex gap-2 flex-wrap">
            @if($lead->whatsapp)
                @can('whatsapp.send')
                <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#whatsappSendModal">
                    <i class="bi bi-whatsapp"></i> إرسال WhatsApp
                </button>
                @endcan
                <a href="https://wa.me/{{ preg_replace('/\D/', '', $lead->whatsapp) }}" target="_blank"
                   class="btn btn-outline-success btn-sm" title="فتح في تطبيق WhatsApp">
                    <i class="bi bi-box-arrow-up-right"></i>
                </a>
            @endif
            <a href="tel:{{ $lead->phone }}" class="btn btn-primary btn-sm">
                <i class="bi bi-telephone"></i> اتصال
            </a>
            @if($lead->email)
                <a href="mailto:{{ $lead->email }}" class="btn btn-info btn-sm">
                    <i class="bi bi-envelope"></i> بريد
                </a>
            @endif

            @can('leads.activities.create')
            <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#activityModal">
                <i class="bi bi-plus-circle"></i> تسجيل نشاط
            </button>
            @endcan

            @can('leads.convert')
            @if(!$lead->isConverted())
                <form action="{{ route('admin.crm.leads.convert', $lead) }}" method="POST" class="d-inline"
                      onsubmit="return confirm('سيتم إنشاء عميل بنفس البيانات ووضع الـ Lead في حالة فائز. متأكد؟');">
                    @csrf
                    <button type="submit" class="btn btn-warning btn-sm">
                        <i class="bi bi-person-plus"></i> تحويل لعميل
                    </button>
                </form>
            @else
                <a href="{{ route('admin.customers.show', $lead->customer) }}" class="btn btn-outline-success btn-sm">
                    <i class="bi bi-check-circle"></i> تم التحويل — عرض العميل
                </a>
            @endif
            @endcan

            @can('leads.update')
            <a href="{{ route('admin.crm.leads.edit', $lead) }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-pencil"></i> تعديل
            </a>
            @endcan
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        {{-- Activity Timeline --}}
        <div class="info-card">
            <div class="head">
                <h6><i class="bi bi-clock-history text-primary"></i> سجل النشاطات ({{ $lead->activities->count() }})</h6>
            </div>
            <div class="body">
                @if($lead->activities->isEmpty())
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-inbox" style="font-size:2rem;"></i>
                        <p class="mt-2 mb-0">لم تُسجَّل أي نشاطات بعد</p>
                        @can('leads.activities.create')
                        <small>اضغط "تسجيل نشاط" لإضافة أول مكالمة أو ملاحظة</small>
                        @endcan
                    </div>
                @else
                    <div class="timeline">
                        @foreach($lead->activities as $act)
                            <div class="timeline-item">
                                <div class="timeline-icon t-{{ $act->type }}">
                                    <i class="bi bi-{{ $act->type_icon }}"></i>
                                </div>
                                <div class="timeline-body">
                                    <div class="timeline-head">
                                        <span>
                                            {{ $act->type_label }}
                                            @if($act->subject) — {{ $act->subject }} @endif
                                            @if($act->outcome)
                                                <span class="badge bg-{{ match($act->outcome) {
                                                    'positive' => 'success', 'negative' => 'danger',
                                                    'no_answer' => 'secondary', 'follow_up' => 'warning',
                                                    default => 'info'
                                                } }}-soft x-small ms-1">{{ $act->outcome_label }}</span>
                                            @endif
                                        </span>
                                        <span class="timeline-meta">
                                            {{ $act->creator?->name ?? '—' }} • {{ $act->created_at?->diffForHumans() }}
                                        </span>
                                    </div>
                                    @if($act->body)
                                        <p>{{ $act->body }}</p>
                                    @endif
                                    @if($act->next_action_date)
                                        @php
                                            $cls = $act->next_action_done ? 'done' : ($act->isFollowUpDue() ? 'overdue' : '');
                                        @endphp
                                        <div class="timeline-followup {{ $cls }}">
                                            <i class="bi bi-{{ $act->next_action_done ? 'check-circle' : 'bell' }}"></i>
                                            متابعة: {{ $act->next_action_date->format('Y-m-d') }}
                                            @if($act->next_action_done)
                                                — مُكتمل
                                            @elseif($act->isFollowUpDue())
                                                — متأخر
                                            @endif

                                            @if(!$act->next_action_done && auth()->user()?->can('leads.activities.create'))
                                                <form action="{{ route('admin.crm.leads.activities.mark_done', [$lead, $act]) }}"
                                                      method="POST" class="d-inline ms-2">
                                                    @csrf
                                                    <button class="btn btn-sm btn-link p-0" type="submit" title="تأكيد الإتمام">
                                                        <i class="bi bi-check2"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        {{-- Contact details --}}
        <div class="info-card">
            <div class="head"><h6><i class="bi bi-person-vcard"></i> بيانات التواصل</h6></div>
            <div class="body">
                <div class="kv"><span class="k">الهاتف</span><span class="v" dir="ltr">{{ $lead->phone }}</span></div>
                @if($lead->whatsapp && $lead->whatsapp !== $lead->phone)
                <div class="kv"><span class="k">واتساب</span><span class="v" dir="ltr">{{ $lead->whatsapp }}</span></div>
                @endif
                @if($lead->email)
                <div class="kv"><span class="k">البريد</span><span class="v" dir="ltr">{{ $lead->email }}</span></div>
                @endif
                @if($lead->city)
                <div class="kv"><span class="k">المدينة</span><span class="v">{{ $lead->city }}</span></div>
                @endif
            </div>
        </div>

        {{-- Sales meta --}}
        <div class="info-card">
            <div class="head"><h6><i class="bi bi-graph-up"></i> بيانات المبيعات</h6></div>
            <div class="body">
                <div class="kv"><span class="k">المصدر</span><span class="v">{{ $lead->source_label }}</span></div>
                <div class="kv"><span class="k">الاهتمام</span><span class="v">{{ $lead->interest_label }}</span></div>
                <div class="kv"><span class="k">المسؤول</span><span class="v">{{ $lead->assignee?->name ?? '—' }}</span></div>
                <div class="kv"><span class="k">القيمة المتوقعة</span><span class="v">{{ number_format($lead->estimated_value, 0) }} ج.م</span></div>
                @if($lead->expected_close_date)
                <div class="kv"><span class="k">الإغلاق المتوقع</span><span class="v">{{ $lead->expected_close_date->format('Y-m-d') }}</span></div>
                @endif
                @if($lead->status === 'lost' && $lead->lost_reason)
                <div class="kv"><span class="k text-danger">سبب الخسارة</span><span class="v">{{ $lead->lost_reason }}</span></div>
                @endif
                <div class="kv"><span class="k">منذ</span><span class="v">{{ $lead->created_at?->diffForHumans() }}</span></div>
            </div>
        </div>

        @if($lead->notes)
        <div class="info-card">
            <div class="head"><h6><i class="bi bi-sticky"></i> ملاحظات</h6></div>
            <div class="body small">{!! nl2br(e($lead->notes)) !!}</div>
        </div>
        @endif

        @if($lead->opportunities->isNotEmpty())
        <div class="info-card">
            <div class="head"><h6><i class="bi bi-briefcase"></i> الصفقات</h6></div>
            <div class="body">
                @foreach($lead->opportunities as $opp)
                    <div class="kv">
                        <span class="k">
                            <code>{{ $opp->code }}</code>
                            <span class="badge bg-{{ $opp->stage_badge }}-soft">{{ $opp->stage_label }}</span>
                        </span>
                        <span class="v">{{ number_format($opp->estimated_value, 0) }} ج.م</span>
                    </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>

{{-- Activity modal --}}
{{-- WhatsApp send modal --}}
@can('whatsapp.send')
    @if($lead->whatsapp)
        @include('admin.crm.whatsapp._send_modal', [
            'waToPhone'     => $lead->whatsapp,
            'waRelatedType' => 'lead',
            'waRelatedId'   => $lead->id,
            'waDefaultText' => 'مرحباً ' . $lead->full_name . '،',
        ])
    @endif
@endcan

@can('leads.activities.create')
<div class="modal fade" id="activityModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form class="modal-content" action="{{ route('admin.crm.leads.activities.store', $lead) }}" method="POST">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-plus-circle text-primary"></i> تسجيل نشاط جديد</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">نوع النشاط <span class="text-danger">*</span></label>
                        <select name="type" class="form-select" required>
                            <option value="call">📞 مكالمة</option>
                            <option value="whatsapp">💬 واتساب</option>
                            <option value="email">✉️ بريد إلكتروني</option>
                            <option value="sms">📱 رسالة نصية</option>
                            <option value="meeting">🤝 اجتماع</option>
                            <option value="visit">🚪 زيارة</option>
                            <option value="note">📝 ملاحظة</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">الموضوع</label>
                        <input type="text" name="subject" class="form-control" maxlength="200" placeholder="مثال: مكالمة أولى، استفسار عن البرنامج...">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">النتيجة</label>
                        <select name="outcome" class="form-select">
                            <option value="">— غير محدد —</option>
                            <option value="positive">إيجابي</option>
                            <option value="neutral">محايد</option>
                            <option value="negative">سلبي</option>
                            <option value="no_answer">لم يرد</option>
                            <option value="follow_up">متابعة لاحقة</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">موعد المتابعة القادمة</label>
                        <input type="date" name="next_action_date" class="form-control">
                    </div>

                    <div class="col-12">
                        <label class="form-label">التفاصيل</label>
                        <textarea name="body" rows="4" class="form-control" maxlength="2000" placeholder="ملخص ما تم في النشاط..."></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">تراجع</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> حفظ</button>
            </div>
        </form>
    </div>
</div>
@endcan

@endsection
