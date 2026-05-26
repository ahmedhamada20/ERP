@extends('layouts.master')

@section('title', 'الربحية الشهرية')
@section('page_title', 'تقرير الربحية الشهرية')
@section('page_subtitle', 'صافي الأرباح والإيرادات شهرياً — سياحة دينية + داخلية')

@section('content')

@include('admin.reports.analytics._filter')

{{-- ── KPIs الأعلى ─────────────────────────────── --}}
<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="text-muted small fw-bold mb-1"><i class="bi bi-cash-stack text-success"></i> إجمالي الإيرادات</div>
                <div class="h4 fw-bold mb-0 text-success">{{ number_format($summary['total_revenue'], 2) }} <small class="text-muted">ج.م</small></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="text-muted small fw-bold mb-1"><i class="bi bi-graph-up-arrow text-primary"></i> صافي الربح</div>
                <div class="h4 fw-bold mb-0 text-primary">{{ number_format($summary['total_profit'], 2) }} <small class="text-muted">ج.م</small></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="text-muted small fw-bold mb-1"><i class="bi bi-percent text-warning"></i> هامش الربح</div>
                <div class="h4 fw-bold mb-0 text-warning">{{ $summary['margin'] }}%</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="text-muted small fw-bold mb-1"><i class="bi bi-bookmark-check text-info"></i> عدد الحجوزات</div>
                <div class="h4 fw-bold mb-0 text-info">{{ number_format($summary['bookings']) }}</div>
            </div>
        </div>
    </div>
</div>

{{-- ── الجدول التفصيلي ─────────────────────────── --}}
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3">
        <h6 class="mb-0 fw-bold"><i class="bi bi-table"></i> التفصيل الشهري</h6>
    </div>
    <div class="card-body p-0">
        @if($rows->isEmpty())
            <div class="text-center text-muted py-5">
                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                لا توجد حجوزات في الفترة المحددة
            </div>
        @else
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>الشهر</th>
                        <th class="text-center">حجوزات دينية</th>
                        <th class="text-end">إيراد ديني</th>
                        <th class="text-end">ربح ديني</th>
                        <th class="text-center">حجوزات داخلية</th>
                        <th class="text-end">إيراد داخلي</th>
                        <th class="text-end">ربح داخلي</th>
                        <th class="text-end fw-bold">إجمالي الإيراد</th>
                        <th class="text-end fw-bold">صافي الربح</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $r)
                        <tr>
                            <td class="fw-bold">{{ $r->month }}</td>
                            <td class="text-center">{{ $r->religious_bookings }}</td>
                            <td class="text-end">{{ number_format($r->religious_revenue, 2) }}</td>
                            <td class="text-end text-primary">{{ number_format($r->religious_profit, 2) }}</td>
                            <td class="text-center">{{ $r->domestic_bookings }}</td>
                            <td class="text-end">{{ number_format($r->domestic_revenue, 2) }}</td>
                            <td class="text-end text-primary">{{ number_format($r->domestic_profit, 2) }}</td>
                            <td class="text-end fw-bold text-success">{{ number_format($r->total_revenue, 2) }}</td>
                            <td class="text-end fw-bold {{ $r->total_profit >= 0 ? 'text-success' : 'text-danger' }}">
                                {{ number_format($r->total_profit, 2) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="table-light fw-bold">
                    <tr>
                        <td>الإجمالي</td>
                        <td class="text-center">{{ $rows->sum('religious_bookings') }}</td>
                        <td class="text-end">{{ number_format($rows->sum('religious_revenue'), 2) }}</td>
                        <td class="text-end">{{ number_format($rows->sum('religious_profit'), 2) }}</td>
                        <td class="text-center">{{ $rows->sum('domestic_bookings') }}</td>
                        <td class="text-end">{{ number_format($rows->sum('domestic_revenue'), 2) }}</td>
                        <td class="text-end">{{ number_format($rows->sum('domestic_profit'), 2) }}</td>
                        <td class="text-end text-success">{{ number_format($rows->sum('total_revenue'), 2) }}</td>
                        <td class="text-end text-success">{{ number_format($rows->sum('total_profit'), 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
        @endif
    </div>
</div>

@endsection
