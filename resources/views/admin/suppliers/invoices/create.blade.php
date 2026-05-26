@extends('layouts.master')

@section('title', 'فاتورة مورد جديدة')
@section('page_title', 'إنشاء فاتورة مورد')
@section('page_subtitle', 'سيُنشأ قيد محاسبي مرحّل تلقائياً عند الترحيل')

@push('styles')
<style>
    .help-card { background:#fef3c7; border:1px solid #fde68a; color:#92400e;
                 border-radius:10px; padding:.8rem 1rem; font-size:.88rem; }
    .help-card code { background:#fff; color:#a16207; padding:.05rem .35rem; border-radius:4px; }
    .total-card { background:linear-gradient(135deg, #eef2ff, #fff); border:1px solid #c7d2fe;
                  border-radius:12px; padding:1.25rem; text-align:center; }
    .total-card .lbl { color:#6b7280; font-size:.85rem; font-weight:600; }
    .total-card .val { color:#4338ca; font-weight:800; font-size:1.75rem; line-height:1.1; margin-top:.3rem; }
    .total-card .sub { color:#94a3b8; font-size:.78rem; margin-top:.3rem; }
</style>
@endpush

@section('content')
<form action="{{ route('admin.supplier_invoices.store') }}" method="POST" id="invoiceForm">
    @csrf
    <div class="card mb-3">
        <div class="card-body">

            <div class="help-card mb-3">
                <i class="bi bi-info-circle"></i>
                <strong>سيتم إنشاء قيد:</strong>
                <code>مدين: حساب المصروف</code> + <code>(مدين: ضريبة 2131 لو فيه)</code> +
                <code>دائن: حساب المورد (حسب نوعه)</code>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">المورد *</label>
                    <select name="supplier_id" class="form-select @error('supplier_id') is-invalid @enderror" id="supplierSelect" required>
                        <option value="">— اختر المورد —</option>
                        @foreach($suppliers->groupBy('type') as $type => $items)
                            @php $label = ['hotel'=>'فنادق','airline'=>'طيران','transport'=>'نقل','visa'=>'تأشيرات','other'=>'أخرى'][$type] ?? $type; @endphp
                            <optgroup label="{{ $label }}">
                                @foreach($items as $s)
                                    <option value="{{ $s->id }}"
                                            data-currency="{{ $s->currency }}"
                                            {{ ($presetSupplier ?? old('supplier_id')) === $s->id ? 'selected' : '' }}>
                                        {{ $s->code }} — {{ $s->name }} ({{ $s->currency }})
                                    </option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">حساب المصروف / الأصل *</label>
                    <select name="expense_account_id" class="form-select @error('expense_account_id') is-invalid @enderror" required>
                        <option value="">— اختر الحساب —</option>
                        @foreach($expenseAccounts->groupBy('type') as $type => $items)
                            @php $tLabel = ['expense'=>'مصروفات','asset'=>'أصول'][$type] ?? $type; @endphp
                            <optgroup label="{{ $tLabel }}">
                                @foreach($items as $a)
                                    <option value="{{ $a->id }}" {{ old('expense_account_id') === $a->id ? 'selected' : '' }}>
                                        {{ $a->code }} — {{ $a->name }}
                                    </option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">تاريخ الفاتورة *</label>
                    <input type="date" name="invoice_date" class="form-control"
                           value="{{ old('invoice_date', now()->toDateString()) }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">تاريخ الاستحقاق</label>
                    <input type="date" name="due_date" class="form-control"
                           value="{{ old('due_date', now()->addDays(30)->toDateString()) }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">رقم فاتورة المورد</label>
                    <input type="text" name="supplier_reference" class="form-control"
                           value="{{ old('supplier_reference') }}" placeholder="رقم الفاتورة عند المورد">
                </div>

                <div class="col-md-3">
                    <label class="form-label">العملة *</label>
                    <select name="currency" id="currencySelect" class="form-select" required>
                        <option value="EGP" {{ old('currency', 'EGP') === 'EGP' ? 'selected' : '' }}>جنيه (EGP)</option>
                        <option value="SAR" {{ old('currency') === 'SAR' ? 'selected' : '' }}>ريال (SAR)</option>
                        <option value="USD" {{ old('currency') === 'USD' ? 'selected' : '' }}>دولار (USD)</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">القيمة قبل الضريبة *</label>
                    <input type="number" step="0.01" min="0.01" name="amount" id="amountInput"
                           class="form-control text-end" value="{{ old('amount') }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">قيمة الضريبة</label>
                    <input type="number" step="0.01" min="0" name="tax_amount" id="taxInput"
                           class="form-control text-end" value="{{ old('tax_amount', 0) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">سعر الصرف</label>
                    <input type="number" step="0.0001" min="0" name="exchange_rate" id="rateInput"
                           class="form-control" value="{{ old('exchange_rate') }}" placeholder="تلقائي">
                </div>

                <div class="col-12">
                    <label class="form-label">البيان *</label>
                    <textarea name="description" rows="2" class="form-control" required>{{ old('description') }}</textarea>
                </div>

                <div class="col-md-8">
                    @can('supplier_invoices.post')
                    <div class="form-check form-switch mt-2">
                        <input type="hidden" name="post_immediately" value="0">
                        <input type="checkbox" name="post_immediately" id="postNow" value="1" class="form-check-input">
                        <label class="form-check-label" for="postNow">
                            <strong>ترحيل فوري بعد الحفظ</strong> — لا يمكن التعديل بعدها
                        </label>
                    </div>
                    @endcan
                </div>
                <div class="col-md-4">
                    <div class="total-card">
                        <div class="lbl"><i class="bi bi-calculator"></i> الإجمالي</div>
                        <div class="val"><span id="totalDisplay">0.00</span> <small style="font-size:1rem;" id="currencyDisplay">EGP</small></div>
                        <div class="sub" id="egpDisplay" style="display:none;"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer d-flex justify-content-between">
            <a href="{{ route('admin.supplier_invoices.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-x"></i> إلغاء
            </a>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check2-circle"></i> حفظ الفاتورة
            </button>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
(function () {
    const amount = document.getElementById('amountInput');
    const tax    = document.getElementById('taxInput');
    const rate   = document.getElementById('rateInput');
    const cur    = document.getElementById('currencySelect');
    const totalEl    = document.getElementById('totalDisplay');
    const curEl      = document.getElementById('currencyDisplay');
    const egpEl      = document.getElementById('egpDisplay');

    const fmt = n => Number(n).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    function recalc() {
        const a = parseFloat(amount.value) || 0;
        const t = parseFloat(tax.value)    || 0;
        const c = cur.value;
        const r = parseFloat(rate.value)   || 1;
        const total = a + t;
        totalEl.textContent = fmt(total);
        curEl.textContent = c;

        if (c !== 'EGP' && r > 1) {
            egpEl.style.display = '';
            egpEl.textContent = '≈ ' + fmt(total * r) + ' ج.م';
        } else {
            egpEl.style.display = 'none';
        }
    }

    [amount, tax, rate, cur].forEach(el => el.addEventListener('input', recalc));
    cur.addEventListener('change', () => {
        if (cur.value === 'EGP') rate.value = '';
        recalc();
    });

    // Auto-set currency to supplier's preferred currency on selection
    document.getElementById('supplierSelect').addEventListener('change', function () {
        const sc = this.selectedOptions[0]?.dataset.currency;
        if (sc && !{{ old('currency') ? 'true' : 'false' }}) {
            cur.value = sc;
            recalc();
        }
    });

    recalc();
})();
</script>
@endpush
