@extends('admin.religious.print._layout')

@section('title', 'إيصال - ' . $payment->receipt_number)
@section('doc_number', $payment->receipt_number)
@section('doc_date', 'تاريخ الإيصال: ' . $payment->payment_date->format('Y-m-d'))
@section('doc_title', 'إيصال استلام دفعة')

@section('content')

<div class="section">
    <div class="section-title">بيانات العميل</div>
    <table class="kv-table">
        <tr>
            <td class="label">اسم العميل</td>
            <td class="val"><strong>{{ $payment->booking?->customer?->full_name }}</strong></td>
        </tr>
        <tr>
            <td class="label">رقم الهاتف</td>
            <td class="val" dir="ltr">{{ $payment->booking?->customer?->phone }}</td>
        </tr>
        <tr>
            <td class="label">رقم الحجز</td>
            <td class="val" dir="ltr"><strong>{{ $payment->booking?->booking_number }}</strong></td>
        </tr>
        <tr>
            <td class="label">نوع الرحلة</td>
            <td class="val">{{ $payment->booking?->type_label }} — {{ $payment->booking?->program?->name ?: '—' }}</td>
        </tr>
    </table>
</div>

<div class="section">
    <div class="section-title">تفاصيل الدفعة</div>
    <table class="data-table">
        <tr>
            <th width="40%">البند</th>
            <th>القيمة</th>
        </tr>
        <tr>
            <td class="label" style="background:#f9fafb;">نوع الدفعة</td>
            <td>
                @switch($payment->payment_type)
                    @case('deposit')     <span class="badge badge-blue">مقدم</span> @break
                    @case('installment') <span class="badge badge-gold">قسط</span> @break
                    @case('final')       <span class="badge badge-green">دفعة نهائية</span> @break
                    @case('refund')      <span class="badge badge-gray">استرداد</span> @break
                @endswitch
            </td>
        </tr>
        <tr>
            <td class="label" style="background:#f9fafb;">طريقة الدفع</td>
            <td>{{ $payment->method_label }}</td>
        </tr>
        <tr>
            <td class="label" style="background:#f9fafb;">العملة</td>
            <td>{{ $payment->currency }}</td>
        </tr>
        @if($payment->bank_name)
        <tr>
            <td class="label" style="background:#f9fafb;">البنك</td>
            <td>{{ $payment->bank_name }}</td>
        </tr>
        @endif
        @if($payment->transaction_reference)
        <tr>
            <td class="label" style="background:#f9fafb;">المرجع</td>
            <td dir="ltr">{{ $payment->transaction_reference }}</td>
        </tr>
        @endif
        @if($payment->cheque_number)
        <tr>
            <td class="label" style="background:#f9fafb;">رقم الشيك</td>
            <td dir="ltr">{{ $payment->cheque_number }}</td>
        </tr>
        <tr>
            <td class="label" style="background:#f9fafb;">تاريخ استحقاق الشيك</td>
            <td>{{ $payment->cheque_due_date?->format('Y-m-d') }}</td>
        </tr>
        @endif
        <tr class="totals-row">
            <td>المبلغ المستلم</td>
            <td>
                <strong style="font-size:14px;">{{ number_format($payment->amount, 2) }}</strong> {{ $payment->currency }}
                @if($payment->currency !== 'EGP')
                    <span style="font-size:9px;">({{ number_format($payment->amount_egp, 2) }} ج.م — سعر صرف: {{ $payment->exchange_rate }})</span>
                @endif
            </td>
        </tr>
    </table>
</div>

<div class="section">
    <div class="section-title">ملخص الحساب</div>
    <table class="data-table">
        <tr>
            <td class="label" style="background:#f9fafb; width:50%;">قيمة الحجز الإجمالية</td>
            <td><strong>{{ number_format($payment->booking?->selling_price ?? 0, 2) }}</strong> ج.م</td>
        </tr>
        <tr>
            <td class="label" style="background:#f9fafb;">إجمالي المدفوع (شامل هذه الدفعة)</td>
            <td>{{ number_format($payment->booking?->total_paid ?? 0, 2) }} ج.م</td>
        </tr>
        <tr class="totals-row">
            <td>الرصيد المتبقي</td>
            <td><strong>{{ number_format($payment->booking?->outstanding_balance ?? 0, 2) }}</strong> ج.م</td>
        </tr>
    </table>
</div>

@if($payment->notes)
<div class="section">
    <div class="section-title">ملاحظات</div>
    <p style="font-size:11px;">{{ $payment->notes }}</p>
</div>
@endif

<div class="signature-block">
    <div class="signature-row">
        <div class="signature-cell">
            <div class="signature-line">المستلم (الشركة) — {{ $payment->receiver?->name ?? '' }}</div>
        </div>
        <div class="signature-cell">
            <div class="signature-line">الدافع (العميل)</div>
        </div>
    </div>
</div>

@endsection
