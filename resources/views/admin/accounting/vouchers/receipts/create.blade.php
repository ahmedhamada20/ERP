@extends('layouts.master')

@section('title', 'سند قبض جديد')
@section('page_title', 'إنشاء سند قبض')
@section('page_subtitle', 'سيتم إنشاء قيد محاسبي مرحّل تلقائياً بعد الحفظ')

@push('styles')
<style>
    .help-card {
        background:#eef2ff; border:1px solid #c7d2fe; color:#3730a3;
        border-radius:10px; padding:.8rem 1rem; font-size:.88rem;
    }
    .help-card code { background:#fff; color:#4f46e5; padding:.05rem .35rem; border-radius:4px; }
</style>
@endpush

@section('content')
<form action="{{ route('admin.accounting.vouchers.receipts.store') }}" method="POST">
    @csrf
    <div class="card">
        <div class="card-body">

            <div class="help-card mb-3">
                <i class="bi bi-info-circle"></i>
                <strong>سند القبض</strong> يسجل أي مبلغ بيدخل الشركة. سيتم تلقائياً إنشاء قيد:
                <code>مدين: الخزينة/البنك</code> · <code>دائن: الحساب المقابل</code>
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
                    <label class="form-label">الخزينة / البنك (مدين) *</label>
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
                    <label class="form-label">الحساب المقابل (دائن) *</label>
                    <select name="counter_account_id" class="form-select" required>
                        <option value="">— اختر —</option>
                        @foreach($counterAccounts->groupBy('type') as $type => $items)
                            <optgroup label="{{ ['asset'=>'أصول','liability'=>'خصوم','equity'=>'حقوق ملكية','revenue'=>'إيرادات','expense'=>'مصروفات'][$type] ?? $type }}">
                                @foreach($items as $a)
                                    <option value="{{ $a->id }}" {{ old('counter_account_id') === $a->id ? 'selected' : '' }}>
                                        {{ $a->code }} — {{ $a->name }}
                                    </option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                    <div class="form-text">للإيرادات، اختر حساب الإيراد. للتحصيل من عميل، اختر حساب العميل.</div>
                </div>

                <div class="col-md-6">
                    <label class="form-label">اسم المستلم / الدافع *</label>
                    <input type="text" name="party_name" class="form-control" value="{{ old('party_name') }}"
                           placeholder="مثال: أحمد محمد علي" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">المرجع</label>
                    <input type="text" name="reference" class="form-control" value="{{ old('reference') }}"
                           placeholder="رقم شيك / تحويل / مستند">
                </div>

                <div class="col-12">
                    <label class="form-label">البيان *</label>
                    <textarea name="description" rows="2" class="form-control" required>{{ old('description') }}</textarea>
                </div>
            </div>
        </div>
        <div class="card-footer d-flex justify-content-between">
            <a href="{{ route('admin.accounting.vouchers.receipts.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-x"></i> إلغاء
            </a>
            <button type="submit" class="btn btn-success">
                <i class="bi bi-check2-circle"></i> حفظ وترحيل السند
            </button>
        </div>
    </div>
</form>
@endsection
