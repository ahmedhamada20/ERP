@php
    /** @var \App\Models\Opportunity|null $opp */
    $opp ??= null;
    $preselected_lead ??= null;
    $isEdit = $opp && $opp->exists;
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
    .form-control, .form-select { height:44px; font-size:.9rem; border-radius:11px; border:1.5px solid #e2e8f0; }
    .form-control:focus, .form-select:focus { border-color:var(--brand-gold); box-shadow:0 0 0 .2rem rgba(212,164,55,.15); }
    textarea.form-control { height:auto; min-height:80px; }

    .form-footer { background:#fff; border-top:1px solid #f1f5f9; padding:1rem 1.25rem; border-radius:0 0 14px 14px; display:flex; justify-content:flex-end; gap:.65rem; flex-wrap:wrap; }
    .form-footer .btn { min-width:140px; }

    .btype-grid { display:grid; grid-template-columns:1fr 1fr; gap:.75rem; }
    .btype-card {
        background:#fff; border:2px solid #e2e8f0; border-radius:12px;
        padding:1rem; text-align:center; cursor:pointer; transition:all .2s;
    }
    .btype-card:hover { border-color:var(--brand-gold); transform:translateY(-2px); }
    .btype-card input { position:absolute; opacity:0; }
    .btype-card .big-ic { font-size:1.6rem; color:#94a3b8; margin-bottom:.35rem; }
    .btype-card .tt { font-weight:800; font-size:.9rem; color:var(--brand-navy); }
    .btype-card.selected { border-color:var(--brand-gold); background:linear-gradient(135deg, #fffbeb, #fef3c7); }
    .btype-card.selected .big-ic { color:#b45309; }
</style>

<div class="form-wrap">
    <div class="row g-3">
        <div class="col-lg-8">
            {{-- Section 1: Basic info --}}
            <div class="section-card">
                <div class="head">
                    <div class="sec-icon"><i class="bi bi-info-circle"></i></div>
                    <div>
                        <h6>بيانات الصفقة</h6>
                        <div class="sub">العنوان، المصدر (Lead/Customer)، الوصف</div>
                    </div>
                </div>
                <div class="body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">عنوان الصفقة <span class="req">*</span></label>
                            <input type="text" name="title" class="form-control"
                                   placeholder="مثال: صفقة عمرة رمضان - عائلة محمد"
                                   value="{{ old('title', $opp?->title ?? '') }}" required maxlength="200">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">العميل المحتمل (Lead)</label>
                            <select name="lead_id" class="form-select select2">
                                <option value="">— بدون lead —</option>
                                @foreach($leads as $l)
                                    <option value="{{ $l->id }}"
                                        {{ old('lead_id', $opp?->lead_id ?? $preselected_lead?->id) == $l->id ? 'selected' : '' }}>
                                        {{ $l->code }} — {{ $l->full_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">العميل (موجود)</label>
                            <select name="customer_id" class="form-select select2">
                                <option value="">— اختر عميل أو اتركه فاضي —</option>
                                @foreach($customers as $c)
                                    <option value="{{ $c->id }}" {{ old('customer_id', $opp?->customer_id ?? '') == $c->id ? 'selected' : '' }}>
                                        {{ $c->full_name }} — {{ $c->phone }} ({{ $c->code }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Section 2: Booking spec --}}
            <div class="section-card">
                <div class="head">
                    <div class="sec-icon" style="background:linear-gradient(135deg,#fef3c7,#fde68a); color:#b45309;">
                        <i class="bi bi-tag"></i>
                    </div>
                    <div>
                        <h6>تفاصيل الحجز المطلوب</h6>
                        <div class="sub">نوع الحجز، الوجهة، العدد</div>
                    </div>
                </div>
                <div class="body">
                    <div class="mb-3">
                        <label class="form-label">نوع الحجز <span class="req">*</span></label>
                        <div class="btype-grid">
                            @php $currentBT = old('booking_type', $opp?->booking_type ?? 'religious'); @endphp
                            <label class="btype-card {{ $currentBT === 'religious' ? 'selected' : '' }}">
                                <input type="radio" name="booking_type" value="religious" {{ $currentBT === 'religious' ? 'checked' : '' }} required>
                                <div class="big-ic"><i class="bi bi-mosque"></i></div>
                                <div class="tt">سياحة دينية</div>
                            </label>
                            <label class="btype-card {{ $currentBT === 'domestic' ? 'selected' : '' }}">
                                <input type="radio" name="booking_type" value="domestic" {{ $currentBT === 'domestic' ? 'checked' : '' }} required>
                                <div class="big-ic"><i class="bi bi-map"></i></div>
                                <div class="tt">سياحة داخلية</div>
                            </label>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">النوع الفرعي</label>
                            <select name="sub_type" id="subTypeSelect" class="form-select">
                                <option value="">— اختياري —</option>
                                {{-- Religious options --}}
                                <optgroup label="دينية" class="sub-religious">
                                    <option value="umrah" {{ old('sub_type', $opp?->sub_type) === 'umrah' ? 'selected' : '' }}>عمرة</option>
                                    <option value="hajj"  {{ old('sub_type', $opp?->sub_type) === 'hajj' ? 'selected' : '' }}>حج</option>
                                </optgroup>
                                {{-- Domestic options --}}
                                <optgroup label="داخلية" class="sub-domestic">
                                    <option value="package"    {{ old('sub_type', $opp?->sub_type) === 'package' ? 'selected' : '' }}>باكدج كامل</option>
                                    <option value="hotel_only" {{ old('sub_type', $opp?->sub_type) === 'hotel_only' ? 'selected' : '' }}>إقامة فندقية</option>
                                    <option value="day_trip"   {{ old('sub_type', $opp?->sub_type) === 'day_trip' ? 'selected' : '' }}>رحلة يوم</option>
                                    <option value="cruise"     {{ old('sub_type', $opp?->sub_type) === 'cruise' ? 'selected' : '' }}>رحلة نيلية/بحرية</option>
                                    <option value="camp"       {{ old('sub_type', $opp?->sub_type) === 'camp' ? 'selected' : '' }}>مخيم</option>
                                    <option value="event"      {{ old('sub_type', $opp?->sub_type) === 'event' ? 'selected' : '' }}>فعالية</option>
                                </optgroup>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">الوجهة</label>
                            <input type="text" name="destination" class="form-control"
                                   placeholder="مثال: مكة، الغردقة، الأقصر..."
                                   value="{{ old('destination', $opp?->destination ?? '') }}" maxlength="200">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">عدد الأشخاص <span class="req">*</span></label>
                            <input type="number" name="pax_count" min="1" max="1000" class="form-control"
                                   value="{{ old('pax_count', $opp?->pax_count ?? 1) }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">تاريخ السفر المتوقع</label>
                            <input type="date" name="expected_trip_date" class="form-control"
                                   value="{{ old('expected_trip_date', $opp?->expected_trip_date?->format('Y-m-d') ?? '') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">تاريخ الإغلاق المتوقع</label>
                            <input type="date" name="expected_close_date" class="form-control"
                                   value="{{ old('expected_close_date', $opp?->expected_close_date?->format('Y-m-d') ?? '') }}">
                        </div>
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
                    </div>
                </div>
                <div class="body">
                    <textarea name="notes" rows="3" class="form-control" placeholder="ملاحظات إضافية...">{{ old('notes', $opp?->notes ?? '') }}</textarea>
                </div>
            </div>
        </div>

        {{-- Sidebar column --}}
        <div class="col-lg-4">
            {{-- Pricing + probability --}}
            <div class="section-card">
                <div class="head">
                    <div class="sec-icon" style="background:linear-gradient(135deg,#fef3c7,#fde68a); color:#92400e;">
                        <i class="bi bi-cash-stack"></i>
                    </div>
                    <div>
                        <h6>القيمة والاحتمال</h6>
                    </div>
                </div>
                <div class="body">
                    <label class="form-label">القيمة المتوقعة <span class="req">*</span></label>
                    <div class="input-group input-group-lg mb-3">
                        <input type="number" name="estimated_value" min="0" step="0.01"
                               class="form-control form-control-lg" style="font-size:1.2rem; font-weight:800;"
                               value="{{ old('estimated_value', $opp?->estimated_value ?? '') }}" required>
                        <span class="input-group-text" style="background:#fef3c7; color:#92400e; font-weight:800;">ج.م</span>
                    </div>

                    <label class="form-label d-flex justify-content-between">
                        <span>احتمال الفوز</span>
                        <span id="probDisplay" class="text-primary">{{ old('probability', $opp?->probability ?? 50) }}%</span>
                    </label>
                    <input type="range" name="probability" id="probSlider" min="0" max="100" step="5"
                           class="form-range" value="{{ old('probability', $opp?->probability ?? 50) }}">
                </div>
            </div>

            {{-- Stage + assignment --}}
            <div class="section-card">
                <div class="head">
                    <div class="sec-icon" style="background:linear-gradient(135deg,#ccfbf1,#a7f3d0); color:#0f766e;">
                        <i class="bi bi-flag"></i>
                    </div>
                    <div>
                        <h6>المرحلة والمسؤولية</h6>
                    </div>
                </div>
                <div class="body">
                    @if($isEdit)
                    <div class="mb-3">
                        <label class="form-label">المرحلة</label>
                        <select name="stage" class="form-select">
                            @foreach(\App\Models\Opportunity::STAGE_LABELS as $val => $label)
                                <option value="{{ $val }}" {{ old('stage', $opp?->stage) === $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    <label class="form-label">الموظف المسؤول</label>
                    <select name="assigned_to" class="form-select">
                        <option value="">— أنا —</option>
                        @foreach($employees as $u)
                            <option value="{{ $u->id }}" {{ old('assigned_to', $opp?->assigned_to ?? auth()->id()) == $u->id ? 'selected' : '' }}>
                                {{ $u->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="form-footer">
        <a href="{{ route('admin.crm.opportunities.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-x-circle"></i> إلغاء
        </a>
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-circle"></i> {{ $isEdit ? 'حفظ' : 'إنشاء صفقة' }}
        </button>
    </div>
</div>

@push('scripts')
<script>
$(function () {
    if ($.fn.select2) $('.select2').select2({ width: '100%', dir: 'rtl' });

    // Booking type cards visual selection
    $(document).on('click', '.btype-card', function () {
        $(this).siblings().removeClass('selected');
        $(this).addClass('selected');
        $(this).find('input[type=radio]').prop('checked', true).trigger('change');
    });

    // Filter sub_type optgroups based on booking_type
    function filterSubTypes() {
        const bt = $('input[name=booking_type]:checked').val();
        $('#subTypeSelect optgroup.sub-religious').toggle(bt === 'religious');
        $('#subTypeSelect optgroup.sub-domestic').toggle(bt === 'domestic');
    }
    $('input[name=booking_type]').on('change', filterSubTypes);
    filterSubTypes();

    // Probability slider
    $('#probSlider').on('input', function () {
        $('#probDisplay').text($(this).val() + '%');
    });
});
</script>
@endpush
