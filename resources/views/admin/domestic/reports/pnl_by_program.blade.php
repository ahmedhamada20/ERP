@extends('layouts.master')

@section('title', 'الأرباح والخسائر حسب البرنامج')
@section('page_title', 'تقرير الأرباح والخسائر حسب البرنامج')
@section('page_subtitle', 'تحليل الإيرادات والتكاليف لكل برنامج سياحة داخلية')

@push('styles')
<style>
    .kpi-card { background:#fff; border-radius:14px; padding:1.1rem 1.2rem; box-shadow:0 1px 4px rgba(15,23,42,.04); display:flex; align-items:center; gap:.85rem; height:100%; }
    .kpi-icon { width:48px; height:48px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:1.35rem; flex-shrink:0; }
    .kpi-body .lbl { font-size:.78rem; color:#64748b; font-weight:500; margin-bottom:.15rem; }
    .kpi-body .val { font-size:1.4rem; font-weight:800; color:var(--brand-navy); line-height:1; }
    .kpi-body .sub { font-size:.7rem; color:#94a3b8; }
    .kpi-i-navy   { background:#eef2ff; color:#1e3a8a; }
    .kpi-i-green  { background:#dcfce7; color:#15803d; }
    .kpi-i-red    { background:#fee2e2; color:#b91c1c; }
    .kpi-i-blue   { background:#dbeafe; color:#1d4ed8; }
    .kpi-i-purple { background:#f3e8ff; color:#6b21a8; }

    .filter-bar { background:#fff; border-radius:12px; padding:1rem; box-shadow:0 1px 3px rgba(15,23,42,.04); border:1px solid var(--brand-border); margin-bottom:1rem; }
    .pnl-table th { font-size:.82rem; background:#f1f5f9; color:#475569; white-space:nowrap; }
    .pnl-table td { font-size:.85rem; vertical-align:middle; }
    .margin-bar { display:inline-block; height:6px; background:#e2e8f0; border-radius:3px; min-width:60px; position:relative; vertical-align:middle; }
    .margin-bar .fill { position:absolute; left:0; top:0; bottom:0; border-radius:3px; }
    .margin-bar .fill.positive { background:linear-gradient(90deg, #15803d, #22c55e); }
    .margin-bar .fill.negative { background:linear-gradient(90deg, #b91c1c, #ef4444); }
    .program-cell { display:flex; flex-direction:column; }
    .program-cell .name { font-weight:700; color:var(--brand-navy); }
    .program-cell .code { font-size:.72rem; color:#94a3b8; font-family:'Cairo',monospace; }
</style>
@endpush

@section('content')

<div class="row g-3 mb-3">
    <div class="col-xl-3 col-md-6 col-6">
        <div class="kpi-card">
            <div class="kpi-icon kpi-i-navy"><i class="bi bi-journal-bookmark"></i></div>
            <div class="kpi-body">
                <div class="lbl">إجمالي الحجوزات</div>
                <div class="val">{{ number_format($totals['bookings']) }}</div>
                <div class="sub">من {{ $rows->count() }} برنامج</div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 col-6">
        <div class="kpi-card">
            <div class="kpi-icon kpi-i-blue"><i class="bi bi-cash-stack"></i></div>
            <div class="kpi-body">
                <div class="lbl">الإيرادات</div>
                <div class="val text-primary">{{ number_format($totals['revenue'], 0) }}</div>
                <div class="sub">ج.م</div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 col-6">
        <div class="kpi-card">
            <div class="kpi-icon kpi-i-red"><i class="bi bi-arrow-down-circle"></i></div>
            <div class="kpi-body">
                <div class="lbl">التكاليف</div>
                <div class="val text-danger">{{ number_format($totals['cost'], 0) }}</div>
                <div class="sub">ج.م</div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 col-6">
        <div class="kpi-card">
            <div class="kpi-icon kpi-i-green"><i class="bi bi-graph-up-arrow"></i></div>
            <div class="kpi-body">
                <div class="lbl">صافي الربح</div>
                <div class="val {{ $totals['profit'] >= 0 ? 'text-success' : 'text-danger' }}">
                    {{ number_format($totals['profit'], 0) }}
                </div>
                <div class="sub">هامش متوسط: {{ number_format($totals['avg_margin'], 1) }}%</div>
            </div>
        </div>
    </div>
</div>

<div class="filter-bar">
    <form method="GET" class="row g-3 align-items-end">
        <div class="col-md-2">
            <label class="form-label small">من تاريخ</label>
            <input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] }}">
        </div>
        <div class="col-md-2">
            <label class="form-label small">إلى تاريخ</label>
            <input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] }}">
        </div>
        <div class="col-md-2">
            <label class="form-label small">المدينة</label>
            <input type="text" name="city_filter" class="form-control" placeholder="الكل" value="{{ $filters['city_filter'] }}">
        </div>
        <div class="col-md-2">
            <label class="form-label small">النوع</label>
            <select name="type_filter" class="form-select">
                <option value="">الكل</option>
                <option value="package"    {{ $filters['type_filter'] === 'package' ? 'selected' : '' }}>باكدج</option>
                <option value="hotel_only" {{ $filters['type_filter'] === 'hotel_only' ? 'selected' : '' }}>إقامة فندقية</option>
                <option value="day_trip"   {{ $filters['type_filter'] === 'day_trip' ? 'selected' : '' }}>رحلة يوم</option>
                <option value="cruise"     {{ $filters['type_filter'] === 'cruise' ? 'selected' : '' }}>رحلة نيلية/بحرية</option>
                <option value="camp"       {{ $filters['type_filter'] === 'camp' ? 'selected' : '' }}>مخيم</option>
                <option value="event"      {{ $filters['type_filter'] === 'event' ? 'selected' : '' }}>فعالية</option>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small">الحالة</label>
            <select name="status_filter" class="form-select">
                <option value="">الكل (عدا الملغية)</option>
                <option value="pending"     {{ $filters['status_filter'] === 'pending' ? 'selected' : '' }}>قيد الانتظار</option>
                <option value="confirmed"   {{ $filters['status_filter'] === 'confirmed' ? 'selected' : '' }}>مؤكد</option>
                <option value="in_progress" {{ $filters['status_filter'] === 'in_progress' ? 'selected' : '' }}>جارية</option>
                <option value="completed"   {{ $filters['status_filter'] === 'completed' ? 'selected' : '' }}>مكتمل</option>
            </select>
        </div>
        <div class="col-md-2 d-flex gap-1">
            <button class="btn btn-primary flex-fill"><i class="bi bi-funnel"></i> تطبيق</button>
            <a href="{{ route('admin.domestic.reports.pnl_by_program.export', request()->query()) }}"
               class="btn btn-outline-success" title="تصدير CSV">
                <i class="bi bi-download"></i>
            </a>
        </div>
    </form>
</div>

<div class="card">
    <div class="card-body">
        @if($rows->isEmpty())
            <div class="text-center py-5 text-muted">
                <i class="bi bi-pie-chart" style="font-size:3rem;"></i>
                <h5 class="mt-3">لا توجد بيانات للفلاتر المحددة</h5>
                <p class="mb-0">جرّب تعديل نطاق التاريخ أو إزالة الفلاتر</p>
            </div>
        @else
            @php
                $maxAbsMargin = max($rows->max('margin_pct'), abs($rows->min('margin_pct')), 1);
            @endphp
            <div class="table-responsive">
                <table class="table pnl-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>البرنامج</th>
                            <th>الوجهة</th>
                            <th class="text-center">الحجوزات</th>
                            <th class="text-end">الإيراد</th>
                            <th class="text-end">التكلفة</th>
                            <th class="text-end">صافي الربح</th>
                            <th class="text-center" style="min-width:140px;">الهامش</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rows as $r)
                            <tr>
                                <td>
                                    <div class="program-cell">
                                        <span class="name">{{ $r->program_name ?? '(بدون برنامج)' }}</span>
                                        @if($r->program_code)
                                            <span class="code"><i class="bi bi-hash"></i>{{ $r->program_code }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td><i class="bi bi-geo-alt text-danger"></i> {{ $r->destination_city ?? '—' }}</td>
                                <td class="text-center"><span class="badge bg-light text-dark">{{ number_format($r->bookings_count) }}</span></td>
                                <td class="text-end text-primary fw-bold">{{ number_format($r->revenue, 0) }} <small class="text-muted">ج.م</small></td>
                                <td class="text-end text-danger">{{ number_format($r->cost, 0) }}</td>
                                <td class="text-end fw-bold {{ $r->profit >= 0 ? 'text-success' : 'text-danger' }}">
                                    {{ $r->profit >= 0 ? '+' : '' }}{{ number_format($r->profit, 0) }}
                                </td>
                                <td class="text-center">
                                    @php
                                        $widthPct = min(100, abs($r->margin_pct) / $maxAbsMargin * 100);
                                    @endphp
                                    <div>
                                        <strong class="{{ $r->margin_pct >= 0 ? 'text-success' : 'text-danger' }}">
                                            {{ number_format($r->margin_pct, 1) }}%
                                        </strong>
                                    </div>
                                    <div class="margin-bar">
                                        <div class="fill {{ $r->margin_pct >= 0 ? 'positive' : 'negative' }}"
                                             style="width: {{ $widthPct }}%"></div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <th colspan="2" class="text-end">الإجمالي:</th>
                            <th class="text-center">{{ number_format($totals['bookings']) }}</th>
                            <th class="text-end text-primary">{{ number_format($totals['revenue'], 0) }} ج.م</th>
                            <th class="text-end text-danger">{{ number_format($totals['cost'], 0) }}</th>
                            <th class="text-end {{ $totals['profit'] >= 0 ? 'text-success' : 'text-danger' }}">
                                {{ number_format($totals['profit'], 0) }}
                            </th>
                            <th class="text-center {{ $totals['avg_margin'] >= 0 ? 'text-success' : 'text-danger' }}">
                                {{ number_format($totals['avg_margin'], 1) }}%
                            </th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endif
    </div>
</div>

@endsection
