@csrf
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">الاسم <span class="required-mark">*</span></label>
        <input type="text" name="name" value="{{ old('name', $user->name ?? '') }}"
               class="form-control @error('name') is-invalid @enderror" required>
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">البريد الإلكتروني <span class="required-mark">*</span></label>
        <input type="email" name="email" value="{{ old('email', $user->email ?? '') }}"
               class="form-control @error('email') is-invalid @enderror" required dir="ltr">
        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">رقم الهاتف</label>
        <input type="text" name="phone" value="{{ old('phone', $user->phone ?? '') }}"
               class="form-control @error('phone') is-invalid @enderror">
        @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">الصلاحيات</label>
        <select name="roles[]" class="form-select select2" multiple data-placeholder="اختر الصلاحيات">
            @foreach($roles as $r)
                <option value="{{ $r }}" {{ in_array($r, $userRoles ?? old('roles', [])) ? 'selected' : '' }}>
                    {{ $r }}
                </option>
            @endforeach
        </select>
        @error('roles')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">
            كلمة المرور
            @if(!isset($user)) <span class="required-mark">*</span>
            @else <small class="text-muted">(اتركها فارغة لعدم التغيير)</small> @endif
        </label>
        <input type="password" name="password"
               class="form-control @error('password') is-invalid @enderror"
               {{ !isset($user) ? 'required' : '' }} autocomplete="new-password">
        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">تأكيد كلمة المرور</label>
        <input type="password" name="password_confirmation" class="form-control"
               {{ !isset($user) ? 'required' : '' }} autocomplete="new-password">
    </div>

    <div class="col-md-6">
        <label class="form-label">الصورة الشخصية</label>
        <input type="file" name="avatar" class="form-control" accept="image/*">
        @error('avatar')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        @if(isset($user) && $user->avatar)
            <div class="mt-2">
                <img src="{{ $user->avatar_url }}" class="rounded" width="80" alt="الصورة الحالية">
            </div>
        @endif
    </div>

    <div class="col-md-6 d-flex align-items-end">
        <div class="form-check form-switch">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1"
                   {{ old('is_active', $user->is_active ?? true) ? 'checked' : '' }}>
            <label class="form-check-label" for="is_active">المستخدم نشط</label>
        </div>
    </div>
</div>

<div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
    <a href="{{ route('admin.users.index') }}" class="btn btn-light">
        <i class="bi bi-arrow-right ms-1"></i> إلغاء
    </a>
    <button type="submit" class="btn btn-primary">
        <i class="bi bi-save ms-1"></i> حفظ
    </button>
</div>
