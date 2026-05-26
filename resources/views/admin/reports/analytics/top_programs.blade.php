@extends('layouts.master')

@section('title', 'البرامج الأعلى مبيعاً')
@section('page_title', 'البرامج الأعلى مبيعاً')
@section('page_subtitle', 'ترتيب البرامج (دينية + داخلية) حسب الإيرادات والربحية')

@section('content')

@include('admin.reports.analytics._filter')

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold"><i class="bi bi-trophy"></i> ترتيب البرامج حسب الإيراد</h6>
        <span class="badge bg-primary">{{ $rows->count() }} برنامج</span>
    </div>
    <div class="card-body p-0">
        @if($rows->isEmpty())
            <div class="text-center text-muted py-5">
                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                لا توجد بيانات لبرامج في الفترة المحددة
            </div>
        @else
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th class="text-center" style="width:60px">#</th>
                        <th>اسم البرنامج</th>
                        <th class="text-center">النوع</th>
                        <th class="text-center">عدد الحجوزات</th>
                        <th class="text-end">الإيراد</th>
                        <th class="text-end">صافي الربح</th>
                        <th class="text-end">الهامش</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $i => $r)
                        <tr>
                            <td class="text-center text-muted">{{ $i + 1 }}</td>
                            <td class="fw-bold">{{ $r->name }}</td>
                            <td class="text-center">
                                @if($r->kind === 'religious')
                                    <span class="badge bg-warning text-dark">ديني</span>
                                @else
                                    <span class="badge bg-info text-white">داخلي</span>
                                @endif
                            </td>
                            <td class="text-center">{{ $r->bookings_count }}</td>
                            <td class="text-end fw-bold">{{ number_format($r->revenue, 2) }}</td>
                            <td class="text-end {{ $r->profit >= 0 ? 'text-success' : 'text-danger' }}">
                                {{ number_format($r->profit, 2) }}
                            </td>
                            <td class="text-end">
                                @if($r->revenue > 0)
                                    {{ number_format(($r->profit / $r->revenue) * 100, 1) }}%
                                @else
                                    —
                                @endif
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
