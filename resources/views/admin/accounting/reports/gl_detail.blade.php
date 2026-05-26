@extends('layouts.master')

@section('title', 'دفتر الأستاذ — ' . $account->code)
@section('page_title', 'دفتر أستاذ: ' . $account->code . ' — ' . $account->name)
@section('page_subtitle', 'كل الحركات المرحّلة على هذا الحساب مع الرصيد التراكمي بعد كل حركة')

@push('styles')
<style>
    .gl-filters { background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:1rem 1.25rem; margin-bottom:1rem; }

    .sc { background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:1.1rem 1.25rem; height:100%; }
    .sc .lbl { color:#6b7280; font-size:.82rem; font-weight:600; }
    .sc .val { font-weight:800; font-size:1.4rem; color:#0f172a; line-height:1.1; margin-top:.3rem; }
    .sc.opening { background:linear-gradient(135deg, #f9fafb, #fff); }
    .sc.in   { background:linear-gradient(135deg, #dcfce7, #fff); border-color:#86efac; }
    .sc.in   .val { color:#15803d; }
    .sc.out  { background:linear-gradient(135deg, #fee2e2, #fff); border-color:#fca5a5; }
    .sc.out  .val { color:#b91c1c; }
    .sc.closing { background:linear-gradient(135deg, #eef2ff, #fff); border-color:#c7d2fe; }
    .sc.closing .val { color:#4338ca; font-size:1.7rem; }

    .gl-table { background:#fff; }
    .gl-table th { background:#f3f4f6; color:#374151; font-weight:700; }
    .gl-table th, .gl-table td { padding:.6rem .8rem; vertical-align:middle; }
    .gl-table .je-num { font-family:'JetBrains Mono', monospace; color:#4f46e5; font-weight:600; }
    .gl-table .amount { font-family:'JetBrains Mono', monospace; font-weight:700; text-align:end; }
    .gl-table .amount.dr { color:#15803d; }
    .gl-table .amount.cr { color:#b91c1c; }
    .gl-table .running   { color:#4338ca; font-weight:800; }
    .gl-table .running.negative { color:#b91c1c; }

    .gl-table tr.opening-row td {
        background:#f1f5f9; font-style:italic; color:#475569; font-weight:700;
    }
    .gl-table tr.closing-row td {
        background:#1f2937; color:#fff; font-weight:800;
    }

    .acc-badge {
        font-size:.78rem; padding:.25rem .6rem; border-radius:6px; font-weight:700;
    }
    .ab-asset    { background:#dbeafe; color:#1e40af; }
    .ab-liability{ background:#fee2e2; color:#b91c1c; }
    .ab-equity   { background:#fef3c7; color:#92400e; }
    .ab-revenue  { background:#dcfce7; color:#15803d; }
    .ab-expense  { background:#fce7f3; color:#9d174d; }
</style>
@endpush

@section('content')

{{-- Filters --}}
<form method="GET" class="gl-filters">
    <div class="row g-2 align-items-end">
        <div class="col-md-4">
            <label class="form-label small mb-1">الحساب</label>
            <select name="account_id" class="form-select form-select-sm" onchange="this.form.submit()">
                @foreach($accounts->groupBy('type') as $type => $items)
                    @php $label = ['asset'=>'الأصول','liability'=>'الخصوم','equity'=>'حقوق الملكية','revenue'=>'الإيرادات','expense'=>'المصروفات'][$type] ?? $type; @endphp
                    <optgroup label="{{ $label }}">
                        @foreach($items as $a)
                            <option value="{{ $a->id }}" {{ $a->id === $account->id ? 'selected' : '' }}>
                                {{ $a->code }} — {{ $a->name }}
                            </option>
                        @endforeach
                    </optgroup>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small mb-1">من تاريخ</label>
            <input type="date" name="from" class="form-control form-control-sm"
                   value="{{ request('from', $from->format('Y-m-d')) }}">
        </div>
        <div class="col-md-2">
            <label class="form-label small mb-1">إلى تاريخ</label>
            <input type="date" name="to" class="form-control form-control-sm"
                   value="{{ request('to', $to->format('Y-m-d')) }}">
        </div>
        <div class="col-md-4 d-flex justify-content-end gap-2">
            <button type="submit" class="btn btn-primary btn-sm">
                <i class="bi bi-funnel"></i> تطبيق
            </button>
            <a href="{{ route('admin.accounting.reports.general_ledger.print', request()->all()) }}" target="_blank" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-printer"></i> طباعة
            </a>
            <a href="{{ route('admin.accounting.reports.general_ledger.csv', request()->all()) }}" class="btn btn-outline-success btn-sm">
                <i class="bi bi-file-earmark-spreadsheet"></i> CSV
            </a>
        </div>
    </div>
</form>

{{-- Account info + summary cards --}}
<div class="row g-3 mb-3">
    <div class="col-md-12 mb-1">
        <div class="d-flex align-items-center gap-2">
            <span class="acc-badge ab-{{ $account->type }}">{{ $account->type_label }}</span>
            @if($account->sub_type_label)
                <span class="text-muted small">{{ $account->sub_type_label }}</span>
            @endif
            <span class="text-muted small">• الفترة: <strong>{{ $from->format('Y-m-d') }} ← {{ $to->format('Y-m-d') }}</strong></span>
            <span class="text-muted small">• اتجاه الحساب: <strong>{{ $account->normal_side === 'debit' ? 'مدين' : 'دائن' }}</strong></span>
        </div>
    </div>
    <div class="col-md-3">
        <div class="sc opening">
            <div class="lbl">الرصيد الافتتاحي</div>
            <div class="val">{{ number_format($opening, 2) }}</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="sc in">
            <div class="lbl"><i class="bi bi-arrow-down-circle"></i> إجمالي المدين</div>
            <div class="val">{{ number_format($period_debit, 2) }}</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="sc out">
            <div class="lbl"><i class="bi bi-arrow-up-circle"></i> إجمالي الدائن</div>
            <div class="val">{{ number_format($period_credit, 2) }}</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="sc closing">
            <div class="lbl"><i class="bi bi-flag-fill"></i> الرصيد الختامي</div>
            <div class="val">{{ number_format($closing, 2) }}</div>
        </div>
    </div>
</div>

{{-- Movements table --}}
<div class="card">
    <div class="card-header">
        <h6 class="mb-0"><i class="bi bi-clock-history"></i> الحركات ({{ $lines->count() }})</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table gl-table mb-0">
                <thead>
                    <tr>
                        <th width="100">التاريخ</th>
                        <th width="140">القيد</th>
                        <th>البيان</th>
                        <th width="130">المرجع</th>
                        <th width="120" class="text-end">مدين</th>
                        <th width="120" class="text-end">دائن</th>
                        <th width="140" class="text-end">الرصيد</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="opening-row">
                        <td colspan="6">— الرصيد الافتتاحي قبل {{ $from->format('Y-m-d') }} —</td>
                        <td class="amount running">{{ number_format($opening, 2) }}</td>
                    </tr>

                    @forelse($lines as $line)
                        <tr>
                            <td>{{ $line->entry->date->format('Y-m-d') }}</td>
                            <td>
                                <a href="{{ route('admin.accounting.journal.show', $line->entry) }}" class="je-num">
                                    {{ $line->entry->number }}
                                </a>
                            </td>
                            <td>{{ $line->description ?: $line->entry->description }}</td>
                            <td class="text-muted small">{{ $line->entry->reference ?: '—' }}</td>
                            <td class="amount dr">{{ (float) $line->debit  > 0 ? number_format($line->debit,  2) : '—' }}</td>
                            <td class="amount cr">{{ (float) $line->credit > 0 ? number_format($line->credit, 2) : '—' }}</td>
                            <td class="amount running {{ $line->running_balance < 0 ? 'negative' : '' }}">
                                {{ number_format($line->running_balance, 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                <i class="bi bi-inbox" style="font-size:2rem; opacity:.3;"></i>
                                <p class="mt-2 mb-0">لا توجد حركات على هذا الحساب في هذه الفترة.</p>
                            </td>
                        </tr>
                    @endforelse

                    <tr class="closing-row">
                        <td colspan="4" class="text-end">إجمالي الفترة + الرصيد الختامي</td>
                        <td class="amount">{{ number_format($period_debit, 2) }}</td>
                        <td class="amount">{{ number_format($period_credit, 2) }}</td>
                        <td class="amount">{{ number_format($closing, 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection
