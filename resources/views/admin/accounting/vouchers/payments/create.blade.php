@extends('layouts.master')

@section('title', 'سند صرف جديد')
@section('page_title', 'إنشاء سند صرف')
@section('page_subtitle', 'سيتم إنشاء قيد محاسبي مرحّل تلقائياً بعد الحفظ')

@push('styles')
<style>
    .help-card {
        background:#fee2e2; border:1px solid #fecaca; color:#991b1b;
        border-radius:10px; padding:.8rem 1rem; font-size:.88rem;
    }
    .help-card code { background:#fff; color:#dc2626; padding:.05rem .35rem; border-radius:4px; }
</style>
@endpush

@section('content')
<form action="{{ route('admin.accounting.vouchers.payments.store') }}" method="POST">
    @csrf
    <div class="card">
        <div class="card-body">

            <div class="help-card mb-3">
                <i class="bi bi-info-circle"></i>
                <strong>سند الصرف</strong> يسجل أي مبلغ بيخرج من الشركة. سيتم تلقائياً إنشاء قيد:
                <code>مدين: الحساب المقابل</code> · <code>دائن: الخزينة/البنك</code>
            </div>

            {{-- Supplier mode --}}
            <div class="card border-warning mb-3" style="background:#fffbeb;">
                <div class="card-body">
                    <div class="form-check form-switch mb-3">
                        <input type="checkbox" id="supplierMode" class="form-check-input"
                               {{ ($presetSupplierId ?? old('supplier_id')) ? 'checked' : '' }}>
                        <label for="supplierMode" class="form-check-label">
                            <strong><i class="bi bi-building"></i> سداد لمورد</strong>
                            — اختر المورد وسيتحدد حساب المقابل تلقائياً + يتم ربط الدفعة بكشف حسابه
                        </label>
                    </div>
                    <div class="row g-3 supplier-fields" style="display:none;">
                        <div class="col-md-6">
                            <label class="form-label">المورد</label>
                            <select name="supplier_id" id="supplierSelect" class="form-select">
                                <option value="">— اختر المورد —</option>
                                @foreach($suppliers->groupBy('type') as $type => $items)
                                    @php $label = ['hotel'=>'فنادق','airline'=>'طيران','transport'=>'نقل','visa'=>'تأشيرات','other'=>'أخرى'][$type] ?? $type; @endphp
                                    <optgroup label="{{ $label }}">
                                        @foreach($items as $s)
                                            <option value="{{ $s->id }}"
                                                    data-currency="{{ $s->currency }}"
                                                    data-name="{{ $s->name }}"
                                                    {{ ($presetSupplierId ?? old('supplier_id')) === $s->id ? 'selected' : '' }}>
                                                {{ $s->code }} — {{ $s->name }}
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">فاتورة محددة (اختياري)</label>
                            <select name="supplier_invoice_id" id="invoiceSelect" class="form-select">
                                <option value="">— أي فاتورة / سداد عام —</option>
                                @foreach($openInvoices as $supplierId => $invoices)
                                    @foreach($invoices as $inv)
                                        <option value="{{ $inv->id }}"
                                                data-supplier="{{ $supplierId }}"
                                                data-amount="{{ $inv->amount + $inv->tax_amount }}"
                                                data-currency="{{ $inv->currency }}"
                                                {{ ($presetInvoiceId ?? old('supplier_invoice_id')) === $inv->id ? 'selected' : '' }}>
                                            {{ $inv->number }} — {{ number_format($inv->amount + $inv->tax_amount, 2) }} {{ $inv->currency }} ({{ $inv->invoice_date->format('Y-m-d') }})
                                        </option>
                                    @endforeach
                                @endforeach
                            </select>
                            <div class="form-text" id="invoiceHint">اختر مورد لتظهر فواتيره المرحّلة فقط</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">التاريخ *</label>
                    <input type="date" name="date" class="form-control" value="{{ old('date', now()->toDateString()) }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">العملة *</label>
                    <select name="currency" class="form-select" required>
                        <option value="EGP" {{ old('currency', 'EGP') === 'EGP' ? 'selected' : '' }}>جنيه (EGP)</option>
                        <option value="SAR" {{ old('currency') === 'SAR' ? 'selected' : '' }}>ريال (SAR)</option>
                        <option value="USD" {{ old('currency') === 'USD' ? 'selected' : '' }}>دولار (USD)</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">المبلغ *</label>
                    <input type="number" step="0.01" min="0.01" name="amount" class="form-control text-end"
                           value="{{ old('amount') }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">سعر الصرف</label>
                    <input type="number" step="0.0001" min="0" name="exchange_rate" class="form-control"
                           value="{{ old('exchange_rate') }}" placeholder="تلقائي للعملات الأجنبية">
                </div>

                <div class="col-md-6">
                    <label class="form-label">الخزينة / البنك (دائن — منها يتم الصرف) *</label>
                    <select name="cash_account_id" class="form-select" required>
                        <option value="">— اختر —</option>
                        <optgroup label="الخزائن النقدية">
                            @foreach($cashAccounts->where('sub_type', 'cash') as $a)
                                <option value="{{ $a->id }}" {{ old('cash_account_id') === $a->id ? 'selected' : '' }}>
                                    {{ $a->code }} — {{ $a->name }}
                                </option>
                            @endforeach
                        </optgroup>
                        <optgroup label="الحسابات البنكية">
                            @foreach($cashAccounts->where('sub_type', 'bank') as $a)
                                <option value="{{ $a->id }}" {{ old('cash_account_id') === $a->id ? 'selected' : '' }}>
                                    {{ $a->code }} — {{ $a->name }}
                                </option>
                            @endforeach
                        </optgroup>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">الحساب المقابل (مدين) *</label>
                    <select name="counter_account_id" class="form-select" required>
                        <option value="">— اختر —</option>
                        @foreach($counterAccounts->groupBy('type') as $type => $items)
                            <optgroup label="{{ ['expense'=>'مصروفات','liability'=>'خصوم','asset'=>'أصول','equity'=>'حقوق ملكية','revenue'=>'إيرادات'][$type] ?? $type }}">
                                @foreach($items as $a)
                                    <option value="{{ $a->id }}" {{ old('counter_account_id') === $a->id ? 'selected' : '' }}>
                                        {{ $a->code }} — {{ $a->name }}
                                    </option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                    <div class="form-text">لمصاريف الشركة (إيجار/كهرباء/...) اختر حساب المصروف. لسداد مورد، اختر حساب المورد.</div>
                </div>

                <div class="col-md-6">
                    <label class="form-label">اسم المستفيد *</label>
                    <input type="text" name="party_name" class="form-control" value="{{ old('party_name') }}"
                           placeholder="مثال: شركة الفنادق المتحدة" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">المرجع</label>
                    <input type="text" name="reference" class="form-control" value="{{ old('reference') }}"
                           placeholder="رقم شيك / تحويل / فاتورة">
                </div>

                <div class="col-12">
                    <label class="form-label">البيان *</label>
                    <textarea name="description" rows="2" class="form-control" required>{{ old('description') }}</textarea>
                </div>
            </div>
        </div>
        <div class="card-footer d-flex justify-content-between">
            <a href="{{ route('admin.accounting.vouchers.payments.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-x"></i> إلغاء
            </a>
            <button type="submit" class="btn btn-danger">
                <i class="bi bi-check2-circle"></i> حفظ وترحيل السند
            </button>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
(function () {
    const modeToggle    = document.getElementById('supplierMode');
    const supplierBox   = document.querySelector('.supplier-fields');
    const supplierSel   = document.getElementById('supplierSelect');
    const invoiceSel    = document.getElementById('invoiceSelect');
    const partyNameInput = document.querySelector('input[name="party_name"]');
    const counterSel    = document.querySelector('select[name="counter_account_id"]');
    const amountInput   = document.querySelector('input[name="amount"]');
    const currencySel   = document.querySelector('select[name="currency"]');

    function toggleSupplierMode() {
        if (modeToggle.checked) {
            supplierBox.style.display = '';
            if (counterSel) counterSel.disabled = true; // server overrides anyway
        } else {
            supplierBox.style.display = 'none';
            supplierSel.value = '';
            invoiceSel.value = '';
            if (counterSel) counterSel.disabled = false;
            filterInvoices();
        }
    }

    function filterInvoices() {
        const sid = supplierSel.value;
        let visibleCount = 0;
        [...invoiceSel.options].forEach(opt => {
            if (!opt.dataset.supplier) { opt.hidden = false; return; }
            const match = !sid || opt.dataset.supplier === sid;
            opt.hidden = !match;
            if (match) visibleCount++;
        });
        // Reset selected if hidden
        if (invoiceSel.selectedOptions[0]?.hidden) invoiceSel.value = '';
        document.getElementById('invoiceHint').textContent = sid
            ? (visibleCount > 0 ? `${visibleCount} فاتورة مرحّلة متاحة` : 'لا توجد فواتير مرحّلة لهذا المورد')
            : 'اختر مورد لتظهر فواتيره المرحّلة فقط';
    }

    supplierSel.addEventListener('change', function () {
        filterInvoices();
        const opt = this.selectedOptions[0];
        if (opt && opt.dataset.name && partyNameInput && !partyNameInput.value) {
            partyNameInput.value = opt.dataset.name;
        }
        if (opt && opt.dataset.currency && currencySel) {
            currencySel.value = opt.dataset.currency;
        }
    });

    invoiceSel.addEventListener('change', function () {
        const opt = this.selectedOptions[0];
        if (opt && opt.dataset.amount && amountInput && !amountInput.value) {
            amountInput.value = opt.dataset.amount;
        }
        if (opt && opt.dataset.currency && currencySel) {
            currencySel.value = opt.dataset.currency;
        }
    });

    modeToggle.addEventListener('change', toggleSupplierMode);
    toggleSupplierMode();
    filterInvoices();
})();
</script>
@endpush
