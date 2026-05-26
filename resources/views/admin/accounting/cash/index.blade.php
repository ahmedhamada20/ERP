@extends('layouts.master')

@section('title', 'الخزائن والبنوك')
@section('page_title', 'الخزائن والبنوك')
@section('page_subtitle', 'الأرصدة الحالية لكل خزينة وحساب بنكي — من القيود المرحّلة فقط')

@push('styles')
<style>
    .summary-card {
        background: linear-gradient(135deg, #fff 0%, #f9fafb 100%);
        border: 1px solid #e5e7eb; border-radius: 14px; padding: 1.5rem;
    }
    .summary-card .label { color: #6b7280; font-weight: 600; font-size: .85rem; margin-bottom: .25rem; }
    .summary-card .value { color: #0f172a; font-weight: 800; font-size: 1.75rem; line-height: 1.1; }
    .summary-card .sub   { color: #94a3b8; font-size: .8rem; margin-top: .35rem; }

    .acc-card {
        background: #fff; border: 1px solid #e5e7eb; border-radius: 12px;
        padding: 1.1rem 1.25rem; transition: all .2s;
        display: flex; align-items: center; gap: 1rem;
    }
    .acc-card:hover { border-color: #6366f1; box-shadow: 0 4px 16px rgba(99,102,241,.1); }
    .acc-card .icon {
        width: 50px; height: 50px; border-radius: 12px;
        display: flex; align-items: center; justify-content: center; font-size: 1.6rem;
    }
    .acc-card .code { font-family: 'JetBrains Mono', monospace; color: #4f46e5; font-weight: 700; }
    .acc-card .name { font-weight: 800; color: #0f172a; }
    .acc-card .balance { font-weight: 800; color: #15803d; font-size: 1.25rem; }
    .acc-card .balance.negative { color: #b91c1c; }
    .acc-card .meta { color: #6b7280; font-size: .8rem; margin-top: .25rem; }

    .ic-cash { background:#fef3c7; color:#a16207; }
    .ic-bank { background:#dbeafe; color:#1e40af; }
</style>
@endpush

@section('content')

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="summary-card">
            <div class="label"><i class="bi bi-cash-stack"></i> إجمالي الخزائن</div>
            <div class="value">{{ number_format($totals['cash_total'], 2) }} <small style="font-size:1rem; color:#6b7280;">ج.م</small></div>
            <div class="sub">{{ $totals['cash_count'] }} خزينة</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="summary-card">
            <div class="label"><i class="bi bi-bank"></i> إجمالي البنوك</div>
            <div class="value">{{ number_format($totals['bank_total'], 2) }} <small style="font-size:1rem; color:#6b7280;">ج.م</small></div>
            <div class="sub">{{ $totals['bank_count'] }} حساب بنكي</div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="summary-card" style="background:linear-gradient(135deg,#dcfce7,#bbf7d0); border-color:#86efac;">
            <div class="label" style="color:#166534;"><i class="bi bi-piggy-bank"></i> الإجمالي العام</div>
            <div class="value" style="color:#166534;">
                {{ number_format($totals['cash_total'] + $totals['bank_total'], 2) }}
                <small style="font-size:1rem;">ج.م</small>
            </div>
            <div class="sub" style="color:#166534;">الأموال السائلة المتاحة في الشركة</div>
        </div>
    </div>
</div>

@if($accounts->isEmpty())
    <div class="card">
        <div class="card-body text-center text-muted py-5">
            <i class="bi bi-piggy-bank" style="font-size:3rem; opacity:.3;"></i>
            <p class="mt-3 mb-0">لا توجد خزائن أو بنوك مُعرّفة. أضف حساب جديد من دليل الحسابات بـ <code>sub_type = cash / bank</code>.</p>
        </div>
    </div>
@else
    {{-- Cash boxes section --}}
    @if($totals['cash_count'] > 0)
        <h6 class="mb-3"><i class="bi bi-cash-stack"></i> الخزائن النقدية</h6>
        <div class="row g-3 mb-4">
            @foreach($accounts->where('sub_type', 'cash') as $acc)
                <div class="col-md-6 col-lg-4">
                    <a href="{{ route('admin.accounting.cash.show', $acc) }}" class="text-decoration-none">
                        <div class="acc-card">
                            <div class="icon ic-cash"><i class="bi bi-cash-stack"></i></div>
                            <div class="flex-grow-1">
                                <div><span class="code">{{ $acc->code }}</span> · <span class="name">{{ $acc->name }}</span></div>
                                <div class="meta">العملة: {{ $acc->currency }}</div>
                            </div>
                            <div class="text-end">
                                <div class="balance {{ $acc->current_balance < 0 ? 'negative' : '' }}">
                                    {{ number_format($acc->current_balance, 2) }}
                                </div>
                                <div class="meta">{{ $acc->currency }}</div>
                            </div>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Bank accounts section --}}
    @if($totals['bank_count'] > 0)
        <h6 class="mb-3"><i class="bi bi-bank"></i> الحسابات البنكية</h6>
        <div class="row g-3">
            @foreach($accounts->where('sub_type', 'bank') as $acc)
                <div class="col-md-6 col-lg-4">
                    <a href="{{ route('admin.accounting.cash.show', $acc) }}" class="text-decoration-none">
                        <div class="acc-card">
                            <div class="icon ic-bank"><i class="bi bi-bank"></i></div>
                            <div class="flex-grow-1">
                                <div><span class="code">{{ $acc->code }}</span> · <span class="name">{{ $acc->name }}</span></div>
                                <div class="meta">العملة: {{ $acc->currency }}</div>
                            </div>
                            <div class="text-end">
                                <div class="balance {{ $acc->current_balance < 0 ? 'negative' : '' }}">
                                    {{ number_format($acc->current_balance, 2) }}
                                </div>
                                <div class="meta">{{ $acc->currency }}</div>
                            </div>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>
    @endif
@endif

@endsection
