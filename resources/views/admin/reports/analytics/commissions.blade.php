@extends('layouts.master')

@section('title', 'كشف العمولات')
@section('page_title', 'كشف العمولات المدفوعة')
@section('page_subtitle', 'إجمالي العمولات الفعلية لكل موظف من دورات الرواتب في الفترة')

@section('content')

@include('admin.reports.analytics._filter')

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold"><i class="bi bi-cash-coin"></i> العمولات لكل موظف</h6>
        <span class="badge bg-success">إجمالي: {{ number_format($rows->sum('total_commission'), 2) }} ج.م</span>
    </div>
    <div class="card-body p-0">
        @if($rows->isEmpty())
            <div class="text-center text-muted py-5">
                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                لا توجد عمولات مدفوعة في الفترة المحددة
                <div class="small mt-2">العمولات تظهر فقط من بنود الـ payslip بنوع "عمولة"</div>
            </div>
        @else
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th class="text-center" style="width:60px">#</th>
                        <th>كود الموظف</th>
                        <th>اسم الموظف</th>
                        <th class="text-center">عدد بنود العمولة</th>
                        <th class="text-end">إجمالي العمولة</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $i => $r)
                        <tr>
                            <td class="text-center text-muted">{{ $i + 1 }}</td>
                            <td><span class="badge bg-light text-dark border">{{ $r->code }}</span></td>
                            <td class="fw-bold">{{ $r->full_name }}</td>
                            <td class="text-center">{{ $r->lines_count }}</td>
                            <td class="text-end fw-bold text-success">{{ number_format($r->total_commission, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="table-light fw-bold">
                    <tr>
                        <td colspan="3">الإجمالي</td>
                        <td class="text-center">{{ $rows->sum('lines_count') }}</td>
                        <td class="text-end text-success">{{ number_format($rows->sum('total_commission'), 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
        @endif
    </div>
</div>

@endsection
