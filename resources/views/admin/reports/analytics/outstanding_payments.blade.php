@extends('layouts.master')

@section('title', 'المدفوعات المتأخرة')
@section('page_title', 'تقرير المدفوعات المتأخرة')
@section('page_subtitle', 'الحجوزات المؤكدة/الجارية التي بها رصيد مستحق على العميل')

@section('content')

@include('admin.reports.analytics._filter')

<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="text-muted small fw-bold mb-1"><i class="bi bi-bookmark-x text-warning"></i> حجوزات مستحقة</div>
                <div class="h3 fw-bold mb-0">{{ $summary['count'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="text-muted small fw-bold mb-1"><i class="bi bi-cash text-success"></i> إجمالي المستحق</div>
                <div class="h3 fw-bold mb-0 text-success">{{ number_format($summary['total_outstand'], 2) }} <small class="text-muted">ج.م</small></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="text-muted small fw-bold mb-1"><i class="bi bi-exclamation-triangle text-danger"></i> عاجل (السفر خلال 7 أيام)</div>
                <div class="h3 fw-bold mb-0 text-danger">{{ $summary['urgent_count'] }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3">
        <h6 class="mb-0 fw-bold"><i class="bi bi-list-task"></i> الحجوزات بدفعات معلّقة</h6>
    </div>
    <div class="card-body p-0">
        @if($rows->isEmpty())
            <div class="text-center text-success py-5">
                <i class="bi bi-check-circle fs-1 d-block mb-2"></i>
                ممتاز! لا توجد دفعات متأخرة في الفترة المحددة
            </div>
        @else
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>الحجز</th>
                        <th>العميل</th>
                        <th dir="ltr">التليفون</th>
                        <th>تاريخ السفر</th>
                        <th class="text-center">أيام للسفر</th>
                        <th class="text-end">سعر البيع</th>
                        <th class="text-end">المدفوع</th>
                        <th class="text-end">المتبقي</th>
                        <th class="text-center">إجراء</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $r)
                        <tr>
                            <td>
                                @if($r->kind === 'religious')
                                    <span class="badge bg-warning text-dark me-1">ديني</span>
                                @else
                                    <span class="badge bg-info text-white me-1">داخلي</span>
                                @endif
                                <span class="fw-bold">{{ $r->booking_number }}</span>
                            </td>
                            <td>{{ $r->customer ?? '—' }}</td>
                            <td dir="ltr" class="text-muted">{{ $r->phone ?? '—' }}</td>
                            <td>{{ $r->trip_date?->format('Y-m-d') ?? '—' }}</td>
                            <td class="text-center">
                                @if($r->days_to_trip === null)
                                    <span class="text-muted">—</span>
                                @elseif($r->days_to_trip < 0)
                                    <span class="badge bg-secondary">انتهى</span>
                                @elseif($r->days_to_trip <= 7)
                                    <span class="badge bg-danger">{{ $r->days_to_trip }} يوم</span>
                                @elseif($r->days_to_trip <= 30)
                                    <span class="badge bg-warning text-dark">{{ $r->days_to_trip }} يوم</span>
                                @else
                                    <span class="badge bg-light text-dark border">{{ $r->days_to_trip }} يوم</span>
                                @endif
                            </td>
                            <td class="text-end">{{ number_format($r->selling_price, 2) }}</td>
                            <td class="text-end text-muted">{{ number_format($r->paid, 2) }}</td>
                            <td class="text-end fw-bold text-danger">{{ number_format($r->outstanding, 2) }}</td>
                            <td class="text-center">
                                <a href="{{ $r->show_url }}" class="btn btn-sm btn-outline-primary" title="فتح الحجز">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="table-light fw-bold">
                    <tr>
                        <td colspan="5">الإجمالي</td>
                        <td class="text-end">{{ number_format($rows->sum('selling_price'), 2) }}</td>
                        <td class="text-end">{{ number_format($rows->sum('paid'), 2) }}</td>
                        <td class="text-end text-danger">{{ number_format($rows->sum('outstanding'), 2) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        @endif
    </div>
</div>

@endsection
