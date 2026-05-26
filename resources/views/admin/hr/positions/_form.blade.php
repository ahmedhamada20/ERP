@php
    /** @var \App\Models\Position|null $position */
    $position ??= null;
    $isEdit = $position && $position->exists;
    /** @var \Illuminate\Support\Collection $departments */
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

    .salary-input-group { position:relative; }
    .salary-input-group .input-suffix { position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#94a3b8; font-size:.78rem; font-weight:600; pointer-events:none; }
    .salary-input-group input { padding-left:50px !important; }

    .salary-total-card { background:linear-gradient(135deg,#ecfdf5,#dcfce7); border:1.5px dashed #86efac; border-radius:12px; padding:1rem 1.25rem; display:flex; align-items:center; justify-content:space-between; }
    .salary-total-card .lbl { font-size:.82rem; color:#15803d; font-weight:700; }
    .salary-total-card .val { font-size:1.5rem; font-weight:900; color:#15803d; }

    .commission-card { background:linear-gradient(135deg,#fefce8,#fef9c3); border:1px solid #facc15; border-radius:12px; padding:1rem; margin-top:.5rem; font-size:.82rem; color:#854d0e; }

    .toggle-card { display:flex; align-items:center; gap:.85rem; background:#f8fafc; border:1.5px solid #e2e8f0; padding:.85rem 1rem; border-radius:11px; cursor:pointer; transition:all .2s; }
    .toggle-card.active { background:#ecfdf5; border-color:#86efac; }
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
                    <div class="sec-icon"><i class="bi bi-briefcase"></i></div>
                    <div>
                        <h6>بيانات الوظيفة</h6>
                        <div class="sub">المسمى الوظيفي والقسم</div>
                    </div>
                </div>
                <div class="body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">المسمى الوظيفي <span class="req">*</span></label>
                            <input type="text" name="title" class="form-control @error('title') is-invalid @enderror"
                                   value="{{ old('title', $position?->title ?? '') }}" required maxlength="200"
                                   placeholder="مثال: مندوب مبيعات">
                            @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">بالإنجليزية</label>
                            <input type="text" name="title_en" class="form-control" dir="ltr"
                                   value="{{ old('title_en', $position?->title_en ?? '') }}" maxlength="200">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">القسم</label>
                            <select name="department_id" class="form-select @error('department_id') is-invalid @enderror">
                                <option value="">— غير محدد —</option>
                                @foreach($departments as $d)
                                    <option value="{{ $d->id }}" @selected(old('department_id', $position?->department_id) === $d->id)>
                                        {{ $d->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('department_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label">الوصف</label>
                            <textarea name="description" rows="2" class="form-control" maxlength="2000"
                                      placeholder="نبذة عن مسؤوليات الوظيفة">{{ old('description', $position?->description ?? '') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Salary defaults --}}
            <div class="section-card">
                <div class="head">
                    <div class="sec-icon" style="background:linear-gradient(135deg,#dcfce7,#bbf7d0); color:#15803d;"><i class="bi bi-cash-coin"></i></div>
                    <div>
                        <h6>الراتب الافتراضي</h6>
                        <div class="sub">هذه القيم تُستخدم كافتراضي لكل موظف في الوظيفة — ويمكن تجاوزها لكل موظف على حدة</div>
                    </div>
                </div>
                <div class="body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">الراتب الأساسي</label>
                            <div class="salary-input-group">
                                <input type="number" step="0.01" min="0" name="default_basic_salary" id="basic"
                                       class="form-control salary-input"
                                       value="{{ old('default_basic_salary', $position?->default_basic_salary ?? 0) }}">
                                <span class="input-suffix">ج.م</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">بدل السكن</label>
                            <div class="salary-input-group">
                                <input type="number" step="0.01" min="0" name="default_housing_allowance" id="housing"
                                       class="form-control salary-input"
                                       value="{{ old('default_housing_allowance', $position?->default_housing_allowance ?? 0) }}">
                                <span class="input-suffix">ج.م</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">بدل الانتقال</label>
                            <div class="salary-input-group">
                                <input type="number" step="0.01" min="0" name="default_transport_allowance" id="transport"
                                       class="form-control salary-input"
                                       value="{{ old('default_transport_allowance', $position?->default_transport_allowance ?? 0) }}">
                                <span class="input-suffix">ج.م</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">بدلات أخرى</label>
                            <div class="salary-input-group">
                                <input type="number" step="0.01" min="0" name="default_other_allowances" id="other"
                                       class="form-control salary-input"
                                       value="{{ old('default_other_allowances', $position?->default_other_allowances ?? 0) }}">
                                <span class="input-suffix">ج.م</span>
                            </div>
                        </div>

                        <div class="col-12 mt-2">
                            <div class="salary-total-card">
                                <div>
                                    <i class="bi bi-calculator"></i>
                                    <span class="lbl">إجمالي الراتب الافتراضي</span>
                                </div>
                                <div class="val"><span id="totalSalary">0.00</span> ج.م</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Commission --}}
            <div class="section-card">
                <div class="head">
                    <div class="sec-icon" style="background:linear-gradient(135deg,#fef3c7,#fde68a); color:#b45309;"><i class="bi bi-percent"></i></div>
                    <div>
                        <h6>العمولة</h6>
                        <div class="sub">نسبة العمولة من الحجوزات — تُحسب وقت تشغيل الرواتب</div>
                    </div>
                </div>
                <div class="body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">نسبة العمولة (%)</label>
                            <input type="number" step="0.01" min="0" max="100" name="commission_rate"
                                   class="form-control"
                                   value="{{ old('commission_rate', $position?->commission_rate ?? 0) }}">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">أساس احتساب العمولة <span class="req">*</span></label>
                            <select name="commission_basis" class="form-select">
                                <option value="net_profit" @selected(old('commission_basis', $position?->commission_basis ?? 'net_profit') === 'net_profit')>
                                    صافي الربح (سعر البيع − التكلفة)
                                </option>
                                <option value="selling_price" @selected(old('commission_basis', $position?->commission_basis) === 'selling_price')>
                                    سعر البيع (إجمالي قيمة الحجز)
                                </option>
                            </select>
                        </div>

                        <div class="col-12">
                            <div class="commission-card">
                                <strong><i class="bi bi-lightbulb"></i> ملاحظة:</strong>
                                صافي الربح أكثر عدلاً للشركة لأنه يستثني تكلفة الحجز، بينما سعر البيع يحمّس البائع على البيع بأسعار أعلى.
                                يمكن تجاوز هذه القيمة لأي موظف من شاشة الموظف.
                            </div>
                        </div>
                    </div>
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
                    <label class="toggle-card {{ old('is_active', $position?->is_active ?? true) ? 'active' : '' }}" id="activeLabel">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" id="is_active" class="form-check-input"
                               role="switch" @checked(old('is_active', $position?->is_active ?? true))>
                        <div class="toggle-meta">
                            <strong>نشطة</strong>
                            <div>الوظيفة تظهر في قوائم الاختيار</div>
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
                    <div><strong>الكود:</strong> <code>{{ $position->code }}</code></div>
                    <div><strong>تاريخ الإنشاء:</strong> {{ $position->created_at?->format('Y-m-d') }}</div>
                    <div><strong>آخر تعديل:</strong> {{ $position->updated_at?->diffForHumans() }}</div>
                </div>
            </div>
            @endif
        </div>
    </div>

    <div class="form-footer">
        <a href="{{ route('admin.hr.positions.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-x-circle"></i> إلغاء
        </a>
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-circle"></i> {{ $isEdit ? 'حفظ التعديلات' : 'إنشاء الوظيفة' }}
        </button>
    </div>
</div>

@push('scripts')
<script>
$(function () {
    function recalcTotal() {
        const sum = ['#basic', '#housing', '#transport', '#other']
            .map(s => parseFloat($(s).val()) || 0)
            .reduce((a, b) => a + b, 0);
        $('#totalSalary').text(sum.toLocaleString('en-EG', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
    }
    $('.salary-input').on('input', recalcTotal);
    recalcTotal();

    $('#is_active').on('change', function () { $('#activeLabel').toggleClass('active', $(this).is(':checked')); });
});
</script>
@endpush
