@php
    /** @var \App\Models\Employee|null $employee */
    $employee ??= null;
    $isEdit = $employee && $employee->exists;
    /** @var \Illuminate\Support\Collection $branches */
    /** @var \Illuminate\Support\Collection $departments */
    /** @var \Illuminate\Support\Collection $positions */
    /** @var \Illuminate\Support\Collection $managers */
    /** @var \Illuminate\Support\Collection $users */
    $canSeeSalary = auth()->user()?->can('employees.view_salary');
@endphp

<style>
    .form-wrap { background:linear-gradient(180deg, #f8fafc 0%, #fff 100%); border-radius:18px; padding:1.5rem; box-shadow:0 2px 8px rgba(15,23,42,.05); margin-bottom:1rem; }
    .section-card { background:#fff; border-radius:14px; border:1px solid #f1f5f9; margin-bottom:1rem; overflow:hidden; }
    .section-card .head { padding:1rem 1.25rem; background:linear-gradient(135deg, #fafbff, #f8fafc); border-bottom:1px solid #f1f5f9; display:flex; align-items:center; gap:.75rem; }
    .section-card .head .sec-icon { width:38px; height:38px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.1rem; }
    .section-card .head h6 { margin:0; color:var(--brand-navy); font-weight:800; }
    .section-card .head .sub { font-size:.72rem; color:#64748b; }
    .section-card .body { padding:1.25rem; }

    .ic-identity { background:linear-gradient(135deg,#dbeafe,#bfdbfe); color:#1d4ed8; }
    .ic-contact  { background:linear-gradient(135deg,#fce7f3,#fbcfe8); color:#be185d; }
    .ic-org      { background:linear-gradient(135deg,#e0e7ff,#c7d2fe); color:#4338ca; }
    .ic-salary   { background:linear-gradient(135deg,#dcfce7,#bbf7d0); color:#15803d; }
    .ic-payment  { background:linear-gradient(135deg,#fef3c7,#fde68a); color:#b45309; }
    .ic-files    { background:linear-gradient(135deg,#f3e8ff,#e9d5ff); color:#6b21a8; }
    .ic-state    { background:linear-gradient(135deg,#dcfce7,#bbf7d0); color:#15803d; }

    .form-label { font-size:.82rem; font-weight:700; color:#475569; margin-bottom:.4rem; }
    .form-label .req { color:#dc2626; font-weight:900; }
    .form-control, .form-select { height:44px; font-size:.9rem; border-radius:11px; border:1.5px solid #e2e8f0; }
    .form-control:focus, .form-select:focus { border-color:var(--brand-gold); box-shadow:0 0 0 .2rem rgba(212,164,55,.15); }
    textarea.form-control { height:auto; min-height:80px; }

    .photo-upload { position:relative; display:flex; align-items:center; gap:1rem; padding:1rem; background:#f8fafc; border:1.5px dashed #cbd5e1; border-radius:12px; }
    .photo-upload img { width:80px; height:80px; border-radius:50%; object-fit:cover; border:3px solid #fff; box-shadow:0 2px 8px rgba(0,0,0,.1); }
    .photo-upload .upload-meta { flex:1; }
    .photo-upload .upload-meta strong { font-size:.88rem; color:var(--brand-navy); }
    .photo-upload .upload-meta div { font-size:.72rem; color:#64748b; }

    .salary-locked-note { background:linear-gradient(135deg,#fef3c7,#fde68a); border:1px solid #f59e0b; border-radius:12px; padding:1rem; color:#78350f; font-size:.85rem; }

    .commission-hint { background:linear-gradient(135deg,#eff6ff,#dbeafe); border:1px solid #93c5fd; border-radius:10px; padding:.65rem .85rem; color:#1e40af; font-size:.78rem; margin-top:.5rem; }

    .form-footer { background:#fff; border-top:1px solid #f1f5f9; padding:1rem 1.25rem; border-radius:0 0 14px 14px; display:flex; justify-content:flex-end; gap:.65rem; flex-wrap:wrap; }
    .form-footer .btn { min-width:140px; }
</style>

<div class="form-wrap">
    <div class="row g-3">
        <div class="col-lg-8">

            {{-- ════════ 1) Identity ════════ --}}
            <div class="section-card">
                <div class="head">
                    <div class="sec-icon ic-identity"><i class="bi bi-person-vcard"></i></div>
                    <div>
                        <h6>البيانات الشخصية</h6>
                        <div class="sub">الاسم والرقم القومي والمعلومات الأساسية</div>
                    </div>
                </div>
                <div class="body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">الاسم بالكامل <span class="req">*</span></label>
                            <input type="text" name="full_name" class="form-control @error('full_name') is-invalid @enderror"
                                   value="{{ old('full_name', $employee?->full_name ?? '') }}" required maxlength="200"
                                   placeholder="مثال: محمد أحمد علي">
                            @error('full_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">الاسم بالإنجليزية</label>
                            <input type="text" name="full_name_en" class="form-control" dir="ltr"
                                   value="{{ old('full_name_en', $employee?->full_name_en ?? '') }}" maxlength="200">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">الرقم القومي</label>
                            <input type="text" name="national_id" class="form-control @error('national_id') is-invalid @enderror"
                                   value="{{ old('national_id', $employee?->national_id ?? '') }}" maxlength="20" dir="ltr">
                            @error('national_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">رقم الجواز</label>
                            <input type="text" name="passport_number" class="form-control"
                                   value="{{ old('passport_number', $employee?->passport_number ?? '') }}" maxlength="30" dir="ltr">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">تاريخ الميلاد</label>
                            <input type="date" name="birth_date" class="form-control"
                                   value="{{ old('birth_date', $employee?->birth_date?->format('Y-m-d') ?? '') }}">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">النوع</label>
                            <select name="gender" class="form-select">
                                <option value="">—</option>
                                <option value="male"   @selected(old('gender', $employee?->gender) === 'male')>ذكر</option>
                                <option value="female" @selected(old('gender', $employee?->gender) === 'female')>أنثى</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">الحالة الاجتماعية</label>
                            <select name="marital_status" class="form-select">
                                <option value="">—</option>
                                <option value="single"   @selected(old('marital_status', $employee?->marital_status) === 'single')>أعزب</option>
                                <option value="married"  @selected(old('marital_status', $employee?->marital_status) === 'married')>متزوج</option>
                                <option value="divorced" @selected(old('marital_status', $employee?->marital_status) === 'divorced')>مطلق</option>
                                <option value="widowed"  @selected(old('marital_status', $employee?->marital_status) === 'widowed')>أرمل</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">الجنسية</label>
                            <input type="text" name="nationality" class="form-control"
                                   value="{{ old('nationality', $employee?->nationality ?? 'مصري') }}" maxlength="100">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">الديانة</label>
                            <input type="text" name="religion" class="form-control"
                                   value="{{ old('religion', $employee?->religion ?? '') }}" maxlength="100">
                        </div>
                    </div>
                </div>
            </div>

            {{-- ════════ 2) Contact ════════ --}}
            <div class="section-card">
                <div class="head">
                    <div class="sec-icon ic-contact"><i class="bi bi-telephone"></i></div>
                    <div>
                        <h6>التواصل والعنوان</h6>
                    </div>
                </div>
                <div class="body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">الهاتف <span class="req">*</span></label>
                            <input type="tel" name="phone" class="form-control @error('phone') is-invalid @enderror"
                                   value="{{ old('phone', $employee?->phone ?? '') }}" required maxlength="30" dir="ltr">
                            @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">واتساب</label>
                            <input type="tel" name="whatsapp" class="form-control"
                                   value="{{ old('whatsapp', $employee?->whatsapp ?? '') }}" maxlength="30" dir="ltr"
                                   placeholder="نفس الهاتف لو فاضي">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">البريد الإلكتروني</label>
                            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                                   value="{{ old('email', $employee?->email ?? '') }}" maxlength="200" dir="ltr">
                            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">اسم جهة الطوارئ</label>
                            <input type="text" name="emergency_contact_name" class="form-control"
                                   value="{{ old('emergency_contact_name', $employee?->emergency_contact_name ?? '') }}" maxlength="200">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">هاتف الطوارئ</label>
                            <input type="tel" name="emergency_contact_phone" class="form-control"
                                   value="{{ old('emergency_contact_phone', $employee?->emergency_contact_phone ?? '') }}" maxlength="30" dir="ltr">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">المدينة</label>
                            <input type="text" name="city" class="form-control"
                                   value="{{ old('city', $employee?->city ?? '') }}" maxlength="100">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">العنوان</label>
                            <input type="text" name="address" class="form-control"
                                   value="{{ old('address', $employee?->address ?? '') }}" maxlength="500">
                        </div>
                    </div>
                </div>
            </div>

            {{-- ════════ 3) Organizational ════════ --}}
            <div class="section-card">
                <div class="head">
                    <div class="sec-icon ic-org"><i class="bi bi-diagram-3"></i></div>
                    <div>
                        <h6>التنظيم والوظيفة</h6>
                        <div class="sub">الفرع، القسم، الوظيفة، والمدير المباشر</div>
                    </div>
                </div>
                <div class="body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">الفرع</label>
                            <select name="branch_id" class="form-select @error('branch_id') is-invalid @enderror">
                                <option value="">—</option>
                                @foreach($branches as $b)
                                    <option value="{{ $b->id }}" @selected(old('branch_id', $employee?->branch_id) === $b->id)>{{ $b->name }}</option>
                                @endforeach
                            </select>
                            @error('branch_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">القسم</label>
                            <select name="department_id" id="department_id" class="form-select @error('department_id') is-invalid @enderror">
                                <option value="">—</option>
                                @foreach($departments as $d)
                                    <option value="{{ $d->id }}" @selected(old('department_id', $employee?->department_id) === $d->id)>{{ $d->name }}</option>
                                @endforeach
                            </select>
                            @error('department_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">الوظيفة</label>
                            <select name="position_id" id="position_id" class="form-select @error('position_id') is-invalid @enderror"
                                    data-positions='@json($positions)'>
                                <option value="">—</option>
                                @foreach($positions as $p)
                                    <option value="{{ $p->id }}" data-dept="{{ $p->department_id }}"
                                            @selected(old('position_id', $employee?->position_id) === $p->id)>{{ $p->title }}</option>
                                @endforeach
                            </select>
                            @error('position_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">المدير المباشر</label>
                            <select name="reports_to" class="form-select @error('reports_to') is-invalid @enderror">
                                <option value="">—</option>
                                @foreach($managers as $m)
                                    @if($isEdit && $m->id === $employee->id) @continue @endif
                                    <option value="{{ $m->id }}" @selected(old('reports_to', $employee?->reports_to) === $m->id)>
                                        {{ $m->full_name }} ({{ $m->code }})
                                    </option>
                                @endforeach
                            </select>
                            @error('reports_to') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">حساب الدخول (اختياري)</label>
                            <select name="user_id" class="form-select @error('user_id') is-invalid @enderror">
                                <option value="">— الموظف لا يدخل النظام —</option>
                                @if($isEdit && $employee->user)
                                    <option value="{{ $employee->user->id }}" selected>{{ $employee->user->name }} ({{ $employee->user->email }})</option>
                                @endif
                                @foreach($users as $u)
                                    <option value="{{ $u->id }}" @selected(old('user_id', $employee?->user_id) === $u->id)>
                                        {{ $u->name }} ({{ $u->email }})
                                    </option>
                                @endforeach
                            </select>
                            @error('user_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <small class="text-muted">اربط الموظف بحساب موجود — السائقون والعمال غالباً لا يحتاجون حساب دخول</small>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">تاريخ التعيين <span class="req">*</span></label>
                            <input type="date" name="hire_date" class="form-control @error('hire_date') is-invalid @enderror"
                                   value="{{ old('hire_date', $employee?->hire_date?->format('Y-m-d') ?? now()->format('Y-m-d')) }}" required>
                            @error('hire_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">تاريخ انتهاء الخدمة</label>
                            <input type="date" name="termination_date" class="form-control"
                                   value="{{ old('termination_date', $employee?->termination_date?->format('Y-m-d') ?? '') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">نوع التعاقد <span class="req">*</span></label>
                            <select name="employment_type" class="form-select" required>
                                @foreach(\App\Models\Employee::EMPLOYMENT_TYPE_LABELS as $k => $v)
                                    <option value="{{ $k }}" @selected(old('employment_type', $employee?->employment_type ?? 'full_time') === $k)>{{ $v }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">الحالة <span class="req">*</span></label>
                            <select name="status" class="form-select" required>
                                @foreach(\App\Models\Employee::STATUS_LABELS as $k => $v)
                                    <option value="{{ $k }}" @selected(old('status', $employee?->status ?? 'active') === $k)>{{ $v }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ════════ 4) Salary + Commission (gated) ════════ --}}
            @if($canSeeSalary)
            <div class="section-card">
                <div class="head">
                    <div class="sec-icon ic-salary"><i class="bi bi-cash-coin"></i></div>
                    <div>
                        <h6>الراتب والعمولة</h6>
                        <div class="sub">اترك الحقول صفر/فاضية لتأخذ القيم الافتراضية من الوظيفة</div>
                    </div>
                </div>
                <div class="body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">الراتب الأساسي</label>
                            <input type="number" step="0.01" min="0" name="basic_salary" class="form-control"
                                   value="{{ old('basic_salary', $employee?->basic_salary ?? 0) }}"
                                   placeholder="0 = من الوظيفة">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">بدل السكن</label>
                            <input type="number" step="0.01" min="0" name="housing_allowance" class="form-control"
                                   value="{{ old('housing_allowance', $employee?->housing_allowance ?? 0) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">بدل الانتقال</label>
                            <input type="number" step="0.01" min="0" name="transport_allowance" class="form-control"
                                   value="{{ old('transport_allowance', $employee?->transport_allowance ?? 0) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">بدلات أخرى</label>
                            <input type="number" step="0.01" min="0" name="other_allowances" class="form-control"
                                   value="{{ old('other_allowances', $employee?->other_allowances ?? 0) }}">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">نسبة العمولة (%)</label>
                            <input type="number" step="0.01" min="0" max="100" name="commission_rate" class="form-control"
                                   value="{{ old('commission_rate', $employee?->commission_rate ?? '') }}"
                                   placeholder="فاضي = من الوظيفة">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">أساس العمولة</label>
                            <select name="commission_basis" class="form-select">
                                <option value="">— من الوظيفة —</option>
                                <option value="net_profit"   @selected(old('commission_basis', $employee?->commission_basis) === 'net_profit')>صافي الربح</option>
                                <option value="selling_price" @selected(old('commission_basis', $employee?->commission_basis) === 'selling_price')>سعر البيع</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <div class="commission-hint">
                                <i class="bi bi-info-circle"></i>
                                <strong>قاعدة الـ Fallback:</strong>
                                لو الحقل صفر أو فاضي → بيستعمل القيمة الافتراضية من الوظيفة. ده بيخليك ترفع راتب موظف معين من غير ما تغير الوظيفة كلها.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ════════ 5) Payment ════════ --}}
            <div class="section-card">
                <div class="head">
                    <div class="sec-icon ic-payment"><i class="bi bi-bank"></i></div>
                    <div>
                        <h6>طريقة الدفع</h6>
                    </div>
                </div>
                <div class="body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">طريقة الدفع <span class="req">*</span></label>
                            <select name="payment_method" id="payment_method" class="form-select" required>
                                @foreach(\App\Models\Employee::PAYMENT_METHOD_LABELS as $k => $v)
                                    <option value="{{ $k }}" @selected(old('payment_method', $employee?->payment_method ?? 'bank_transfer') === $k)>{{ $v }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 bank-field">
                            <label class="form-label">البنك</label>
                            <input type="text" name="bank_name" class="form-control @error('bank_name') is-invalid @enderror"
                                   value="{{ old('bank_name', $employee?->bank_name ?? '') }}" maxlength="200">
                            @error('bank_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4 bank-field">
                            <label class="form-label">رقم الحساب</label>
                            <input type="text" name="bank_account" class="form-control @error('bank_account') is-invalid @enderror"
                                   value="{{ old('bank_account', $employee?->bank_account ?? '') }}" maxlength="50" dir="ltr">
                            @error('bank_account') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6 bank-field">
                            <label class="form-label">IBAN</label>
                            <input type="text" name="iban" class="form-control"
                                   value="{{ old('iban', $employee?->iban ?? '') }}" maxlength="50" dir="ltr">
                        </div>
                    </div>
                </div>
            </div>
            @else
            <div class="salary-locked-note">
                <i class="bi bi-lock-fill"></i>
                <strong>قسم الراتب والدفع محجوب</strong> — لا تملك صلاحية <code>employees.view_salary</code>.
                المسؤول عن الرواتب فقط يستطيع تعديل هذه البيانات.
            </div>
            @endif

            {{-- Notes --}}
            <div class="section-card">
                <div class="head">
                    <div class="sec-icon" style="background:linear-gradient(135deg,#f1f5f9,#e2e8f0); color:#475569;"><i class="bi bi-sticky"></i></div>
                    <div>
                        <h6>ملاحظات</h6>
                    </div>
                </div>
                <div class="body">
                    <textarea name="notes" rows="2" class="form-control" maxlength="2000">{{ old('notes', $employee?->notes ?? '') }}</textarea>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            {{-- Photo --}}
            <div class="section-card">
                <div class="head">
                    <div class="sec-icon ic-files"><i class="bi bi-image"></i></div>
                    <div>
                        <h6>الصور والمستندات</h6>
                    </div>
                </div>
                <div class="body">
                    <div class="mb-3">
                        <label class="form-label">صورة شخصية</label>
                        <div class="photo-upload">
                            <img src="{{ $employee?->photo_url ?? asset('admin/img/user-placeholder.png') }}" alt="" id="photoPreview">
                            <div class="upload-meta">
                                <strong>JPG / PNG / WEBP</strong>
                                <div>حتى 2 ميجا</div>
                                <input type="file" name="photo" id="photoInput" accept="image/*" class="form-control form-control-sm mt-2">
                            </div>
                        </div>
                        @error('photo') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>

                    <div>
                        <label class="form-label">صورة البطاقة</label>
                        <input type="file" name="id_image" accept="image/*" class="form-control">
                        @if($employee?->id_image)
                            <div class="mt-2">
                                <a href="{{ asset('storage/' . $employee->id_image) }}" target="_blank" class="btn btn-sm btn-light">
                                    <i class="bi bi-eye"></i> عرض البطاقة الحالية
                                </a>
                            </div>
                        @endif
                        @error('id_image') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>
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
                    <div><strong>الكود:</strong> <code>{{ $employee->code }}</code></div>
                    <div><strong>تاريخ الإضافة:</strong> {{ $employee->created_at?->format('Y-m-d') }}</div>
                    <div><strong>آخر تعديل:</strong> {{ $employee->updated_at?->diffForHumans() }}</div>
                </div>
            </div>
            @endif
        </div>
    </div>

    <div class="form-footer">
        <a href="{{ route('admin.hr.employees.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-x-circle"></i> إلغاء
        </a>
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-circle"></i> {{ $isEdit ? 'حفظ التعديلات' : 'إضافة الموظف' }}
        </button>
    </div>
</div>

@push('scripts')
<script>
$(function () {
    // ── Photo preview ───────────────────────────────────────────────
    $('#photoInput').on('change', function (e) {
        const file = e.target.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = ev => $('#photoPreview').attr('src', ev.target.result);
        reader.readAsDataURL(file);
    });

    // ── Position → auto-set Department (if not set) ─────────────────
    $('#position_id').on('change', function () {
        const dept = $(this).find(':selected').data('dept');
        if (dept && !$('#department_id').val()) {
            $('#department_id').val(dept);
        }
    });

    // ── Payment method → toggle bank fields ─────────────────────────
    const $bankFields = $('.bank-field');
    function toggleBank() {
        const isBank = $('#payment_method').val() === 'bank_transfer';
        $bankFields.toggle(isBank);
    }
    $('#payment_method').on('change', toggleBank);
    toggleBank();
});
</script>
@endpush
