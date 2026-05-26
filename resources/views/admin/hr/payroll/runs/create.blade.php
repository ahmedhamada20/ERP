@extends('layouts.master')

@section('title', 'دورة رواتب جديدة')
@section('page_title', 'إنشاء دورة رواتب')
@section('page_subtitle', 'حدد الفرع والفترة لبدء احتساب الرواتب الشهرية')

@push('styles')
<style>
    .info-box { background:#eef2ff; border:1px solid #c7d2fe; border-radius:10px; padding:.85rem 1rem; color:#3730a3; font-size:.88rem; }
    .info-box i { font-size:1.1rem; vertical-align:-2px; }
    .month-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:.5rem; }
    .month-btn { padding:.65rem; background:#fff; border:2px solid #e2e8f0; border-radius:10px; font-size:.85rem; font-weight:600; color:#475569; cursor:pointer; transition:all .15s; text-align:center; }
    .month-btn:hover { border-color:var(--brand-navy); color:var(--brand-navy); }
    .month-btn.active { background:var(--brand-navy); color:#fff; border-color:var(--brand-navy); }
</style>
@endpush

@section('content')

<form action="{{ route('admin.hr.payroll.runs.store') }}" method="POST" class="row g-3">
    @csrf

    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header bg-white border-bottom">
                <h6 class="m-0"><i class="bi bi-calendar-plus text-primary"></i> بيانات الدورة</h6>
            </div>
            <div class="card-body">

                <div class="info-box mb-3">
                    <i class="bi bi-info-circle"></i>
                    سيتم إنشاء الدورة في حالة <strong>مسودة</strong>. بعد الإنشاء، اضغط
                    <strong>"احسب الدورة"</strong> لاستخراج الرواتب لجميع موظفي الفرع المختار.
                </div>

                <div class="row g-3">
                    <div class="col-md-12">
                        <label class="form-label fw-bold">الفرع <span class="text-danger">*</span></label>
                        <select name="branch_id" class="form-select form-select-lg @error('branch_id') is-invalid @enderror" required>
                            <option value="">— اختر الفرع —</option>
                            @foreach($branches as $b)
                                <option value="{{ $b->id }}" @selected(old('branch_id', $userBranchId) === $b->id)>
                                    {{ $b->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('branch_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <small class="text-muted">سيتم احتساب رواتب جميع الموظفين النشطين في هذا الفرع فقط.</small>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-bold">السنة <span class="text-danger">*</span></label>
                        <input type="number" name="period_year" class="form-control form-control-lg @error('period_year') is-invalid @enderror"
                               value="{{ old('period_year', $currentYear) }}" min="2020" max="2099" required>
                        @error('period_year')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-8">
                        <label class="form-label fw-bold">الشهر <span class="text-danger">*</span></label>
                        <div class="month-grid">
                            @php
                                $months = ['يناير','فبراير','مارس','أبريل','مايو','يونيو','يوليو','أغسطس','سبتمبر','أكتوبر','نوفمبر','ديسمبر'];
                                $selectedMonth = (int) old('period_month', $currentMonth);
                            @endphp
                            @foreach($months as $i => $name)
                                <div class="month-btn {{ $selectedMonth === ($i+1) ? 'active' : '' }}"
                                     data-month="{{ $i + 1 }}" onclick="selectMonth({{ $i + 1 }}, this)">
                                    {{ $name }}
                                </div>
                            @endforeach
                        </div>
                        <input type="hidden" name="period_month" id="periodMonth" value="{{ $selectedMonth }}">
                        @error('period_month')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">تاريخ الصرف (اختياري)</label>
                        <input type="date" name="payment_date" class="form-control @error('payment_date') is-invalid @enderror"
                               value="{{ old('payment_date') }}">
                        @error('payment_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <small class="text-muted">اتركه فارغاً لتحديده لاحقاً عند الترحيل.</small>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label">ملاحظات</label>
                        <textarea name="notes" rows="2" class="form-control" placeholder="أي ملاحظات داخلية...">{{ old('notes') }}</textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card shadow-sm">
            <div class="card-header bg-white border-bottom">
                <h6 class="m-0"><i class="bi bi-list-check text-primary"></i> الخطوات</h6>
            </div>
            <div class="card-body small">
                <ol class="ps-3 m-0">
                    <li class="mb-2"><strong>إنشاء الدورة</strong> — حدد الفرع والفترة</li>
                    <li class="mb-2"><strong>احتساب</strong> — يتم استخراج الرواتب تلقائياً من بيانات الموظفين</li>
                    <li class="mb-2"><strong>مراجعة</strong> — يمكن تعديل المكافآت والغياب لكل بايسليب</li>
                    <li class="mb-2"><strong>اعتماد</strong> — يقفل الحسابات للاعتماد النهائي</li>
                    <li class="mb-2"><strong>ترحيل</strong> — ينشئ قيد محاسبي تلقائي في دفتر اليومية</li>
                </ol>
            </div>
        </div>

        <div class="d-flex gap-2 mt-3">
            <a href="{{ route('admin.hr.payroll.runs.index') }}" class="btn btn-light flex-fill">
                <i class="bi bi-x-lg"></i> إلغاء
            </a>
            <button type="submit" class="btn btn-primary flex-fill">
                <i class="bi bi-check-lg"></i> إنشاء الدورة
            </button>
        </div>
    </div>
</form>

@endsection

@push('scripts')
<script>
function selectMonth(month, el) {
    document.querySelectorAll('.month-btn').forEach(b => b.classList.remove('active'));
    el.classList.add('active');
    document.getElementById('periodMonth').value = month;
}
</script>
@endpush
