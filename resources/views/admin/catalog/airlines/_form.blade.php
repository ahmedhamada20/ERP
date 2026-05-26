@php
    /** @var \App\Models\Airline|null $airline */
    $airline ??= null;
    $isEdit  = $airline && $airline->exists;
@endphp

<style>
    .form-wrap { background: linear-gradient(180deg, #f8fafc 0%, #fff 100%); border-radius: 18px; padding: 1.5rem; margin-bottom: 1rem; box-shadow: 0 2px 8px rgba(15,23,42,.05); }
    .section-card { background: #fff; border-radius: 14px; border: 1px solid #f1f5f9; margin-bottom: 1rem; overflow: hidden; }
    .section-card .head { padding: 1rem 1.25rem; background: linear-gradient(135deg, #fafbff, #f8fafc); border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; gap: .75rem; }
    .section-card .head .sec-icon { width: 38px; height: 38px; border-radius: 10px; background: linear-gradient(135deg, #dbeafe, #bfdbfe); color: #1e40af; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; }
    .section-card .head h6 { margin: 0; color: var(--brand-navy); font-weight: 800; }
    .section-card .head .sub { font-size: .72rem; color: #64748b; }
    .section-card .body { padding: 1.25rem; }
    .form-label { font-size: .82rem; font-weight: 700; color: #475569; margin-bottom: .4rem; }
    .form-label .req { color: #dc2626; }
    .form-control, .form-select { height: 44px; font-size: .9rem; border-radius: 11px; border: 1.5px solid #e2e8f0; }
    .form-control:focus, .form-select:focus { border-color: var(--brand-gold); box-shadow: 0 0 0 .2rem rgba(212,164,55,.15); }
    textarea.form-control { height: auto; min-height: 80px; }
</style>

<div class="form-wrap">
    <div class="section-card">
        <div class="head">
            <div class="sec-icon"><i class="bi bi-airplane-fill"></i></div>
            <div>
                <h6>بيانات شركة الطيران</h6>
                <div class="sub">المعلومات الأساسية والمسار</div>
            </div>
        </div>
        <div class="body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">اسم الشركة <span class="req">*</span></label>
                    <input type="text" name="airline_name" class="form-control" placeholder="مصر للطيران"
                           value="{{ old('airline_name', $airline?->airline_name ?? '') }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">كود الشركة (IATA)</label>
                    <input type="text" name="airline_code" class="form-control" placeholder="MS"
                           dir="ltr" maxlength="10"
                           value="{{ old('airline_code', $airline?->airline_code ?? '') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">المسار <span class="req">*</span></label>
                    <input type="text" name="route" class="form-control" placeholder="CAI-JED"
                           dir="ltr" maxlength="30"
                           value="{{ old('route', $airline?->route ?? '') }}" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label">درجة الكابينة <span class="req">*</span></label>
                    <select name="cabin_class" class="form-select" required>
                        @foreach(['economy'=>'اقتصادي','business'=>'رجال أعمال','first'=>'أولى'] as $v=>$l)
                            <option value="{{ $v }}" @selected(old('cabin_class', $airline?->cabin_class ?? 'economy') === $v)>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">نوع الطائرة</label>
                    <input type="text" name="aircraft_type" class="form-control" placeholder="Boeing 777"
                           value="{{ old('aircraft_type', $airline?->aircraft_type ?? '') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">مدة الرحلة (دقيقة)</label>
                    <input type="number" name="flight_duration_minutes" class="form-control" min="1" max="1440"
                           value="{{ old('flight_duration_minutes', $airline?->flight_duration_minutes ?? '') }}">
                </div>

                <div class="col-md-4">
                    <label class="form-label">وقت الإقلاع</label>
                    <input type="time" name="departure_time" class="form-control"
                           value="{{ old('departure_time', $airline?->departure_time ?? '') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">وقت الوصول</label>
                    <input type="time" name="arrival_time" class="form-control"
                           value="{{ old('arrival_time', $airline?->arrival_time ?? '') }}">
                </div>
            </div>
        </div>
    </div>

    <div class="section-card">
        <div class="head">
            <div class="sec-icon" style="background:linear-gradient(135deg,#fef3c7,#fde68a); color:#92400e;"><i class="bi bi-cash-stack"></i></div>
            <div>
                <h6>التسعير والسعة</h6>
                <div class="sub">سعر التذكرة والمقاعد</div>
            </div>
        </div>
        <div class="body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">السعر للراكب <span class="req">*</span></label>
                    <div class="input-group">
                        <input type="number" name="base_price_per_pax" class="form-control" min="0" step="0.01"
                               value="{{ old('base_price_per_pax', $airline?->base_price_per_pax ?? '') }}" required>
                        <select name="currency" class="form-select" style="max-width:90px;">
                            @foreach(['EGP','SAR','USD'] as $c)
                                <option value="{{ $c }}" @selected(old('currency', $airline?->currency ?? 'EGP') === $c)>{{ $c }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">سعة الطائرة</label>
                    <input type="number" name="capacity" class="form-control" min="0" max="1000"
                           value="{{ old('capacity', $airline?->capacity ?? 0) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">المقاعد المتاحة</label>
                    <input type="number" name="available_seats" class="form-control" min="0" max="1000"
                           value="{{ old('available_seats', $airline?->available_seats ?? 0) }}">
                </div>
            </div>
        </div>
    </div>

    <div class="section-card">
        <div class="head">
            <div class="sec-icon" style="background:linear-gradient(135deg,#dcfce7,#bbf7d0); color:#15803d;"><i class="bi bi-telephone"></i></div>
            <div><h6>التواصل والملاحظات</h6></div>
        </div>
        <div class="body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">هاتف</label>
                    <input type="text" name="contact_phone" class="form-control" dir="ltr"
                           value="{{ old('contact_phone', $airline?->contact_phone ?? '') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">إيميل</label>
                    <input type="email" name="contact_email" class="form-control" dir="ltr"
                           value="{{ old('contact_email', $airline?->contact_email ?? '') }}">
                </div>
                <div class="col-12">
                    <label class="form-label">ملاحظات</label>
                    <textarea name="notes" rows="3" class="form-control">{{ old('notes', $airline?->notes ?? '') }}</textarea>
                </div>
                <div class="col-12">
                    <label class="d-flex align-items-center gap-2">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" class="form-check-input"
                               @checked(old('is_active', $airline?->is_active ?? true))>
                        <strong>نشط</strong>
                        <span class="small text-muted">يظهر في قائمة الاختيار عند إنشاء حجز</span>
                    </label>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2">
        <a href="{{ route('admin.catalog.airlines.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-x-circle"></i> إلغاء
        </a>
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-circle"></i> {{ $isEdit ? 'حفظ التعديلات' : 'حفظ الشركة' }}
        </button>
    </div>
</div>
