@extends('admin.religious.print._layout')

@section('title', 'عقد حجز ديني - ' . $booking->booking_number)
@section('doc_number', $booking->booking_number)
@section('doc_date', 'تاريخ العقد: ' . ($booking->booking_date?->format('Y-m-d') ?? '—'))
@section('doc_title', 'عقد حجز رحلة ' . $booking->type_label)

@section('content')

<div class="section">
    <div class="section-title">طرفا العقد</div>
    <table class="kv-table">
        <tr>
            <td class="label">الطرف الأول (الشركة)</td>
            <td class="val">{{ config('app.name', 'CoreX Tourism') }} — للسياحة والسفر</td>
        </tr>
        <tr>
            <td class="label">الطرف الثاني (العميل)</td>
            <td class="val">
                <strong>{{ $booking->customer?->full_name }}</strong>
                @if($booking->customer?->national_id) — رقم قومي: {{ $booking->customer->national_id }} @endif
            </td>
        </tr>
        <tr>
            <td class="label">رقم الهاتف</td>
            <td class="val" dir="ltr">{{ $booking->customer?->phone }}</td>
        </tr>
        <tr>
            <td class="label">العنوان</td>
            <td class="val">{{ $booking->customer?->address ?: '—' }}</td>
        </tr>
    </table>
</div>

<div class="section">
    <div class="section-title">تفاصيل الرحلة</div>
    <table class="kv-table">
        <tr>
            <td class="label">نوع الرحلة</td>
            <td class="val"><span class="badge badge-green">{{ $booking->type_label }}</span></td>
            <td class="label">رقم الحجز</td>
            <td class="val" dir="ltr"><strong>{{ $booking->booking_number }}</strong></td>
        </tr>
        <tr>
            <td class="label">البرنامج</td>
            <td class="val">{{ $booking->program?->name ?: '—' }}</td>
            <td class="label">المدة</td>
            <td class="val">{{ $booking->duration_days }} يوم</td>
        </tr>
        <tr>
            <td class="label">تاريخ السفر</td>
            <td class="val">{{ $booking->trip_date?->format('Y-m-d') }}</td>
            <td class="label">تاريخ العودة</td>
            <td class="val">{{ $booking->return_date?->format('Y-m-d') ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">نوع التأشيرة</td>
            <td class="val">{{ $booking->visa_type_label }}</td>
            <td class="label">نوع التسكين</td>
            <td class="val">{{ $booking->accommodation_label }}</td>
        </tr>
        <tr>
            <td class="label">نظام الإقامة</td>
            <td class="val">{{ strtoupper($booking->meal_plan) }}</td>
            <td class="label">وسيلة النقل</td>
            <td class="val">
                @switch($booking->transport_type)
                    @case('flight') طيران @break
                    @case('bus') باص @break
                    @case('train') قطار @break
                    @case('vip') VIP @break
                @endswitch
            </td>
        </tr>
        <tr>
            <td class="label">عدد البالغين</td>
            <td class="val"><strong>{{ $booking->adults_count }}</strong></td>
            <td class="label">عدد الأطفال</td>
            <td class="val"><strong>{{ $booking->children_count + $booking->infants_count }}</strong></td>
        </tr>
    </table>
</div>

@if($booking->pilgrims->isNotEmpty())
<div class="section">
    <div class="section-title">قائمة المعتمرين/الحجاج ({{ $booking->pilgrims->count() }})</div>
    <table class="data-table">
        <thead>
            <tr>
                <th width="30">#</th>
                <th>الاسم</th>
                <th width="60">الجنس</th>
                <th width="100">الرقم القومي</th>
                <th width="100">رقم الجواز</th>
                <th width="80">صلاحية الجواز</th>
            </tr>
        </thead>
        <tbody>
            @foreach($booking->pilgrims as $i => $p)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $p->full_name }}</td>
                <td>{{ $p->gender === 'female' ? 'أنثى' : 'ذكر' }}</td>
                <td>{{ $p->national_id ?: '—' }}</td>
                <td dir="ltr">{{ $p->passport_number ?: '—' }}</td>
                <td>{{ $p->passport_expiry_date?->format('Y-m-d') ?? '—' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

<div class="section">
    <div class="section-title">قيمة العقد والمدفوعات</div>
    <table class="data-table">
        <tr>
            <td class="label" style="background:#f9fafb; font-weight:700;">قيمة العقد الإجمالية</td>
            <td><strong>{{ number_format($booking->selling_price, 2) }}</strong> جنيه مصري</td>
        </tr>
        <tr>
            <td class="label" style="background:#f9fafb; font-weight:700;">المدفوع حتى الآن</td>
            <td>{{ number_format($booking->total_paid, 2) }} جنيه مصري</td>
        </tr>
        <tr class="totals-row">
            <td>الرصيد المتبقي</td>
            <td>{{ number_format($booking->outstanding_balance, 2) }} جنيه مصري</td>
        </tr>
    </table>
</div>

<div class="section">
    <div class="section-title">الشروط والأحكام</div>
    <div class="terms">
        <ol>
            <li>يقر الطرف الثاني بأنه اطلع على جميع تفاصيل الرحلة وموافق عليها.</li>
            <li>يلتزم الطرف الثاني بدفع كامل المبلغ قبل تاريخ السفر بأسبوعين على الأقل.</li>
            <li>في حالة إلغاء الحجز قبل السفر بأقل من 15 يوم، يحق للشركة خصم 50% من قيمة الحجز.</li>
            <li>الشركة غير مسؤولة عن أي تأخير ناتج عن قوى قاهرة (جوية، صحية، أمنية).</li>
            <li>على الطرف الثاني تقديم جواز سفر ساري الصلاحية لمدة 6 أشهر من تاريخ السفر.</li>
            <li>الشركة غير مسؤولة عن الأمتعة الشخصية ويُنصح بعمل تأمين سفر.</li>
            <li>أي تعديل أو إضافة على الخدمات يحتاج لاتفاق كتابي بين الطرفين.</li>
        </ol>
    </div>
</div>

<div class="signature-block">
    <div class="signature-row">
        <div class="signature-cell">
            <div class="signature-line">توقيع الطرف الأول (الشركة)</div>
        </div>
        <div class="signature-cell">
            <div class="signature-line">توقيع الطرف الثاني (العميل)</div>
        </div>
    </div>
</div>

@endsection
