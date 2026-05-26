@php
    /** @var \App\Models\Branch|null $branch */
    $branch ??= null;
    $isEdit = $branch && $branch->exists;
@endphp

<style>
    .form-wrap { background:linear-gradient(180deg, #f8fafc 0%, #fff 100%); border-radius:18px; padding:1.5rem; box-shadow:0 2px 8px rgba(15,23,42,.05); margin-bottom:1rem; }
    .section-card { background:#fff; border-radius:14px; border:1px solid #f1f5f9; margin-bottom:1rem; overflow:hidden; }
    .section-card .head { padding:1rem 1.25rem; background:linear-gradient(135deg, #fafbff, #f8fafc); border-bottom:1px solid #f1f5f9; display:flex; align-items:center; gap:.75rem; }
    .section-card .head .sec-icon { width:38px; height:38px; border-radius:10px; background:linear-gradient(135deg, #dbeafe, #bfdbfe); color:#1d4ed8; display:flex; align-items:center; justify-content:center; font-size:1.1rem; }
    .section-card .head h6 { margin:0; color:var(--brand-navy); font-weight:800; }
    .section-card .head .sub { font-size:.72rem; color:#64748b; }
    .section-card .body { padding:1.25rem; }

    .form-label { font-size:.82rem; font-weight:700; color:#475569; margin-bottom:.4rem; }
    .form-label .req { color:#dc2626; font-weight:900; }
    .form-control, .form-select { height:44px; font-size:.9rem; border-radius:11px; border:1.5px solid #e2e8f0; }
    .form-control:focus, .form-select:focus { border-color:var(--brand-gold); box-shadow:0 0 0 .2rem rgba(212,164,55,.15); }
    textarea.form-control { height:auto; min-height:80px; }

    .toggle-card { display:flex; align-items:center; gap:.85rem; background:#f8fafc; border:1.5px solid #e2e8f0; padding:.85rem 1rem; border-radius:11px; cursor:pointer; transition:all .2s; }
    .toggle-card.active { background:#ecfdf5; border-color:#86efac; }
    .toggle-card.main { background:#fffbeb; border-color:#fcd34d; }
    .toggle-card .form-check-input { width:2.5em; height:1.4em; margin:0; flex-shrink:0; }
    .toggle-card .toggle-meta { flex:1; }
    .toggle-card .toggle-meta strong { font-size:.9rem; color:var(--brand-navy); }
    .toggle-card .toggle-meta div { font-size:.72rem; color:#64748b; }

    .form-footer { background:#fff; border-top:1px solid #f1f5f9; padding:1rem 1.25rem; border-radius:0 0 14px 14px; display:flex; justify-content:flex-end; gap:.65rem; flex-wrap:wrap; }
    .form-footer .btn { min-width:140px; }
</style>

<div class="form-wrap">
    <div class="row g-3">
        <div class="col-lg-8">
            {{-- Basic info --}}
            <div class="section-card">
                <div class="head">
                    <div class="sec-icon"><i class="bi bi-info-circle"></i></div>
                    <div>
                        <h6>بيانات الفرع</h6>
                        <div class="sub">الاسم والمدير الرئيسي</div>
                    </div>
                </div>
                <div class="body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">اسم الفرع <span class="req">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name', $branch?->name ?? '') }}" required maxlength="200"
                                   placeholder="مثال: فرع الإسكندرية">
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">الاسم بالإنجليزية</label>
                            <input type="text" name="name_en" class="form-control" dir="ltr"
                                   value="{{ old('name_en', $branch?->name_en ?? '') }}" maxlength="200">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">مدير الفرع</label>
                            <input type="text" name="manager_name" class="form-control"
                                   value="{{ old('manager_name', $branch?->manager_name ?? '') }}" maxlength="200">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">هاتف الفرع</label>
                            <input type="tel" name="phone" class="form-control" dir="ltr"
                                   value="{{ old('phone', $branch?->phone ?? '') }}" maxlength="30">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">البريد الإلكتروني</label>
                            <input type="email" name="email" class="form-control" dir="ltr"
                                   value="{{ old('email', $branch?->email ?? '') }}" maxlength="200">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Address --}}
            <div class="section-card">
                <div class="head">
                    <div class="sec-icon" style="background:linear-gradient(135deg,#fee2e2,#fecaca); color:#b91c1c;"><i class="bi bi-geo-alt"></i></div>
                    <div>
                        <h6>العنوان</h6>
                    </div>
                </div>
                <div class="body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">الدولة <span class="req">*</span></label>
                            <input type="text" name="country" class="form-control"
                                   value="{{ old('country', $branch?->country ?? 'مصر') }}" required maxlength="80">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">المحافظة</label>
                            <input type="text" name="governorate" class="form-control"
                                   value="{{ old('governorate', $branch?->governorate ?? '') }}" maxlength="120">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">المدينة</label>
                            <input type="text" name="city" class="form-control"
                                   value="{{ old('city', $branch?->city ?? '') }}" maxlength="120">
                        </div>
                        <div class="col-12">
                            <label class="form-label">العنوان التفصيلي</label>
                            <textarea name="address" rows="2" class="form-control" maxlength="500">{{ old('address', $branch?->address ?? '') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Notes --}}
            <div class="section-card">
                <div class="head">
                    <div class="sec-icon" style="background:linear-gradient(135deg,#f3e8ff,#e9d5ff); color:#6b21a8;"><i class="bi bi-sticky"></i></div>
                    <div>
                        <h6>ملاحظات</h6>
                    </div>
                </div>
                <div class="body">
                    <textarea name="notes" rows="3" class="form-control" maxlength="2000">{{ old('notes', $branch?->notes ?? '') }}</textarea>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="section-card">
                <div class="head">
                    <div class="sec-icon" style="background:linear-gradient(135deg,#dcfce7,#bbf7d0); color:#15803d;"><i class="bi bi-toggles"></i></div>
                    <div>
                        <h6>الحالة</h6>
                    </div>
                </div>
                <div class="body">
                    <label class="toggle-card mb-2 {{ old('is_active', $branch?->is_active ?? true) ? 'active' : '' }}" id="activeLabel">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" id="is_active" class="form-check-input"
                               role="switch" @checked(old('is_active', $branch?->is_active ?? true))>
                        <div class="toggle-meta">
                            <strong>نشط</strong>
                            <div>الفرع يظهر في قوائم الاختيار</div>
                        </div>
                    </label>

                    <label class="toggle-card {{ old('is_main', $branch?->is_main ?? false) ? 'main' : '' }}" id="mainLabel">
                        <input type="hidden" name="is_main" value="0">
                        <input type="checkbox" name="is_main" value="1" id="is_main" class="form-check-input"
                               role="switch" @checked(old('is_main', $branch?->is_main ?? false))>
                        <div class="toggle-meta">
                            <strong><i class="bi bi-star-fill text-warning"></i> فرع رئيسي</strong>
                            <div>تعيين هذا الفرع كرئيسي يلغي تعيين الفرع الرئيسي الحالي</div>
                        </div>
                    </label>
                </div>
            </div>

            @if($isEdit)
            <div class="section-card">
                <div class="head">
                    <div class="sec-icon" style="background:linear-gradient(135deg,#e0e7ff,#c7d2fe); color:#4338ca;"><i class="bi bi-hash"></i></div>
                    <div>
                        <h6>معلومات النظام</h6>
                    </div>
                </div>
                <div class="body small text-muted">
                    <div><strong>الكود:</strong> <code>{{ $branch->code }}</code></div>
                    <div><strong>تاريخ الإنشاء:</strong> {{ $branch->created_at?->format('Y-m-d') }}</div>
                    <div><strong>آخر تعديل:</strong> {{ $branch->updated_at?->diffForHumans() }}</div>
                </div>
            </div>
            @endif
        </div>
    </div>

    <div class="form-footer">
        <a href="{{ route('admin.hr.branches.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-x-circle"></i> إلغاء
        </a>
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-circle"></i> {{ $isEdit ? 'حفظ التعديلات' : 'إنشاء الفرع' }}
        </button>
    </div>
</div>

@push('scripts')
<script>
$(function () {
    $('#is_active').on('change', function () { $('#activeLabel').toggleClass('active', $(this).is(':checked')); });
    $('#is_main').on('change', function () { $('#mainLabel').toggleClass('main', $(this).is(':checked')); });
});
</script>
@endpush
