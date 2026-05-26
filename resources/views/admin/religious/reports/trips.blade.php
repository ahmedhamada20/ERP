@extends('layouts.master')

@section('title', 'حصر الرحلات')
@section('page_title', 'حصر الرحلات الدينية')
@section('page_subtitle', 'تقرير شامل لكل الرحلات - إجمالي الغرف، الأفراد، التأشيرات، الباركودات، المبيعات، الأرباح، والعمولات')

@push('styles')
<style>
    /* ── Filter bar ─────────────────────────────────────────── */
    .report-filters {
        background: #fff; border-radius: 14px;
        padding: 1.1rem 1.25rem; margin-bottom: 1rem;
        border: 1px solid #f1f5f9;
        box-shadow: 0 2px 8px rgba(15,23,42,.04);
    }
    .report-filters .form-label {
        font-size: .75rem; font-weight: 700; color: #475569;
        margin-bottom: .35rem;
    }
    .report-filters .form-control,
    .report-filters .form-select {
        font-size: .85rem; height: 38px; border-radius: 9px;
        border: 1.5px solid #e2e8f0;
    }
    .report-filters .form-control:focus,
    .report-filters .form-select:focus {
        border-color: var(--brand-gold);
        box-shadow: 0 0 0 .2rem rgba(212,164,55,.15);
    }
    .filter-actions { display: flex; gap: .5rem; flex-wrap: wrap; justify-content: flex-end; }
    .filter-actions .btn { padding: .5rem 1rem; font-weight: 700; font-size: .82rem; }

    /* ── Totals grid (KPIs) ─────────────────────────────────── */
    .totals-grid {
        display: grid; gap: .85rem; margin-bottom: 1.25rem;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    }
    .total-box {
        background: #fff; border-radius: 14px;
        padding: 1rem 1.15rem;
        border: 1px solid #f1f5f9;
        box-shadow: 0 2px 8px rgba(15,23,42,.04);
        position: relative; overflow: hidden;
        transition: all .25s cubic-bezier(.4,0,.2,1);
    }
    .total-box:hover { transform: translateY(-3px); box-shadow: 0 10px 24px rgba(15,23,42,.08); }
    .total-box::after {
        content: ''; position: absolute;
        right: -30px; bottom: -30px;
        width: 100px; height: 100px;
        background: var(--accent, #f1f5f9);
        border-radius: 50%; opacity: .15;
        transition: transform .3s;
    }
    .total-box:hover::after { transform: scale(1.3); }

    .total-box .head-row {
        display: flex; justify-content: space-between; align-items: flex-start;
        margin-bottom: .65rem;
    }
    .total-box .ico {
        width: 38px; height: 38px; border-radius: 10px;
        background: var(--accent, #f1f5f9);
        color: var(--accent-color, #64748b);
        display: flex; align-items: center; justify-content: center;
        font-size: 1.1rem;
    }
    .total-box .lbl {
        font-size: .76rem; color: #64748b; font-weight: 700;
        margin-bottom: .15rem;
    }
    .total-box .val {
        font-size: 1.65rem; font-weight: 900; color: var(--brand-navy);
        line-height: 1.05; position: relative; z-index: 1;
    }
    .total-box .sub {
        font-size: .68rem; color: #94a3b8; margin-top: .25rem;
        font-weight: 600;
    }

    /* Themed totals — color hints */
    .total-box.t-bookings   { --accent:#dbeafe; --accent-color:#1d4ed8; }
    .total-box.t-pilgrims   { --accent:#e0e7ff; --accent-color:#4338ca; }
    .total-box.t-rooms      { --accent:#f3e8ff; --accent-color:#7c3aed; }
    .total-box.t-flights    { --accent:#ccfbf1; --accent-color:#0f766e; }
    .total-box.t-visas      { --accent:#fef3c7; --accent-color:#b45309; }
    .total-box.t-barcodes   { --accent:#fed7aa; --accent-color:#c2410c; }
    .total-box.t-sales      {
        background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
        border-color: #fde68a;
        --accent:#fcd34d; --accent-color:#92400e;
    }
    .total-box.t-sales .val { color: #92400e; }
    .total-box.t-cost {
        background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
        border-color: #fecaca;
        --accent:#fca5a5; --accent-color:#991b1b;
    }
    .total-box.t-cost .val { color: #b91c1c; }
    .total-box.t-profit {
        background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
        border-color: #bbf7d0;
        --accent:#86efac; --accent-color:#15803d;
    }
    .total-box.t-profit .val { color: #15803d; }
    .total-box.t-commission { --accent:#cffafe; --accent-color:#0e7490; }

    /* ── Trips table ─────────────────────────────────────── */
    .report-table-wrap {
        background: #fff; border-radius: 14px; overflow: hidden;
        border: 1px solid #f1f5f9;
        box-shadow: 0 2px 8px rgba(15,23,42,.04);
        margin-bottom: 1rem;
    }
    .report-table-wrap .head {
        padding: 1rem 1.25rem;
        background: linear-gradient(135deg, #fafbff, #f8fafc);
        border-bottom: 1px solid #f1f5f9;
        display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: .5rem;
    }
    .report-table-wrap .head h6 {
        margin: 0; font-weight: 800; color: var(--brand-navy);
        display: flex; align-items: center; gap: .5rem;
    }
    .report-table-wrap .head .badge-count {
        background: linear-gradient(135deg, var(--brand-navy), #1e293b);
        color: #fff; padding: .2rem .7rem; border-radius: 8px;
        font-size: .75rem; font-weight: 800;
    }

    .report-table { width: 100%; margin: 0; }
    .report-table thead th {
        background: #f9fafb; color: #475569;
        font-weight: 800; font-size: .76rem;
        padding: .85rem .65rem; text-align: right;
        white-space: nowrap;
        position: sticky; top: 0; z-index: 1;
        border-bottom: 2px solid #e2e8f0;
    }
    .report-table tbody td {
        padding: .85rem .65rem; font-size: .85rem;
        vertical-align: middle;
        border-bottom: 1px solid #f3f4f6;
    }
    .report-table tbody tr { transition: background .15s; }
    .report-table tbody tr:hover { background: linear-gradient(90deg, #fffbeb, transparent); }
    .report-table tfoot td {
        background: linear-gradient(135deg, var(--brand-navy), #1e293b);
        color: #fff; font-weight: 900;
        padding: 1rem .65rem; font-size: .9rem;
        border: none;
    }
    .report-table tfoot td:first-child { text-align: right; }
    .report-table tfoot .gold { color: var(--brand-gold); }

    .type-tag {
        display: inline-flex; align-items: center; gap: .3rem;
        padding: .25rem .55rem; border-radius: 7px;
        font-size: .72rem; font-weight: 800;
    }
    .type-tag.hajj  { background: linear-gradient(135deg,#fef3c7,#fde68a); color: #92400e; }
    .type-tag.umrah { background: linear-gradient(135deg,#e0e7ff,#c7d2fe); color: #4338ca; }

    .status-chip {
        display: inline-block; padding: .25rem .65rem;
        border-radius: 999px; font-size: .7rem; font-weight: 800;
    }
    .status-chip.pending     { background: #fef3c7; color: #92400e; }
    .status-chip.confirmed   { background: #dbeafe; color: #1e40af; }
    .status-chip.in_progress { background: #e0e7ff; color: #4338ca; }
    .status-chip.completed   { background: #dcfce7; color: #15803d; }
    .status-chip.cancelled   { background: #fee2e2; color: #991b1b; }

    .booking-link {
        font-family: 'JetBrains Mono', monospace; font-weight: 800;
        color: var(--brand-navy); text-decoration: none;
        background: #f1f5f9; padding: .2rem .55rem; border-radius: 7px;
        font-size: .78rem; transition: all .15s;
    }
    .booking-link:hover { background: var(--brand-navy); color: #fff; }

    .money-cell { font-weight: 700; white-space: nowrap; font-variant-numeric: tabular-nums; }
    .money-cell.profit-pos { color: #15803d; }
    .money-cell.profit-neg { color: #b91c1c; }
    .money-cell.cost { color: #b91c1c; }
    .money-cell.sales { color: var(--brand-navy); }

    .empty-row td {
        padding: 3rem 1rem !important; text-align: center;
        color: #94a3b8; font-size: .95rem;
    }
    .empty-row .empty-ico {
        font-size: 2.5rem; color: #cbd5e1; display: block; margin-bottom: .75rem;
    }

    @media print {
        .no-print { display: none !important; }
        body { background: #fff; }
        .total-box { box-shadow: none; border: 1px solid #cbd5e1; transform: none !important; }
        .total-box::after { display: none; }
        .report-table thead th { background: #e2e8f0 !important; position: static; }
        .report-table tbody tr:hover { background: transparent !important; }
        .report-table-wrap { box-shadow: none; border: 1px solid #cbd5e1; }
    }

    @media (max-width: 991.98px) {
        .totals-grid { grid-template-columns: repeat(auto-fit, minmax(155px, 1fr)); }
        .total-box .val { font-size: 1.35rem; }
    }
    @media (max-width: 575.98px) {
        .report-table thead th, .report-table tbody td { font-size: .75rem; padding: .55rem .4rem; }
    }
</style>
@endpush

@section('content')

{{-- ── Filter Bar ─────────────────────────────────────────── --}}
<div class="report-filters no-print">
    <form method="GET" action="{{ route('admin.religious.reports.trips') }}">
        <div class="row g-2">
            <div class="col-md-2 col-6">
                <label class="form-label">من تاريخ</label>
                <input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] ?? '' }}">
            </div>
            <div class="col-md-2 col-6">
                <label class="form-label">إلى تاريخ</label>
                <input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] ?? '' }}">
            </div>
            <div class="col-md-2 col-6">
                <label class="form-label">النوع</label>
                <select name="type" class="form-select">
                    <option value="">— الكل —</option>
                    <option value="hajj"  @selected(($filters['type'] ?? '') === 'hajj')>حج</option>
                    <option value="umrah" @selected(($filters['type'] ?? '') === 'umrah')>عمرة</option>
                </select>
            </div>
            <div class="col-md-3 col-6">
                <label class="form-label">البرنامج</label>
                <select name="program_id" class="form-select">
                    <option value="">— الكل —</option>
                    @foreach($programs as $p)
                        <option value="{{ $p->id }}" @selected(($filters['program_id'] ?? '') === $p->id)>{{ $p->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 col-12">
                <label class="form-label">الموظف المسؤول</label>
                <select name="employee_id" class="form-select">
                    <option value="">— الكل —</option>
                    @foreach($employees as $u)
                        <option value="{{ $u->id }}" @selected(($filters['employee_id'] ?? '') === $u->id)>{{ $u->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 col-6">
                <label class="form-label">الحالة</label>
                <select name="status" class="form-select">
                    <option value="">— الكل —</option>
                    <option value="pending"     @selected(($filters['status'] ?? '') === 'pending')>قيد الانتظار</option>
                    <option value="confirmed"   @selected(($filters['status'] ?? '') === 'confirmed')>مؤكد</option>
                    <option value="in_progress" @selected(($filters['status'] ?? '') === 'in_progress')>جارية</option>
                    <option value="completed"   @selected(($filters['status'] ?? '') === 'completed')>مكتمل</option>
                    <option value="cancelled"   @selected(($filters['status'] ?? '') === 'cancelled')>ملغي</option>
                </select>
            </div>
            <div class="col-md-10 col-6">
                <label class="form-label">&nbsp;</label>
                <div class="filter-actions">
                    <a href="{{ route('admin.religious.reports.trips') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-counterclockwise"></i> إعادة تعيين
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-funnel-fill"></i> تطبيق
                    </button>
                    <a href="{{ route('admin.religious.reports.trips.export', request()->query()) }}" class="btn btn-success">
                        <i class="bi bi-file-earmark-excel-fill"></i> Excel
                    </a>
                    <button type="button" onclick="window.print()" class="btn btn-dark">
                        <i class="bi bi-printer-fill"></i> طباعة
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

{{-- ── Totals Grid ────────────────────────────────────────── --}}
<div class="totals-grid">
    <div class="total-box t-bookings">
        <div class="head-row">
            <div>
                <div class="lbl">عدد الحجوزات</div>
                <div class="val">{{ number_format($totals['bookings_count']) }}</div>
            </div>
            <div class="ico"><i class="bi bi-journal-bookmark-fill"></i></div>
        </div>
    </div>
    <div class="total-box t-pilgrims">
        <div class="head-row">
            <div>
                <div class="lbl">إجمالي الأفراد</div>
                <div class="val">{{ number_format($totals['pilgrims_total']) }}</div>
                <div class="sub">معتمر / حاج</div>
            </div>
            <div class="ico"><i class="bi bi-people-fill"></i></div>
        </div>
    </div>
    <div class="total-box t-rooms">
        <div class="head-row">
            <div>
                <div class="lbl">إجمالي الغرف</div>
                <div class="val">{{ number_format($totals['rooms_total']) }}</div>
            </div>
            <div class="ico"><i class="bi bi-house-door-fill"></i></div>
        </div>
    </div>
    <div class="total-box t-flights">
        <div class="head-row">
            <div>
                <div class="lbl">تذاكر الطيران</div>
                <div class="val">{{ number_format($totals['flight_tickets']) }}</div>
            </div>
            <div class="ico"><i class="bi bi-airplane-fill"></i></div>
        </div>
    </div>
    <div class="total-box t-visas">
        <div class="head-row">
            <div>
                <div class="lbl">التأشيرات الصادرة</div>
                <div class="val">{{ number_format($totals['visas_issued']) }}</div>
            </div>
            <div class="ico"><i class="bi bi-passport-fill"></i></div>
        </div>
    </div>
    <div class="total-box t-barcodes">
        <div class="head-row">
            <div>
                <div class="lbl">إجمالي الباركودات</div>
                <div class="val">{{ number_format($totals['barcodes_total']) }}</div>
            </div>
            <div class="ico"><i class="bi bi-upc-scan"></i></div>
        </div>
    </div>
    <div class="total-box t-sales">
        <div class="head-row">
            <div>
                <div class="lbl">إجمالي المبيعات</div>
                <div class="val">{{ number_format($totals['sales_total'], 0) }}</div>
                <div class="sub">جنيه مصري</div>
            </div>
            <div class="ico"><i class="bi bi-cash-stack"></i></div>
        </div>
    </div>
    <div class="total-box t-cost">
        <div class="head-row">
            <div>
                <div class="lbl">إجمالي التكلفة</div>
                <div class="val">{{ number_format($totals['cost_total'], 0) }}</div>
                <div class="sub">جنيه مصري</div>
            </div>
            <div class="ico"><i class="bi bi-wallet2"></i></div>
        </div>
    </div>
    <div class="total-box t-profit">
        <div class="head-row">
            <div>
                <div class="lbl">صافي الربح</div>
                <div class="val">{{ number_format($totals['profit_total'], 0) }}</div>
                <div class="sub">
                    @if($totals['sales_total'] > 0)
                        هامش: {{ number_format(($totals['profit_total'] / $totals['sales_total']) * 100, 1) }}%
                    @else
                        —
                    @endif
                </div>
            </div>
            <div class="ico"><i class="bi bi-graph-up-arrow"></i></div>
        </div>
    </div>
    <div class="total-box t-commission">
        <div class="head-row">
            <div>
                <div class="lbl">تقفيل عمولات الموظفين</div>
                <div class="val">{{ number_format($totals['commissions_total'], 0) }}</div>
                <div class="sub">جنيه مصري</div>
            </div>
            <div class="ico"><i class="bi bi-person-check-fill"></i></div>
        </div>
    </div>
</div>

{{-- ── Trips Table ────────────────────────────────────────── --}}
<div class="report-table-wrap">
    <div class="head">
        <h6>
            <i class="bi bi-list-ul"></i> تفاصيل الرحلات
            <span class="badge-count">{{ $totals['bookings_count'] }} رحلة</span>
        </h6>
        <div class="small text-muted">
            صفحة <strong>{{ $bookings->currentPage() }}</strong> من <strong>{{ $bookings->lastPage() }}</strong>
        </div>
    </div>
    <div class="table-responsive">
        <table class="report-table">
            <thead>
                <tr>
                    <th width="40">#</th>
                    <th>رقم الحجز</th>
                    <th>النوع</th>
                    <th>العميل</th>
                    <th>البرنامج</th>
                    <th>تاريخ السفر</th>
                    <th>المدة</th>
                    <th>الأفراد</th>
                    <th>المبيعات</th>
                    <th>التكلفة</th>
                    <th>الربح</th>
                    <th>البائع</th>
                    <th>الحالة</th>
                </tr>
            </thead>
            <tbody>
                @forelse($bookings as $i => $b)
                <tr>
                    <td>{{ ($bookings->firstItem() ?? 0) + $i }}</td>
                    <td>
                        <a href="{{ route('admin.religious.bookings.show', $b) }}" class="booking-link">
                            {{ $b->booking_number }}
                        </a>
                    </td>
                    <td>
                        <span class="type-tag {{ $b->type }}">
                            @if($b->type === 'hajj')
                                <span style="font-family:'Apple Color Emoji','Segoe UI Emoji',sans-serif;">🕋</span>
                            @else
                                <i class="bi bi-moon-stars-fill"></i>
                            @endif
                            {{ $b->type_label }}
                        </span>
                    </td>
                    <td>{{ $b->customer?->full_name ?: '—' }}</td>
                    <td class="small text-muted">{{ $b->program?->name ?: '—' }}</td>
                    <td class="small" style="white-space:nowrap;">
                        <i class="bi bi-calendar-event text-muted"></i>
                        {{ $b->trip_date?->format('Y-m-d') }}
                    </td>
                    <td class="small text-center">{{ $b->duration_days }} يوم</td>
                    <td class="text-center fw-bold">{{ $b->adults_count + $b->children_count }}</td>
                    <td class="money-cell sales">{{ number_format($b->selling_price, 0) }}</td>
                    <td class="money-cell cost">{{ number_format($b->total_cost, 0) }}</td>
                    <td class="money-cell {{ $b->net_profit >= 0 ? 'profit-pos' : 'profit-neg' }}">
                        {{ number_format($b->net_profit, 0) }}
                    </td>
                    <td class="small">{{ $b->employee?->name ?: '—' }}</td>
                    <td>
                        <span class="status-chip {{ $b->status }}">{{ $b->status_label }}</span>
                    </td>
                </tr>
                @empty
                <tr class="empty-row">
                    <td colspan="13">
                        <i class="bi bi-inbox empty-ico"></i>
                        لا توجد بيانات للفلاتر المختارة
                    </td>
                </tr>
                @endforelse
            </tbody>
            @if($totals['bookings_count'] > 0)
            <tfoot>
                <tr>
                    <td colspan="7" class="text-end">إجمالي كل النتائج المفلترة</td>
                    <td class="gold">{{ number_format($totals['pilgrims_total']) }}</td>
                    <td class="gold">{{ number_format($totals['sales_total'], 0) }}</td>
                    <td>{{ number_format($totals['cost_total'], 0) }}</td>
                    <td class="gold">{{ number_format($totals['profit_total'], 0) }}</td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>

<div class="no-print">
    {{ $bookings->links() }}
</div>

@endsection
