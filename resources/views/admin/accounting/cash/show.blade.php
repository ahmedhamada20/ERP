@extends('layouts.master')

@section('title', $account->code . ' — ' . $account->name)
@section('page_title', $account->name)
@section('page_subtitle', ($account->sub_type === 'cash' ? 'خزينة نقدية' : 'حساب بنكي') . ' — كود الحساب: ' . $account->code)

@push('styles')
<style>
    .stat-card {
        background: #fff; border: 1px solid #e5e7eb; border-radius: 12px;
        padding: 1.2rem 1.3rem; height: 100%;
    }
    .stat-card .lbl { color: #6b7280; font-weight: 600; font-size: .82rem; margin-bottom: .35rem; }
    .stat-card .val { font-weight: 800; font-size: 1.4rem; color: #0f172a; }
    .stat-card.in  .val { color: #15803d; }
    .stat-card.out .val { color: #b91c1c; }
    .stat-card.balance { background: linear-gradient(135deg, #eef2ff, #fff); border-color: #c7d2fe; }
    .stat-card.balance .val { color: #4338ca; font-size: 1.75rem; }

    .mv-table th { background: #f9fafb; color: #475569; font-weight: 700; font-size: .85rem; }
    .mv-table td { vertical-align: middle; }
    .mv-table .je-num { font-family: 'JetBrains Mono', monospace; color: #4f46e5; font-weight: 600; }
    .mv-amount-in  { color: #15803d; font-weight: 800; }
    .mv-amount-out { color: #b91c1c; font-weight: 800; }
</style>
@endpush

@section('content')

<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="lbl">الرصيد الافتتاحي</div>
            <div class="val">{{ number_format($opening, 2) }} <small style="font-size:.85rem; color:#6b7280;">{{ $account->currency }}</small></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card in">
            <div class="lbl"><i class="bi bi-arrow-down-circle"></i> إجمالي الوارد</div>
            <div class="val">{{ number_format($totalDebit, 2) }}</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card out">
            <div class="lbl"><i class="bi bi-arrow-up-circle"></i> إجمالي المنصرف</div>
            <div class="val">{{ number_format($totalCredit, 2) }}</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card balance">
            <div class="lbl"><i class="bi bi-wallet2"></i> الرصيد الحالي</div>
            <div class="val">{{ number_format($current, 2) }}</div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0"><i class="bi bi-clock-history"></i> آخر الحركات</h6>
        <a href="{{ route('admin.accounting.cash.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-right"></i> رجوع للقائمة
        </a>
    </div>
    <div class="card-body p-0">
        @if($movements->isEmpty())
            <div class="text-center text-muted py-5">
                <i class="bi bi-inbox" style="font-size:2.5rem; opacity:.3;"></i>
                <p class="mt-3 mb-0">لا توجد حركات مرحّلة على هذا الحساب بعد.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table mv-table mb-0">
                    <thead>
                        <tr>
                            <th>التاريخ</th>
                            <th>القيد</th>
                            <th>المرجع</th>
                            <th>البيان</th>
                            <th class="text-end">وارد</th>
                            <th class="text-end">منصرف</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($movements as $m)
                        <tr>
                            <td>{{ $m->entry?->date?->format('Y-m-d') }}</td>
                            <td>
                                <a href="{{ route('admin.accounting.journal.show', $m->entry) }}" class="je-num">
                                    {{ $m->entry?->number }}
                                </a>
                            </td>
                            <td class="text-muted small">{{ $m->entry?->reference ?: '—' }}</td>
                            <td>{{ $m->description ?: $m->entry?->description }}</td>
                            <td class="text-end mv-amount-in">{{ (float) $m->debit  > 0 ? number_format($m->debit, 2)  : '—' }}</td>
                            <td class="text-end mv-amount-out">{{ (float) $m->credit > 0 ? number_format($m->credit, 2) : '—' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-footer text-muted small">
                عرض آخر {{ $movements->count() }} حركة. الـ ledger التفصيلي قادم في تقرير "دفتر الأستاذ" (Step 12).
            </div>
        @endif
    </div>
</div>
@endsection
