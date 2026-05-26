@csrf
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">اسم الصلاحية <span class="required-mark">*</span></label>
        <input type="text" name="name" value="{{ old('name', $role->name ?? '') }}"
               class="form-control @error('name') is-invalid @enderror" required>
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>

<div class="section-divider mt-4">
    <i class="bi bi-key"></i> الأذونات
</div>

<div class="row g-3">
    @foreach($permissions as $group => $perms)
    <div class="col-md-4">
        <div class="card border h-100">
            <div class="card-header bg-light d-flex justify-content-between align-items-center py-2">
                <strong>{{ $group }}</strong>
                <div class="form-check m-0">
                    <input type="checkbox" class="form-check-input check-group" id="group-{{ $group }}" data-group="{{ $group }}">
                    <label class="form-check-label small" for="group-{{ $group }}">الكل</label>
                </div>
            </div>
            <div class="card-body py-2">
                @foreach($perms as $perm)
                <div class="form-check mb-1">
                    <input type="checkbox" class="form-check-input perm-check group-{{ $group }}"
                           id="perm-{{ $perm->id }}" name="permissions[]" value="{{ $perm->name }}"
                           {{ in_array($perm->name, $rolePermissions ?? old('permissions', [])) ? 'checked' : '' }}>
                    <label class="form-check-label" for="perm-{{ $perm->id }}">{{ $perm->name }}</label>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endforeach
</div>

<div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
    <a href="{{ route('admin.roles.index') }}" class="btn btn-light">
        <i class="bi bi-arrow-right ms-1"></i> إلغاء
    </a>
    <button type="submit" class="btn btn-primary">
        <i class="bi bi-save ms-1"></i> حفظ
    </button>
</div>

@push('scripts')
<script>
$(function () {
    $('.check-group').on('change', function () {
        var group = $(this).data('group');
        $('.group-' + group).prop('checked', $(this).prop('checked'));
    });
});
</script>
@endpush
