@extends('layouts.master')

@section('title', 'كشف حساب ' . $supplier->name)
@section('page_title', 'كشف حساب: ' . $supplier->name)
@section('page_subtitle', $supplier->code . ' — ' . $supplier->type_label . ' — كل الفواتير والسدادات + الرصيد المتراكم')

@push('styles')
<style>
    .gl-filters { background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:1rem 1.25rem; margin-bottom:1rem; }

    .sc { background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:1.1rem 1.25rem; height:100%; }
    .sc .lbl { color:#6b7280; font-size:.82rem; font-weight:600; }
    .sc .val { font-weight:800; font-size:1.4rem; color:#0f172a; line-height:1.1; margin-top:.3rem; }
    .sc.opening { background:linear-gradient(135deg, #f9fafb, #fff); }
    .sc.invoices { background:linear-gradient(135deg, #fee2e2, #fff); border-color:#fca5a5; }
    .sc.invoices .val { color:#b91c1c; }
    .sc.payments { background:linear-gradient(135deg, #dcfce7, #fff); border-color:#86efac; }
    .sc.payments .val { color:#15803d; }
    .sc.closing { background:linear-gradient(135deg, #eef2ff, #fff); border-color:#c7d2fe; }
    .sc.closing .val { color:#4338ca; font-size:1.7rem; }

    .stmt-table { background:#fff; }
    .stmt-table th { background:#f3f4f6; color:#374151; font-weight:700; }
    .stmt-table th, .stmt-table td { padding:.6rem .8rem; vertical-align:middle; }
    .stmt-table .row-num   { font-family:'JetBrains Mono', monospace; color:#4f46e5; font-weight:600; }
    .stmt-table .amount    { font-family:'JetBrains Mono', monospace; font-weight:700; text-align:end; }
    .stmt-table .amount.dr { color:#15803d; }
    .stmt-table .amount.cr { color:#b91c1c; }
    .stmt-table .running   { color:#4338ca; font-weight:800; }
    .stmt-table .running.negative { color:#15803d; }
    .stmt-table tr.opening-row td { background:#f1f5f9; font-style:italic; color:#475569; font-weight:700; }
    .stmt-table tr.closing-row td { background:#1f2937; color:#fff; font-weight:800; padding:.7rem .8rem; }

    .type-pill { font-size:.72rem; padding:.15rem .55rem; border-radius:6px; font-weight:700; }
    .pill-invoice { background:#fee2e2; color:#b91c1c; }
    .pill-payment { background:#dcfce7; color:#15803d; }
</style>
@endpush

@section('content')

{{-- Filters --}}
<form method="GET" class="gl-filters">
    <div class="row g-2 align-items-end">
        <div class="col-md-4">
            <label class="form-label small mb-1">المورد</label>
            <select name="supplier_id" class="form-select form-select-sm" onchange="this.form.submit()">
                @foreach($suppliers->groupBy('type') as $type => $items)
                    @php $label = ['hotel'=>'فنادق','airline'=>'طيران','transport'=>'نقل','visa'=>'تأشيرات','other'=>'أخرى'][$type] ?? $type; @endphp
                    <optgroup label="{{ $label }}">
                        @foreach($items as $s)
                            <option value="{{ $s->id }}" {{ $s->id === $supplier->id ? 'selected' : '' }}>
                                {{ $s->code }} — {{ $s->name }}
                            </option>
                        @endforeach
                    </optgroup>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small mb-1">من تاريخ</label>
            <input type="date" name="from" class="form-control form-control-sm" value="{{ request('from', $from->format('Y-m-d')) }}">
        </div>
        <div class="col-md-2">
            <label class="form-label small mb-1">إلى تاريخ</label>
            <input type="date" name="to" class="form-control form-control-sm" value="{{ request('to', $to->format('Y-m-d')) }}">
        </div>
        <div class="col-md-4 d-flex justify-content-end gap-2">
            <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-funnel"></i> تطبيق</button>
            <a href="{{ route('admin.suppliers.statement.print', request()->all()) }}" target="_blank" class="btn btn-outline-secondary btn-sm"><i class="bi bi-printer"></i> طباعة</a>
            <a href="{{ route('admin.suppliers.statement.csv', request()->all()) }}" class="btn btn-outline-success btn-sm"><i class="bi bi-file-earmark-spreadsheet"></i> CSV</a>
            <a href="{{ route('admin.suppliers.show', $supplier) }}" class="btn btn-outline-info btn-sm"><i class="bi bi-eye"></i> ملف المورد</a>
        </div>
    </div>
</form>

<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="sc opening">
        <div class="lbl">الرصيد الافتتاحي</div>
        <div class="val">{{ number_format($opening, 2) }} <small style="font-size:.85rem; color:#6b7280;">ج.م</small></div>
    </div></div>
    <div class="col-md-3"><div class="sc invoices">
        <div class="lbl"><i class="bi bi-receipt"></i> فواتير الفترة</div>
        <div class="val">{{ number_format($total_invoices, 2) }}</div>
    </div></div>
    <div class="col-md-3"><div class="sc payments">
        <div class="lbl"><i class="bi bi-cash-stack"></i> سدادات الفترة</div>
        <div class="val">{{ number_format($total_payments, 2) }}</div>
    </div></div>
    <div class="col-md-3"><div class="sc closing">
        <div class="lbl"><i class="bi bi-flag-fill"></i> الرصيد الختامي</div>
        <div class="val">{{ number_format($closing, 2) }}</div>
    </div></div>
</div>

<div class="card">
    <div class="card-header">
        <h6 class="mb-0">
            <i class="bi bi-list-ul"></i> حركات المورد ({{ $lines->count() }})
            <small class="text-muted">— موجب = مستحق له، سالب = مستحق علينا</small>
        </h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table stmt-table mb-0">
                <thead>
                    <tr>
                        <th width="100">التاريخ</th>
                        <th width="100">النوع</th>
                        <th width="140">الرقم</th>
                        <th>البيان</th>
                        <th width="120" class="text-end">مدين (سداد)</th>
                        <th width="120" class="text-end">دائن (فاتورة)</th>
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
                            <td>{{ $line->date->format('Y-m-d') }}</td>
                            <td>
                                @if($line->type === 'invoice')
                                    <span class="type-pill pill-invoice"><i class="bi bi-receipt"></i> فاتورة</span>
                                @else
                                    <span class="type-pill pill-payment"><i class="bi bi-cash"></i> سداد</span>
                                @endif
                            </td>
                            <td><a href="{{ $line->link }}" class="row-num">{{ $line->number }}</a></td>
                            <td>
                                {{ $line->description }}
                                @if($line->reference)
                                    <div class="small text-muted"><i class="bi bi-link-45deg"></i> {{ $line->reference }}</div>
                                @endif
                            </td>
                            <td class="amount dr">{{ $line->debit  > 0 ? number_format($line->debit,  2) : '—' }}</td>
                            <td class="amount cr">{{ $line->credit > 0 ? number_format($line->credit, 2) : '—' }}</td>
                            <td class="amount running {{ $line->running_balance < 0 ? 'negative' : '' }}">
                                {{ number_format($line->running_balance, 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">
                            <i class="bi bi-inbox" style="font-size:2rem; opacity:.3;"></i>
                            <p class="mt-2 mb-0">لا توجد حركات لهذا المورد في الفترة المختارة.</p>
                        </td></tr>
                    @endforelse

                    <tr class="closing-row">
                        <td colspan="4" class="text-end">إجمالي الفترة + الرصيد الختامي</td>
                        <td class="amount">{{ number_format($total_payments, 2) }}</td>
                        <td class="amount">{{ number_format($total_invoices, 2) }}</td>
                        <td class="amount">{{ number_format($closing, 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection
