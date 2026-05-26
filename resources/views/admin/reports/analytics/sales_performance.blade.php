@extends('layouts.master')

@section('title', 'أداء البائعين')
@section('page_title', 'أداء مبيعات الموظفين')
@section('page_subtitle', 'تقرير عدد الحجوزات والإيرادات والأرباح لكل موظف مبيعات')

@section('content')

@include('admin.reports.analytics._filter')

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold"><i class="bi bi-person-workspace"></i> ترتيب البائعين حسب الإيراد</h6>
        <span class="badge bg-primary">{{ $rows->count() }} موظف</span>
    </div>
    <div class="card-body p-0">
        @if($rows->isEmpty())
            <div class="text-center text-muted py-5">
                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                لا توجد حجوزات مسندة لموظفي مبيعات في الفترة المحددة
            </div>
        @else
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th class="text-center" style="width:60px">#</th>
                        <th>كود الموظف</th>
                        <th>اسم الموظف</th>
                        <th class="text-center">حجوزات دينية</th>
                        <th class="text-center">حجوزات داخلية</th>
                        <th class="text-center">إجمالي العدد</th>
                        <th class="text-end">إجمالي الإيراد</th>
                        <th class="text-end">صافي الربح</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $i => $r)
                        <tr>
                            <td class="text-center">
                                @if($i === 0)      <i class="bi bi-trophy-fill text-warning fs-5"></i>
                                @elseif($i === 1)  <i class="bi bi-trophy-fill text-secondary fs-5"></i>
                                @elseif($i === 2)  <i class="bi bi-trophy-fill" style="color:#cd7f32"></i>
                                @else              <span class="text-muted">{{ $i + 1 }}</span>
                                @endif
                            </td>
                            <td><span class="badge bg-light text-dark border">{{ $r->code }}</span></td>
                            <td class="fw-bold">{{ $r->full_name }}</td>
                            <td class="text-center">{{ $r->religious_count }}</td>
                            <td class="text-center">{{ $r->domestic_count }}</td>
                            <td class="text-center fw-bold">{{ $r->religious_count + $r->domestic_count }}</td>
                            <td class="text-end fw-bold text-success">{{ number_format($r->total_revenue, 2) }}</td>
                            <td class="text-end fw-bold {{ $r->total_profit >= 0 ? 'text-success' : 'text-danger' }}">
                                {{ number_format($r->total_profit, 2) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="table-light fw-bold">
                    <tr>
                        <td colspan="3">الإجمالي</td>
                        <td class="text-center">{{ $rows->sum('religious_count') }}</td>
                        <td class="text-center">{{ $rows->sum('domestic_count') }}</td>
                        <td class="text-center">{{ $rows->sum('religious_count') + $rows->sum('domestic_count') }}</td>
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
