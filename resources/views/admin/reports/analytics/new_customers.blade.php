@extends('layouts.master')

@section('title', 'العملاء الجدد')
@section('page_title', 'العملاء الجدد')
@section('page_subtitle', 'العملاء الذين تم تسجيلهم في الفترة المحددة')

@section('content')

@include('admin.reports.analytics._filter')

<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="text-muted small fw-bold mb-1"><i class="bi bi-person-plus text-primary"></i> إجمالي العملاء الجدد</div>
                <div class="h3 fw-bold mb-0">{{ $summary['total'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="text-muted small fw-bold mb-1"><i class="bi bi-check-circle text-success"></i> حجزوا بالفعل</div>
                <div class="h3 fw-bold mb-0 text-success">{{ $summary['with_booking'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="text-muted small fw-bold mb-1"><i class="bi bi-hourglass text-warning"></i> لم يحجزوا بعد</div>
                <div class="h3 fw-bold mb-0 text-warning">{{ $summary['no_booking'] }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3">
        <h6 class="mb-0 fw-bold"><i class="bi bi-list-ul"></i> قائمة العملاء الجدد</h6>
    </div>
    <div class="card-body p-0">
        @if($rows->isEmpty())
            <div class="text-center text-muted py-5">
                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                لم يُسجَّل أي عميل في الفترة المحددة
            </div>
        @else
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>تاريخ التسجيل</th>
                        <th>الكود</th>
                        <th>الاسم</th>
                        <th>التليفون</th>
                        <th>النوع</th>
                        <th class="text-center">حجوزات</th>
                        <th class="text-center" style="width:80px">إجراء</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $r)
                        <tr>
                            <td class="text-muted small">{{ $r->created_at->format('Y-m-d') }}</td>
                            <td><span class="badge bg-light text-dark border">{{ $r->code }}</span></td>
                            <td class="fw-bold">{{ $r->full_name }}</td>
                            <td dir="ltr" class="text-muted">{{ $r->phone }}</td>
                            <td>
                                @switch($r->type)
                                    @case('individual') <span class="badge bg-primary">فرد</span> @break
                                    @case('agency')     <span class="badge bg-info">وكالة</span> @break
                                    @case('group')      <span class="badge bg-warning text-dark">مجموعة</span> @break
                                    @default            {{ $r->type }}
                                @endswitch
                            </td>
                            <td class="text-center">
                                @php $totalBookings = $r->religious_bookings_count + $r->domestic_bookings_count; @endphp
                                @if($totalBookings > 0)
                                    <span class="badge bg-success">{{ $totalBookings }}</span>
                                @else
                                    <span class="badge bg-light text-dark border">لم يحجز بعد</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <a href="{{ route('admin.customers.show', $r->id) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>

@endsection
