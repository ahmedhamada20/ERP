@extends('layouts.master')

@section('title', 'إضافة حساب')
@section('page_title', 'إضافة حساب جديد')
@section('page_subtitle', 'أضف حساب جديد إلى دليل الحسابات')

@section('content')
<div class="card">
    <div class="card-body">
        <form action="{{ route('admin.accounting.accounts.store') }}" method="POST">
            @include('admin.accounting.accounts._form')

            <hr class="my-4">
            <div class="d-flex gap-2 justify-content-end">
                <a href="{{ route('admin.accounting.accounts.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-x"></i> إلغاء
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check2-circle"></i> حفظ الحساب
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const codeInput   = document.getElementById('accountCodeInput');
    const unlockBtn   = document.getElementById('accountCodeUnlockBtn');
    const hint        = document.getElementById('accountCodeHint');
    const parentSel   = document.querySelector('select[name="parent_id"]');
    const typeSel     = document.querySelector('select[name="type"]');
    const nextCodeUrl = @json(route('admin.accounting.accounts.next-code'));

    if (! codeInput) return;

    let locked = codeInput.readOnly;
    let userTouched = false;

    function setCode(value) {
        if (! locked && userTouched) return;
        codeInput.value = value || '';
    }

    async function fetchSuggestion() {
        if (! locked) return;
        const params = new URLSearchParams();
        if (parentSel?.value) params.set('parent_id', parentSel.value);
        if (typeSel?.value)   params.set('type', typeSel.value);
        try {
            const res = await fetch(nextCodeUrl + '?' + params.toString(), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });
            if (! res.ok) return;
            const data = await res.json();
            setCode(data.code);
        } catch (_) { /* network errors are non-fatal — leave existing value */ }
    }

    parentSel?.addEventListener('change', fetchSuggestion);
    typeSel?.addEventListener('change', fetchSuggestion);

    unlockBtn?.addEventListener('click', function () {
        locked = false;
        codeInput.readOnly = false;
        codeInput.required = true;
        codeInput.focus();
        codeInput.select();
        unlockBtn.remove();
        if (hint) hint.innerHTML = '<i class="bi bi-pencil-square"></i> تعديل يدوي مفعّل';
    });

    codeInput.addEventListener('input', function () {
        if (! locked) userTouched = true;
    });
})();
</script>
@endpush
