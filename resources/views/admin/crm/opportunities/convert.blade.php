@extends('layouts.master')

@section('title', 'تحويل ' . $opp->code . ' لحجز')
@section('page_title', 'تحويل الصفقة لحجز')
@section('page_subtitle', $opp->code . ' — ' . $opp->title)

@push('styles')
<style>
    .info-card { background:#fff; border-radius:14px; box-shadow:0 1px 3px rgba(15,23,42,.04); border:1px solid var(--brand-border); overflow:hidden; margin-bottom:1rem; }
    .info-card .head { padding:.85rem 1.1rem; border-bottom:1px solid var(--brand-border); background:linear-gradient(180deg,#fafbff,#f1f5f9); }
    .info-card .head h6 { margin:0; color:var(--brand-navy); font-weight:800; }
    .info-card .body { padding:1.25rem; }
    .form-label { font-size:.82rem; font-weight:700; color:#475569; margin-bottom:.4rem; }
    .form-label .req { color:#dc2626; }
    .form-control, .form-select { height:44px; font-size:.9rem; border-radius:11px; border:1.5px solid #e2e8f0; }
    .form-control:focus, .form-select:focus { border-color:var(--brand-gold); box-shadow:0 0 0 .2rem rgba(212,164,55,.15); }

    .summary-box { background:linear-gradient(135deg, #4338ca, #6b21a8); color:#fff; border-radius:14px; padding:1.25rem; margin-bottom:1rem; }
    .summary-box .label { font-size:.72rem; opacity:.85; }
    .summary-box .value { font-size:1.1rem; font-weight:800; }
    .summary-box .grid { display:grid; grid-template-columns:repeat(2, 1fr); gap:.85rem; margin-top:.5rem; }
</style>
@endpush

@section('content')

<div class="alert alert-warning">
    <strong><i class="bi bi-info-circle"></i> ملاحظة:</strong>
    سيتم إنشاء حجز جديد بناءً على بيانات الصفقة. الحجز سيُفتح للتعديل بعد الإنشاء لإضافة التفاصيل التشغيلية.
</div>

<form action="{{ route('admin.crm.opportunities.convert', $opp) }}" method="POST">
    @csrf

    <div class="row g-3">
        <div class="col-lg-8">
            {{-- Customer selection --}}
            <div class="info-card">
                <div class="head"><h6><i class="bi bi-person"></i> العميل</h6></div>
                <div class="body">
                    @if($opp->customer)
                        <div class="alert alert-success mb-0">
                            <strong><i class="bi bi-check-circle"></i> العميل محدد:</strong>
                            {{ $opp->customer->full_name }} ({{ $opp->customer->code }})
                            <input type="hidden" name="customer_id" value="{{ $opp->customer_id }}">
                        </div>
                    @else
                        @if($opp->lead)
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="radio" name="customer_choice" id="createNew" value="create" checked>
                                <label class="form-check-label" for="createNew">
                                    <strong>إنشاء عميل جديد من الـ Lead:</strong> {{ $opp->lead->full_name }} ({{ $opp->lead->phone }})
                                </label>
                                <input type="hidden" name="create_customer" id="createCustomerFlag" value="1">
                            </div>
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="radio" name="customer_choice" id="useExisting" value="existing">
                                <label class="form-check-label" for="useExisting">اختيار عميل موجود</label>
                            </div>
                        @endif

                        <div id="customerSelectWrap" style="display:{{ $opp->lead ? 'none' : 'block' }}">
                            <label class="form-label">العميل <span class="req">*</span></label>
                            <select name="customer_id" class="form-select select2">
                                <option value="">— اختر عميل —</option>
                                @foreach($customers as $c)
                                    <option value="{{ $c->id }}">{{ $c->full_name }} — {{ $c->phone }} ({{ $c->code }})</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Booking details --}}
            <div class="info-card">
                <div class="head">
                    <h6><i class="bi bi-calendar-event"></i> بيانات الحجز الأساسية</h6>
                </div>
                <div class="body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">تاريخ السفر <span class="req">*</span></label>
                            <input type="date" name="trip_date" class="form-control" required
                                   value="{{ old('trip_date', $opp->expected_trip_date?->format('Y-m-d') ?? now()->addDays(15)->format('Y-m-d')) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">سعر البيع <span class="req">*</span></label>
                            <div class="input-group">
                                <input type="number" name="selling_price" min="0" step="0.01" class="form-control"
                                       value="{{ old('selling_price', $opp->estimated_value) }}" required>
                                <span class="input-group-text">ج.م</span>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info mt-3 mb-0 small">
                        <i class="bi bi-info-circle"></i>
                        باقي تفاصيل الحجز (المدة، نوع الغرفة، الوجبات...) ستُملأ بقيم افتراضية ويمكنك تعديلها من شاشة الحجز.
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            {{-- Summary --}}
            <div class="summary-box">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="mb-0 text-white">ملخص الصفقة</h6>
                    <span class="badge bg-light text-dark">{{ $opp->booking_type_label }}</span>
                </div>
                <div class="grid">
                    <div>
                        <div class="label">الكود</div>
                        <div class="value">{{ $opp->code }}</div>
                    </div>
                    <div>
                        <div class="label">العدد</div>
                        <div class="value">{{ $opp->pax_count }} فرد</div>
                    </div>
                    <div>
                        <div class="label">الوجهة</div>
                        <div class="value">{{ $opp->destination ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="label">القيمة</div>
                        <div class="value">{{ number_format($opp->estimated_value, 0) }} ج.م</div>
                    </div>
                </div>
            </div>

            <div class="info-card">
                <div class="body">
                    <button type="submit" class="btn btn-success w-100 btn-lg">
                        <i class="bi bi-check-circle"></i> تأكيد التحويل لحجز
                    </button>
                    <a href="{{ route('admin.crm.opportunities.show', $opp) }}" class="btn btn-outline-secondary w-100 mt-2">
                        <i class="bi bi-arrow-right"></i> إلغاء
                    </a>
                </div>
            </div>
        </div>
    </div>
</form>

@endsection

@push('scripts')
<script>
$(function () {
    if ($.fn.select2) $('.select2').select2({ width: '100%', dir: 'rtl' });

    @if(!$opp->customer && $opp->lead)
    $('input[name=customer_choice]').on('change', function () {
        const useExisting = $(this).val() === 'existing';
        $('#customerSelectWrap').toggle(useExisting);
        $('#createCustomerFlag').val(useExisting ? '0' : '1');
    });
    @endif
});
</script>
@endpush
