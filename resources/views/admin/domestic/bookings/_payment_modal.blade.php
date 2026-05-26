@php
    /** @var \App\Models\DomesticBooking $booking */
    $isClosed   = $booking->workflow_stage === 'closed';
    $canManage  = auth()->user()?->can('domestic_bookings.manage_payments');
    $canApprove = auth()->user()?->can('domestic_bookings.approve_refund');

    $eligibleForRefund = $booking->payments
        ->where('payment_type', '!=', 'refund')
        ->values();
@endphp

@if(!$isClosed && $canManage)
{{-- Payment / Refund modal --}}
<div class="modal fade" id="paymentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form id="paymentForm" class="modal-content" method="POST" enctype="multipart/form-data"
              action="{{ route('admin.domestic.bookings.payments.store', $booking) }}">
            @csrf
            <input type="hidden" name="_method" id="paymentMethod" value="POST">

            <div class="modal-header">
                <h5 class="modal-title" id="paymentModalTitle">
                    <i class="bi bi-plus-circle text-success"></i> تسجيل دفعة
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                {{-- Refund-only fields --}}
                <div id="refundFields" style="display:none">
                    <div class="alert alert-warning small">
                        <i class="bi bi-info-circle"></i>
                        طلب استرداد يحتاج موافقة المدير قبل صرفه. الحد الأقصى المتاح حالياً يظهر تلقائياً.
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">الدفعة الأصلية <small class="text-muted">(اختياري)</small></label>
                            <select name="refunded_payment_id" id="refundedPaymentId" class="form-select">
                                <option value="">— استرداد عام —</option>
                                @foreach($eligibleForRefund as $orig)
                                    <option value="{{ $orig->id }}">
                                        {{ $orig->receipt_number }} — {{ number_format($orig->amount_egp, 2) }} ج.م ({{ $orig->payment_date?->format('Y-m-d') }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">سبب الاسترداد <span class="text-danger">*</span></label>
                            <input type="text" name="refund_reason" id="refundReason" class="form-control" maxlength="500">
                        </div>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">نوع الدفعة <span class="text-danger">*</span></label>
                        <select name="payment_type" id="paymentType" class="form-select" required>
                            <option value="deposit">عربون</option>
                            <option value="installment" selected>قسط</option>
                            <option value="final">دفعة أخيرة</option>
                            <option value="refund">استرداد</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">تاريخ الدفع <span class="text-danger">*</span></label>
                        <input type="date" name="payment_date" id="paymentDate" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">طريقة الدفع <span class="text-danger">*</span></label>
                        <select name="method" id="paymentMethodSel" class="form-select" required>
                            <option value="cash">نقدي</option>
                            <option value="bank_transfer">تحويل بنكي</option>
                            <option value="credit_card">بطاقة ائتمان</option>
                            <option value="cheque">شيك</option>
                            <option value="instapay">إنستا باي</option>
                            <option value="vodafone_cash">فودافون كاش</option>
                        </select>
                    </div>

                    <div class="col-md-8">
                        <label class="form-label">حساب الخزينة / البنك <span class="text-danger">*</span></label>
                        <select name="cash_account_id" id="cashAccountSel" class="form-select" required>
                            <option value="">-- اختر الحساب --</option>
                            @foreach(($cashAccounts ?? []) as $acc)
                                <option value="{{ $acc->id }}" data-subtype="{{ $acc->sub_type }}">
                                    [{{ $acc->code }}] {{ $acc->name }}
                                    ({{ $acc->sub_type === 'cash' ? 'خزينة' : 'بنك' }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">العملة <span class="text-danger">*</span></label>
                        <select name="currency" id="paymentCurrency" class="form-select" required>
                            <option value="EGP">EGP — جنيه</option>
                            <option value="USD">USD — دولار</option>
                            <option value="EUR">EUR — يورو</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">المبلغ <span class="text-danger">*</span></label>
                        <input type="number" name="amount" id="paymentAmount" min="0.01" step="0.01" class="form-control" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">سعر الصرف</label>
                        <input type="number" name="exchange_rate" id="paymentExchangeRate" min="0" step="0.0001" class="form-control" placeholder="تلقائي">
                    </div>
                    <div class="col-md-3 d-none" id="bankNameWrap">
                        <label class="form-label">اسم البنك</label>
                        <input type="text" name="bank_name" id="paymentBankName" class="form-control" maxlength="120">
                    </div>

                    <div class="col-md-6 d-none" id="txnRefWrap">
                        <label class="form-label">رقم العملية / الإيصال</label>
                        <input type="text" name="transaction_reference" id="paymentTxnRef" class="form-control" maxlength="120">
                    </div>
                    <div class="col-md-3 d-none" id="chequeNumWrap">
                        <label class="form-label">رقم الشيك</label>
                        <input type="text" name="cheque_number" id="paymentChequeNum" class="form-control" maxlength="80">
                    </div>
                    <div class="col-md-3 d-none" id="chequeDateWrap">
                        <label class="form-label">تاريخ الاستحقاق</label>
                        <input type="date" name="cheque_due_date" id="paymentChequeDate" class="form-control">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">المرفق (صورة الإيصال)</label>
                        <input type="file" name="attachment" class="form-control" accept="image/*,.pdf">
                        <small class="text-muted">JPG/PNG/PDF حتى 4MB</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">ملاحظات</label>
                        <textarea name="notes" id="paymentNotes" rows="1" class="form-control" maxlength="500"></textarea>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">تراجع</button>
                <button type="submit" class="btn btn-success"><i class="bi bi-check-circle"></i> حفظ</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function resetPaymentForm(mode) {
    const f = document.getElementById('paymentForm');
    f.action = '{{ route('admin.domestic.bookings.payments.store', $booking) }}';
    document.getElementById('paymentMethod').value = 'POST';
    f.reset();

    const today = new Date().toISOString().slice(0,10);
    document.getElementById('paymentDate').value = today;
    document.getElementById('paymentCurrency').value = 'EGP';
    document.getElementById('paymentMethodSel').value = 'cash';

    if (mode === 'refund') {
        document.getElementById('paymentType').value = 'refund';
        document.getElementById('paymentModalTitle').innerHTML = '<i class="bi bi-arrow-counterclockwise text-warning"></i> طلب استرداد';
    } else {
        document.getElementById('paymentType').value = 'installment';
        document.getElementById('paymentModalTitle').innerHTML = '<i class="bi bi-plus-circle text-success"></i> تسجيل دفعة';
    }
    $('#paymentType').trigger('change');
    $('#paymentMethodSel').trigger('change');
}

function editPayment(payment) {
    const f = document.getElementById('paymentForm');
    f.action = '{{ url('admin/domestic/bookings/' . $booking->id . '/payments') }}/' + payment.id;
    document.getElementById('paymentMethod').value = 'PUT';
    document.getElementById('paymentModalTitle').innerHTML = '<i class="bi bi-pencil text-info"></i> تعديل الدفعة';

    document.getElementById('paymentType').value         = payment.payment_type;
    document.getElementById('paymentDate').value         = payment.payment_date?.substring(0, 10) ?? '';
    document.getElementById('paymentMethodSel').value    = payment.method;
    document.getElementById('paymentCurrency').value     = payment.currency;
    document.getElementById('paymentAmount').value       = payment.amount;
    document.getElementById('paymentExchangeRate').value = payment.currency === 'EGP' ? '' : payment.exchange_rate;
    document.getElementById('cashAccountSel').value      = payment.cash_account_id ?? '';
    document.getElementById('paymentBankName').value     = payment.bank_name ?? '';
    document.getElementById('paymentTxnRef').value       = payment.transaction_reference ?? '';
    document.getElementById('paymentChequeNum').value    = payment.cheque_number ?? '';
    document.getElementById('paymentChequeDate').value   = payment.cheque_due_date?.substring(0, 10) ?? '';
    document.getElementById('paymentNotes').value        = payment.notes ?? '';

    if (payment.payment_type === 'refund') {
        document.getElementById('refundReason').value      = payment.refund_reason ?? '';
        document.getElementById('refundedPaymentId').value = payment.refunded_payment_id ?? '';
    }

    $('#paymentType').trigger('change');
    $('#paymentMethodSel').trigger('change');
}

// Show/hide refund fields + method-specific fields
$(function () {
    $('#paymentType').on('change', function () {
        $('#refundFields').toggle($(this).val() === 'refund');
        $('#refundReason').prop('required', $(this).val() === 'refund');
    });

    $('#paymentMethodSel').on('change', function () {
        const m = $(this).val();
        const needsBank   = ['bank_transfer', 'credit_card', 'cheque'].includes(m);
        const needsTxnRef = ['bank_transfer', 'credit_card', 'instapay', 'vodafone_cash'].includes(m);
        const needsCheque = m === 'cheque';

        $('#bankNameWrap').toggleClass('d-none', !needsBank);
        $('#txnRefWrap').toggleClass('d-none', !needsTxnRef);
        $('#chequeNumWrap').toggleClass('d-none', !needsCheque);
        $('#chequeDateWrap').toggleClass('d-none', !needsCheque);

        // فلترة dropdown حساب الخزينة/البنك
        const expected = m === 'cash' ? 'cash' : 'bank';
        const $accSel = $('#cashAccountSel');
        let firstMatch = null;
        $accSel.find('option').each(function () {
            if (!this.value) { this.hidden = false; return; }
            const ok = this.dataset.subtype === expected;
            this.hidden = !ok;
            if (ok && !firstMatch) firstMatch = this.value;
        });
        const current = $accSel.find('option:selected')[0];
        if (!current || current.hidden || !current.value) {
            $accSel.val(firstMatch || '');
        }
    });

    $('#paymentCurrency').on('change', function () {
        $('#paymentExchangeRate').prop('disabled', $(this).val() === 'EGP');
        if ($(this).val() === 'EGP') $('#paymentExchangeRate').val('');
    });
});
</script>
@endpush

{{-- Approve/Reject refund modal --}}
@if($canApprove)
<div class="modal fade" id="approveRefundModal" tabindex="-1">
    <div class="modal-dialog">
        <form id="approveRefundForm" class="modal-content" method="POST">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title" id="approveRefundTitle"><i class="bi bi-check-circle"></i> موافقة على الاسترداد</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="approveRefundInfo" class="alert alert-light border mb-3"></div>
                <label class="form-label">ملاحظات <span id="approveNotesReq" class="text-danger d-none">*</span></label>
                <textarea name="approval_notes" id="approveRefundNotes" rows="3" class="form-control" maxlength="500"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">تراجع</button>
                <button type="submit" class="btn" id="approveRefundSubmit"></button>
            </div>
        </form>
    </div>
</div>

<script>
function setRefundContext(payment, action) {
    const f = document.getElementById('approveRefundForm');
    const isApprove = action === 'approve';

    f.action = isApprove
        ? '{{ url('admin/domestic/bookings/' . $booking->id . '/payments') }}/' + payment.id + '/approve-refund'
        : '{{ url('admin/domestic/bookings/' . $booking->id . '/payments') }}/' + payment.id + '/reject-refund';

    document.getElementById('approveRefundTitle').innerHTML = isApprove
        ? '<i class="bi bi-check-circle text-success"></i> موافقة على الاسترداد'
        : '<i class="bi bi-x-circle text-danger"></i> رفض طلب الاسترداد';

    document.getElementById('approveRefundInfo').innerHTML =
        '<strong>الإيصال:</strong> ' + payment.receipt_number +
        '<br><strong>المبلغ:</strong> ' + (payment.amount_egp ?? '').toString() + ' ج.م' +
        (payment.refund_reason ? '<br><strong>السبب:</strong> ' + payment.refund_reason : '');

    const btn = document.getElementById('approveRefundSubmit');
    btn.className = 'btn ' + (isApprove ? 'btn-success' : 'btn-danger');
    btn.innerHTML = isApprove
        ? '<i class="bi bi-check-circle"></i> تأكيد الموافقة'
        : '<i class="bi bi-x-circle"></i> تأكيد الرفض';

    document.getElementById('approveNotesReq').classList.toggle('d-none', isApprove);
    document.getElementById('approveRefundNotes').required = !isApprove;
    document.getElementById('approveRefundNotes').value = '';
}
</script>
@endif
@endif
