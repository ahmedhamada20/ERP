@csrf

<style>
    /* ── Form tabs ───────────────────────────────────────── */
    .form-tabs {
        display: flex;
        gap: .25rem;
        border-bottom: 2px solid var(--brand-border);
        margin-bottom: 1.5rem;
        overflow-x: auto;
        flex-wrap: nowrap;
    }
    .form-tabs button {
        background: transparent;
        border: none;
        padding: .75rem 1.1rem;
        color: var(--text-muted);
        font-weight: 600;
        font-size: .9rem;
        border-bottom: 3px solid transparent;
        margin-bottom: -2px;
        cursor: pointer;
        white-space: nowrap;
        transition: all .15s;
        display: inline-flex; align-items: center; gap: .4rem;
    }
    .form-tabs button:hover { color: var(--brand-navy); }
    .form-tabs button.active {
        color: var(--brand-navy);
        border-bottom-color: var(--brand-gold);
    }
    .form-tabs button .tab-num {
        width: 22px; height: 22px;
        background: #f1f5f9;
        color: var(--text-muted);
        border-radius: 50%;
        font-size: .72rem;
        font-weight: 800;
        display: inline-flex; align-items: center; justify-content: center;
    }
    .form-tabs button.active .tab-num {
        background: var(--brand-gold);
        color: #fff;
    }
    .tab-pane { display: none; animation: fadeIn .25s; }
    .tab-pane.active { display: block; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(4px); } to { opacity: 1; transform: none; } }

    /* ── Upload preview cards ────────────────────────────── */
    .upload-card {
        border: 2px dashed var(--brand-border);
        border-radius: 12px;
        padding: 1rem;
        text-align: center;
        background: #fafbff;
        transition: all .15s;
        position: relative;
        min-height: 160px;
        display: flex; flex-direction: column;
        align-items: center; justify-content: center;
    }
    .upload-card:hover { border-color: var(--brand-gold); background: #fffdf5; }
    .upload-card .up-icon {
        width: 48px; height: 48px;
        background: #fff;
        border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.4rem; color: var(--brand-navy);
        margin: 0 auto .6rem;
        box-shadow: 0 1px 3px rgba(0,0,0,.04);
    }
    .upload-card label {
        cursor: pointer;
        font-weight: 700;
        color: var(--brand-navy);
        font-size: .88rem;
        margin: 0;
    }
    .upload-card .up-hint { font-size: .72rem; color: var(--text-muted); margin-top: .25rem; }
    .upload-card input[type=file] {
        position: absolute; opacity: 0;
        inset: 0; cursor: pointer;
    }
    .upload-card .preview {
        width: 100%;
        max-height: 140px;
        object-fit: contain;
        border-radius: 8px;
        margin-bottom: .5rem;
    }
    .upload-card.has-image { padding: .65rem; border-style: solid; border-color: #e2e8f0; }

    /* ── Form footer (sticky) ────────────────────────────── */
    .form-footer {
        position: sticky;
        bottom: 0;
        background: #fff;
        margin: 1.5rem -1.25rem -1.25rem;
        padding: 1rem 1.25rem;
        border-top: 1px solid var(--brand-border);
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
        flex-wrap: wrap;
        border-radius: 0 0 14px 14px;
        z-index: 5;
    }
    .form-footer .nav-btns { display: flex; gap: .5rem; }

    /* ── Section title inside tab ────────────────────────── */
    .pane-head {
        display: flex; align-items: center; gap: .65rem;
        margin-bottom: 1.1rem;
        padding-bottom: .75rem;
        border-bottom: 1px solid var(--brand-border);
    }
    .pane-head .icon {
        width: 36px; height: 36px;
        background: #eef2ff; color: #1e3a8a;
        border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.05rem;
    }
    .pane-head h6 { margin: 0; color: var(--brand-navy); font-weight: 800; }
    .pane-head p  { margin: 0; font-size: .76rem; color: var(--text-muted); }

    /* tab error badge */
    .form-tabs button .err-dot {
        width: 8px; height: 8px;
        background: #ef4444;
        border-radius: 50%;
        display: none;
    }
    .form-tabs button.has-error .err-dot { display: inline-block; }

    /* ── Responsive ─────────────────────────────────────── */
    @media (max-width: 767.98px) {
        .form-tabs { gap: 0; }
        .form-tabs button { padding: .65rem .75rem; font-size: .82rem; }
        .form-tabs button i { display: none; }
        .form-tabs button .tab-num { width: 20px; height: 20px; font-size: .68rem; }
        .pane-head { gap: .5rem; margin-bottom: .85rem; padding-bottom: .55rem; }
        .pane-head .icon { width: 32px; height: 32px; font-size: .95rem; }
        .pane-head h6 { font-size: .95rem; }
        .pane-head p { font-size: .72rem; }
        .upload-card { min-height: 130px; padding: .85rem; }
        .upload-card .up-icon { width: 40px; height: 40px; font-size: 1.15rem; margin-bottom: .45rem; }
        .upload-card label { font-size: .82rem; }
        .upload-card .preview { max-height: 110px; }
    }
    @media (max-width: 575.98px) {
        .form-tabs button { padding: .55rem .55rem; font-size: .76rem; gap: .25rem; }
        .form-tabs button .tab-num { display: none; }
        .form-footer {
            margin: 1rem -.85rem -.85rem;
            padding: .75rem .85rem;
            flex-direction: column-reverse;
            align-items: stretch;
            gap: .5rem;
        }
        .form-footer .nav-btns { flex-direction: row; justify-content: space-between; width: 100%; }
        .form-footer .nav-btns .btn { flex: 1; padding: .55rem .55rem; font-size: .82rem; }
        .form-footer > a.btn-light { width: 100%; }
    }
</style>

{{-- ════════════════════════════════════════════════════════════
     Tabs Nav
     ════════════════════════════════════════════════════════════ --}}
<div class="form-tabs" role="tablist">
    <button type="button" class="active" data-tab="tab-basic">
        <span class="tab-num">1</span>
        <i class="bi bi-person"></i> البيانات الأساسية
        <span class="err-dot"></span>
    </button>
    <button type="button" data-tab="tab-passport">
        <span class="tab-num">2</span>
        <i class="bi bi-passport"></i> جواز السفر
        <span class="err-dot"></span>
    </button>
    <button type="button" data-tab="tab-contact">
        <span class="tab-num">3</span>
        <i class="bi bi-telephone"></i> الاتصال والعنوان
        <span class="err-dot"></span>
    </button>
    <button type="button" data-tab="tab-classify">
        <span class="tab-num">4</span>
        <i class="bi bi-tags"></i> التصنيف
        <span class="err-dot"></span>
    </button>
    <button type="button" data-tab="tab-attach">
        <span class="tab-num">5</span>
        <i class="bi bi-images"></i> المرفقات والملاحظات
        <span class="err-dot"></span>
    </button>
</div>

{{-- ════════════════════════════════════════════════════════════
     1) Basic info
     ════════════════════════════════════════════════════════════ --}}
<div class="tab-pane active" id="tab-basic">
    <div class="pane-head">
        <div class="icon"><i class="bi bi-person-vcard"></i></div>
        <div>
            <h6>البيانات الأساسية للعميل</h6>
            <p>اسم العميل، رقمه القومي، جنسه، جنسيته وتفاصيله الشخصية</p>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">الاسم رباعي بالعربي <span class="required-mark">*</span></label>
            <input type="text" name="full_name" value="{{ old('full_name', $customer->full_name ?? '') }}"
                   class="form-control @error('full_name') is-invalid @enderror"
                   placeholder="مثال: محمد أحمد علي حسن" required>
            @error('full_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-md-6">
            <label class="form-label">الاسم بالإنجليزي (يطابق الجواز)</label>
            <input type="text" name="full_name_en" value="{{ old('full_name_en', $customer->full_name_en ?? '') }}"
                   class="form-control" dir="ltr" placeholder="MOHAMED AHMED ALI HASSAN">
        </div>

        <div class="col-md-4">
            <label class="form-label">الرقم القومي</label>
            <input type="text" name="national_id" value="{{ old('national_id', $customer->national_id ?? '') }}"
                   class="form-control @error('national_id') is-invalid @enderror"
                   maxlength="14" inputmode="numeric" placeholder="14 رقم">
            @error('national_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-md-4">
            <label class="form-label">تاريخ الميلاد</label>
            <input type="date" name="birth_date"
                   value="{{ old('birth_date', isset($customer) ? optional($customer->birth_date)->format('Y-m-d') : '') }}"
                   class="form-control @error('birth_date') is-invalid @enderror">
            @error('birth_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-md-4">
            <label class="form-label">الجنس <span class="required-mark">*</span></label>
            <select name="gender" class="form-select" required>
                <option value="male"   {{ old('gender', $customer->gender ?? '') === 'male'   ? 'selected' : '' }}>ذكر</option>
                <option value="female" {{ old('gender', $customer->gender ?? '') === 'female' ? 'selected' : '' }}>أنثى</option>
            </select>
        </div>

        <div class="col-md-4">
            <label class="form-label">الجنسية</label>
            <input type="text" name="nationality" value="{{ old('nationality', $customer->nationality ?? 'مصري') }}"
                   class="form-control" placeholder="مصري / سعودي / ...">
        </div>

        <div class="col-md-4">
            <label class="form-label">الديانة</label>
            <input type="text" name="religion" value="{{ old('religion', $customer->religion ?? 'مسلم') }}" class="form-control">
        </div>

        <div class="col-md-4">
            <label class="form-label">الحالة الاجتماعية</label>
            <select name="marital_status" class="form-select">
                @php $ms = old('marital_status', $customer->marital_status ?? ''); @endphp
                <option value="">اختر</option>
                <option value="أعزب" {{ $ms==='أعزب' ? 'selected' : '' }}>أعزب</option>
                <option value="متزوج" {{ $ms==='متزوج' ? 'selected' : '' }}>متزوج</option>
                <option value="مطلق" {{ $ms==='مطلق' ? 'selected' : '' }}>مطلق</option>
                <option value="أرمل" {{ $ms==='أرمل' ? 'selected' : '' }}>أرمل</option>
            </select>
        </div>
    </div>
</div>

{{-- ════════════════════════════════════════════════════════════
     2) Passport
     ════════════════════════════════════════════════════════════ --}}
<div class="tab-pane" id="tab-passport">
    <div class="pane-head">
        <div class="icon" style="background:#fef3c7;color:#b45309;"><i class="bi bi-passport"></i></div>
        <div>
            <h6>بيانات جواز السفر</h6>
            <p>رقم الجواز وتواريخه — مهم للحجوزات الدولية والعمرة والحج</p>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-md-4">
            <label class="form-label">رقم الجواز</label>
            <input type="text" name="passport_number" value="{{ old('passport_number', $customer->passport_number ?? '') }}"
                   class="form-control" dir="ltr" placeholder="A12345678">
        </div>

        <div class="col-md-4">
            <label class="form-label">تاريخ الإصدار</label>
            <input type="date" name="passport_issue_date"
                   value="{{ old('passport_issue_date', isset($customer) ? optional($customer->passport_issue_date)->format('Y-m-d') : '') }}"
                   class="form-control">
        </div>

        <div class="col-md-4">
            <label class="form-label">تاريخ الانتهاء <i class="bi bi-info-circle text-muted small" title="يجب أن يكون ساري لمدة 6 أشهر على الأقل للسفر"></i></label>
            <input type="date" name="passport_expiry_date"
                   value="{{ old('passport_expiry_date', isset($customer) ? optional($customer->passport_expiry_date)->format('Y-m-d') : '') }}"
                   class="form-control @error('passport_expiry_date') is-invalid @enderror">
            @error('passport_expiry_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-12">
            <label class="form-label">مكان الإصدار</label>
            <input type="text" name="passport_issue_place" value="{{ old('passport_issue_place', $customer->passport_issue_place ?? '') }}"
                   class="form-control" placeholder="القاهرة / الإسكندرية / ...">
        </div>
    </div>
</div>

{{-- ════════════════════════════════════════════════════════════
     3) Contact
     ════════════════════════════════════════════════════════════ --}}
<div class="tab-pane" id="tab-contact">
    <div class="pane-head">
        <div class="icon" style="background:#dcfce7;color:#15803d;"><i class="bi bi-telephone"></i></div>
        <div>
            <h6>بيانات الاتصال والعنوان</h6>
            <p>طرق التواصل مع العميل وعنوانه التفصيلي</p>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-md-3">
            <label class="form-label">الهاتف <span class="required-mark">*</span></label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-telephone text-success"></i></span>
                <input type="text" name="phone" value="{{ old('phone', $customer->phone ?? '') }}"
                       class="form-control @error('phone') is-invalid @enderror" dir="ltr" required>
            </div>
            @error('phone')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>

        <div class="col-md-3">
            <label class="form-label">الجوال</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-phone text-info"></i></span>
                <input type="text" name="mobile" value="{{ old('mobile', $customer->mobile ?? '') }}" class="form-control" dir="ltr">
            </div>
        </div>

        <div class="col-md-3">
            <label class="form-label">واتساب</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-whatsapp text-success"></i></span>
                <input type="text" name="whatsapp" value="{{ old('whatsapp', $customer->whatsapp ?? '') }}" class="form-control" dir="ltr">
            </div>
        </div>

        <div class="col-md-3">
            <label class="form-label">البريد الإلكتروني</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-envelope text-primary"></i></span>
                <input type="email" name="email" value="{{ old('email', $customer->email ?? '') }}"
                       class="form-control @error('email') is-invalid @enderror" dir="ltr">
            </div>
            @error('email')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>

        <div class="col-md-6">
            <label class="form-label">العنوان التفصيلي</label>
            <input type="text" name="address" value="{{ old('address', $customer->address ?? '') }}"
                   class="form-control" placeholder="الشارع، رقم المبنى، الحي...">
        </div>

        <div class="col-md-2">
            <label class="form-label">المدينة</label>
            <input type="text" name="city" value="{{ old('city', $customer->city ?? '') }}" class="form-control">
        </div>

        <div class="col-md-2">
            <label class="form-label">المحافظة</label>
            <input type="text" name="governorate" value="{{ old('governorate', $customer->governorate ?? '') }}" class="form-control">
        </div>

        <div class="col-md-2">
            <label class="form-label">الدولة</label>
            <input type="text" name="country" value="{{ old('country', $customer->country ?? 'مصر') }}" class="form-control">
        </div>
    </div>
</div>

{{-- ════════════════════════════════════════════════════════════
     4) Classification
     ════════════════════════════════════════════════════════════ --}}
<div class="tab-pane" id="tab-classify">
    <div class="pane-head">
        <div class="icon" style="background:#e0e7ff;color:#4338ca;"><i class="bi bi-tags"></i></div>
        <div>
            <h6>تصنيف العميل وحالته</h6>
            <p>نوع التعامل مع العميل وحالة حسابه في النظام</p>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">نوع العميل <span class="required-mark">*</span></label>
            <select name="type" class="form-select" required>
                @php $t = old('type', $customer->type ?? 'individual'); @endphp
                <option value="individual" {{ $t==='individual' ? 'selected' : '' }}>👤 فرد</option>
                <option value="agency"     {{ $t==='agency'     ? 'selected' : '' }}>💼 وكيل سياحي</option>
                <option value="group"      {{ $t==='group'      ? 'selected' : '' }}>👥 مجموعة / شركة</option>
            </select>
            <small class="text-muted">يحدد نظام الأسعار والعمولات</small>
        </div>

        <div class="col-md-6">
            <label class="form-label">الحالة <span class="required-mark">*</span></label>
            <select name="status" class="form-select" required>
                @php $s = old('status', $customer->status ?? 'active'); @endphp
                <option value="active"      {{ $s==='active'      ? 'selected' : '' }}>✅ نشط</option>
                <option value="inactive"    {{ $s==='inactive'    ? 'selected' : '' }}>⏸️ غير نشط</option>
                <option value="blacklisted" {{ $s==='blacklisted' ? 'selected' : '' }}>🚫 محظور</option>
            </select>
            <small class="text-muted">العملاء المحظورون لا يمكن إنشاء حجوزات لهم</small>
        </div>
    </div>
</div>

{{-- ════════════════════════════════════════════════════════════
     5) Attachments & Notes
     ════════════════════════════════════════════════════════════ --}}
<div class="tab-pane" id="tab-attach">
    <div class="pane-head">
        <div class="icon" style="background:#ffedd5;color:#c2410c;"><i class="bi bi-images"></i></div>
        <div>
            <h6>الصور والمرفقات</h6>
            <p>الصورة الشخصية، صورة الجواز، وصورة الرقم القومي</p>
        </div>
    </div>

    <div class="row g-3">
        {{-- Photo --}}
        <div class="col-md-4">
            <label class="form-label fw-bold">الصورة الشخصية</label>
            <div class="upload-card {{ isset($customer) && $customer->photo ? 'has-image' : '' }}" data-upload="photo">
                @if(isset($customer) && $customer->photo)
                    <img src="{{ $customer->photo_url }}" class="preview" id="preview-photo">
                @else
                    <img class="preview d-none" id="preview-photo">
                    <div class="up-icon"><i class="bi bi-person-circle"></i></div>
                @endif
                <label>اختر صورة</label>
                <div class="up-hint">JPG / PNG — أقصى 2MB</div>
                <input type="file" name="photo" accept="image/*" data-preview="preview-photo">
            </div>
            @error('photo')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>

        {{-- Passport image --}}
        <div class="col-md-4">
            <label class="form-label fw-bold">صورة جواز السفر</label>
            <div class="upload-card {{ isset($customer) && $customer->passport_image ? 'has-image' : '' }}" data-upload="passport_image">
                @if(isset($customer) && $customer->passport_image)
                    <img src="{{ asset('storage/'.$customer->passport_image) }}" class="preview" id="preview-passport">
                @else
                    <img class="preview d-none" id="preview-passport">
                    <div class="up-icon" style="color:#b45309;background:#fef3c7;"><i class="bi bi-passport"></i></div>
                @endif
                <label>اختر صورة الجواز</label>
                <div class="up-hint">صفحة البيانات الرئيسية</div>
                <input type="file" name="passport_image" accept="image/*" data-preview="preview-passport">
            </div>
        </div>

        {{-- National ID image --}}
        <div class="col-md-4">
            <label class="form-label fw-bold">صورة الرقم القومي</label>
            <div class="upload-card {{ isset($customer) && $customer->national_id_image ? 'has-image' : '' }}" data-upload="national_id_image">
                @if(isset($customer) && $customer->national_id_image)
                    <img src="{{ asset('storage/'.$customer->national_id_image) }}" class="preview" id="preview-nid">
                @else
                    <img class="preview d-none" id="preview-nid">
                    <div class="up-icon" style="color:#1d4ed8;background:#dbeafe;"><i class="bi bi-card-text"></i></div>
                @endif
                <label>اختر صورة البطاقة</label>
                <div class="up-hint">الوجه الأمامي للبطاقة</div>
                <input type="file" name="national_id_image" accept="image/*" data-preview="preview-nid">
            </div>
        </div>

        <div class="col-12">
            <label class="form-label fw-bold">
                <i class="bi bi-sticky text-secondary me-1"></i> ملاحظات داخلية
            </label>
            <textarea name="notes" rows="3" class="form-control"
                      placeholder="أي ملاحظات مهمة عن العميل (تفضيلات الفنادق، الطعام، الحساسية، تعاملات سابقة...)">{{ old('notes', $customer->notes ?? '') }}</textarea>
        </div>
    </div>
</div>

{{-- ════════════════════════════════════════════════════════════
     Sticky footer
     ════════════════════════════════════════════════════════════ --}}
<div class="form-footer">
    <a href="{{ route('admin.customers.index') }}" class="btn btn-light">
        <i class="bi bi-arrow-right ms-1"></i> إلغاء والعودة
    </a>
    <div class="nav-btns">
        <button type="button" class="btn btn-outline-secondary" id="prevTab">
            <i class="bi bi-arrow-right"></i> السابق
        </button>
        <button type="button" class="btn btn-outline-primary" id="nextTab">
            التالي <i class="bi bi-arrow-left"></i>
        </button>
        <button type="submit" class="btn btn-primary" id="saveBtn">
            <i class="bi bi-save ms-1"></i> حفظ العميل
        </button>
    </div>
</div>

<script>
(function () {
    const tabs   = document.querySelectorAll('.form-tabs button');
    const panes  = document.querySelectorAll('.tab-pane');
    const prev   = document.getElementById('prevTab');
    const next   = document.getElementById('nextTab');

    function setActive(i) {
        tabs.forEach((t, idx) => t.classList.toggle('active', idx === i));
        panes.forEach((p, idx) => p.classList.toggle('active', idx === i));
        prev.disabled = i === 0;
        next.style.display = i === tabs.length - 1 ? 'none' : '';
    }

    tabs.forEach((t, i) => t.addEventListener('click', () => setActive(i)));
    prev.addEventListener('click', () => {
        const i = [...tabs].findIndex(t => t.classList.contains('active'));
        if (i > 0) setActive(i - 1);
    });
    next.addEventListener('click', () => {
        const i = [...tabs].findIndex(t => t.classList.contains('active'));
        if (i < tabs.length - 1) setActive(i + 1);
    });

    // Initial state: jump to first tab with error, if any
    const errorTabs = [...tabs].map((t, idx) => {
        const pane = panes[idx];
        const hasErr = pane.querySelector('.is-invalid, .invalid-feedback');
        if (hasErr) t.classList.add('has-error');
        return hasErr ? idx : -1;
    }).filter(i => i >= 0);
    if (errorTabs.length) setActive(errorTabs[0]);
    else setActive(0);

    // Image preview
    document.querySelectorAll('input[type=file][data-preview]').forEach(inp => {
        inp.addEventListener('change', e => {
            const file = e.target.files[0];
            if (!file) return;
            const previewId = inp.dataset.preview;
            const img = document.getElementById(previewId);
            const card = inp.closest('.upload-card');
            const oldIcon = card.querySelector('.up-icon');
            const reader = new FileReader();
            reader.onload = ev => {
                img.src = ev.target.result;
                img.classList.remove('d-none');
                if (oldIcon) oldIcon.style.display = 'none';
                card.classList.add('has-image');
            };
            reader.readAsDataURL(file);
        });
    });
})();
</script>
