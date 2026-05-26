@php
    /** @var \App\Models\ReligiousBooking|null $booking */
    $booking ??= null;
    $isEdit  = $booking && $booking->exists;
    $sarRate = $sar_rate ?? 0;
@endphp

<style>
    /* ── Wizard layout ─────────────────────────────────────── */
    .wizard-wrap {
        background: linear-gradient(180deg, #f8fafc 0%, #fff 100%);
        border-radius: 18px; padding: 1.25rem;
        box-shadow: 0 2px 8px rgba(15,23,42,.05);
        margin-bottom: 1rem;
    }

    /* ── Step progress bar ─────────────────────────────────── */
    .step-track {
        display: flex; gap: 0; position: relative;
        margin-bottom: 1.75rem; padding: 0 .5rem;
    }
    .step-track::before {
        content: ''; position: absolute;
        top: 22px; right: 24px; left: 24px;
        height: 3px; background: #e2e8f0; z-index: 0;
        border-radius: 2px;
    }
    .step-track .progress-fill {
        position: absolute; top: 22px; right: 24px;
        height: 3px; background: linear-gradient(90deg, #d4a437, #0f172a);
        border-radius: 2px; z-index: 1;
        transition: width .4s ease;
    }
    .step-item {
        flex: 1; position: relative; z-index: 2;
        display: flex; flex-direction: column; align-items: center;
        cursor: pointer;
        transition: transform .15s;
    }
    .step-item:hover { transform: translateY(-2px); }
    .step-item .circle {
        width: 46px; height: 46px; border-radius: 50%;
        background: #fff; border: 3px solid #e2e8f0;
        color: #94a3b8; font-weight: 800; font-size: 1.05rem;
        display: flex; align-items: center; justify-content: center;
        transition: all .3s; box-shadow: 0 1px 4px rgba(15,23,42,.05);
    }
    .step-item.done .circle { background: #15803d; border-color: #15803d; color: #fff; }
    .step-item.done .circle::before { content: '\F26B'; font-family: 'bootstrap-icons'; font-size: 1.3rem; }
    .step-item.done .circle .num { display: none; }
    .step-item.active .circle {
        background: var(--brand-navy); border-color: var(--brand-gold);
        color: #fff; box-shadow: 0 4px 14px rgba(15,23,42,.25);
        transform: scale(1.08);
    }
    .step-item .step-label {
        font-size: .76rem; font-weight: 700; color: #94a3b8;
        margin-top: .55rem; text-align: center; line-height: 1.2;
    }
    .step-item.active .step-label { color: var(--brand-navy); }
    .step-item.done .step-label { color: #15803d; }
    .step-item .step-sub {
        font-size: .65rem; color: #94a3b8; margin-top: 2px;
    }

    /* ── Step content cards ────────────────────────────────── */
    .step-pane { display: none; animation: stepFadeIn .35s ease; }
    .step-pane.active { display: block; }
    @keyframes stepFadeIn {
        from { opacity: 0; transform: translateY(8px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    .step-header {
        display: flex; align-items: center; gap: .75rem;
        margin-bottom: 1.1rem; padding-bottom: .85rem;
        border-bottom: 1px solid #f1f5f9;
    }
    .step-header .step-icon {
        width: 48px; height: 48px; border-radius: 12px;
        background: linear-gradient(135deg, #fef3c7, #fde68a);
        color: #92400e; display: flex; align-items: center; justify-content: center;
        font-size: 1.4rem; flex-shrink: 0;
    }
    .step-header .step-meta h5 {
        margin: 0; color: var(--brand-navy); font-weight: 800;
    }
    .step-header .step-meta p {
        margin: 0; color: #64748b; font-size: .82rem;
    }

    /* ── Form fields ───────────────────────────────────────── */
    .field-group { margin-bottom: 1rem; }
    .field-group .form-label {
        font-size: .82rem; font-weight: 700; color: #475569;
        margin-bottom: .4rem;
    }
    .field-group .form-label .req { color: #dc2626; font-weight: 900; }
    .field-group .form-label .hint {
        font-size: .68rem; color: #94a3b8;
        font-weight: 500; margin-right: .35rem;
    }
    .field-group .form-control,
    .field-group .form-select {
        height: 46px; font-size: .92rem; border-radius: 11px;
        border: 1.5px solid #e2e8f0;
        transition: all .15s; padding-right: 1rem;
    }
    .field-group .form-control:focus,
    .field-group .form-select:focus {
        border-color: var(--brand-gold); box-shadow: 0 0 0 .2rem rgba(212,164,55,.15);
    }
    .field-group textarea.form-control { height: auto; min-height: 80px; }

    /* ── Visual card selection ─────────────────────────────── */
    .option-grid {
        display: grid; gap: .75rem;
        grid-template-columns: repeat(auto-fit, minmax(155px, 1fr));
    }
    .option-card {
        position: relative; overflow: hidden;
        background: #fff;
        border: 2px solid #e2e8f0;
        border-radius: 14px; padding: 1rem .75rem;
        cursor: pointer; text-align: center;
        transition: all .25s cubic-bezier(.4,0,.2,1);
        user-select: none; min-height: 110px;
        display: flex; flex-direction: column; justify-content: center; align-items: center;
    }
    .option-card::before {
        content: ''; position: absolute; inset: 0;
        background: linear-gradient(135deg, rgba(212,164,55,.0) 0%, rgba(212,164,55,.08) 100%);
        opacity: 0; transition: opacity .25s; pointer-events: none;
    }
    .option-card:hover {
        border-color: #d4a437; transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(15,23,42,.10);
    }
    .option-card:hover::before { opacity: 1; }
    .option-card input[type=radio] {
        position: absolute; opacity: 0; pointer-events: none;
    }
    .option-card .opt-icon {
        font-size: 2rem; color: #94a3b8;
        margin-bottom: .4rem; line-height: 1;
        transition: all .25s cubic-bezier(.4,0,.2,1);
        display: inline-flex; align-items: center; justify-content: center;
        width: 52px; height: 52px; border-radius: 14px;
        background: #f8fafc;
    }
    .option-card .opt-title {
        font-weight: 800; font-size: .92rem; color: #1f2937;
        margin-bottom: .15rem;
    }
    .option-card .opt-desc {
        font-size: .7rem; color: #64748b; line-height: 1.3;
        padding: 0 .25rem;
    }
    .option-card.selected {
        border-color: var(--brand-gold);
        background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
        box-shadow: 0 6px 20px rgba(212,164,55,.25);
        transform: translateY(-2px);
    }
    .option-card.selected .opt-icon {
        color: #fff;
        background: linear-gradient(135deg, #d4a437, #b8860b);
        box-shadow: 0 4px 12px rgba(212,164,55,.4);
        transform: scale(1.05);
    }
    .option-card.selected .opt-title { color: #92400e; }
    .option-card.selected::after {
        content: '\F26B'; font-family: 'bootstrap-icons';
        position: absolute; top: 8px; left: 10px;
        color: #fff; background: #15803d;
        width: 22px; height: 22px; border-radius: 50%;
        font-size: .75rem; font-weight: 900;
        display: flex; align-items: center; justify-content: center;
        box-shadow: 0 2px 6px rgba(21,128,61,.35);
        animation: pop .3s cubic-bezier(.4,0,.2,1);
    }
    @keyframes pop {
        0% { transform: scale(0); }
        70% { transform: scale(1.2); }
        100% { transform: scale(1); }
    }

    /* ── Number stepper ────────────────────────────────────── */
    .num-stepper {
        display: inline-flex; align-items: center;
        background: #f8fafc; border: 1.5px solid #e2e8f0;
        border-radius: 11px; overflow: hidden;
    }
    .num-stepper button {
        width: 44px; height: 44px;
        background: transparent; border: none;
        font-size: 1.2rem; color: var(--brand-navy);
        cursor: pointer; font-weight: 700;
        transition: background .15s;
    }
    .num-stepper button:hover { background: #e2e8f0; }
    .num-stepper input {
        width: 60px; text-align: center;
        background: #fff; border: none;
        border-right: 1px solid #e2e8f0;
        border-left: 1px solid #e2e8f0;
        height: 44px; font-weight: 800; color: var(--brand-navy);
        font-size: 1rem;
    }

    /* ── Customer preview card ─────────────────────────────── */
    .customer-preview, .program-preview {
        background: linear-gradient(135deg, #eef2ff, #e0e7ff);
        border-radius: 12px; padding: 1rem;
        margin-top: .75rem; display: none;
        border-right: 4px solid #4338ca;
    }
    .customer-preview.show, .program-preview.show { display: block; animation: stepFadeIn .25s; }
    .customer-preview .name { font-weight: 800; color: var(--brand-navy); font-size: 1.05rem; }
    .customer-preview .meta { font-size: .78rem; color: #475569; margin-top: .25rem; }

    /* ── Children dynamic rows ─────────────────────────────── */
    .children-list { background: #f8fafc; border-radius: 11px; padding: .75rem; }
    .children-list .child-row {
        display: flex; gap: .5rem; align-items: center;
        margin-bottom: .5rem; background: #fff;
        padding: .5rem .65rem; border-radius: 8px;
        border: 1px solid #e2e8f0;
    }
    .children-list .child-row .form-control { height: 36px; font-size: .85rem; }
    .children-list .child-row .child-num {
        background: var(--brand-gold); color: #fff;
        width: 26px; height: 26px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-weight: 800; font-size: .8rem; flex-shrink: 0;
    }

    /* ── Live summary sidebar — receipt-style ──────────────── */
    .live-summary {
        position: sticky; top: 100px;
        background: #fff;
        border-radius: 16px; padding: 0;
        box-shadow: 0 10px 30px rgba(15,23,42,.08), 0 2px 6px rgba(15,23,42,.04);
        overflow: hidden;
        border: 1px solid #f1f5f9;
    }
    .live-summary .summary-head {
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
        color: #fff; padding: 1.1rem 1.25rem;
        position: relative; overflow: hidden;
    }
    .live-summary .summary-head::before {
        content: ''; position: absolute;
        right: -40px; top: -40px;
        width: 140px; height: 140px;
        background: radial-gradient(circle, rgba(212,164,55,.25), transparent);
        border-radius: 50%;
    }
    .live-summary .summary-head h6 {
        margin: 0; color: var(--brand-gold); font-weight: 800;
        font-size: .92rem; display: flex; align-items: center; gap: .5rem;
        position: relative; z-index: 1;
    }
    .live-summary .summary-head .head-sub {
        color: rgba(255,255,255,.7); font-size: .72rem;
        margin-top: .25rem; position: relative; z-index: 1;
    }
    .live-summary .summary-body { padding: 1.1rem 1.25rem 1rem; }
    .live-summary .sum-row {
        display: flex; justify-content: space-between; align-items: center;
        padding: .55rem 0; border-bottom: 1px dashed #e2e8f0;
        font-size: .85rem;
    }
    .live-summary .sum-row:last-child { border-bottom: none; }
    .live-summary .sum-row .lbl {
        color: #64748b; font-weight: 600;
        display: flex; align-items: center; gap: .35rem;
    }
    .live-summary .sum-row .lbl i { color: #94a3b8; font-size: .9rem; }
    .live-summary .sum-row .val {
        font-weight: 800; color: #0f172a;
    }
    .live-summary .sum-row.highlight {
        background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
        padding: 1rem 1.15rem;
        border-radius: 12px; border: 1px solid rgba(212,164,55,.35);
        margin: .85rem -.25rem 0; border-bottom: none;
        box-shadow: 0 4px 12px rgba(212,164,55,.12);
    }
    .live-summary .sum-row.highlight .lbl { color: #92400e; font-weight: 800; }
    .live-summary .sum-row.highlight .val { color: #92400e; font-size: 1.35rem; font-weight: 900; }
    .live-summary .empty-summary {
        text-align: center; padding: 2rem 1rem;
        color: #94a3b8; font-size: .85rem;
    }
    .live-summary .empty-summary .ico {
        width: 64px; height: 64px; border-radius: 50%;
        background: linear-gradient(135deg, #f1f5f9, #e2e8f0);
        color: #64748b; font-size: 1.8rem;
        display: flex; align-items: center; justify-content: center;
        margin: 0 auto .8rem;
    }
    .live-summary .empty-summary .empty-tip {
        font-size: .72rem; color: #cbd5e1;
        margin-top: .5rem; display: block;
    }
    /* Completion ring */
    .live-summary .completion {
        margin-top: 1rem; padding-top: 1rem;
        border-top: 1px dashed #e2e8f0;
        display: flex; align-items: center; gap: .65rem;
    }
    .live-summary .completion .ring {
        width: 38px; height: 38px; flex-shrink: 0;
        border-radius: 50%; background:
            conic-gradient(#15803d 0%, #15803d var(--pct, 0%), #e2e8f0 var(--pct, 0%));
        display: flex; align-items: center; justify-content: center;
        position: relative;
    }
    .live-summary .completion .ring::after {
        content: ''; position: absolute; inset: 4px;
        background: #fff; border-radius: 50%;
    }
    .live-summary .completion .ring span {
        position: relative; z-index: 1;
        font-size: .68rem; font-weight: 800; color: #15803d;
    }
    .live-summary .completion .meta { flex: 1; font-size: .78rem; color: #475569; }
    .live-summary .completion .meta strong { color: #0f172a; }

    /* ── Quick-fill demo button (super-admin only) ─────────── */
    .quick-fill-bar {
        display: flex; align-items: center; gap: .75rem;
        background: linear-gradient(135deg, #fef3c7, #fde68a);
        border: 1.5px dashed #d4a437; border-radius: 12px;
        padding: .65rem .9rem; margin-bottom: 1rem;
    }
    .quick-fill-bar .qf-text { flex: 1; font-size: .8rem; color: #92400e; font-weight: 700; }
    .quick-fill-bar .qf-text small { display: block; color: #b45309; font-weight: 500; }
    .quick-fill-bar .btn { white-space: nowrap; }

    /* ── Wizard footer ─────────────────────────────────────── */
    .wizard-footer {
        display: flex; justify-content: space-between; align-items: center;
        padding-top: 1.25rem; margin-top: 1.25rem;
        border-top: 1px solid #f1f5f9;
        gap: .5rem; flex-wrap: wrap;
    }
    .wizard-footer .btn { min-width: 130px; }
    .wizard-footer .step-counter {
        font-size: .82rem; color: #64748b; font-weight: 600;
    }
    .wizard-footer .step-counter strong { color: var(--brand-navy); font-weight: 800; }

    /* ── Review summary ────────────────────────────────────── */
    .review-section {
        background: #f8fafc; border-radius: 12px;
        padding: 1rem 1.15rem; margin-bottom: .85rem;
    }
    .review-section h6 {
        color: var(--brand-navy); font-weight: 800; font-size: .85rem;
        margin-bottom: .55rem; display: flex; justify-content: space-between; align-items: center;
    }
    .review-section h6 .edit-link {
        font-size: .72rem; color: #1d4ed8; font-weight: 600;
        text-decoration: none; cursor: pointer;
    }
    .review-section .review-grid {
        display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: .65rem;
    }
    .review-section .rev-kv { font-size: .82rem; }
    .review-section .rev-kv .k { color: #64748b; font-size: .7rem; display: block; }
    .review-section .rev-kv .v { color: #0f172a; font-weight: 700; }

    /* ── Responsive ────────────────────────────────────────── */
    @media (max-width: 991.98px) {
        .live-summary { position: static; margin-bottom: 1rem; }
        .step-track { padding: 0; gap: 4px; overflow-x: auto; }
        .step-item .step-sub { display: none; }
        .step-item .step-label { font-size: .68rem; }
        .step-item .circle { width: 38px; height: 38px; font-size: .9rem; }
        .step-track::before { top: 18px; }
        .step-track .progress-fill { top: 18px; }
    }
    @media (max-width: 575.98px) {
        .wizard-wrap { padding: .85rem; border-radius: 14px; }
        .step-header .step-icon { width: 40px; height: 40px; font-size: 1.15rem; }
        .step-header .step-meta h5 { font-size: 1rem; }
        .option-grid { grid-template-columns: repeat(2, 1fr); }
        .wizard-footer .btn { min-width: auto; flex: 1; }
    }
</style>

<div class="row g-3">
    {{-- ════════════════════════════════════════════════════════════
         Wizard left column (form)
         ════════════════════════════════════════════════════════════ --}}
    <div class="col-lg-8">
        <div class="wizard-wrap">
            @if(!$isEdit && auth()->user()?->hasRole('super-admin'))
            <div class="quick-fill-bar">
                <i class="bi bi-magic" style="font-size:1.5rem; color:#92400e;"></i>
                <div class="qf-text">
                    وضع المطور — املأ الفورم ببيانات تجريبية صحيحة بضغطة زر
                    <small>يظهر للسوبر أدمن فقط — للاختبار السريع</small>
                </div>
                <button type="button" id="quickFillBtn" class="btn btn-sm btn-warning fw-bold">
                    <i class="bi bi-lightning-charge-fill"></i> ملء تلقائي
                </button>
            </div>
            @endif

            {{-- Progress steps --}}
            <div class="step-track" id="stepTrack">
                <div class="progress-fill" id="progressFill" style="width: 0;"></div>
                <div class="step-item active" data-step="1">
                    <div class="circle"><span class="num">1</span></div>
                    <div class="step-label">العميل والبرنامج</div>
                    <div class="step-sub">من + ماذا</div>
                </div>
                <div class="step-item" data-step="2">
                    <div class="circle"><span class="num">2</span></div>
                    <div class="step-label">الرحلة</div>
                    <div class="step-sub">متى + المدة</div>
                </div>
                <div class="step-item" data-step="3">
                    <div class="circle"><span class="num">3</span></div>
                    <div class="step-label">الأفراد</div>
                    <div class="step-sub">كم شخص</div>
                </div>
                <div class="step-item" data-step="4">
                    <div class="circle"><span class="num">4</span></div>
                    <div class="step-label">الإعدادات</div>
                    <div class="step-sub">تأشيرة + سكن</div>
                </div>
                <div class="step-item" data-step="5">
                    <div class="circle"><span class="num">5</span></div>
                    <div class="step-label">المالية</div>
                    <div class="step-sub">السعر</div>
                </div>
                <div class="step-item" data-step="6">
                    <div class="circle"><span class="num">6</span></div>
                    <div class="step-label">المراجعة</div>
                    <div class="step-sub">حفظ</div>
                </div>
            </div>

            {{-- ── Step 1: Customer & Program ──────────────── --}}
            <div class="step-pane active" data-pane="1">
                <div class="step-header">
                    <div class="step-icon"><i class="bi bi-person-vcard"></i></div>
                    <div class="step-meta">
                        <h5>اختر العميل والبرنامج</h5>
                        <p>ابدأ بتحديد العميل صاحب الحجز وبرنامج الرحلة المناسب</p>
                    </div>
                </div>

                <div class="field-group">
                    <label class="form-label">
                        نوع الرحلة <span class="req">*</span>
                        <span class="hint">حدد نوع الرحلة الدينية</span>
                    </label>
                    <div class="option-grid" data-options="type">
                        <label class="option-card {{ old('type', $booking?->type ?? '') === 'umrah' ? 'selected' : '' }}">
                            <input type="radio" name="type" value="umrah" {{ old('type', $booking?->type ?? '') === 'umrah' ? 'checked' : '' }}>
                            <div class="opt-icon"><i class="bi bi-moon-stars"></i></div>
                            <div class="opt-title">عمرة</div>
                            <div class="opt-desc">رحلة عمرة قصيرة 10-14 يوم</div>
                        </label>
                        <label class="option-card {{ old('type', $booking?->type ?? '') === 'hajj' ? 'selected' : '' }}">
                            <input type="radio" name="type" value="hajj" {{ old('type', $booking?->type ?? '') === 'hajj' ? 'checked' : '' }}>
                            <div class="opt-icon" style="font-family:'Apple Color Emoji','Segoe UI Emoji','Noto Color Emoji',sans-serif;">🕋</div>
                            <div class="opt-title">حج</div>
                            <div class="opt-desc">موسم الحج 21-25 يوم</div>
                        </label>
                    </div>
                </div>

                <div class="field-group">
                    <label class="form-label">
                        العميل <span class="req">*</span>
                        <span class="hint">ابحث بالاسم أو رقم الهاتف</span>
                    </label>
                    <select name="customer_id" id="customerSelect" class="form-select select2" data-placeholder="ابحث عن عميل..." required>
                        <option value=""></option>
                        @foreach($customers as $c)
                            <option value="{{ $c->id }}"
                                data-name="{{ $c->full_name }}"
                                data-phone="{{ $c->phone }}"
                                data-code="{{ $c->code }}"
                                @selected(old('customer_id', $booking?->customer_id ?? request('customer_id')) === $c->id)>
                                {{ $c->full_name }} — {{ $c->phone }}
                            </option>
                        @endforeach
                    </select>
                    <div class="customer-preview" id="customerPreview">
                        <div class="name" id="custName"></div>
                        <div class="meta">
                            <i class="bi bi-telephone"></i> <span id="custPhone" dir="ltr"></span>
                            • <i class="bi bi-hash"></i> <span id="custCode"></span>
                        </div>
                    </div>
                </div>

                <div class="field-group">
                    <label class="form-label">
                        البرنامج
                        <span class="hint">اختياري - الإعدادات الافتراضية ستُملأ تلقائياً</span>
                    </label>
                    <select name="program_id" id="programSelect" class="form-select select2" data-placeholder="اختر برنامج (اختياري)">
                        <option value=""></option>
                        @foreach($programs as $p)
                            <option value="{{ $p->id }}"
                                data-name="{{ $p->name }}"
                                data-code="{{ $p->code }}"
                                data-type="{{ $p->type }}"
                                data-duration="{{ $p->duration_days }}"
                                data-price="{{ $p->base_price_per_person }}"
                                data-visa="{{ $p->default_visa_type }}"
                                data-transport="{{ $p->default_transport_type }}"
                                data-meal="{{ $p->default_meal_plan }}"
                                data-mutawif="{{ $p->default_mutawif_grade }}"
                                @selected(old('program_id', $booking?->program_id ?? '') === $p->id)>
                                {{ $p->name }} ({{ $p->code }})
                            </option>
                        @endforeach
                    </select>
                    <div class="program-preview" id="programPreview" style="background:linear-gradient(135deg,#fef3c7,#fde68a); border-right-color:#d4a437;">
                        <div class="name" id="progName"></div>
                        <div class="meta">
                            <i class="bi bi-clock"></i> <span id="progDuration"></span> يوم
                            • <i class="bi bi-cash"></i> <span id="progPrice"></span> ج.م للفرد
                        </div>
                    </div>
                </div>

                <div class="row g-2 field-group">
                    <div class="col-md-6">
                        <label class="form-label">رقم العقد</label>
                        <input type="text" name="contract_number" class="form-control"
                               placeholder="اختياري"
                               value="{{ old('contract_number', $booking?->contract_number ?? '') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">رقم الإيصال</label>
                        <input type="text" name="receipt_number" class="form-control"
                               placeholder="اختياري"
                               value="{{ old('receipt_number', $booking?->receipt_number ?? '') }}">
                    </div>
                </div>
            </div>

            {{-- ── Step 2: Trip Dates ──────────────────────── --}}
            <div class="step-pane" data-pane="2">
                <div class="step-header">
                    <div class="step-icon"><i class="bi bi-calendar-event"></i></div>
                    <div class="step-meta">
                        <h5>تواريخ ومدة الرحلة</h5>
                        <p>حدد تاريخ السفر، العودة، والمدة الإجمالية للرحلة</p>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-md-4 field-group">
                        <label class="form-label">
                            تاريخ الحجز <span class="req">*</span>
                        </label>
                        <input type="date" name="booking_date" class="form-control"
                               value="{{ old('booking_date', $booking?->booking_date?->format('Y-m-d') ?? now()->toDateString()) }}" required>
                    </div>
                    <div class="col-md-4 field-group">
                        <label class="form-label">
                            تاريخ السفر <span class="req">*</span>
                        </label>
                        <input type="date" name="trip_date" id="tripDateInput" class="form-control"
                               value="{{ old('trip_date', $booking?->trip_date?->format('Y-m-d') ?? '') }}" required>
                    </div>
                    <div class="col-md-4 field-group">
                        <label class="form-label">تاريخ العودة</label>
                        <input type="date" name="return_date" id="returnDateInput" class="form-control"
                               value="{{ old('return_date', $booking?->return_date?->format('Y-m-d') ?? '') }}">
                    </div>
                </div>

                <div class="field-group">
                    <label class="form-label">
                        مدة الرحلة (بالأيام) <span class="req">*</span>
                        <span class="hint">يحسب تلقائياً من التواريخ، أو حدد يدوياً</span>
                    </label>
                    <div class="num-stepper">
                        <button type="button" data-step-action="dec" data-target="duration_days">−</button>
                        <input type="number" name="duration_days" id="durationField" min="1" max="90"
                               value="{{ old('duration_days', $booking?->duration_days ?? 10) }}" required>
                        <button type="button" data-step-action="inc" data-target="duration_days">+</button>
                    </div>
                </div>
            </div>

            {{-- ── Step 3: Pilgrims Count ──────────────────── --}}
            <div class="step-pane" data-pane="3">
                <div class="step-header">
                    <div class="step-icon"><i class="bi bi-people"></i></div>
                    <div class="step-meta">
                        <h5>عدد الأفراد</h5>
                        <p>حدد عدد البالغين والأطفال والرضع - الأطفال أضف أسماءهم وأعمارهم</p>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-md-4 field-group">
                        <label class="form-label">
                            البالغون <span class="req">*</span>
                            <span class="hint">12 سنة فما فوق</span>
                        </label>
                        <div class="num-stepper">
                            <button type="button" data-step-action="dec" data-target="adults_count">−</button>
                            <input type="number" name="adults_count" min="1" max="500"
                                   value="{{ old('adults_count', $booking?->adults_count ?? 1) }}" required>
                            <button type="button" data-step-action="inc" data-target="adults_count">+</button>
                        </div>
                    </div>
                    <div class="col-md-4 field-group">
                        <label class="form-label">
                            الرضع
                            <span class="hint">أقل من سنتين</span>
                        </label>
                        <div class="num-stepper">
                            <button type="button" data-step-action="dec" data-target="infants_count">−</button>
                            <input type="number" name="infants_count" min="0" max="50"
                                   value="{{ old('infants_count', $booking?->infants_count ?? 0) }}">
                            <button type="button" data-step-action="inc" data-target="infants_count">+</button>
                        </div>
                    </div>
                    <div class="col-md-4 field-group">
                        <label class="form-label">
                            عدد الأطفال
                            <span class="hint">يُحسب من القائمة أدناه</span>
                        </label>
                        <input type="text" id="childrenCounter" class="form-control text-center fw-bold" readonly
                               value="0" style="background:#f1f5f9;">
                    </div>
                </div>

                <div class="field-group">
                    <label class="form-label">
                        قائمة الأطفال (مع الأعمار)
                        <span class="hint">من 2 إلى 12 سنة</span>
                    </label>
                    <div class="children-list" id="childrenContainer">
                        @php $childrenOld = old('children_data', $booking?->children_data ?? []); @endphp
                        @forelse($childrenOld as $i => $child)
                        <div class="child-row">
                            <div class="child-num">{{ $i + 1 }}</div>
                            <input type="text" name="children_data[{{ $i }}][name]" class="form-control"
                                   placeholder="اسم الطفل" value="{{ $child['name'] ?? '' }}">
                            <input type="number" name="children_data[{{ $i }}][age]" class="form-control" style="max-width:90px;"
                                   placeholder="العمر" min="0" max="17" value="{{ $child['age'] ?? '' }}">
                            <button type="button" class="btn btn-sm btn-outline-danger child-remove"><i class="bi bi-x"></i></button>
                        </div>
                        @empty @endforelse
                    </div>
                    <button type="button" id="addChildBtn" class="btn btn-outline-primary btn-sm mt-2">
                        <i class="bi bi-plus-circle"></i> إضافة طفل
                    </button>
                </div>
            </div>

            {{-- ── Step 4: Trip Configuration ──────────────── --}}
            <div class="step-pane" data-pane="4">
                <div class="step-header">
                    <div class="step-icon"><i class="bi bi-gear-wide-connected"></i></div>
                    <div class="step-meta">
                        <h5>إعدادات الرحلة</h5>
                        <p>اختر التأشيرة، السكن، النقل، نظام الإقامة، ومستوى المطوف</p>
                    </div>
                </div>

                <div class="field-group">
                    <label class="form-label">نوع التأشيرة <span class="req">*</span></label>
                    <div class="option-grid" data-options="visa_type">
                        @foreach(['standard'=>['عادية','passport','تأشيرة عمرة قياسية'],
                                  'haram'=>['حرم','mosque','تأشيرة الحرم - مميزة'],
                                  'kaaba'=>['كعبة','star','تأشيرة كعبة - VIP']] as $v => $info)
                        <label class="option-card {{ old('visa_type', $booking?->visa_type ?? 'standard') === $v ? 'selected' : '' }}">
                            <input type="radio" name="visa_type" value="{{ $v }}" {{ old('visa_type', $booking?->visa_type ?? 'standard') === $v ? 'checked' : '' }}>
                            <div class="opt-icon"><i class="bi bi-{{ $info[1] }}"></i></div>
                            <div class="opt-title">{{ $info[0] }}</div>
                            <div class="opt-desc">{{ $info[2] }}</div>
                        </label>
                        @endforeach
                    </div>
                </div>

                <div class="field-group">
                    <label class="form-label">نوع التسكين <span class="req">*</span></label>
                    <div class="option-grid" data-options="accommodation_type">
                        @foreach([
                            'single'=>['فردي','1','شخص واحد بالغرفة'],
                            'double'=>['ثنائي','2','شخصان بالغرفة'],
                            'triple'=>['ثلاثي','3','ثلاثة بالغرفة'],
                            'quad'=>['رباعي','4','أربعة بالغرفة'],
                            'quintuple'=>['خماسي','5','خمسة بالغرفة'],
                            'sextuple'=>['سداسي','6','ستة بالغرفة'],
                        ] as $v => $info)
                        <label class="option-card {{ old('accommodation_type', $booking?->accommodation_type ?? 'quad') === $v ? 'selected' : '' }}">
                            <input type="radio" name="accommodation_type" value="{{ $v }}" {{ old('accommodation_type', $booking?->accommodation_type ?? 'quad') === $v ? 'checked' : '' }}>
                            <div class="opt-icon" style="font-weight:900; font-size:1.5rem;">{{ $info[1] }}</div>
                            <div class="opt-title">{{ $info[0] }}</div>
                            <div class="opt-desc">{{ $info[2] }}</div>
                        </label>
                        @endforeach
                    </div>
                </div>

                <div class="row g-3 field-group">
                    <div class="col-md-6">
                        <label class="form-label">نظام الإقامة <span class="req">*</span></label>
                        <div class="option-grid" data-options="meal_plan">
                            <label class="option-card {{ old('meal_plan', $booking?->meal_plan ?? 'hp') === 'hp' ? 'selected' : '' }}">
                                <input type="radio" name="meal_plan" value="hp" {{ old('meal_plan', $booking?->meal_plan ?? 'hp') === 'hp' ? 'checked' : '' }}>
                                <div class="opt-icon"><i class="bi bi-cup-hot"></i></div>
                                <div class="opt-title">H.P</div>
                                <div class="opt-desc">نصف إقامة (إفطار وعشاء)</div>
                            </label>
                            <label class="option-card {{ old('meal_plan', $booking?->meal_plan ?? '') === 'pp' ? 'selected' : '' }}">
                                <input type="radio" name="meal_plan" value="pp" {{ old('meal_plan', $booking?->meal_plan ?? '') === 'pp' ? 'checked' : '' }}>
                                <div class="opt-icon"><i class="bi bi-cup-straw"></i></div>
                                <div class="opt-title">P.P</div>
                                <div class="opt-desc">إقامة كاملة (3 وجبات)</div>
                            </label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">وسيلة النقل <span class="req">*</span></label>
                        <div class="option-grid" data-options="transport_type">
                            @foreach([
                                'flight'=>['طيران','airplane'],
                                'bus'=>['باص','bus-front'],
                                'train'=>['قطار','train-front'],
                                'vip'=>['VIP','car-front'],
                            ] as $v => $info)
                            <label class="option-card {{ old('transport_type', $booking?->transport_type ?? 'flight') === $v ? 'selected' : '' }}">
                                <input type="radio" name="transport_type" value="{{ $v }}" {{ old('transport_type', $booking?->transport_type ?? 'flight') === $v ? 'checked' : '' }}>
                                <div class="opt-icon"><i class="bi bi-{{ $info[1] }}"></i></div>
                                <div class="opt-title">{{ $info[0] }}</div>
                            </label>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="field-group">
                    <label class="form-label">مستوى المطوف <span class="req">*</span></label>
                    <div class="option-grid" data-options="mutawif_grade">
                        @foreach([
                            'economy'=>['اقتصادي','star-half','مرشد ديني عادي'],
                            'land'=>['بري','signpost-2','مع التنقل البري'],
                            '5_stars'=>['5 نجوم','stars','مطوف متميز VIP'],
                        ] as $v => $info)
                        <label class="option-card {{ old('mutawif_grade', $booking?->mutawif_grade ?? 'economy') === $v ? 'selected' : '' }}">
                            <input type="radio" name="mutawif_grade" value="{{ $v }}" {{ old('mutawif_grade', $booking?->mutawif_grade ?? 'economy') === $v ? 'checked' : '' }}>
                            <div class="opt-icon"><i class="bi bi-{{ $info[1] }}"></i></div>
                            <div class="opt-title">{{ $info[0] }}</div>
                            <div class="opt-desc">{{ $info[2] }}</div>
                        </label>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- ── Step 5: Pricing & Responsibility ────────── --}}
            <div class="step-pane" data-pane="5">
                <div class="step-header">
                    <div class="step-icon"><i class="bi bi-cash-stack"></i></div>
                    <div class="step-meta">
                        <h5>التسعير والمسؤولية</h5>
                        <p>حدد سعر البيع، سعر صرف الريال، والموظفين المسؤولين</p>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-md-7 field-group">
                        <label class="form-label">
                            سعر البيع الإجمالي <span class="req">*</span>
                            <span class="hint">السعر النهائي المتفق عليه مع العميل</span>
                        </label>
                        <div class="input-group input-group-lg">
                            <input type="number" name="selling_price" id="sellingPrice" min="0" step="0.01"
                                   class="form-control form-control-lg" style="font-size:1.4rem; font-weight:800; color:var(--brand-navy);"
                                   value="{{ old('selling_price', $booking?->selling_price ?? '') }}" required>
                            <span class="input-group-text" style="background:#fef3c7; color:#92400e; font-weight:800;">ج.م</span>
                        </div>
                        <div class="mt-2 small text-muted">
                            السعر المقترح للفرد: <strong id="suggestedPerPerson">—</strong> ج.م
                        </div>
                    </div>
                    <div class="col-md-5 field-group">
                        <label class="form-label">
                            سعر صرف الريال
                            <span class="hint">يتم حفظه مع الحجز للتقارير</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text">SAR 1 =</span>
                            <input type="number" name="exchange_rate_sar" step="0.0001"
                                   class="form-control"
                                   value="{{ old('exchange_rate_sar', $booking?->exchange_rate_sar ?? $sarRate) }}">
                            <span class="input-group-text">ج.م</span>
                        </div>
                        <div class="mt-2 small text-muted">
                            السعر الحالي: <strong>{{ number_format($sarRate, 4) }}</strong>
                        </div>
                    </div>
                </div>

                <div class="row g-3 field-group">
                    <div class="col-md-6">
                        <label class="form-label">
                            <i class="bi bi-person-badge text-warning"></i> المدير المسؤول
                        </label>
                        <select name="responsible_manager_id" class="form-select select2" data-placeholder="اختر المدير">
                            <option value=""></option>
                            @foreach($employees as $u)
                                <option value="{{ $u->id }}"
                                    @selected(old('responsible_manager_id', $booking?->responsible_manager_id ?? '') === $u->id)>
                                    {{ $u->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">
                            <i class="bi bi-person-workspace text-primary"></i> الموظف المسؤول (البائع)
                        </label>
                        <select name="responsible_employee_id" class="form-select select2" data-placeholder="اختر الموظف">
                            <option value=""></option>
                            @foreach($employees as $u)
                                <option value="{{ $u->id }}"
                                    @selected(old('responsible_employee_id', $booking?->responsible_employee_id ?? auth()->id()) === $u->id)>
                                    {{ $u->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="field-group">
                    <label class="form-label">ملاحظات داخلية</label>
                    <textarea name="notes" rows="3" class="form-control" placeholder="ملاحظات للفريق - لا تظهر للعميل">{{ old('notes', $booking?->notes ?? '') }}</textarea>
                </div>

                @if($isEdit)
                <div class="row g-3 field-group">
                    <div class="col-md-6">
                        <label class="form-label">حالة الحجز</label>
                        <select name="status" class="form-select">
                            @foreach(['pending'=>'⏳ قيد الانتظار','confirmed'=>'✓ مؤكد','in_progress'=>'✈ جارية','completed'=>'☆ مكتمل','cancelled'=>'✗ ملغي'] as $v=>$l)
                                <option value="{{ $v }}" @selected(old('status', $booking->status) === $v)>{{ $l }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">مرحلة سير العمل</label>
                        <select name="workflow_stage" class="form-select">
                            @foreach(['sales'=>'🛒 المبيعات','manager_review'=>'👤 مراجعة المدير','operations'=>'⚙ العمليات','finance'=>'💰 المالية','closed'=>'🔒 مُقفل'] as $v=>$l)
                                <option value="{{ $v }}" @selected(old('workflow_stage', $booking->workflow_stage) === $v)>{{ $l }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                @endif
            </div>

            {{-- ── Step 6: Review ──────────────────────────── --}}
            <div class="step-pane" data-pane="6">
                <div class="step-header">
                    <div class="step-icon" style="background:linear-gradient(135deg,#dcfce7,#bbf7d0); color:#15803d;">
                        <i class="bi bi-check2-circle"></i>
                    </div>
                    <div class="step-meta">
                        <h5>مراجعة قبل الحفظ</h5>
                        <p>راجع كل البيانات أدناه ثم اضغط حفظ لإنشاء الحجز</p>
                    </div>
                </div>

                <div id="reviewBody">
                    <div class="review-section">
                        <h6>
                            <span><i class="bi bi-person-vcard text-primary"></i> العميل والبرنامج</span>
                            <a class="edit-link" data-jump="1">تعديل ←</a>
                        </h6>
                        <div class="review-grid">
                            <div class="rev-kv"><span class="k">نوع الرحلة</span> <span class="v" id="r_type">—</span></div>
                            <div class="rev-kv"><span class="k">العميل</span> <span class="v" id="r_customer">—</span></div>
                            <div class="rev-kv"><span class="k">البرنامج</span> <span class="v" id="r_program">—</span></div>
                        </div>
                    </div>

                    <div class="review-section">
                        <h6>
                            <span><i class="bi bi-calendar-event text-primary"></i> التواريخ والأفراد</span>
                            <a class="edit-link" data-jump="2">تعديل ←</a>
                        </h6>
                        <div class="review-grid">
                            <div class="rev-kv"><span class="k">تاريخ السفر</span> <span class="v" id="r_trip_date">—</span></div>
                            <div class="rev-kv"><span class="k">المدة</span> <span class="v" id="r_duration">—</span></div>
                            <div class="rev-kv"><span class="k">إجمالي الأفراد</span> <span class="v" id="r_total_pax">—</span></div>
                        </div>
                    </div>

                    <div class="review-section">
                        <h6>
                            <span><i class="bi bi-gear text-primary"></i> الإعدادات</span>
                            <a class="edit-link" data-jump="4">تعديل ←</a>
                        </h6>
                        <div class="review-grid">
                            <div class="rev-kv"><span class="k">التأشيرة</span> <span class="v" id="r_visa">—</span></div>
                            <div class="rev-kv"><span class="k">التسكين</span> <span class="v" id="r_accom">—</span></div>
                            <div class="rev-kv"><span class="k">النقل</span> <span class="v" id="r_transport">—</span></div>
                            <div class="rev-kv"><span class="k">نظام الإقامة</span> <span class="v" id="r_meal">—</span></div>
                        </div>
                    </div>

                    <div class="review-section" style="background:linear-gradient(135deg,#fef3c7,#fde68a);">
                        <h6>
                            <span><i class="bi bi-cash-stack text-warning"></i> المالية</span>
                            <a class="edit-link" data-jump="5">تعديل ←</a>
                        </h6>
                        <div class="review-grid">
                            <div class="rev-kv"><span class="k">سعر البيع</span> <span class="v" id="r_price" style="font-size:1.1rem; color:#92400e;">—</span></div>
                            <div class="rev-kv"><span class="k">السعر للفرد</span> <span class="v" id="r_per_person">—</span></div>
                            <div class="rev-kv"><span class="k">سعر صرف SAR</span> <span class="v" id="r_rate">—</span></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── Wizard Footer ───────────────────────────── --}}
            <div class="wizard-footer">
                <button type="button" id="prevBtn" class="btn btn-outline-secondary" disabled>
                    <i class="bi bi-arrow-right"></i> السابق
                </button>

                <div class="step-counter">
                    الخطوة <strong id="currentStepNum">1</strong> من <strong>6</strong>
                </div>

                <button type="button" id="nextBtn" class="btn btn-primary">
                    التالي <i class="bi bi-arrow-left"></i>
                </button>
                <button type="submit" id="submitBtn" class="btn btn-success" style="display:none;">
                    <i class="bi bi-check-circle"></i> {{ $isEdit ? 'حفظ التعديلات' : 'إنشاء الحجز' }}
                </button>
            </div>
        </div>
    </div>

    {{-- ════════════════════════════════════════════════════════════
         Live summary right column
         ════════════════════════════════════════════════════════════ --}}
    <div class="col-lg-4">
        <div class="live-summary">
            <div class="summary-head">
                <h6><i class="bi bi-receipt"></i> ملخص الحجز المباشر</h6>
                <div class="head-sub">يتحدّث تلقائياً مع كل تغيير</div>
            </div>

            <div class="summary-body">
                <div id="summaryEmpty" class="empty-summary">
                    <div class="ico"><i class="bi bi-clipboard-plus"></i></div>
                    <div>ابدأ بملء بيانات الحجز</div>
                    <small class="empty-tip">سيظهر الملخص هنا تلقائياً عند الكتابة</small>
                </div>

                <div id="summaryContent" style="display:none;">
                    <div class="sum-row">
                        <span class="lbl"><i class="bi bi-tag"></i> نوع الرحلة</span>
                        <span class="val" id="sumType">—</span>
                    </div>
                    <div class="sum-row" id="sumCustomerRow" style="display:none;">
                        <span class="lbl"><i class="bi bi-person"></i> العميل</span>
                        <span class="val" id="sumCustomer">—</span>
                    </div>
                    <div class="sum-row" id="sumProgramRow" style="display:none;">
                        <span class="lbl"><i class="bi bi-bookmark-star"></i> البرنامج</span>
                        <span class="val" id="sumProgram">—</span>
                    </div>
                    <div class="sum-row" id="sumDateRow" style="display:none;">
                        <span class="lbl"><i class="bi bi-calendar-event"></i> تاريخ السفر</span>
                        <span class="val" id="sumDate">—</span>
                    </div>
                    <div class="sum-row" id="sumDurationRow" style="display:none;">
                        <span class="lbl"><i class="bi bi-hourglass-split"></i> المدة</span>
                        <span class="val"><span id="sumDuration">—</span> يوم</span>
                    </div>
                    <div class="sum-row" id="sumPaxRow" style="display:none;">
                        <span class="lbl"><i class="bi bi-people-fill"></i> إجمالي الأفراد</span>
                        <span class="val" id="sumPax">—</span>
                    </div>
                    <div class="sum-row" id="sumVisaRow" style="display:none;">
                        <span class="lbl"><i class="bi bi-passport"></i> التأشيرة</span>
                        <span class="val" id="sumVisa">—</span>
                    </div>
                    <div class="sum-row" id="sumAccomRow" style="display:none;">
                        <span class="lbl"><i class="bi bi-house-door"></i> التسكين</span>
                        <span class="val" id="sumAccom">—</span>
                    </div>
                    <div class="sum-row highlight" id="sumPriceRow" style="display:none;">
                        <span class="lbl"><i class="bi bi-cash-coin"></i> سعر البيع</span>
                        <span class="val"><span id="sumPrice">—</span> ج.م</span>
                    </div>
                    <div class="sum-row" id="sumPerPersonRow" style="display:none;">
                        <span class="lbl"><i class="bi bi-person-arms-up"></i> للفرد الواحد</span>
                        <span class="val"><span id="sumPerPerson">—</span> ج.م</span>
                    </div>
                </div>

                <div class="completion" id="completionMeter">
                    <div class="ring" id="completionRing" style="--pct: 0%;"><span id="completionPct">0%</span></div>
                    <div class="meta">
                        <strong>تقدم الإكمال</strong><br>
                        <span id="completionLabel">ابدأ بإدخال البيانات</span>
                    </div>
                </div>
            </div>
        </div>

        @if($isEdit)
        <div class="mt-3 p-3 rounded-3" style="background:#fef3c7; border-right:4px solid #d4a437;">
            <div class="small text-muted mb-1">
                <i class="bi bi-info-circle"></i> رقم الحجز
            </div>
            <div class="fw-bold" style="color:#92400e; font-size:1.05rem;">
                {{ $booking->booking_number }}
            </div>
        </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
(function () {
    'use strict';

    const $form = $('form');
    const totalSteps = 6;
    let currentStep = 1;

    // Labels for visual cards
    const LABELS = {
        type:               { umrah: 'عمرة', hajj: 'حج' },
        visa_type:          { standard: 'عادية', haram: 'حرم', kaaba: 'كعبة' },
        accommodation_type: { single: 'فردي', double: 'ثنائي', triple: 'ثلاثي', quad: 'رباعي', quintuple: 'خماسي', sextuple: 'سداسي' },
        meal_plan:          { hp: 'H.P', pp: 'P.P' },
        transport_type:     { flight: 'طيران', bus: 'باص', train: 'قطار', vip: 'VIP' },
        mutawif_grade:      { economy: 'اقتصادي', land: 'بري', '5_stars': '5 نجوم' },
    };

    // ── Step navigation ─────────────────────────────
    function showStep(n) {
        currentStep = Math.max(1, Math.min(totalSteps, n));
        $('.step-pane').removeClass('active');
        $(`.step-pane[data-pane="${currentStep}"]`).addClass('active');

        // Progress bar fill
        $('.step-item').each(function () {
            const s = parseInt($(this).data('step'));
            $(this).removeClass('active done');
            if (s < currentStep) $(this).addClass('done');
            if (s === currentStep) $(this).addClass('active');
        });

        const pct = ((currentStep - 1) / (totalSteps - 1)) * 100;
        $('#progressFill').css('width', `calc(${pct}% - 24px)`);

        $('#prevBtn').prop('disabled', currentStep === 1);
        $('#nextBtn').toggle(currentStep < totalSteps);
        $('#submitBtn').toggle(currentStep === totalSteps);
        $('#currentStepNum').text(currentStep);

        if (currentStep === totalSteps) buildReview();
        updateSummary();

        // Scroll to top of wizard
        $('html,body').animate({ scrollTop: $('.wizard-wrap').offset().top - 80 }, 280);
    }

    function validateStep() {
        // Find the visible step pane and check its required fields
        const $pane = $(`.step-pane[data-pane="${currentStep}"]`);
        const $requireds = $pane.find('[required]');
        let valid = true, firstBad = null;
        $requireds.each(function () {
            if (!$(this).val() || ($(this).is(':radio') && !$pane.find(`[name="${$(this).attr('name')}"]:checked`).length)) {
                valid = false;
                if (!firstBad) firstBad = $(this);
            }
        });

        // For radio groups visible as cards, check at least one is checked
        $pane.find('[data-options]').each(function () {
            const name = $(this).data('options');
            if (!$pane.find(`[name="${name}"]:checked`).length && $pane.find(`[name="${name}"][required]`).length) {
                valid = false;
            }
        });

        if (!valid) {
            toastr.warning('من فضلك أكمل الحقول المطلوبة قبل المتابعة');
            if (firstBad && firstBad.focus) firstBad.focus();
        }
        return valid;
    }

    $('#nextBtn').on('click', () => { if (validateStep()) showStep(currentStep + 1); });
    $('#prevBtn').on('click', () => showStep(currentStep - 1));

    // Click on step circle to jump (only to previous/completed)
    $(document).on('click', '.step-item', function () {
        const s = parseInt($(this).data('step'));
        if (s < currentStep) showStep(s);
        else if (s === currentStep + 1 && validateStep()) showStep(s);
    });

    // Click "edit" links in review
    $(document).on('click', '.edit-link', function () {
        showStep(parseInt($(this).data('jump')));
    });

    // ── Visual card selection ──────────────────────
    $(document).on('click', '.option-card', function () {
        const $input = $(this).find('input[type=radio]');
        if (!$input.length) return;
        $(this).siblings().removeClass('selected');
        $(this).addClass('selected');
        $input.prop('checked', true).trigger('change');
        updateSummary();
    });

    // ── Number stepper ─────────────────────────────
    $(document).on('click', '[data-step-action]', function () {
        const name = $(this).data('target');
        const $input = $(this).parent().find(`input[name="${name}"]`);
        let val = parseInt($input.val() || 0);
        const min = parseInt($input.attr('min') ?? 0);
        const max = parseInt($input.attr('max') ?? 9999);
        val += $(this).data('step-action') === 'inc' ? 1 : -1;
        val = Math.max(min, Math.min(max, val));
        $input.val(val).trigger('change');
        updateSummary();
    });

    // ── Children dynamic rows ──────────────────────
    let childIdx = $('#childrenContainer .child-row').length;
    function refreshChildNums() {
        $('#childrenContainer .child-row').each(function (i) {
            $(this).find('.child-num').text(i + 1);
        });
        $('#childrenCounter').val($('#childrenContainer .child-row').length);
    }
    refreshChildNums();
    $('#addChildBtn').on('click', () => {
        const html = `
            <div class="child-row">
                <div class="child-num">0</div>
                <input type="text" name="children_data[${childIdx}][name]" class="form-control" placeholder="اسم الطفل">
                <input type="number" name="children_data[${childIdx}][age]" class="form-control" style="max-width:90px;" placeholder="العمر" min="0" max="17">
                <button type="button" class="btn btn-sm btn-outline-danger child-remove"><i class="bi bi-x"></i></button>
            </div>`;
        $('#childrenContainer').append(html);
        childIdx++;
        refreshChildNums();
    });
    $(document).on('click', '.child-remove', function () {
        $(this).closest('.child-row').remove();
        refreshChildNums();
    });

    // ── Auto-fill from program ─────────────────────
    $('#programSelect').on('change', function () {
        const opt = this.options[this.selectedIndex];
        if (!opt || !opt.value) {
            $('#programPreview').removeClass('show');
            return;
        }
        // Show preview
        $('#progName').text(opt.dataset.name);
        $('#progDuration').text(opt.dataset.duration);
        $('#progPrice').text(parseFloat(opt.dataset.price || 0).toLocaleString('en-US', { minimumFractionDigits: 0 }));
        $('#programPreview').addClass('show');

        // Autofill defaults if currently empty
        if (opt.dataset.type)      selectCard('type', opt.dataset.type);
        if (opt.dataset.visa)      selectCard('visa_type', opt.dataset.visa);
        if (opt.dataset.transport) selectCard('transport_type', opt.dataset.transport);
        if (opt.dataset.meal)      selectCard('meal_plan', opt.dataset.meal);
        if (opt.dataset.mutawif)   selectCard('mutawif_grade', opt.dataset.mutawif);
        if (opt.dataset.duration)  $('input[name="duration_days"]').val(opt.dataset.duration);
        if (!$('#sellingPrice').val() && opt.dataset.price) {
            // Suggest selling = base × adults_count
            const adults = parseInt($('input[name="adults_count"]').val() || 1);
            $('#sellingPrice').val(parseFloat(opt.dataset.price) * adults);
        }
        updateSummary();
    });

    function selectCard(name, value) {
        const $card = $(`[data-options="${name}"] input[name="${name}"][value="${value}"]`).closest('.option-card');
        if ($card.length) {
            $card.siblings().removeClass('selected');
            $card.addClass('selected').find('input').prop('checked', true);
        }
    }

    // ── Customer preview ───────────────────────────
    $('#customerSelect').on('change', function () {
        const opt = this.options[this.selectedIndex];
        if (!opt || !opt.value) {
            $('#customerPreview').removeClass('show');
            return;
        }
        $('#custName').text(opt.dataset.name);
        $('#custPhone').text(opt.dataset.phone);
        $('#custCode').text(opt.dataset.code);
        $('#customerPreview').addClass('show');
        updateSummary();
    });

    // ── Date → duration ────────────────────────────
    $('#tripDateInput, #returnDateInput').on('change', function () {
        const start = $('#tripDateInput').val();
        const end   = $('#returnDateInput').val();
        if (start && end) {
            const d = (new Date(end) - new Date(start)) / (1000 * 60 * 60 * 24);
            if (d > 0) $('#durationField').val(d);
        }
        updateSummary();
    });

    // ── Generic change listener for summary ────────
    $form.on('change input', 'input, select, textarea', updateSummary);

    // ── Live summary builder ───────────────────────
    function updateSummary() {
        const data = collectFormData();
        const hasAny = data.type || data.customer_id || data.trip_date;

        $('#summaryEmpty').toggle(!hasAny);
        $('#summaryContent').toggle(!!hasAny);

        if (!hasAny) return;

        // Trip type
        const typeLbl = LABELS.type[data.type] || '—';
        $('#sumType').text(typeLbl);

        // Customer
        if (data.customer_id) {
            const $opt = $('#customerSelect option:selected');
            $('#sumCustomer').text($opt.data('name') || '—');
            $('#sumCustomerRow').show();
        } else $('#sumCustomerRow').hide();

        // Program
        if (data.program_id) {
            const $opt = $('#programSelect option:selected');
            $('#sumProgram').text($opt.data('name') || '—');
            $('#sumProgramRow').show();
        } else $('#sumProgramRow').hide();

        // Date
        if (data.trip_date) {
            $('#sumDate').text(data.trip_date);
            $('#sumDateRow').show();
        } else $('#sumDateRow').hide();

        // Duration
        if (data.duration_days) {
            $('#sumDuration').text(data.duration_days);
            $('#sumDurationRow').show();
        } else $('#sumDurationRow').hide();

        // Pax
        const totalPax = (parseInt(data.adults_count || 0)) + $('#childrenContainer .child-row').length + (parseInt(data.infants_count || 0));
        if (totalPax) {
            $('#sumPax').text(totalPax);
            $('#sumPaxRow').show();
        } else $('#sumPaxRow').hide();

        // Visa
        if (data.visa_type) {
            $('#sumVisa').text(LABELS.visa_type[data.visa_type]);
            $('#sumVisaRow').show();
        } else $('#sumVisaRow').hide();

        // Accommodation
        if (data.accommodation_type) {
            $('#sumAccom').text(LABELS.accommodation_type[data.accommodation_type]);
            $('#sumAccomRow').show();
        } else $('#sumAccomRow').hide();

        // Price
        if (data.selling_price && data.selling_price > 0) {
            $('#sumPrice').text(parseFloat(data.selling_price).toLocaleString('en-US', { minimumFractionDigits: 0 }));
            $('#sumPriceRow').show();
            // Per person
            if (totalPax > 0) {
                $('#sumPerPerson').text((data.selling_price / totalPax).toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 0 }));
                $('#sumPerPersonRow').show();
                $('#suggestedPerPerson').text((data.selling_price / totalPax).toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 0 }));
            } else $('#sumPerPersonRow').hide();
        } else {
            $('#sumPriceRow').hide();
            $('#sumPerPersonRow').hide();
            $('#suggestedPerPerson').text('—');
        }
    }

    function collectFormData() {
        return {
            type:               $('input[name="type"]:checked').val(),
            customer_id:        $('#customerSelect').val(),
            program_id:         $('#programSelect').val(),
            trip_date:          $('#tripDateInput').val(),
            duration_days:      $('#durationField').val(),
            adults_count:       $('input[name="adults_count"]').val(),
            infants_count:      $('input[name="infants_count"]').val(),
            visa_type:          $('input[name="visa_type"]:checked').val(),
            accommodation_type: $('input[name="accommodation_type"]:checked').val(),
            meal_plan:          $('input[name="meal_plan"]:checked').val(),
            transport_type:     $('input[name="transport_type"]:checked').val(),
            mutawif_grade:      $('input[name="mutawif_grade"]:checked').val(),
            selling_price:      parseFloat($('#sellingPrice').val() || 0),
            exchange_rate_sar:  $('input[name="exchange_rate_sar"]').val(),
        };
    }

    function buildReview() {
        const data = collectFormData();
        const totalPax = (parseInt(data.adults_count || 0)) + $('#childrenContainer .child-row').length + (parseInt(data.infants_count || 0));

        $('#r_type').text(LABELS.type[data.type] || '—');
        $('#r_customer').text($('#customerSelect option:selected').data('name') || '—');
        $('#r_program').text($('#programSelect option:selected').data('name') || '— بدون برنامج محدد —');
        $('#r_trip_date').text(data.trip_date || '—');
        $('#r_duration').text(data.duration_days ? data.duration_days + ' يوم' : '—');
        $('#r_total_pax').text(totalPax || '—');
        $('#r_visa').text(LABELS.visa_type[data.visa_type] || '—');
        $('#r_accom').text(LABELS.accommodation_type[data.accommodation_type] || '—');
        $('#r_transport').text(LABELS.transport_type[data.transport_type] || '—');
        $('#r_meal').text(LABELS.meal_plan[data.meal_plan] || '—');
        $('#r_price').text(data.selling_price > 0 ? parseFloat(data.selling_price).toLocaleString('en-US') + ' ج.م' : '—');
        $('#r_per_person').text(totalPax > 0 ? (data.selling_price / totalPax).toLocaleString('en-US', {maximumFractionDigits: 0}) + ' ج.م' : '—');
        $('#r_rate').text(data.exchange_rate_sar || '—');
    }

    // ── Completion ring ─────────────────────────────
    function updateCompletion() {
        const required = [
            $('input[name="type"]:checked').val(),
            $('#customerSelect').val(),
            $('#tripDateInput').val(),
            $('#durationField').val(),
            $('input[name="adults_count"]').val() > 0,
            $('input[name="visa_type"]:checked').val(),
            $('input[name="accommodation_type"]:checked').val(),
            $('input[name="meal_plan"]:checked').val(),
            $('input[name="transport_type"]:checked').val(),
            $('input[name="mutawif_grade"]:checked').val(),
            parseFloat($('#sellingPrice').val()) > 0,
        ];
        const total = required.length;
        const done = required.filter(Boolean).length;
        const pct = Math.round((done / total) * 100);
        $('#completionRing').css('--pct', pct + '%');
        $('#completionPct').text(pct + '%');
        let label = 'ابدأ بإدخال البيانات';
        if (pct === 100) label = '<i class="bi bi-check-circle-fill text-success"></i> جاهز للحفظ';
        else if (pct >= 70) label = 'اقترب الإكمال — راجع الباقي';
        else if (pct >= 40) label = 'نصف الطريق — استمر';
        else if (pct > 0)   label = 'في البداية — أكمل الخطوات';
        $('#completionLabel').html(label);
    }

    // Hook completion update into the existing summary refresh
    const _origUpdateSummary = updateSummary;
    updateSummary = function () { _origUpdateSummary.apply(this, arguments); updateCompletion(); };

    // ── Enhanced Select2 — rich customer template ─────
    if ($.fn.select2 && $('#customerSelect').data('select2')) {
        $('#customerSelect').select2('destroy');
    }
    $('#customerSelect').select2({
        dir: 'rtl', theme: 'bootstrap-5',
        placeholder: 'ابحث بالاسم أو رقم التليفون...',
        allowClear: true, width: '100%',
        // Default matcher searches the option's text — which contains
        // "{name} — {phone}" so phone search already works. We also
        // expand it to match the customer code.
        matcher: function (params, data) {
            if (!params.term) return data;
            if (!data.element) return null;
            const term  = params.term.toLowerCase().trim();
            const text  = (data.text || '').toLowerCase();
            const code  = ($(data.element).data('code') || '').toString().toLowerCase();
            const phone = ($(data.element).data('phone') || '').toString().toLowerCase();
            if (text.includes(term) || code.includes(term) || phone.includes(term)) return data;
            return null;
        },
        templateResult: function (data) {
            if (!data.element) return data.text;
            const $el = $(data.element);
            const name  = $el.data('name')  || data.text;
            const phone = $el.data('phone') || '';
            const code  = $el.data('code')  || '';
            return $(`
                <div style="display:flex; align-items:center; gap:.6rem; padding:.2rem 0;">
                    <div style="width:34px; height:34px; border-radius:50%; background:linear-gradient(135deg,#fef3c7,#fde68a); color:#92400e; display:flex; align-items:center; justify-content:center; font-weight:800; font-size:.85rem;">
                        ${name.charAt(0)}
                    </div>
                    <div style="flex:1;">
                        <div style="font-weight:700; color:#0f172a;">${name}</div>
                        <div style="font-size:.72rem; color:#64748b;">
                            <i class="bi bi-telephone"></i> <span dir="ltr">${phone}</span>
                            &nbsp;•&nbsp; <i class="bi bi-hash"></i> ${code}
                        </div>
                    </div>
                </div>
            `);
        },
        templateSelection: function (data) {
            if (!data.element) return data.text;
            const $el = $(data.element);
            const phone = $el.data('phone') || '';
            return $(`<span><strong>${$el.data('name')}</strong> &nbsp;·&nbsp; <span dir="ltr" style="color:#64748b;">${phone}</span></span>`);
        }
    });

    // ── Quick-Fill (super-admin) ───────────────────
    $('#quickFillBtn').on('click', function () {
        const $btn = $(this);
        $btn.prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> جاري الملء...');

        // 1) Pick a random customer
        const $opts = $('#customerSelect option').filter((_, o) => $(o).val());
        if ($opts.length) {
            const rand = $opts.eq(Math.floor(Math.random() * $opts.length));
            $('#customerSelect').val(rand.val()).trigger('change');
        }

        // 2) Pick a program (Umrah preferred)
        const $progs = $('#programSelect option').filter((_, o) => $(o).val() && $(o).data('type') === 'umrah');
        if ($progs.length) {
            const p = $progs.eq(Math.floor(Math.random() * $progs.length));
            $('#programSelect').val(p.val()).trigger('change');
        }

        // 3) Force type = umrah (if no program selected this still works)
        selectCard('type', 'umrah');

        // 4) Dates: today + 30 days, return = trip + duration
        const today = new Date();
        const trip  = new Date(); trip.setDate(today.getDate() + 30);
        const dur   = parseInt($('#durationField').val()) || 10;
        const ret   = new Date(trip); ret.setDate(trip.getDate() + dur);
        const fmt = d => d.toISOString().slice(0, 10);
        $('input[name="booking_date"]').val(fmt(today));
        $('#tripDateInput').val(fmt(trip));
        $('#returnDateInput').val(fmt(ret));

        // 5) Pax
        $('input[name="adults_count"]').val(2);
        $('input[name="infants_count"]').val(0);

        // 6) Trip configuration
        selectCard('visa_type', 'standard');
        selectCard('accommodation_type', 'quad');
        selectCard('meal_plan', 'hp');
        selectCard('transport_type', 'flight');
        selectCard('mutawif_grade', 'economy');

        // 7) Pricing — use program price if available, fallback to 35000 × adults
        const progEl = $('#programSelect option:selected');
        const base = parseFloat(progEl.data('price') || 35000);
        $('#sellingPrice').val(base * 2);
        $('select[name="responsible_employee_id"]').val('{{ auth()->id() }}').trigger('change');

        updateSummary();

        // Jump to review step
        setTimeout(() => {
            showStep(6);
            $btn.prop('disabled', false).html('<i class="bi bi-lightning-charge-fill"></i> ملء تلقائي');
            if (window.toastr) toastr.success('تم ملء النموذج ببيانات تجريبية — راجع واضغط حفظ');
        }, 400);
    });

    // ── Init ───────────────────────────────────────
    $('#customerSelect').trigger('change');
    $('#programSelect').trigger('change');
    updateSummary();

    // If editing, jump to last step so user sees everything
    @if($isEdit)
    showStep(1);
    @endif
})();
</script>
@endpush
