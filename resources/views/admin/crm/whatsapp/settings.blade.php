@extends('layouts.master')

@section('title', 'إعدادات WhatsApp Cloud API')
@section('page_title', 'إعدادات WhatsApp Cloud API')
@section('page_subtitle', 'ضبط الاتصال بـ Meta WhatsApp Business Cloud API')

@push('styles')
<style>
    .info-card { background:#fff; border-radius:14px; box-shadow:0 1px 3px rgba(15,23,42,.04); border:1px solid var(--brand-border); overflow:hidden; margin-bottom:1rem; }
    .info-card .head { padding:.85rem 1.1rem; border-bottom:1px solid var(--brand-border); background:linear-gradient(180deg,#fafbff,#f1f5f9); }
    .info-card .head h6 { margin:0; color:var(--brand-navy); font-weight:800; display:inline-flex; align-items:center; gap:.5rem; }
    .info-card .body { padding:1.25rem; }
    .form-label { font-size:.82rem; font-weight:700; color:#475569; margin-bottom:.4rem; }
    .form-label .hint { font-size:.68rem; color:#94a3b8; font-weight:500; margin-right:.35rem; }
    .form-control, .form-select { font-size:.9rem; border-radius:11px; border:1.5px solid #e2e8f0; }
    .form-control:focus { border-color:#25D366; box-shadow:0 0 0 .2rem rgba(37, 211, 102, 0.15); }

    .wa-banner {
        background: linear-gradient(135deg, #25D366 0%, #128C7E 100%);
        color:#fff; border-radius:18px; padding:1.5rem; margin-bottom:1rem;
        box-shadow:0 10px 25px rgba(37, 211, 102, 0.25);
    }
    .wa-banner h4 { margin:0 0 .35rem; font-weight:800; }
    .wa-banner p  { margin:0; opacity:.95; font-size:.88rem; }
    .wa-banner .status-badge {
        background:rgba(255,255,255,.2); padding:.4rem .85rem; border-radius:8px;
        font-weight:700; font-size:.82rem;
    }

    .webhook-box {
        background:#f8fafc; border:2px dashed #cbd5e1; border-radius:10px;
        padding:1rem; font-family:'Cairo', monospace;
    }
    .webhook-box code { color:#0f172a; font-weight:700; word-break:break-all; }

    .copy-btn { background:#e0e7ff; color:#4338ca; border:none; padding:.25rem .6rem; border-radius:6px; font-size:.7rem; font-weight:700; cursor:pointer; }
    .copy-btn:hover { background:#c7d2fe; }
</style>
@endpush

@section('content')

<div class="wa-banner d-flex justify-content-between align-items-center flex-wrap gap-3">
    <div>
        <h4><i class="bi bi-whatsapp"></i> WhatsApp Business Cloud API</h4>
        <p>اتكامل مباشر مع Meta للإرسال والاستقبال</p>
    </div>
    @if($isConfigured)
        <span class="status-badge"><i class="bi bi-check-circle-fill"></i> الإعدادات مكتملة</span>
    @else
        <span class="status-badge"><i class="bi bi-exclamation-triangle-fill"></i> الإعدادات ناقصة</span>
    @endif
</div>

<form action="{{ route('admin.crm.whatsapp.settings.update') }}" method="POST">
    @csrf @method('PUT')

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="info-card">
                <div class="head"><h6><i class="bi bi-key"></i> بيانات الاعتماد</h6></div>
                <div class="body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Access Token <span class="hint">long-lived من Meta</span></label>
                            <input type="password" name="access_token" class="form-control" dir="ltr"
                                   placeholder="EAAxxxxx..."
                                   value="{{ $settings['access_token'] }}">
                            <small class="text-muted">يبقى مشفر في DB. اتركه فاضي للحفاظ على القيمة الحالية.</small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Phone Number ID</label>
                            <input type="text" name="phone_number_id" class="form-control" dir="ltr"
                                   placeholder="123456789012345"
                                   value="{{ $settings['phone_number_id'] }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">WhatsApp Business Account ID <span class="hint">WABA</span></label>
                            <input type="text" name="business_account_id" class="form-control" dir="ltr"
                                   placeholder="123456789012345"
                                   value="{{ $settings['business_account_id'] }}">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">API Version</label>
                            <input type="text" name="api_version" class="form-control" dir="ltr"
                                   value="{{ $settings['api_version'] }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">لغة القوالب الافتراضية</label>
                            <select name="default_language" class="form-select">
                                <option value="ar" {{ $settings['default_language'] === 'ar' ? 'selected' : '' }}>العربية (ar)</option>
                                <option value="en" {{ $settings['default_language'] === 'en' ? 'selected' : '' }}>English (en)</option>
                                <option value="ar_EG" {{ $settings['default_language'] === 'ar_EG' ? 'selected' : '' }}>عربية مصرية (ar_EG)</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="info-card">
                <div class="head"><h6><i class="bi bi-bell"></i> قوالب الإشعارات التلقائية</h6></div>
                <div class="body">
                    <p class="text-muted small mb-3">
                        اكتب اسم القالب المعتمد من Meta لكل حدث. اتركه فاضي لإيقاف الإشعار التلقائي لذلك الحدث.
                    </p>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">
                                <i class="bi bi-check-circle text-success"></i> تأكيد الحجز
                                <span class="hint">يُرسل عند تأكيد حجز ديني/داخلي</span>
                            </label>
                            <input type="text" name="tpl_booking_confirmed" class="form-control" dir="ltr"
                                   placeholder="booking_confirmed"
                                   value="{{ $settings['tpl_booking_confirmed'] }}">
                            <small class="text-muted">المتغيرات: 1=اسم العميل, 2=رقم الحجز, 3=تاريخ السفر, 4=سعر البيع</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">
                                <i class="bi bi-cash-coin text-primary"></i> استلام دفعة
                                <span class="hint">يُرسل عند تسجيل أي دفعة</span>
                            </label>
                            <input type="text" name="tpl_payment_received" class="form-control" dir="ltr"
                                   placeholder="payment_received"
                                   value="{{ $settings['tpl_payment_received'] }}">
                            <small class="text-muted">المتغيرات: 1=اسم العميل, 2=المبلغ, 3=رقم الإيصال, 4=الرصيد المتبقي</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">
                                <i class="bi bi-arrow-counterclockwise text-warning"></i> صرف استرداد
                                <span class="hint">يُرسل عند تأكيد صرف الاسترداد</span>
                            </label>
                            <input type="text" name="tpl_refund_paid" class="form-control" dir="ltr"
                                   placeholder="refund_paid"
                                   value="{{ $settings['tpl_refund_paid'] }}">
                            <small class="text-muted">المتغيرات: 1=اسم العميل, 2=المبلغ المسترد, 3=السبب</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">
                                <i class="bi bi-clock text-info"></i> تذكير قبل السفر
                                <span class="hint">يُرسل قبل تاريخ السفر بـ 24 ساعة</span>
                            </label>
                            <input type="text" name="tpl_trip_reminder" class="form-control" dir="ltr"
                                   placeholder="trip_reminder"
                                   value="{{ $settings['tpl_trip_reminder'] }}">
                            <small class="text-muted">المتغيرات: 1=اسم العميل, 2=رقم الحجز, 3=تاريخ السفر, 4=الوجهة</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="info-card">
                <div class="head"><h6><i class="bi bi-link-45deg"></i> إعداد Webhook</h6></div>
                <div class="body">
                    <p class="text-muted small mb-3">
                        ضِف الـ URL ده في إعدادات Meta App → WhatsApp → Configuration → Webhook.
                        Meta هتطلب الـ verify token اللي تحت.
                    </p>

                    <label class="form-label">Callback URL</label>
                    <div class="webhook-box mb-3 d-flex align-items-center justify-content-between">
                        <code id="webhookUrl">{{ $webhookUrl }}</code>
                        <button type="button" class="copy-btn" onclick="copyToClipboard('webhookUrl')">
                            <i class="bi bi-clipboard"></i> نسخ
                        </button>
                    </div>

                    <label class="form-label">Verify Token</label>
                    <div class="d-flex gap-2 align-items-center">
                        <input type="text" name="webhook_verify_token" class="form-control" dir="ltr"
                               id="verifyToken"
                               placeholder="random_secret_string"
                               value="{{ $settings['webhook_verify_token'] }}">
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="copyToClipboard('verifyToken')">
                            <i class="bi bi-clipboard"></i>
                        </button>
                    </div>

                    <button type="button" class="btn btn-sm btn-link mt-2 p-0"
                            onclick="if(confirm('سيُولّد token جديد ولن يعمل الـ webhook لحد ما تحدّث Meta')) document.getElementById('regenForm').submit()">
                        <i class="bi bi-arrow-clockwise"></i> توليد verify token جديد
                    </button>

                    <div class="alert alert-info small mt-3 mb-0">
                        <strong><i class="bi bi-info-circle"></i> Webhook fields المطلوبة:</strong>
                        <code>messages</code>, <code>message_status</code>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="info-card">
                <div class="head"><h6><i class="bi bi-bookmarks"></i> دليل سريع</h6></div>
                <div class="body small">
                    <ol class="ps-3 mb-0" style="line-height:1.9;">
                        <li>افتح <a href="https://developers.facebook.com/apps" target="_blank">Meta for Developers</a></li>
                        <li>أنشئ App بنوع <strong>Business</strong></li>
                        <li>أضف منتج <strong>WhatsApp</strong></li>
                        <li>انسخ <em>Phone number ID</em> و <em>WABA ID</em></li>
                        <li>ولّد <em>Access Token</em> طويل العمر (60 يوم → تجديد، أو System User Token)</li>
                        <li>اضبط الـ Webhook على الـ URL أعلاه</li>
                        <li>اشترك في الـ <code>messages</code> + <code>message_status</code></li>
                    </ol>
                </div>
            </div>

            <div class="info-card">
                <div class="body">
                    <button type="submit" class="btn btn-success w-100">
                        <i class="bi bi-save"></i> حفظ الإعدادات
                    </button>
                    <a href="{{ route('admin.crm.whatsapp.messages.index') }}" class="btn btn-outline-secondary w-100 mt-2">
                        <i class="bi bi-list"></i> سجل الرسائل
                    </a>
                </div>
            </div>
        </div>
    </div>
</form>

<form id="regenForm" action="{{ route('admin.crm.whatsapp.settings.regenerate_token') }}" method="POST" class="d-none">
    @csrf
</form>

@endsection

@push('scripts')
<script>
function copyToClipboard(id) {
    const el = document.getElementById(id);
    const text = el.tagName === 'INPUT' ? el.value : el.innerText;
    if (!text) return;
    navigator.clipboard.writeText(text).then(() => {
        if (window.toastr) toastr.success('تم النسخ'); else alert('تم النسخ');
    });
}
</script>
@endpush
