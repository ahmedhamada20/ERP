@php
    /** @var \App\Models\Lead|null $lead */
    $lead ??= null;
    $isEdit = $lead && $lead->exists;
@endphp

<style>
    .form-wrap { background:linear-gradient(180deg, #f8fafc 0%, #fff 100%); border-radius:18px; padding:1.5rem; box-shadow:0 2px 8px rgba(15,23,42,.05); margin-bottom:1rem; }
    .section-card { background:#fff; border-radius:14px; border:1px solid #f1f5f9; margin-bottom:1rem; overflow:hidden; }
    .section-card .head { padding:1rem 1.25rem; background:linear-gradient(135deg, #fafbff, #f8fafc); border-bottom:1px solid #f1f5f9; display:flex; align-items:center; gap:.75rem; }
    .section-card .head .sec-icon { width:38px; height:38px; border-radius:10px; background:linear-gradient(135deg, #dbeafe, #bfdbfe); color:#1d4ed8; display:flex; align-items:center; justify-content:center; font-size:1.1rem; flex-shrink:0; }
    .section-card .head h6 { margin:0; color:var(--brand-navy); font-weight:800; }
    .section-card .head .sub { font-size:.72rem; color:#64748b; margin-top:1px; }
    .section-card .body { padding:1.25rem; }

    .form-label { font-size:.82rem; font-weight:700; color:#475569; margin-bottom:.4rem; }
    .form-label .req { color:#dc2626; font-weight:900; }
    .form-label .hint { font-size:.68rem; color:#94a3b8; font-weight:500; margin-right:.35rem; }
    .form-control, .form-select { height:44px; font-size:.9rem; border-radius:11px; border:1.5px solid #e2e8f0; }
    .form-control:focus, .form-select:focus { border-color:var(--brand-gold); box-shadow:0 0 0 .2rem rgba(212,164,55,.15); }
    textarea.form-control { height:auto; min-height:90px; }

    .form-footer { background:#fff; border-top:1px solid #f1f5f9; padding:1rem 1.25rem; border-radius:0 0 14px 14px; display:flex; justify-content:flex-end; gap:.65rem; flex-wrap:wrap; }
    .form-footer .btn { min-width:140px; }

    .meta-card { background:#eef2ff; border-radius:12px; padding:1rem; font-size:.82rem; border-right:4px solid #4338ca; }
    .meta-card .meta-kv { display:flex; justify-content:space-between; padding:.35rem 0; border-bottom:1px dashed #c7d2fe; }
    .meta-card .meta-kv:last-child { border-bottom:none; }
    .meta-card .meta-kv .k { color:#6366f1; font-weight:600; }
    .meta-card .meta-kv .v { color:#1e293b; font-weight:700; }
</style>

<div class="form-wrap">
    <div class="row g-3">
        <div class="col-lg-8">
            {{-- Section 1: Contact info --}}
            <div class="section-card">
                <div class="head">
                    <div class="sec-icon"><i class="bi bi-person-vcard"></i></div>
                    <div>
                        <h6>بيانات التواصل</h6>
                        <div class="sub">المعلومات الأساسية للعميل المحتمل</div>
                    </div>
                </div>
                <div class="body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">الاسم الكامل <span class="req">*</span></label>
                            <input type="text" name="full_name" class="form-control @error('full_name') is-invalid @enderror"
                                   value="{{ old('full_name', $lead?->full_name ?? '') }}" required maxlength="200">
                            @error('full_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">رقم الهاتف <span class="req">*</span></label>
                            <input type="tel" name="phone" id="phoneInput" class="form-control @error('phone') is-invalid @enderror"
                                   value="{{ old('phone', $lead?->phone ?? '') }}" required dir="ltr">
                            @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">واتساب <span class="hint">يُملأ من رقم الهاتف لو فاضي</span></label>
                            <input type="tel" name="whatsapp" id="whatsappInput" class="form-control"
                                   value="{{ old('whatsapp', $lead?->whatsapp ?? '') }}" dir="ltr">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">البريد الإلكتروني</label>
                            <input type="email" name="email" class="form-control"
                                   value="{{ old('email', $lead?->email ?? '') }}" dir="ltr">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">المدينة</label>
                            <input type="text" name="city" class="form-control"
                                   value="{{ old('city', $lead?->city ?? '') }}" maxlength="120">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Section 2: Sales info --}}
            <div class="section-card">
                <div class="head">
                    <div class="sec-icon" style="background:linear-gradient(135deg,#fef3c7,#fde68a); color:#92400e;">
                        <i class="bi bi-graph-up"></i>
                    </div>
                    <div>
                        <h6>بيانات المبيعات</h6>
                        <div class="sub">المصدر، الاهتمام، الحالة، والقيمة المتوقعة</div>
                    </div>
                </div>
                <div class="body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">المصدر <span class="req">*</span></label>
                            <select name="source" class="form-select" required>
                                @foreach(\App\Models\Lead::SOURCE_LABELS as $val => $label)
                                    <option value="{{ $val }}" {{ old('source', $lead?->source ?? 'other') === $val ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">نوع الاهتمام <span class="req">*</span></label>
                            <select name="interest_type" class="form-select" required>
                                @foreach(\App\Models\Lead::INTEREST_LABELS as $val => $label)
                                    <option value="{{ $val }}" {{ old('interest_type', $lead?->interest_type ?? 'other') === $val ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        @if($isEdit)
                        <div class="col-md-4">
                            <label class="form-label">الحالة</label>
                            <select name="status" class="form-select" id="statusSelect">
                                @foreach(\App\Models\Lead::STATUS_LABELS as $val => $label)
                                    <option value="{{ $val }}" {{ old('status', $lead?->status) === $val ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        @endif

                        <div class="col-md-6">
                            <label class="form-label">القيمة المتوقعة</label>
                            <div class="input-group">
                                <input type="number" name="estimated_value" min="0" step="0.01" class="form-control"
                                       value="{{ old('estimated_value', $lead?->estimated_value ?? 0) }}">
                                <span class="input-group-text" style="background:#fef3c7; color:#92400e; font-weight:800;">ج.م</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">تاريخ الإغلاق المتوقع</label>
                            <input type="date" name="expected_close_date" class="form-control"
                                   value="{{ old('expected_close_date', $lead?->expected_close_date?->format('Y-m-d') ?? '') }}">
                        </div>

                        @if($isEdit)
                        <div class="col-12" id="lostReasonWrap" style="display:none">
                            <label class="form-label">سبب الخسارة <span class="hint">مطلوب لو الحالة "خاسر"</span></label>
                            <input type="text" name="lost_reason" class="form-control"
                                   value="{{ old('lost_reason', $lead?->lost_reason ?? '') }}" maxlength="200">
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Section 3: Notes --}}
            <div class="section-card">
                <div class="head">
                    <div class="sec-icon" style="background:linear-gradient(135deg,#f3e8ff,#e9d5ff); color:#6b21a8;">
                        <i class="bi bi-sticky"></i>
                    </div>
                    <div>
                        <h6>ملاحظات</h6>
                        <div class="sub">أي معلومات إضافية مهمة</div>
                    </div>
                </div>
                <div class="body">
                    <textarea name="notes" rows="3" class="form-control" placeholder="مثال: مهتم بالعمرة في رمضان، يفضل السكن قريب من الحرم...">{{ old('notes', $lead?->notes ?? '') }}</textarea>
                </div>
            </div>
        </div>

        {{-- Sidebar column --}}
        <div class="col-lg-4">
            <div class="section-card">
                <div class="head">
                    <div class="sec-icon" style="background:linear-gradient(135deg,#ccfbf1,#a7f3d0); color:#0f766e;">
                        <i class="bi bi-person-workspace"></i>
                    </div>
                    <div>
                        <h6>المسؤولية</h6>
                        <div class="sub">من يتابع هذا الـ Lead</div>
                    </div>
                </div>
                <div class="body">
                    <label class="form-label">الموظف المسؤول</label>
                    <select name="assigned_to" class="form-select">
                        <option value="">— أنا (الافتراضي) —</option>
                        @foreach($employees as $u)
                            <option value="{{ $u->id }}" {{ old('assigned_to', $lead?->assigned_to ?? auth()->id()) == $u->id ? 'selected' : '' }}>
                                {{ $u->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            @if($isEdit)
            <div class="section-card">
                <div class="head">
                    <div class="sec-icon" style="background:linear-gradient(135deg,#e0e7ff,#c7d2fe); color:#4338ca;">
                        <i class="bi bi-hash"></i>
                    </div>
                    <div>
                        <h6>معلومات النظام</h6>
                    </div>
                </div>
                <div class="body">
                    <div class="meta-card">
                        <div class="meta-kv">
                            <span class="k">الكود</span>
                            <span class="v" dir="ltr"><code>{{ $lead->code }}</code></span>
                        </div>
                        <div class="meta-kv">
                            <span class="k">الحالة الحالية</span>
                            <span class="v">{{ $lead->status_label }}</span>
                        </div>
                        <div class="meta-kv">
                            <span class="k">تاريخ الإنشاء</span>
                            <span class="v">{{ $lead->created_at?->format('Y-m-d') }}</span>
                        </div>
                        @if($lead->isConverted())
                        <div class="meta-kv">
                            <span class="k">العميل المُحوّل</span>
                            <span class="v">
                                <a href="{{ route('admin.customers.show', $lead->customer) }}">{{ $lead->customer?->code }}</a>
                            </span>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>

    <div class="form-footer">
        <a href="{{ route('admin.crm.leads.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-x-circle"></i> إلغاء
        </a>
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-circle"></i> {{ $isEdit ? 'حفظ التعديلات' : 'إنشاء Lead' }}
        </button>
    </div>
</div>

@push('scripts')
<script>
$(function () {
    // Auto-fill whatsapp from phone if empty
    $('#phoneInput').on('blur', function () {
        const $wa = $('#whatsappInput');
        if (!$wa.val().trim()) $wa.val($(this).val());
    });

    // Show "lost reason" field when status=lost
    $('#statusSelect').on('change', function () {
        $('#lostReasonWrap').toggle($(this).val() === 'lost');
    }).trigger('change');
});
</script>
@endpush
