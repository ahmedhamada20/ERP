@php
    /** @var \App\Models\DomesticBooking $booking */
    $isClosed = $booking->workflow_stage === 'closed';
    $canManage = auth()->user()?->can('domestic_bookings.manage_costs');
    $categoryLabels = \App\Models\DomesticBookingCost::CATEGORY_LABELS;
@endphp

@if(!$isClosed && $canManage)
<div class="modal fade" id="costModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form id="costForm" class="modal-content" method="POST"
              action="{{ route('admin.domestic.bookings.costs.store', $booking) }}">
            @csrf
            <input type="hidden" name="_method" id="costMethod" value="POST">

            <div class="modal-header">
                <h5 class="modal-title" id="costModalTitle">
                    <i class="bi bi-plus-circle text-success"></i> إضافة بند تكلفة
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">الفئة <span class="text-danger">*</span></label>
                        <select name="category" id="costCategory" class="form-select" required>
                            @foreach($categoryLabels as $val => $label)
                                <option value="{{ $val }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">الوصف</label>
                        <input type="text" name="description" id="costDescription" class="form-control" maxlength="200" placeholder="اختياري">
                    </div>

                    <div class="col-md-12">
                        <label class="form-label">المورد <small class="text-muted">(اختياري — يربط البند بكشف حساب المورد)</small></label>
                        <select name="supplier_id" id="costSupplier" class="form-select">
                            <option value="">— بدون مورد محدد —</option>
                            @foreach(($suppliers ?? []) as $sup)
                                <option value="{{ $sup->id }}">
                                    [{{ $sup->code }}] {{ $sup->name }} ({{ $sup->type_label ?? $sup->type }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">العملة <span class="text-danger">*</span></label>
                        <select name="currency" id="costCurrency" class="form-select" required>
                            <option value="EGP">EGP — جنيه</option>
                            <option value="USD">USD — دولار</option>
                            <option value="EUR">EUR — يورو</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">المبلغ <span class="text-danger">*</span></label>
                        <input type="number" name="amount" id="costAmount" min="0" step="0.01" class="form-control" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">سعر الصرف</label>
                        <input type="number" name="exchange_rate" id="costExchangeRate" min="0" step="0.0001" class="form-control" placeholder="تلقائي">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">الكمية <span class="text-danger">*</span></label>
                        <input type="number" name="quantity" id="costQuantity" min="1" max="5000" class="form-control" value="1" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">طريقة الحساب <span class="text-danger">*</span></label>
                        <select name="per_unit" id="costPerUnit" class="form-select" required>
                            <option value="total">إجمالي</option>
                            <option value="per_person">لكل شخص</option>
                            <option value="per_room">لكل غرفة</option>
                            <option value="per_night">لكل ليلة</option>
                            <option value="per_trip">لكل رحلة</option>
                        </select>
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                        <div class="form-check form-switch mt-2">
                            <input type="hidden" name="is_revenue" value="0">
                            <input class="form-check-input" type="checkbox" name="is_revenue" value="1" id="costIsRevenue" role="switch">
                            <label class="form-check-label fw-bold" for="costIsRevenue">بند إيراد (وليس تكلفة)</label>
                        </div>
                    </div>

                    <div class="col-12">
                        <label class="form-label">ملاحظات</label>
                        <textarea name="notes" id="costNotes" rows="2" class="form-control" maxlength="500"></textarea>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">تراجع</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> حفظ</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function resetCostForm() {
    const f = document.getElementById('costForm');
    f.action = '{{ route('admin.domestic.bookings.costs.store', $booking) }}';
    document.getElementById('costMethod').value = 'POST';
    document.getElementById('costModalTitle').innerHTML = '<i class="bi bi-plus-circle text-success"></i> إضافة بند تكلفة';
    f.reset();
    document.getElementById('costQuantity').value = 1;
    document.getElementById('costPerUnit').value = 'total';
    document.getElementById('costCurrency').value = 'EGP';
    document.getElementById('costIsRevenue').checked = false;
}

function editCost(cost) {
    const f = document.getElementById('costForm');
    f.action = '{{ url('admin/domestic/bookings/' . $booking->id . '/costs') }}/' + cost.id;
    document.getElementById('costMethod').value = 'PUT';
    document.getElementById('costModalTitle').innerHTML = '<i class="bi bi-pencil text-info"></i> تعديل بند التكلفة';

    document.getElementById('costCategory').value      = cost.category;
    document.getElementById('costDescription').value   = cost.description ?? '';
    document.getElementById('costCurrency').value      = cost.currency;
    document.getElementById('costAmount').value        = cost.amount;
    document.getElementById('costExchangeRate').value  = cost.currency === 'EGP' ? '' : cost.exchange_rate;
    document.getElementById('costQuantity').value      = cost.quantity;
    document.getElementById('costPerUnit').value       = cost.per_unit;
    document.getElementById('costIsRevenue').checked   = !!cost.is_revenue;
    document.getElementById('costNotes').value         = cost.notes ?? '';
    document.getElementById('costSupplier').value      = cost.supplier_id ?? '';
}

// Auto-toggle is_revenue when category = profit
$('#costCategory').on('change', function () {
    if ($(this).val() === 'profit') {
        $('#costIsRevenue').prop('checked', true);
    }
});

// Hide exchange rate when EGP
$('#costCurrency').on('change', function () {
    $('#costExchangeRate').prop('disabled', $(this).val() === 'EGP');
    if ($(this).val() === 'EGP') $('#costExchangeRate').val('');
}).trigger('change');
</script>
@endpush
@endif
