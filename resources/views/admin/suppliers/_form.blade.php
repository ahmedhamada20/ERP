@csrf
@isset($supplier) @method('PUT') @endisset

@php $s = $supplier ?? null; @endphp

{{-- Basic Info --}}
<div class="card mb-3">
    <div class="card-header"><h6 class="mb-0"><i class="bi bi-building"></i> بيانات أساسية</h6></div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">اسم المورد *</label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                       value="{{ old('name', $s?->name) }}" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label">الاسم بالإنجليزية</label>
                <input type="text" name="name_en" class="form-control" dir="ltr"
                       value="{{ old('name_en', $s?->name_en) }}">
            </div>

            <div class="col-md-4">
                <label class="form-label">تصنيف المورد *</label>
                <select name="type" class="form-select @error('type') is-invalid @enderror" required>
                    @foreach(['hotel'=>'فنادق','airline'=>'طيران','transport'=>'نقل','visa'=>'تأشيرات','other'=>'أخرى'] as $val => $label)
                        <option value="{{ $val }}" {{ old('type', $s?->type) === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                <div class="form-text">يحدد حساب GL الأب اللي بيتراكم عليه رصيد المورد</div>
            </div>
            <div class="col-md-4">
                <label class="form-label">البلد</label>
                <input type="text" name="country" class="form-control"
                       value="{{ old('country', $s?->country ?? 'مصر') }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">المدينة</label>
                <input type="text" name="city" class="form-control"
                       value="{{ old('city', $s?->city) }}">
            </div>
        </div>
    </div>
</div>

{{-- Contact --}}
<div class="card mb-3">
    <div class="card-header"><h6 class="mb-0"><i class="bi bi-telephone"></i> بيانات التواصل</h6></div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">المسؤول</label>
                <input type="text" name="contact_person" class="form-control"
                       value="{{ old('contact_person', $s?->contact_person) }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">الهاتف</label>
                <input type="text" name="phone" class="form-control" dir="ltr"
                       value="{{ old('phone', $s?->phone) }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">الجوال</label>
                <input type="text" name="mobile" class="form-control" dir="ltr"
                       value="{{ old('mobile', $s?->mobile) }}">
            </div>

            <div class="col-md-6">
                <label class="form-label">البريد الإلكتروني</label>
                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" dir="ltr"
                       value="{{ old('email', $s?->email) }}">
                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label">العنوان</label>
                <input type="text" name="address" class="form-control"
                       value="{{ old('address', $s?->address) }}">
            </div>
        </div>
    </div>
</div>

{{-- Legal & Financial --}}
<div class="card mb-3">
    <div class="card-header"><h6 class="mb-0"><i class="bi bi-cash-coin"></i> البيانات المالية والقانونية</h6></div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">الرقم الضريبي</label>
                <input type="text" name="tax_number" class="form-control @error('tax_number') is-invalid @enderror" dir="ltr"
                       value="{{ old('tax_number', $s?->tax_number) }}">
                @error('tax_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label class="form-label">السجل التجاري</label>
                <input type="text" name="commercial_register" class="form-control" dir="ltr"
                       value="{{ old('commercial_register', $s?->commercial_register) }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">العملة *</label>
                <select name="currency" class="form-select" required>
                    @foreach(['EGP'=>'جنيه (EGP)','SAR'=>'ريال (SAR)','USD'=>'دولار (USD)'] as $val => $label)
                        <option value="{{ $val }}" {{ old('currency', $s?->currency ?? 'EGP') === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label">الرصيد الافتتاحي</label>
                <input type="number" step="0.01" name="opening_balance" class="form-control text-end"
                       value="{{ old('opening_balance', $s?->opening_balance ?? 0) }}">
                <div class="form-text">+ مستحق له (دائن) / − مستحق علينا (مدين)</div>
            </div>
            <div class="col-md-4">
                <label class="form-label">تاريخ الرصيد الافتتاحي</label>
                <input type="date" name="opening_balance_date" class="form-control"
                       value="{{ old('opening_balance_date', $s?->opening_balance_date?->format('Y-m-d')) }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">مهلة السداد (أيام)</label>
                <input type="number" min="0" max="365" name="payment_terms_days" class="form-control"
                       value="{{ old('payment_terms_days', $s?->payment_terms_days ?? 30) }}">
                <div class="form-text">يُستخدم في تقرير أعمار الديون</div>
            </div>
        </div>
    </div>
</div>

{{-- Meta --}}
<div class="card mb-3">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3">
                <div class="form-check form-switch">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" id="isActive" value="1" class="form-check-input"
                           {{ old('is_active', $s?->is_active ?? true) ? 'checked' : '' }}>
                    <label class="form-check-label" for="isActive">مورد نشط</label>
                </div>
            </div>
            <div class="col-md-9">
                <label class="form-label">ملاحظات</label>
                <textarea name="notes" rows="2" class="form-control">{{ old('notes', $s?->notes) }}</textarea>
            </div>
        </div>
    </div>
</div>
