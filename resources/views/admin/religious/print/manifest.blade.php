@extends('admin.religious.print._layout')

@section('title', 'قائمة المعتمرين - ' . $booking->booking_number)
@section('doc_number', $booking->booking_number)
@section('doc_date', 'تاريخ الإصدار: ' . now()->format('Y-m-d'))
@section('doc_title', 'قائمة المعتمرين/الحجاج (Manifest)')

@section('content')

<div class="section">
    <div class="section-title">معلومات الرحلة</div>
    <table class="kv-table">
        <tr>
            <td class="label">رقم الحجز</td>
            <td class="val" dir="ltr"><strong>{{ $booking->booking_number }}</strong></td>
            <td class="label">نوع الرحلة</td>
            <td class="val">{{ $booking->type_label }}</td>
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
            <td class="label">صاحب الحجز</td>
            <td class="val">{{ $booking->customer?->full_name }}</td>
        </tr>
        <tr>
            <td class="label">عدد المعتمرين</td>
            <td class="val"><strong>{{ $booking->pilgrims->count() }}</strong> فرد</td>
            <td class="label">باركود صفا الجماعي</td>
            <td class="val" dir="ltr">{{ $booking->safa_barcode ?: '—' }}</td>
        </tr>
    </table>
</div>

<div class="section">
    <div class="section-title">قائمة المعتمرين بالتفصيل</div>
    <table class="data-table">
        <thead>
            <tr>
                <th width="25">#</th>
                <th>الاسم الكامل</th>
                <th>الاسم بالإنجليزية</th>
                <th width="40">الجنس</th>
                <th width="80">رقم الجواز</th>
                <th width="70">انتهاء الجواز</th>
                <th width="70">رقم التأشيرة</th>
                <th width="55">حالة التأشيرة</th>
                <th width="80">باركود صفا</th>
            </tr>
        </thead>
        <tbody>
            @forelse($booking->pilgrims as $i => $p)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $p->full_name }}</td>
                <td dir="ltr" style="font-size:10px;">{{ $p->full_name_en ?: '—' }}</td>
                <td>{{ $p->gender === 'female' ? 'أنثى' : 'ذكر' }}</td>
                <td dir="ltr">{{ $p->passport_number ?: '—' }}</td>
                <td>{{ $p->passport_expiry_date?->format('Y-m-d') ?? '—' }}</td>
                <td dir="ltr">{{ $p->visa_number ?: '—' }}</td>
                <td>
                    @switch($p->visa_status)
                        @case('issued')    <span class="badge badge-green">صادرة</span> @break
                        @case('requested') <span class="badge badge-blue">مطلوبة</span> @break
                        @case('rejected')  <span class="badge badge-gray">مرفوضة</span> @break
                        @default <span class="badge badge-gold">قيد الانتظار</span>
                    @endswitch
                </td>
                <td dir="ltr" style="font-size:9px;">{{ $p->safa_barcode ?: '—' }}</td>
            </tr>
            @empty
            <tr><td colspan="9" style="text-align:center; padding:18px;">لا يوجد معتمرون مسجلون</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="section">
    <div class="section-title">إحصائيات الرحلة</div>
    <table class="data-table" style="width:60%;">
        <tr>
            <td class="label" style="background:#f9fafb; font-weight:700;">إجمالي المعتمرين</td>
            <td><strong>{{ $booking->pilgrims->count() }}</strong></td>
        </tr>
        <tr>
            <td class="label" style="background:#f9fafb;">ذكور</td>
            <td>{{ $booking->pilgrims->where('gender', 'male')->count() }}</td>
        </tr>
        <tr>
            <td class="label" style="background:#f9fafb;">إناث</td>
            <td>{{ $booking->pilgrims->where('gender', 'female')->count() }}</td>
        </tr>
        <tr>
            <td class="label" style="background:#f9fafb;">تأشيرات صادرة</td>
            <td>{{ $booking->pilgrims->where('visa_status', 'issued')->count() }}</td>
        </tr>
        <tr>
            <td class="label" style="background:#f9fafb;">تأشيرات معلقة</td>
            <td>{{ $booking->pilgrims->where('visa_status', '!=', 'issued')->count() }}</td>
        </tr>
    </table>
</div>

<div class="signature-block">
    <div class="signature-row">
        <div class="signature-cell">
            <div class="signature-line">المسؤول عن الرحلة</div>
        </div>
        <div class="signature-cell">
            <div class="signature-line">ختم الشركة</div>
        </div>
    </div>
</div>

@endsection
