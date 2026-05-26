@php
    /** @var \App\Models\Hotel|null $hotel */
    $hotel ??= null;
    $isEdit = $hotel && $hotel->exists;
    $roomTypes = $hotel?->room_types ?? old('room_types', []);
    $amenities = $hotel?->amenities ?? old('amenities', []);
@endphp

<style>
    .form-wrap { background: linear-gradient(180deg, #f8fafc 0%, #fff 100%); border-radius: 18px; padding: 1.5rem; margin-bottom: 1rem; }
    .section-card { background: #fff; border-radius: 14px; border: 1px solid #f1f5f9; margin-bottom: 1rem; overflow: hidden; }
    .section-card .head { padding: 1rem 1.25rem; background: linear-gradient(135deg, #fafbff, #f8fafc); border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; gap: .75rem; }
    .section-card .head .sec-icon { width: 38px; height: 38px; border-radius: 10px; background: linear-gradient(135deg, #ccfbf1, #a7f3d0); color: #0f766e; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; }
    .section-card .head h6 { margin: 0; color: var(--brand-navy); font-weight: 800; }
    .section-card .body { padding: 1.25rem; }
    .form-label { font-size: .82rem; font-weight: 700; color: #475569; margin-bottom: .4rem; }
    .form-label .req { color: #dc2626; }
    .form-control, .form-select { height: 44px; font-size: .9rem; border-radius: 11px; border: 1.5px solid #e2e8f0; }
    .form-control:focus, .form-select:focus { border-color: var(--brand-gold); box-shadow: 0 0 0 .2rem rgba(212,164,55,.15); }
    textarea.form-control { height: auto; min-height: 80px; }
    .chip-pick { display: inline-flex; align-items: center; gap: .4rem; padding: .45rem .85rem; background: #f1f5f9; border-radius: 8px; font-size: .82rem; font-weight: 600; cursor: pointer; user-select: none; border: 1.5px solid transparent; transition: all .15s; }
    .chip-pick input { display: none; }
    .chip-pick:hover { background: #e2e8f0; }
    .chip-pick.selected { background: linear-gradient(135deg, #fffbeb, #fef3c7); color: #92400e; border-color: var(--brand-gold); }
    .cover-preview { width: 100%; max-width: 240px; aspect-ratio: 16/10; border-radius: 10px; object-fit: cover; border: 1px solid #e2e8f0; background: #f1f5f9; margin: 0 auto .65rem; display: block; }
    .cover-wrap { border: 2px dashed #cbd5e1; border-radius: 12px; background: #f8fafc; padding: 1rem; text-align: center; }
    .cover-wrap:hover { border-color: var(--brand-gold); background: #fffbeb; }
</style>

<div class="form-wrap">
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="section-card">
                <div class="head">
                    <div class="sec-icon"><i class="bi bi-building-fill"></i></div>
                    <div><h6>بيانات الفندق</h6></div>
                </div>
                <div class="body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">الاسم بالعربي <span class="req">*</span></label>
                            <input type="text" name="name" class="form-control" placeholder="فندق هيلتون مكة"
                                   value="{{ old('name', $hotel?->name ?? '') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">الاسم بالإنجليزي</label>
                            <input type="text" name="name_en" class="form-control" dir="ltr" placeholder="Hilton Makkah"
                                   value="{{ old('name_en', $hotel?->name_en ?? '') }}">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">المدينة <span class="req">*</span></label>
                            <select name="city" class="form-select" required>
                                @foreach(\App\Models\Hotel::CITY_LABELS as $v => $l)
                                    <option value="{{ $v }}" @selected(old('city', $hotel?->city ?? 'mecca') === $v)>{{ $l }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">الدرجة <span class="req">*</span></label>
                            <select name="grade" class="form-select" required>
                                @foreach(\App\Models\Hotel::GRADE_LABELS as $v => $l)
                                    <option value="{{ $v }}" @selected(old('grade', $hotel?->grade ?? 'economy') === $v)>{{ $l }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">المسافة من المعلم (متر)</label>
                            <input type="number" name="distance_meters" class="form-control" min="0" max="50000"
                                   value="{{ old('distance_meters', $hotel?->distance_meters ?? '') }}">
                        </div>

                        <div class="col-12">
                            <label class="form-label">العنوان</label>
                            <input type="text" name="address" class="form-control"
                                   value="{{ old('address', $hotel?->address ?? '') }}">
                        </div>
                    </div>
                </div>
            </div>

            <div class="section-card">
                <div class="head">
                    <div class="sec-icon" style="background:linear-gradient(135deg,#fef3c7,#fde68a); color:#92400e;"><i class="bi bi-cash-stack"></i></div>
                    <div><h6>التسعير والغرف</h6></div>
                </div>
                <div class="body">
                    <div class="row g-3">
                        <div class="col-md-5">
                            <label class="form-label">سعر الليلة <span class="req">*</span></label>
                            <input type="number" name="base_price_per_night" class="form-control" min="0" step="0.01"
                                   value="{{ old('base_price_per_night', $hotel?->base_price_per_night ?? '') }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">العملة <span class="req">*</span></label>
                            <select name="currency" class="form-select" required>
                                @foreach(['EGP'=>'EGP','SAR'=>'SAR','USD'=>'USD','AED'=>'AED','TRY'=>'TRY'] as $v=>$l)
                                    <option value="{{ $v }}" @selected(old('currency', $hotel?->currency ?? 'SAR') === $v)>{{ $l }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">سعة الإشغال</label>
                            <input type="number" name="max_occupancy" class="form-control" min="1" max="12"
                                   value="{{ old('max_occupancy', $hotel?->max_occupancy ?? 4) }}" required>
                        </div>

                        <div class="col-12">
                            <label class="form-label">أنواع الغرف المتاحة</label>
                            <div class="d-flex gap-2 flex-wrap">
                                @foreach(['single'=>'فردي','double'=>'ثنائي','triple'=>'ثلاثي','quad'=>'رباعي','quintuple'=>'خماسي','sextuple'=>'سداسي','suite'=>'جناح'] as $v=>$l)
                                    <label class="chip-pick {{ in_array($v, $roomTypes ?? []) ? 'selected' : '' }}">
                                        <input type="checkbox" name="room_types[]" value="{{ $v }}" @checked(in_array($v, $roomTypes ?? []))>
                                        <span>{{ $l }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label">المرافق</label>
                            <div class="d-flex gap-2 flex-wrap">
                                @foreach(['wifi'=>'واي فاي','pool'=>'مسبح','gym'=>'جيم','prayer_room'=>'مصلى','restaurant'=>'مطعم','spa'=>'سبا','parking'=>'موقف','airport_shuttle'=>'نقل المطار'] as $v=>$l)
                                    <label class="chip-pick {{ in_array($v, $amenities ?? []) ? 'selected' : '' }}">
                                        <input type="checkbox" name="amenities[]" value="{{ $v }}" @checked(in_array($v, $amenities ?? []))>
                                        <span>{{ $l }}</span>
                                    </label>
                                @endforeach
                            </div>
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
                            <label class="form-label">هاتف</label>
                            <input type="text" name="contact_phone" class="form-control" dir="ltr"
                                   value="{{ old('contact_phone', $hotel?->contact_phone ?? '') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">إيميل</label>
                            <input type="email" name="contact_email" class="form-control" dir="ltr"
                                   value="{{ old('contact_email', $hotel?->contact_email ?? '') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">الموقع الإلكتروني</label>
                            <input type="url" name="website" class="form-control" dir="ltr"
                                   value="{{ old('website', $hotel?->website ?? '') }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">ملاحظات</label>
                            <textarea name="notes" rows="3" class="form-control">{{ old('notes', $hotel?->notes ?? '') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="section-card">
                <div class="head">
                    <div class="sec-icon" style="background:linear-gradient(135deg,#fef3c7,#fde68a); color:#92400e;"><i class="bi bi-image"></i></div>
                    <div><h6>صورة الغلاف</h6></div>
                </div>
                <div class="body">
                    <div class="cover-wrap">
                        <img id="coverPreview" class="cover-preview"
                             src="{{ $isEdit && $hotel->cover_image ? asset('storage/' . $hotel->cover_image) : '' }}"
                             onerror="this.style.display='none'">
                        <label class="btn btn-dark btn-sm">
                            <i class="bi bi-cloud-upload"></i> اختر صورة
                            <input type="file" name="cover_image" accept="image/*" hidden onchange="document.getElementById('coverPreview').src=URL.createObjectURL(this.files[0]); document.getElementById('coverPreview').style.display='block';">
                        </label>
                        <div class="small text-muted mt-2">JPG / PNG / WEBP — حد أقصى 4MB</div>
                    </div>
                </div>
            </div>

            <div class="section-card">
                <div class="body">
                    <label class="d-flex align-items-center gap-2">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" class="form-check-input"
                               @checked(old('is_active', $hotel?->is_active ?? true))>
                        <div>
                            <strong>نشط</strong>
                            <div class="small text-muted">يظهر في قائمة اختيار الفنادق</div>
                        </div>
                    </label>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2">
        <a href="{{ route('admin.catalog.hotels.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-x-circle"></i> إلغاء
        </a>
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-circle"></i> {{ $isEdit ? 'حفظ التعديلات' : 'حفظ الفندق' }}
        </button>
    </div>
</div>

@push('scripts')
<script>
$(function () {
    $(document).on('click', '.chip-pick', function (e) {
        if (e.target.tagName === 'INPUT') return;
        const cb = $(this).find('input[type=checkbox]');
        cb.prop('checked', !cb.is(':checked'));
        $(this).toggleClass('selected', cb.is(':checked'));
    });
});
</script>
@endpush
