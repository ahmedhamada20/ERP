@php
    /** @var \App\Models\VisaType|null $visa */
    $visa ??= null;
    $isEdit = $visa && $visa->exists;
@endphp

<style>
    .form-wrap { background: linear-gradient(180deg, #f8fafc 0%, #fff 100%); border-radius: 18px; padding: 1.5rem; margin-bottom: 1rem; box-shadow: 0 2px 8px rgba(15,23,42,.05); }
    .section-card { background: #fff; border-radius: 14px; border: 1px solid #f1f5f9; margin-bottom: 1rem; overflow: hidden; }
    .section-card .head { padding: 1rem 1.25rem; background: linear-gradient(135deg, #fafbff, #f8fafc); border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; gap: .75rem; }
    .section-card .head .sec-icon { width: 38px; height: 38px; border-radius: 10px; background: linear-gradient(135deg, #fef3c7, #fde68a); color: #92400e; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; }
    .section-card .head h6 { margin: 0; color: var(--brand-navy); font-weight: 800; }
    .section-card .body { padding: 1.25rem; }
    .form-label { font-size: .82rem; font-weight: 700; color: #475569; margin-bottom: .4rem; }
    .form-label .req { color: #dc2626; }
    .form-control, .form-select { height: 44px; font-size: .9rem; border-radius: 11px; border: 1.5px solid #e2e8f0; }
    .form-control:focus, .form-select:focus { border-color: var(--brand-gold); box-shadow: 0 0 0 .2rem rgba(212,164,55,.15); }
    textarea.form-control { height: auto; min-height: 80px; }
    .req-row { display: flex; gap: .35rem; align-items: center; margin-bottom: .35rem; background: #f8fafc; padding: .35rem .55rem; border-radius: 8px; border: 1px solid #e2e8f0; }
    .req-row .form-control { height: 36px; font-size: .85rem; }
</style>

<div class="form-wrap">
    <div class="section-card">
        <div class="head">
            <div class="sec-icon"><i class="bi bi-passport-fill"></i></div>
            <div><h6>بيانات التأشيرة</h6></div>
        </div>
        <div class="body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">الاسم <span class="req">*</span></label>
                    <input type="text" name="name" class="form-control" placeholder="تأشيرة سياحية للإمارات"
                           value="{{ old('name', $visa?->name ?? '') }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">الدولة <span class="req">*</span></label>
                    <input type="text" name="country" class="form-control" placeholder="الإمارات"
                           value="{{ old('country', $visa?->country ?? '') }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">النوع <span class="req">*</span></label>
                    <select name="type" class="form-select" required>
                        @foreach(\App\Models\VisaType::TYPE_LABELS as $v => $l)
                            <option value="{{ $v }}" @selected(old('type', $visa?->type ?? 'tourist') === $v)>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">مدة الإقامة (يوم) <span class="req">*</span></label>
                    <input type="number" name="duration_days" class="form-control" min="1" max="365"
                           value="{{ old('duration_days', $visa?->duration_days ?? 30) }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">مدة الإصدار (يوم) <span class="req">*</span></label>
                    <input type="number" name="processing_days" class="form-control" min="1" max="90"
                           value="{{ old('processing_days', $visa?->processing_days ?? 7) }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">صلاحية (شهر) <span class="req">*</span></label>
                    <input type="number" name="validity_months" class="form-control" min="1" max="60"
                           value="{{ old('validity_months', $visa?->validity_months ?? 3) }}" required>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <label class="d-flex align-items-center gap-2 mb-2">
                        <input type="hidden" name="multiple_entry" value="0">
                        <input type="checkbox" name="multiple_entry" value="1" class="form-check-input"
                               @checked(old('multiple_entry', $visa?->multiple_entry ?? false))>
                        <strong>دخول متعدد</strong>
                    </label>
                </div>
            </div>
        </div>
    </div>

    <div class="section-card">
        <div class="head">
            <div class="sec-icon" style="background:linear-gradient(135deg,#dcfce7,#bbf7d0); color:#15803d;"><i class="bi bi-cash-stack"></i></div>
            <div><h6>الرسوم والمورد</h6></div>
        </div>
        <div class="body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">الرسوم الأساسية <span class="req">*</span></label>
                    <input type="number" name="base_fee" class="form-control" min="0" step="0.01"
                           value="{{ old('base_fee', $visa?->base_fee ?? '') }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">رسوم الخدمة</label>
                    <input type="number" name="service_fee" class="form-control" min="0" step="0.01"
                           value="{{ old('service_fee', $visa?->service_fee ?? 0) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">العملة <span class="req">*</span></label>
                    <select name="currency" class="form-select" required>
                        @foreach(['EGP'=>'EGP - جنيه','SAR'=>'SAR - ريال','USD'=>'USD - دولار'] as $v=>$l)
                            <option value="{{ $v }}" @selected(old('currency', $visa?->currency ?? 'EGP') === $v)>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">اسم المورد</label>
                    <input type="text" name="supplier_name" class="form-control" placeholder="مكتب الجوازات"
                           value="{{ old('supplier_name', $visa?->supplier_name ?? '') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">رقم تواصل المورد</label>
                    <input type="text" name="supplier_contact" class="form-control" dir="ltr"
                           value="{{ old('supplier_contact', $visa?->supplier_contact ?? '') }}">
                </div>
            </div>
        </div>
    </div>

    <div class="section-card">
        <div class="head">
            <div class="sec-icon" style="background:linear-gradient(135deg,#e0e7ff,#c7d2fe); color:#4338ca;"><i class="bi bi-list-check"></i></div>
            <div><h6>المستندات المطلوبة + ملاحظات</h6></div>
        </div>
        <div class="body">
            <label class="form-label">المستندات المطلوبة</label>
            <div id="reqContainer">
                @php $reqs = old('requirements', $visa?->requirements ?? []); @endphp
                @foreach($reqs as $i => $r)
                <div class="req-row">
                    <input type="text" name="requirements[]" class="form-control" placeholder="مستند مطلوب..." value="{{ $r }}">
                    <button type="button" class="btn btn-sm btn-outline-danger req-remove"><i class="bi bi-x"></i></button>
                </div>
                @endforeach
            </div>
            <button type="button" id="addReqBtn" class="btn btn-sm btn-outline-primary mb-3">
                <i class="bi bi-plus-circle"></i> إضافة مستند
            </button>

            <label class="form-label">ملاحظات</label>
            <textarea name="notes" rows="3" class="form-control">{{ old('notes', $visa?->notes ?? '') }}</textarea>

            <label class="d-flex align-items-center gap-2 mt-3">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" class="form-check-input"
                       @checked(old('is_active', $visa?->is_active ?? true))>
                <strong>نشط</strong>
            </label>
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2">
        <a href="{{ route('admin.catalog.visas.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-x-circle"></i> إلغاء
        </a>
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-circle"></i> {{ $isEdit ? 'حفظ التعديلات' : 'حفظ التأشيرة' }}
        </button>
    </div>
</div>

@push('scripts')
<script>
$(function () {
    $('#addReqBtn').on('click', () => {
        const html = `
            <div class="req-row">
                <input type="text" name="requirements[]" class="form-control" placeholder="مستند مطلوب...">
                <button type="button" class="btn btn-sm btn-outline-danger req-remove"><i class="bi bi-x"></i></button>
            </div>`;
        $('#reqContainer').append(html);
    });
    $(document).on('click', '.req-remove', function () { $(this).closest('.req-row').remove(); });
});
</script>
@endpush
