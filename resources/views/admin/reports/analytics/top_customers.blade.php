@extends('layouts.master')

@section('title', 'العملاء الأكثر حجزاً')
@section('page_title', 'العملاء الأكثر حجزاً')
@section('page_subtitle', 'أعلى 50 عميل من حيث إجمالي قيمة الحجوزات في الفترة')

@section('content')

@include('admin.reports.analytics._filter')

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold"><i class="bi bi-people-fill"></i> ترتيب العملاء حسب الإيراد</h6>
        <span class="badge bg-primary">{{ $rows->count() }} عميل</span>
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
                        <th class="text-center" style="width:60px">#</th>
                        <th>كود العميل</th>
                        <th>الاسم</th>
                        <th>التليفون</th>
                        <th>المدينة</th>
                        <th class="text-center">حجوزات دينية</th>
                        <th class="text-center">حجوزات داخلية</th>
                        <th class="text-end">إجمالي القيمة</th>
                        <th class="text-center" style="width:80px">إجراء</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $i => $r)
                        <tr>
                            <td class="text-center text-muted">{{ $i + 1 }}</td>
                            <td><span class="badge bg-light text-dark border">{{ $r->code }}</span></td>
                            <td class="fw-bold">{{ $r->full_name }}</td>
                            <td dir="ltr" class="text-muted">{{ $r->phone }}</td>
                            <td>{{ $r->city ?? '—' }}</td>
                            <td class="text-center">
                                @if($r->religious_count > 0)
                                    <span class="badge bg-warning text-dark">{{ $r->religious_count }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($r->domestic_count > 0)
                                    <span class="badge bg-info text-white">{{ $r->domestic_count }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-end fw-bold text-success">{{ number_format($r->total_revenue, 2) }}</td>
                            <td class="text-center">
                                <a href="{{ route('admin.customers.show', $r->id) }}" class="btn btn-sm btn-outline-primary" title="ملف العميل">
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
