@csrf
@isset($account) @method('PUT') @endisset

@php
    $current = $account ?? null;
    $isSystem = (bool) optional($current)->is_system;
    $selectedParent = old('parent_id', $current?->parent_id ?? ($parentId ?? null));
    $selectedType   = old('type', $current?->type ?? ($defaultType ?? null));
    $isEdit = $current !== null;
    $codeValue = old('code', $current?->code ?? ($suggestedCode ?? ''));
    $codeAutoLocked = ! $isEdit && ! $isSystem;
@endphp

<div class="row g-3">
    <div class="col-md-3">
        <label class="form-label">كود الحساب *</label>
        <div class="input-group">
            <input type="text" name="code" id="accountCodeInput"
                   class="form-control @error('code') is-invalid @enderror"
                   value="{{ $codeValue }}"
                   {{ $isSystem || $codeAutoLocked ? 'readonly' : 'required' }}
                   dir="ltr" placeholder="1111">
            @if($codeAutoLocked)
                <button type="button" class="btn btn-outline-secondary" id="accountCodeUnlockBtn"
                        title="تعديل الكود يدوياً">
                    <i class="bi bi-pencil"></i>
                </button>
            @endif
            @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        @if($isSystem)
            <div class="form-text text-muted">حساب نظام — لا يمكن تغيير الكود</div>
        @elseif($codeAutoLocked)
            <div class="form-text text-success" id="accountCodeHint">
                <i class="bi bi-magic"></i> يتم اقتراحه تلقائياً حسب الحساب الأب
            </div>
        @endif
    </div>

    <div class="col-md-9">
        <label class="form-label">اسم الحساب *</label>
        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
               value="{{ old('name', $current?->name) }}" required>
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">الاسم بالإنجليزية</label>
        <input type="text" name="name_en" class="form-control" dir="ltr"
               value="{{ old('name_en', $current?->name_en) }}">
    </div>

    <div class="col-md-3">
        <label class="form-label">تصنيف الحساب *</label>
        <select name="type" class="form-select @error('type') is-invalid @enderror" {{ $isSystem ? 'disabled' : 'required' }}>
            <option value="">— اختر —</option>
            <option value="asset"     {{ $selectedType === 'asset'     ? 'selected' : '' }}>أصول (Asset)</option>
            <option value="liability" {{ $selectedType === 'liability' ? 'selected' : '' }}>خصوم (Liability)</option>
            <option value="equity"    {{ $selectedType === 'equity'    ? 'selected' : '' }}>حقوق ملكية (Equity)</option>
            <option value="revenue"   {{ $selectedType === 'revenue'   ? 'selected' : '' }}>إيرادات (Revenue)</option>
            <option value="expense"   {{ $selectedType === 'expense'   ? 'selected' : '' }}>مصروفات (Expense)</option>
        </select>
        @if($isSystem)<input type="hidden" name="type" value="{{ $selectedType }}">@endif
        @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-3">
        <label class="form-label">العملة *</label>
        <select name="currency" class="form-select" required>
            <option value="EGP" {{ old('currency', $current?->currency ?? 'EGP') === 'EGP' ? 'selected' : '' }}>جنيه (EGP)</option>
            <option value="SAR" {{ old('currency', $current?->currency) === 'SAR' ? 'selected' : '' }}>ريال (SAR)</option>
            <option value="USD" {{ old('currency', $current?->currency) === 'USD' ? 'selected' : '' }}>دولار (USD)</option>
        </select>
    </div>

    <div class="col-md-6">
        <label class="form-label">التصنيف الفرعي</label>
        <select name="sub_type" class="form-select">
            <option value="">— لا شيء —</option>
            <optgroup label="الأصول">
                <option value="current_asset" {{ old('sub_type', $current?->sub_type) === 'current_asset' ? 'selected' : '' }}>أصول متداولة</option>
                <option value="fixed_asset"   {{ old('sub_type', $current?->sub_type) === 'fixed_asset' ? 'selected' : '' }}>أصول ثابتة</option>
                <option value="other_asset"   {{ old('sub_type', $current?->sub_type) === 'other_asset' ? 'selected' : '' }}>أصول أخرى</option>
                <option value="cash"          {{ old('sub_type', $current?->sub_type) === 'cash' ? 'selected' : '' }}>خزينة (للسندات)</option>
                <option value="bank"          {{ old('sub_type', $current?->sub_type) === 'bank' ? 'selected' : '' }}>حساب بنكي (للسندات)</option>
            </optgroup>
            <optgroup label="الخصوم">
                <option value="current_liability"   {{ old('sub_type', $current?->sub_type) === 'current_liability' ? 'selected' : '' }}>خصوم متداولة</option>
                <option value="long_term_liability" {{ old('sub_type', $current?->sub_type) === 'long_term_liability' ? 'selected' : '' }}>خصوم طويلة الأجل</option>
            </optgroup>
            <optgroup label="حقوق الملكية">
                <option value="equity" {{ old('sub_type', $current?->sub_type) === 'equity' ? 'selected' : '' }}>حقوق ملكية</option>
            </optgroup>
            <optgroup label="الإيرادات">
                <option value="operating_revenue" {{ old('sub_type', $current?->sub_type) === 'operating_revenue' ? 'selected' : '' }}>إيرادات تشغيلية</option>
                <option value="other_revenue"     {{ old('sub_type', $current?->sub_type) === 'other_revenue' ? 'selected' : '' }}>إيرادات أخرى</option>
            </optgroup>
            <optgroup label="المصروفات">
                <option value="cost_of_services"  {{ old('sub_type', $current?->sub_type) === 'cost_of_services' ? 'selected' : '' }}>تكلفة الخدمات</option>
                <option value="operating_expense" {{ old('sub_type', $current?->sub_type) === 'operating_expense' ? 'selected' : '' }}>مصروفات تشغيلية</option>
                <option value="other_expense"     {{ old('sub_type', $current?->sub_type) === 'other_expense' ? 'selected' : '' }}>مصروفات أخرى</option>
            </optgroup>
        </select>
    </div>

    <div class="col-md-6">
        <label class="form-label">الحساب الأب</label>
        <select name="parent_id" class="form-select @error('parent_id') is-invalid @enderror">
            <option value="">— حساب رئيسي بدون أب —</option>
            @foreach($parents as $p)
                <option value="{{ $p->id }}" {{ $selectedParent === $p->id ? 'selected' : '' }}>
                    {{ str_repeat('— ', max(0, strlen($p->code) - 1)) }}{{ $p->code }} · {{ $p->name }}
                </option>
            @endforeach
        </select>
        @error('parent_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <div class="form-check form-switch mt-2">
            <input type="hidden" name="is_group" value="0">
            <input type="checkbox" name="is_group" id="isGroup" value="1" class="form-check-input"
                   {{ old('is_group', $current?->is_group) ? 'checked' : '' }}>
            <label class="form-check-label" for="isGroup">حساب مجمّع (لا يقبل حركات مباشرة)</label>
        </div>
        @error('is_group')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <div class="form-check form-switch mt-2">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" id="isActive" value="1" class="form-check-input"
                   {{ old('is_active', $current?->is_active ?? true) ? 'checked' : '' }}>
            <label class="form-check-label" for="isActive">حساب نشط</label>
        </div>
    </div>

    <div class="col-md-6">
        <label class="form-label">الرصيد الافتتاحي</label>
        <input type="number" name="opening_balance" step="0.01" class="form-control"
               value="{{ old('opening_balance', $current?->opening_balance ?? 0) }}">
        <div class="form-text">للحسابات اللي بتنقل من نظام قديم</div>
    </div>

    <div class="col-md-6">
        <label class="form-label">تاريخ الرصيد الافتتاحي</label>
        <input type="date" name="opening_balance_date" class="form-control"
               value="{{ old('opening_balance_date', $current?->opening_balance_date?->format('Y-m-d')) }}">
    </div>

    <div class="col-12">
        <label class="form-label">ملاحظات</label>
        <textarea name="notes" rows="2" class="form-control">{{ old('notes', $current?->notes) }}</textarea>
    </div>
</div>
