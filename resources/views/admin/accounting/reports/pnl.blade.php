@extends('layouts.master')

@section('title', 'قائمة الدخل')
@section('page_title', 'قائمة الدخل')
@section('page_subtitle', 'الإيرادات والمصروفات للفترة المختارة + مجمل الربح وصافي الربح')

@push('styles')
<style>
    .pnl-filters { background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:1rem 1.25rem; margin-bottom:1rem; }

    .pnl-table { background:#fff; }
    .pnl-table th, .pnl-table td { padding:.65rem 1rem; vertical-align:middle; }
    .pnl-table .section-row td {
        background:#eef2ff; color:#3730a3; font-weight:800; font-size:1rem;
        border-top:2px solid #c7d2fe;
    }
    .pnl-table .acc-row td { padding-inline-start: 2.5rem; }
    .pnl-table .acc-code { font-family:'JetBrains Mono', monospace; color:#4f46e5; font-weight:600; font-size:.85rem; }
    .pnl-table .amount { font-family:'JetBrains Mono', monospace; font-weight:700; text-align:end; }
    .pnl-table .subtotal-row td {
        background:#f9fafb; font-weight:800; border-top:1px dashed #d1d5db;
    }
    .pnl-table .key-row td {
        background:#1f2937; color:#fff; font-weight:800; font-size:1.05rem;
        padding-block:.9rem;
    }
    .pnl-table .net-row td {
        background:linear-gradient(90deg, #15803d, #166534); color:#fff;
        font-weight:800; font-size:1.15rem; padding-block:1.1rem;
    }
    .pnl-table .net-row.loss td {
        background:linear-gradient(90deg, #b91c1c, #991b1b);
    }

    .summary-cards { margin-bottom:1rem; }
    .sc { background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:1.1rem 1.25rem; }
    .sc .lbl { color:#6b7280; font-size:.82rem; font-weight:600; }
    .sc .val { font-weight:800; font-size:1.5rem; color:#0f172a; line-height:1.1; margin-top:.3rem; }
    .sc.rev   { background:linear-gradient(135deg, #dbeafe, #fff); border-color:#bfdbfe; }
    .sc.rev   .val { color:#1e40af; }
    .sc.gross { background:linear-gradient(135deg, #dcfce7, #fff); border-color:#86efac; }
    .sc.gross .val { color:#15803d; }
    .sc.net   { background:linear-gradient(135deg, #fef3c7, #fff); border-color:#fde68a; }
    .sc.net   .val { color:#92400e; }
    .sc.net.loss { background:linear-gradient(135deg, #fee2e2, #fff); border-color:#fca5a5; }
    .sc.net.loss .val { color:#b91c1c; }

    .margin-badge { font-size:.78rem; color:#6b7280; font-weight:600; margin-top:.2rem; display:block; }
</style>
@endpush

@section('content')

{{-- Filters --}}
<form method="GET" class="pnl-filters">
    <div class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label small mb-1">من تاريخ</label>
            <input type="date" name="from" class="form-control form-control-sm"
                   value="{{ request('from', $from->format('Y-m-d')) }}">
        </div>
        <div class="col-md-3">
            <label class="form-label small mb-1">إلى تاريخ</label>
            <input type="date" name="to" class="form-control form-control-sm"
                   value="{{ request('to', $to->format('Y-m-d')) }}">
        </div>
        <div class="col-md-6 d-flex justify-content-end gap-2">
            <button type="submit" class="btn btn-primary btn-sm">
                <i class="bi bi-funnel"></i> تطبيق
            </button>
            <a href="{{ route('admin.accounting.reports.pnl.print', request()->all()) }}" target="_blank" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-printer"></i> طباعة
            </a>
            <a href="{{ route('admin.accounting.reports.pnl.csv', request()->all()) }}" class="btn btn-outline-success btn-sm">
                <i class="bi bi-file-earmark-spreadsheet"></i> CSV
            </a>
        </div>
    </div>
</form>

{{-- Summary cards --}}
<div class="row g-3 summary-cards">
    <div class="col-md-4">
        <div class="sc rev">
            <div class="lbl"><i class="bi bi-graph-up-arrow"></i> إجمالي الإيرادات</div>
            <div class="val">{{ number_format($revenue['total'], 2) }} <small style="font-size:.85rem; color:#6b7280;">ج.م</small></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="sc gross">
            <div class="lbl"><i class="bi bi-cash-coin"></i> مجمل الربح</div>
            <div class="val">{{ number_format($gross_profit, 2) }}</div>
            <span class="margin-badge">هامش: {{ $gross_margin }}%</span>
        </div>
    </div>
    <div class="col-md-4">
        <div class="sc net {{ $net_profit < 0 ? 'loss' : '' }}">
            <div class="lbl"><i class="bi bi-piggy-bank"></i> صافي الربح</div>
            <div class="val">{{ number_format($net_profit, 2) }}</div>
            <span class="margin-badge">هامش: {{ $net_margin }}%</span>
        </div>
    </div>
</div>

{{-- Report --}}
<div class="card">
    <div class="card-header">
        <h6 class="mb-0">
            <i class="bi bi-bar-chart-line"></i>
            قائمة الدخل من <strong>{{ $from->format('Y-m-d') }}</strong> إلى <strong>{{ $to->format('Y-m-d') }}</strong>
        </h6>
    </div>
    <div class="card-body p-0">
        <table class="table pnl-table mb-0">
            @php
                $renderSection = function ($title, $icon, $rows, $total, $totalLabel = 'الإجمالي') {
                    echo '<tr class="section-row"><td colspan="3"><i class="bi bi-' . $icon . '"></i> ' . $title . '</td></tr>';
                    if ($rows->isEmpty()) {
                        echo '<tr class="acc-row"><td colspan="3" class="text-muted small">لا توجد حركات في هذه الفئة خلال الفترة</td></tr>';
                    } else {
                        foreach ($rows as $r) {
                            echo '<tr class="acc-row">';
                            echo '<td><span class="acc-code">' . e($r->code) . '</span></td>';
                            echo '<td>' . e($r->name) . '</td>';
                            echo '<td class="amount">' . number_format($r->amount, 2) . '</td>';
                            echo '</tr>';
                        }
                    }
                    echo '<tr class="subtotal-row"><td colspan="2" class="text-end">' . $totalLabel . '</td>';
                    echo '<td class="amount">' . number_format($total, 2) . '</td></tr>';
                };
            @endphp

            {{-- Revenue --}}
            @php $renderSection('الإيرادات', 'arrow-down-circle-fill text-success', $revenue['rows'], $revenue['total'], 'إجمالي الإيرادات'); @endphp

            {{-- Cost of Services --}}
            @php $renderSection('تكلفة الخدمات', 'box-arrow-up', $cost_of_services['rows'], $cost_of_services['total'], 'إجمالي تكلفة الخدمات'); @endphp

            {{-- Gross Profit --}}
            <tr class="key-row">
                <td colspan="2" class="text-end">مجمل الربح (الإيرادات - تكلفة الخدمات)</td>
                <td class="amount">{{ number_format($gross_profit, 2) }}</td>
            </tr>

            {{-- Operating Expenses --}}
            @php $renderSection('مصروفات تشغيلية', 'building', $operating_expense['rows'], $operating_expense['total'], 'إجمالي المصروفات التشغيلية'); @endphp

            {{-- Other Expenses --}}
            @php $renderSection('مصروفات أخرى', 'three-dots', $other_expense['rows'], $other_expense['total'], 'إجمالي المصروفات الأخرى'); @endphp

            {{-- Net Profit --}}
            <tr class="net-row {{ $net_profit < 0 ? 'loss' : '' }}">
                <td colspan="2" class="text-end">
                    <i class="bi bi-trophy"></i>
                    {{ $net_profit >= 0 ? 'صافي الربح' : 'صافي الخسارة' }}
                </td>
                <td class="amount">{{ number_format($net_profit, 2) }}</td>
            </tr>
        </table>
    </div>
</div>

@endsection
