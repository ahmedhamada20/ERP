@php
    /** @var \App\Models\TransportProvider|null $transport */
    $transport ??= null;
    $isEdit = $transport && $transport->exists;
    $routes = $transport?->routes ?? old('routes', []);
@endphp

<style>
    .form-wrap { background: linear-gradient(180deg, #f8fafc 0%, #fff 100%); border-radius: 18px; padding: 1.5rem; margin-bottom: 1rem; }
    .section-card { background: #fff; border-radius: 14px; border: 1px solid #f1f5f9; margin-bottom: 1rem; overflow: hidden; }
    .section-card .head { padding: 1rem 1.25rem; background: linear-gradient(135deg, #fafbff, #f8fafc); border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; gap: .75rem; }
    .section-card .head .sec-icon { width: 38px; height: 38px; border-radius: 10px; background: linear-gradient(135deg, #fed7aa, #fdba74); color: #c2410c; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; }
    .section-card .head h6 { margin: 0; color: var(--brand-navy); font-weight: 800; }
    .section-card .body { padding: 1.25rem; }
    .form-label { font-size: .82rem; font-weight: 700; color: #475569; margin-bottom: .4rem; }
    .form-label .req { color: #dc2626; }
    .form-control, .form-select { height: 44px; font-size: .9rem; border-radius: 11px; border: 1.5px solid #e2e8f0; }
    .form-control:focus, .form-select:focus { border-color: var(--brand-gold); box-shadow: 0 0 0 .2rem rgba(212,164,55,.15); }
    textarea.form-control { height: auto; min-height: 80px; }
    .route-row { display: flex; gap: .35rem; align-items: center; margin-bottom: .35rem; background: #f8fafc; padding: .35rem .55rem; border-radius: 8px; border: 1px solid #e2e8f0; }
    .route-row .form-control { height: 36px; font-size: .85rem; font-family: 'JetBrains Mono', monospace; }
</style>

<div class="form-wrap">
    <div class="section-card">
        <div class="head">
            <div class="sec-icon"><i class="bi bi-bus-front-fill"></i></div>
            <div><h6>بيانات شركة النقل</h6></div>
        </div>
        <div class="body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">اسم الشركة <span class="req">*</span></label>
                    <input type="text" name="name" class="form-control" placeholder="شركة سابتكو السعودية"
                           value="{{ old('name', $transport?->name ?? '') }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">نوع النقل <span class="req">*</span></label>
                    <select name="type" class="form-select" required>
                        @foreach(\App\Models\TransportProvider::TYPE_LABELS as $v => $l)
                            <option value="{{ $v }}" @selected(old('type', $transport?->type ?? 'bus') === $v)>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">الدولة <span class="req">*</span></label>
                    <select name="country" class="form-select" required>
                        @foreach(\App\Models\TransportProvider::COUNTRY_LABELS as $v => $l)
                            <option value="{{ $v }}" @selected(old('country', $transport?->country ?? 'SA') === $v)>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">عدد السيارات <span class="req">*</span></label>
                    <input type="number" name="vehicle_count" class="form-control" min="1" max="1000"
                           value="{{ old('vehicle_count', $transport?->vehicle_count ?? 1) }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">سعة السيارة <span class="req">*</span></label>
                    <input type="number" name="capacity_per_vehicle" class="form-control" min="1" max="100"
                           value="{{ old('capacity_per_vehicle', $transport?->capacity_per_vehicle ?? 45) }}" required>
                </div>
                <div class="col-md-6 d-flex align-items-end">
                    <div class="alert alert-info py-2 mb-0 w-100" style="font-size:.85rem;">
                        <i class="bi bi-info-circle"></i> إجمالي الطاقة الاستيعابية يُحسب تلقائياً (سيارات × سعة)
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="section-card">
        <div class="head">
            <div class="sec-icon" style="background:linear-gradient(135deg,#fef3c7,#fde68a); color:#92400e;"><i class="bi bi-cash-stack"></i></div>
            <div><h6>التسعير والمسارات</h6></div>
        </div>
        <div class="body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">سعر الراكب <span class="req">*</span></label>
                    <input type="number" name="base_price_per_pax" class="form-control" min="0" step="0.01"
                           value="{{ old('base_price_per_pax', $transport?->base_price_per_pax ?? '') }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">سعر السيارة كاملة</label>
                    <input type="number" name="base_price_per_vehicle" class="form-control" min="0" step="0.01"
                           value="{{ old('base_price_per_vehicle', $transport?->base_price_per_vehicle ?? 0) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">العملة <span class="req">*</span></label>
                    <select name="currency" class="form-select" required>
                        @foreach(['EGP','SAR','USD'] as $c)
                            <option value="{{ $c }}" @selected(old('currency', $transport?->currency ?? 'SAR') === $c)>{{ $c }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12">
                    <label class="form-label">المسارات المتاحة</label>
                    <div id="routesContainer">
                        @foreach($routes as $r)
                        <div class="route-row">
                            <input type="text" name="routes[]" class="form-control" dir="ltr" placeholder="MEC-MED" value="{{ $r }}">
                            <button type="button" class="btn btn-sm btn-outline-danger route-remove"><i class="bi bi-x"></i></button>
                        </div>
                        @endforeach
                    </div>
                    <button type="button" id="addRouteBtn" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-plus-circle"></i> إضافة مسار
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="section-card">
        <div class="head">
            <div class="sec-icon" style="background:linear-gradient(135deg,#dbeafe,#bfdbfe); color:#1d4ed8;"><i class="bi bi-telephone"></i></div>
            <div><h6>التواصل والملاحظات</h6></div>
        </div>
        <div class="body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">المسؤول</label>
                    <input type="text" name="contact_person" class="form-control"
                           value="{{ old('contact_person', $transport?->contact_person ?? '') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">هاتف</label>
                    <input type="text" name="contact_phone" class="form-control" dir="ltr"
                           value="{{ old('contact_phone', $transport?->contact_phone ?? '') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">إيميل</label>
                    <input type="email" name="contact_email" class="form-control" dir="ltr"
                           value="{{ old('contact_email', $transport?->contact_email ?? '') }}">
                </div>
                <div class="col-12">
                    <label class="form-label">ملاحظات</label>
                    <textarea name="notes" rows="3" class="form-control">{{ old('notes', $transport?->notes ?? '') }}</textarea>
                </div>
                <div class="col-12">
                    <label class="d-flex align-items-center gap-2">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" class="form-check-input"
                               @checked(old('is_active', $transport?->is_active ?? true))>
                        <strong>نشط</strong>
                    </label>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2">
        <a href="{{ route('admin.catalog.transport.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-x-circle"></i> إلغاء
        </a>
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-circle"></i> {{ $isEdit ? 'حفظ التعديلات' : 'حفظ الشركة' }}
        </button>
    </div>
</div>

@push('scripts')
<script>
$(function () {
    $('#addRouteBtn').on('click', () => {
        const html = `
            <div class="route-row">
                <input type="text" name="routes[]" class="form-control" dir="ltr" placeholder="MEC-MED">
                <button type="button" class="btn btn-sm btn-outline-danger route-remove"><i class="bi bi-x"></i></button>
            </div>`;
        $('#routesContainer').append(html);
    });
    $(document).on('click', '.route-remove', function () { $(this).closest('.route-row').remove(); });
});
</script>
@endpush
