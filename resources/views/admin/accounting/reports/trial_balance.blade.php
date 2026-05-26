@extends('layouts.master')

@section('title', 'ميزان المراجعة')
@section('page_title', 'ميزان المراجعة')
@section('page_subtitle', 'كل أرصدة الحسابات كما في تاريخ محدد — مع التحقق من تساوي المدين والدائن')

@push('styles')
<style>
    .tb-filters { background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:1rem 1.25rem; margin-bottom:1rem; }

    .tb-table { background:#fff; }
    .tb-table thead { background:#f3f4f6; color:#374151; font-weight:700; }
    .tb-table th, .tb-table td { padding:.6rem .75rem; vertical-align:middle; }
    .tb-table tr.section-header td {
        background:#eef2ff; color:#3730a3; font-weight:800; font-size:.95rem;
        padding:.55rem .75rem; border-top:2px solid #c7d2fe;
    }
    .tb-table .acc-code { font-family:'JetBrains Mono', monospace; color:#4f46e5; font-weight:600; }
    .tb-table .amount { font-family:'JetBrains Mono', monospace; font-weight:700; text-align:end; }
    .tb-table .amount.dr { color:#15803d; }
    .tb-table .amount.cr { color:#b91c1c; }
    .tb-table tfoot { background:#1f2937; color:#fff; font-weight:800; }
    .tb-table tfoot td { padding:.85rem .75rem; }

    .balance-pill {
        display:inline-flex; align-items:center; gap:.5rem;
        padding:.45rem 1rem; border-radius:999px; font-weight:800;
    }
    .balance-pill.ok    { background:#dcfce7; color:#15803d; }
    .balance-pill.error { background:#fee2e2; color:#b91c1c; }

    @media print {
        .no-print { display:none !important; }
        .card { border:none !important; box-shadow:none !important; }
    }
</style>
@endpush

@section('content')

{{-- Filters --}}
<form method="GET" class="tb-filters no-print">
    <div class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label small mb-1">كما في تاريخ</label>
            <input type="date" name="as_of" class="form-control form-control-sm"
                   value="{{ request('as_of', $as_of->format('Y-m-d')) }}">
        </div>
        <div class="col-md-3">
            <div class="form-check form-switch mt-3">
                <input type="hidden" name="include_zero" value="0">
                <input type="checkbox" name="include_zero" id="incZero" value="1" class="form-check-input"
                       {{ $includeZero ? 'checked' : '' }}>
                <label class="form-check-label" for="incZero">عرض الحسابات بدون حركة</label>
            </div>
        </div>
        <div class="col-md-6 d-flex justify-content-end gap-2">
            <button type="submit" class="btn btn-primary btn-sm">
                <i class="bi bi-funnel"></i> تطبيق
            </button>
            <a href="{{ route('admin.accounting.reports.trial_balance.print', request()->all()) }}" target="_blank" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-printer"></i> طباعة
            </a>
            <a href="{{ route('admin.accounting.reports.trial_balance.csv', request()->all()) }}" class="btn btn-outline-success btn-sm">
                <i class="bi bi-file-earmark-spreadsheet"></i> CSV
            </a>
        </div>
    </div>
</form>

{{-- Report --}}
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
        <div>
            <h6 class="mb-0"><i class="bi bi-table"></i> ميزان المراجعة</h6>
            <small class="text-muted">كما في: <strong>{{ $as_of->format('Y-m-d') }}</strong></small>
        </div>
        <div>
            @if($totals['balanced'])
                <span class="balance-pill ok"><i class="bi bi-check2-circle"></i> الميزان متوازن</span>
            @else
                <span class="balance-pill error"><i class="bi bi-exclamation-triangle"></i> فرق: {{ number_format(abs($totals['diff']), 2) }}</span>
            @endif
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table tb-table mb-0">
                <thead>
                    <tr>
                        <th width="80">الكود</th>
                        <th>الحساب</th>
                        <th width="180" class="text-end">مدين</th>
                        <th width="180" class="text-end">دائن</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($grouped as $type => $items)
                        @php
                            $typeName = ['asset'=>'الأصول','liability'=>'الخصوم','equity'=>'حقوق الملكية','revenue'=>'الإيرادات','expense'=>'المصروفات'][$type] ?? $type;
                            $sectionDr = $items->sum('debit_column');
                            $sectionCr = $items->sum('credit_column');
                        @endphp
                        <tr class="section-header">
                            <td colspan="2">{{ $typeName }} <small class="opacity-75">({{ $items->count() }} حساب)</small></td>
                            <td class="text-end">{{ $sectionDr > 0 ? number_format($sectionDr, 2) : '' }}</td>
                            <td class="text-end">{{ $sectionCr > 0 ? number_format($sectionCr, 2) : '' }}</td>
                        </tr>
                        @foreach($items as $row)
                        <tr>
                            <td class="acc-code">{{ $row->code }}</td>
                            <td>
                                {{ $row->name }}
                                @if(! $row->is_active)
                                    <span class="badge bg-secondary" style="font-size:.6rem;">متوقف</span>
                                @endif
                            </td>
                            <td class="amount dr">{{ $row->debit_column  > 0 ? number_format($row->debit_column,  2) : '—' }}</td>
                            <td class="amount cr">{{ $row->credit_column > 0 ? number_format($row->credit_column, 2) : '—' }}</td>
                        </tr>
                        @endforeach
                    @empty
                        <tr><td colspan="4" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox" style="font-size:2.5rem; opacity:.3;"></i>
                            <p class="mt-3 mb-0">لا توجد حركات في هذه الفترة. جرّب تفعيل "عرض الحسابات بدون حركة".</p>
                        </td></tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="2" class="text-center">الإجمالي العام</td>
                        <td class="amount" style="color:#86efac;">{{ number_format($totals['debit'], 2) }}</td>
                        <td class="amount" style="color:#fca5a5;">{{ number_format($totals['credit'], 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

@endsection
