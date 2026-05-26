@php
    /** @var \App\Models\DomesticBooking $booking */
    $isClosed   = $booking->workflow_stage === 'closed';
    $canManage  = auth()->user()?->can('domestic_bookings.manage_payments');
    $canApprove = auth()->user()?->can('domestic_bookings.approve_refund');

    $payments       = $booking->payments->sortByDesc('payment_date');
    $totalReceived  = (float) $payments->where('payment_type', '!=', 'refund')->sum('amount_egp');
    $totalRefunded  = (float) $payments->where('payment_type', 'refund')->where('refund_status', 'paid')->sum('amount_egp');
    $pendingRefunds = (float) $payments->where('payment_type', 'refund')->whereIn('refund_status', ['pending', 'approved'])->sum('amount_egp');
@endphp

<div class="info-card">
    <div class="head">
        <h6>
            <i class="bi bi-receipt-cutoff text-success"></i>
            المدفوعات والإيصالات
            <span class="badge bg-light text-dark ms-2">{{ $payments->count() }} إيصال</span>
        </h6>
        @if(!$isClosed && $canManage)
        <div class="d-flex gap-1">
            <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#paymentModal"
                    onclick="resetPaymentForm('payment')">
                <i class="bi bi-plus-circle"></i> دفعة جديدة
            </button>
            @if($totalReceived > 0)
            <button type="button" class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#paymentModal"
                    onclick="resetPaymentForm('refund')">
                <i class="bi bi-arrow-counterclockwise"></i> طلب استرداد
            </button>
            @endif
        </div>
        @endif
    </div>
    <div class="body">
        @if($pendingRefunds > 0)
        <div class="alert alert-warning small mb-3">
            <i class="bi bi-hourglass-split"></i>
            <strong>{{ number_format($pendingRefunds, 2) }} ج.م</strong> طلبات استرداد قيد التنفيذ
        </div>
        @endif

        @if($payments->isEmpty())
            <div class="text-center py-4 text-muted">
                <i class="bi bi-cash-coin" style="font-size:2rem;"></i>
                <p class="mt-2 mb-0">لم تُسجَّل أي مدفوعات بعد</p>
                @if(!$isClosed && $canManage)
                    <small>اضغط "دفعة جديدة" لتسجيل أول إيصال</small>
                @endif
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>رقم الإيصال</th>
                            <th>التاريخ</th>
                            <th>النوع</th>
                            <th>المبلغ</th>
                            <th>طريقة الدفع</th>
                            <th>الحالة</th>
                            @if(!$isClosed && $canManage)<th width="120">إجراءات</th>@endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($payments as $p)
                            @php
                                $isRefund = $p->payment_type === 'refund';
                                $rowClass = $isRefund
                                    ? ($p->refund_status === 'paid' ? 'table-danger' : ($p->refund_status === 'rejected' ? 'table-secondary' : 'table-warning'))
                                    : '';
                            @endphp
                            <tr class="{{ $rowClass }}">
                                <td><code class="small">{{ $p->receipt_number }}</code></td>
                                <td class="small">{{ $p->payment_date?->format('Y-m-d') }}</td>
                                <td>
                                    @switch($p->payment_type)
                                        @case('deposit')     <span class="badge bg-info">عربون</span> @break
                                        @case('installment') <span class="badge bg-primary">قسط</span> @break
                                        @case('final')       <span class="badge bg-success">دفعة أخيرة</span> @break
                                        @case('refund')      <span class="badge bg-warning"><i class="bi bi-arrow-counterclockwise"></i> استرداد</span> @break
                                    @endswitch
                                </td>
                                <td>
                                    <strong class="{{ $isRefund ? 'text-danger' : 'text-success' }}">
                                        {{ $isRefund ? '−' : '+' }}{{ number_format($p->amount_egp, 2) }}
                                    </strong>
                                    <small class="text-muted d-block">ج.م</small>
                                    @if($p->currency !== 'EGP')
                                        <small class="text-muted">({{ number_format($p->amount, 2) }} {{ $p->currency }})</small>
                                    @endif
                                </td>
                                <td class="small">
                                    {{ $p->method_label }}
                                    @if($p->bank_name) <div class="text-muted x-small">{{ $p->bank_name }}</div>@endif
                                    @if($p->transaction_reference) <div class="text-muted x-small">{{ $p->transaction_reference }}</div>@endif
                                </td>
                                <td>
                                    @if($isRefund)
                                        @switch($p->refund_status)
                                            @case('pending')  <span class="badge bg-warning"><i class="bi bi-hourglass-split"></i> قيد الموافقة</span> @break
                                            @case('approved') <span class="badge bg-info"><i class="bi bi-check2"></i> موافق — لم يُصرف</span> @break
                                            @case('rejected') <span class="badge bg-secondary"><i class="bi bi-x-circle"></i> مرفوض</span> @break
                                            @case('paid')     <span class="badge bg-danger"><i class="bi bi-check2-all"></i> تم الصرف</span> @break
                                        @endswitch
                                        @if($p->approval_notes)
                                            <div class="text-muted x-small" title="{{ $p->approval_notes }}">
                                                <i class="bi bi-info-circle"></i> {{ \Illuminate\Support\Str::limit($p->approval_notes, 30) }}
                                            </div>
                                        @endif
                                    @else
                                        <span class="badge bg-success">مُستلمة</span>
                                    @endif
                                </td>
                                @if(!$isClosed && $canManage)
                                <td>
                                    {{-- Refund-specific actions --}}
                                    @if($isRefund && $p->refund_status === 'pending' && $canApprove)
                                        <button type="button" class="btn btn-sm btn-light-success btn-icon"
                                                data-bs-toggle="modal" data-bs-target="#approveRefundModal"
                                                onclick='setRefundContext(@json($p), "approve")' title="موافقة">
                                            <i class="bi bi-check-circle"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-light-danger btn-icon"
                                                data-bs-toggle="modal" data-bs-target="#approveRefundModal"
                                                onclick='setRefundContext(@json($p), "reject")' title="رفض">
                                            <i class="bi bi-x-circle"></i>
                                        </button>
                                    @endif

                                    @if($isRefund && $p->refund_status === 'approved')
                                        <form action="{{ route('admin.domestic.bookings.payments.mark_refund_paid', [$booking, $p]) }}"
                                              method="POST" class="d-inline"
                                              onsubmit="return confirm('هل تم صرف المبلغ للعميل فعلاً؟');">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-warning btn-icon" title="تأكيد الصرف">
                                                <i class="bi bi-cash"></i>
                                            </button>
                                        </form>
                                    @endif

                                    {{-- Generic edit/delete for non-paid payments --}}
                                    @if(!($isRefund && in_array($p->refund_status, ['paid'])))
                                        <button type="button" class="btn btn-sm btn-light-info btn-icon"
                                                data-bs-toggle="modal" data-bs-target="#paymentModal"
                                                onclick='editPayment(@json($p))' title="تعديل">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-light-danger btn-icon btn-delete-payment"
                                                data-url="{{ route('admin.domestic.bookings.payments.destroy', [$booking, $p]) }}"
                                                title="حذف">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    @endif
                                </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <th colspan="3" class="text-end">إجمالي المُستلَم:</th>
                            <th class="text-success">+{{ number_format($totalReceived, 2) }} ج.م</th>
                            <th colspan="{{ (!$isClosed && $canManage) ? 3 : 2 }}"></th>
                        </tr>
                        @if($totalRefunded > 0)
                        <tr>
                            <th colspan="3" class="text-end">إجمالي المُسترَد:</th>
                            <th class="text-danger">−{{ number_format($totalRefunded, 2) }} ج.م</th>
                            <th colspan="{{ (!$isClosed && $canManage) ? 3 : 2 }}"></th>
                        </tr>
                        <tr>
                            <th colspan="3" class="text-end">الصافي المُسدَّد:</th>
                            <th class="text-primary"><strong>{{ number_format($totalReceived - $totalRefunded, 2) }} ج.م</strong></th>
                            <th colspan="{{ (!$isClosed && $canManage) ? 3 : 2 }}"></th>
                        </tr>
                        @endif
                    </tfoot>
                </table>
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
$(function () {
    $(document).on('click', '.btn-delete-payment', function () {
        CoreX.ajaxDelete($(this).data('url'), null, () => window.location.reload());
    });
});
</script>
@endpush
