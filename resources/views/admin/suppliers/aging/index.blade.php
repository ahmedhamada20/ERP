@extends('layouts.master')

@section('title', 'أعمار ديون الموردين')
@section('page_title', 'أعمار ديون الموردين')
@section('page_subtitle', 'المستحقات لكل مورد مقسومة حسب أعمار الديون')

@push('styles')
<style>
    .age-filters { background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:1rem 1.25rem; margin-bottom:1rem; }

    .summary { display:grid; grid-template-columns: repeat(7, 1fr); gap:.75rem; margin-bottom:1rem; }
    .sb { background:#fff; border:1px solid #e5e7eb; border-radius:10px; padding:.85rem; text-align:center; }
    .sb .lbl { color:#6b7280; font-size:.74rem; font-weight:600; }
    .sb .val { font-weight:800; font-size:1.05rem; color:#0f172a; margin-top:.2rem; }
    .sb.total   { background:linear-gradient(135deg, #fee2e2, #fecaca); border-color:#fca5a5; }
    .sb.total   .val { color:#991b1b; font-size:1.3rem; }
    .sb.current { background:#dcfce7; }
    .sb.current .val { color:#15803d; }
    .sb.b1 { background:#fef3c7; } .sb.b1 .val { color:#92400e; }
    .sb.b2 { background:#fed7aa; } .sb.b2 .val { color:#9a3412; }
    .sb.b3 { background:#fecaca; } .sb.b3 .val { color:#991b1b; }
    .sb.b4 { background:#fca5a5; } .sb.b4 .val { color:#7f1d1d; }
    .sb.b5 { background:#7f1d1d; } .sb.b5 .val { color:#fff; }

    .ag-table { background:#fff; }
    .ag-table th { background:#f3f4f6; color:#374151; font-weight:700; text-align:center; }
    .ag-table th, .ag-table td { padding:.55rem .65rem; vertical-align:middle; }
    .ag-table .name { font-weight:700; }
    .ag-table .name code { color:#4f46e5; font-size:.78rem; }
    .ag-table .amount { font-family:'JetBrains Mono', monospace; font-weight:700; text-align:end; }
    .ag-table .amount.total { color:#b91c1c; font-size:1rem; }
    .ag-table .amount.current { color:#15803d; }
    .ag-table .amount.zero { color:#cbd5e1; font-weight:400; }
    .ag-table tfoot td { background:#1f2937; color:#fff; font-weight:800; padding:.7rem .65rem; }

    .type-pill { font-size:.72rem; padding:.15rem .55rem; border-radius:6px; font-weight:700; }
    .p-hotel { background:#dbeafe; color:#1e40af; }
    .p-airline { background:#e0e7ff; color:#3730a3; }
    .p-transport { background:#fef3c7; color:#92400e; }
    .p-visa { background:#dcfce7; color:#15803d; }
    .p-other { background:#f1f5f9; color:#475569; }
</style>
@endpush

@section('content')

{{-- Filters --}}
<form method="GET" class="age-filters">
    <div class="row g-2 align-items-end">
        <div class="col-md-4">
            <label class="form-label small mb-1">كما في تاريخ</label>
            <input type="date" name="as_of" class="form-control form-control-sm"
                   value="{{ request('as_of', $as_of->format('Y-m-d')) }}">
        </div>
        <div class="col-md-8 d-flex justify-content-end gap-2">
            <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-funnel"></i> تطبيق</button>
            <a href="{{ route('admin.suppliers.aging.print', request()->all()) }}" target="_blank" class="btn btn-outline-secondary btn-sm"><i class="bi bi-printer"></i> طباعة</a>
            <a href="{{ route('admin.suppliers.aging.csv', request()->all()) }}" class="btn btn-outline-success btn-sm"><i class="bi bi-file-earmark-spreadsheet"></i> CSV</a>
        </div>
    </div>
</form>

{{-- Summary buckets --}}
<div class="summary">
    <div class="sb total">
        <div class="lbl">إجمالي مستحق</div>
        <div class="val">{{ number_format($grand_total, 2) }}</div>
    </div>
    <div class="sb current">
        <div class="lbl">حالي</div>
        <div class="val">{{ number_format($totals['current'], 2) }}</div>
    </div>
    <div class="sb b1">
        <div class="lbl">1-30 يوم</div>
        <div class="val">{{ number_format($totals['d_1_30'], 2) }}</div>
    </div>
    <div class="sb b2">
        <div class="lbl">31-60 يوم</div>
        <div class="val">{{ number_format($totals['d_31_60'], 2) }}</div>
    </div>
    <div class="sb b3">
        <div class="lbl">61-90 يوم</div>
        <div class="val">{{ number_format($totals['d_61_90'], 2) }}</div>
    </div>
    <div class="sb b4">
        <div class="lbl">91-120 يوم</div>
        <div class="val">{{ number_format($totals['d_91_120'], 2) }}</div>
    </div>
    <div class="sb b5">
        <div class="lbl">+120 يوم</div>
        <div class="val">{{ number_format($totals['d_120_plus'], 2) }}</div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h6 class="mb-0">
            <i class="bi bi-bar-chart-line"></i> الموردون المستحقون ({{ $rows->count() }})
            <small class="text-muted">— كما في {{ $as_of->format('Y-m-d') }}</small>
        </h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table ag-table mb-0">
                <thead>
                    <tr>
                        <th>المورد</th>
                        <th width="100">النوع</th>
                        <th width="110">إجمالي مستحق</th>
                        <th width="100">حالي</th>
                        <th width="100">1-30</th>
                        <th width="100">31-60</th>
                        <th width="100">61-90</th>
                        <th width="100">91-120</th>
                        <th width="100">+120</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                    <tr>
                        <td class="name">
                            <a href="{{ route('admin.suppliers.statement', ['supplier_id' => $row['supplier']->id, 'from' => now()->subYear()->format('Y-m-d'), 'to' => $as_of->format('Y-m-d')]) }}">
                                <code>{{ $row['supplier']->code }}</code> {{ $row['supplier']->name }}
                            </a>
                        </td>
                        <td><span class="type-pill p-{{ $row['supplier']->type }}">{{ $row['supplier']->type_label }}</span></td>
                        <td class="amount total">{{ number_format($row['outstanding'], 2) }}</td>
                        <td class="amount {{ $row['current']    > 0 ? 'current' : 'zero' }}">{{ $row['current']    > 0 ? number_format($row['current'], 2)    : '—' }}</td>
                        <td class="amount {{ $row['d_1_30']     > 0 ? '' : 'zero' }}">{{ $row['d_1_30']     > 0 ? number_format($row['d_1_30'], 2)     : '—' }}</td>
                        <td class="amount {{ $row['d_31_60']    > 0 ? '' : 'zero' }}">{{ $row['d_31_60']    > 0 ? number_format($row['d_31_60'], 2)    : '—' }}</td>
                        <td class="amount {{ $row['d_61_90']    > 0 ? '' : 'zero' }}">{{ $row['d_61_90']    > 0 ? number_format($row['d_61_90'], 2)    : '—' }}</td>
                        <td class="amount {{ $row['d_91_120']   > 0 ? '' : 'zero' }}">{{ $row['d_91_120']   > 0 ? number_format($row['d_91_120'], 2)   : '—' }}</td>
                        <td class="amount {{ $row['d_120_plus'] > 0 ? '' : 'zero' }}">{{ $row['d_120_plus'] > 0 ? number_format($row['d_120_plus'], 2) : '—' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="9" class="text-center text-muted py-5">
                        <i class="bi bi-check2-circle text-success" style="font-size:3rem; opacity:.3;"></i>
                        <p class="mt-3 mb-0"><strong>ممتاز!</strong> لا يوجد موردون لهم أرصدة مستحقة في هذا التاريخ.</p>
                    </td></tr>
                    @endforelse
                </tbody>
                @if($rows->isNotEmpty())
                <tfoot>
                    <tr>
                        <td colspan="2" class="text-end">الإجمالي العام</td>
                        <td class="amount" style="color:#fca5a5;">{{ number_format($grand_total, 2) }}</td>
                        <td class="amount">{{ number_format($totals['current'], 2) }}</td>
                        <td class="amount">{{ number_format($totals['d_1_30'], 2) }}</td>
                        <td class="amount">{{ number_format($totals['d_31_60'], 2) }}</td>
                        <td class="amount">{{ number_format($totals['d_61_90'], 2) }}</td>
                        <td class="amount">{{ number_format($totals['d_91_120'], 2) }}</td>
                        <td class="amount">{{ number_format($totals['d_120_plus'], 2) }}</td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>

@endsection
