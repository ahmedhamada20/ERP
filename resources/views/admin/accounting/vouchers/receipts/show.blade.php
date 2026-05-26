@extends('layouts.master')

@section('title', 'سند قبض ' . $voucher->number)
@section('page_title', 'سند قبض ' . $voucher->number)
@section('page_subtitle', $voucher->description)

@push('styles')
<style>
    .v-info { background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:1.25rem; }
    .v-info h6 { color:#6b7280; font-size:.78rem; font-weight:600; margin-bottom:.3rem; }
    .v-info .v { font-weight:800; color:#0f172a; font-size:1.05rem; }
    .v-amount { background:linear-gradient(135deg, #dcfce7, #bbf7d0); border:1px solid #86efac; }
    .v-amount .v { color:#15803d; font-size:1.7rem; }

    .je-link { background:#eef2ff; border:1px solid #c7d2fe; border-radius:10px; padding:.9rem 1.1rem; }
    .je-link code { color:#4f46e5; font-weight:700; }
</style>
@endpush

@section('content')

<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="v-info"><h6>رقم السند</h6><div class="v"><code>{{ $voucher->number }}</code></div></div></div>
    <div class="col-md-3"><div class="v-info"><h6>التاريخ</h6><div class="v">{{ $voucher->date->format('Y-m-d') }}</div></div></div>
    <div class="col-md-3"><div class="v-info"><h6>المرجع</h6><div class="v">{{ $voucher->reference ?: '—' }}</div></div></div>
    <div class="col-md-3">
        <div class="v-info">
            <h6>الحالة</h6>
            <div class="v">
                @if($voucher->isPosted())
                    <span class="badge bg-success px-3 py-2"><i class="bi bi-check-circle"></i> مرحّل</span>
                @elseif($voucher->isCancelled())
                    <span class="badge bg-danger px-3 py-2"><i class="bi bi-x-circle"></i> ملغي</span>
                @else
                    <span class="badge bg-warning text-dark px-3 py-2">مسودة</span>
                @endif
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="v-info">
            <h6>الخزينة / البنك (المستلم)</h6>
            <div class="v"><code class="text-primary">{{ $voucher->cashAccount->code }}</code> — {{ $voucher->cashAccount->name }}</div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="v-info">
            <h6>الحساب المقابل</h6>
            <div class="v"><code class="text-primary">{{ $voucher->counterAccount->code }}</code> — {{ $voucher->counterAccount->name }}</div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="v-info">
            <h6>اسم المستلم / الدافع</h6>
            <div class="v">{{ $voucher->party_name }}</div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="v-info v-amount">
            <h6>القيمة</h6>
            <div class="v">
                {{ number_format($voucher->amount, 2) }} {{ $voucher->currency }}
                @if($voucher->currency !== 'EGP')
                    <small style="font-size:.9rem; opacity:.7;">({{ number_format($voucher->amount_egp, 2) }} ج.م)</small>
                @endif
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="v-info">
            <h6>البيان</h6>
            <div>{{ $voucher->description }}</div>
        </div>
    </div>
</div>

{{-- Linked Journal Entry --}}
@if($voucher->journalEntry)
<div class="je-link mb-3">
    <div class="d-flex justify-content-between align-items-center flex-wrap">
        <div>
            <i class="bi bi-link-45deg"></i>
            <strong>القيد المحاسبي المرتبط:</strong>
            <code>{{ $voucher->journalEntry->number }}</code>
            — {{ $voucher->journalEntry->lines->count() }} سطر — متوازن
            ({{ number_format($voucher->journalEntry->total_debit, 2) }} ج.م)
        </div>
        <a href="{{ route('admin.accounting.journal.show', $voucher->journalEntry) }}" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-eye"></i> عرض القيد
        </a>
    </div>
</div>
@endif

{{-- Audit info --}}
<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="v-info">
            <h6>أُنشئ بواسطة</h6>
            <div class="v">{{ $voucher->creator?->name ?: '—' }}</div>
            <div class="text-muted small">{{ $voucher->created_at?->format('Y-m-d H:i') }}</div>
        </div>
    </div>
    @if($voucher->isPosted() || $voucher->isCancelled())
    <div class="col-md-4">
        <div class="v-info">
            <h6>رُحّل بواسطة</h6>
            <div class="v">{{ $voucher->poster?->name ?: '—' }}</div>
            <div class="text-muted small">{{ $voucher->posted_at?->format('Y-m-d H:i') }}</div>
        </div>
    </div>
    @endif
    @if($voucher->isCancelled())
    <div class="col-md-4">
        <div class="v-info" style="background:#fef2f2; border-color:#fecaca;">
            <h6>أُلغي بواسطة</h6>
            <div class="v">{{ $voucher->canceller?->name ?: '—' }}</div>
            <div class="text-muted small">{{ $voucher->cancelled_at?->format('Y-m-d H:i') }}</div>
            <div class="text-danger small mt-1"><strong>السبب:</strong> {{ $voucher->cancellation_reason }}</div>
        </div>
    </div>
    @endif
</div>

{{-- Actions --}}
<div class="card">
    <div class="card-body d-flex gap-2 justify-content-between flex-wrap">
        <a href="{{ route('admin.accounting.vouchers.receipts.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-right"></i> رجوع للقائمة
        </a>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.accounting.vouchers.receipts.print', $voucher) }}" target="_blank" class="btn btn-outline-primary">
                <i class="bi bi-printer"></i> طباعة السند
            </a>
            @if($voucher->isPosted())
                @can('accounting.vouchers.create')
                <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#cancelModal">
                    <i class="bi bi-x-circle"></i> إلغاء السند
                </button>
                @endcan
            @endif
        </div>
    </div>
</div>

{{-- Cancel modal --}}
@if($voucher->isPosted())
@can('accounting.vouchers.create')
<div class="modal fade" id="cancelModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('admin.accounting.vouchers.receipts.cancel', $voucher) }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="bi bi-x-circle"></i> إلغاء سند القبض</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>إلغاء السند سيلغي القيد المحاسبي المرتبط به أيضاً.</p>
                <label class="form-label">سبب الإلغاء *</label>
                <textarea name="cancellation_reason" rows="3" class="form-control" required></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">تراجع</button>
                <button type="submit" class="btn btn-danger">تأكيد الإلغاء</button>
            </div>
        </form>
    </div>
</div>
@endcan
@endif

@endsection
