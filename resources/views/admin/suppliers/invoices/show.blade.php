@extends('layouts.master')

@section('title', 'فاتورة ' . $invoice->number)
@section('page_title', 'فاتورة مورد ' . $invoice->number)
@section('page_subtitle', $invoice->description)

@push('styles')
<style>
    .v-info { background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:1.25rem; height:100%; }
    .v-info h6 { color:#6b7280; font-size:.78rem; font-weight:600; margin-bottom:.3rem; }
    .v-info .v { font-weight:800; color:#0f172a; font-size:1.05rem; }
    .v-total { background:linear-gradient(135deg, #fee2e2, #fecaca); border:1px solid #fca5a5; }
    .v-total .v { color:#b91c1c; font-size:1.7rem; }
    .je-link { background:#eef2ff; border:1px solid #c7d2fe; border-radius:10px; padding:.9rem 1.1rem; }
    .je-link code { color:#4f46e5; font-weight:700; }

    .badge-st-draft  { background:#fef3c7; color:#92400e; }
    .badge-st-posted { background:#dcfce7; color:#15803d; }
    .badge-st-cancel { background:#fee2e2; color:#b91c1c; }

    .overdue-banner { background:#fef2f2; border:2px solid #fca5a5; color:#991b1b;
                      padding:.65rem 1rem; border-radius:8px; font-weight:700; }
</style>
@endpush

@section('content')

@if($invoice->status === 'posted' && $invoice->due_date && $invoice->due_date->isPast())
<div class="overdue-banner mb-3">
    <i class="bi bi-exclamation-triangle"></i>
    <strong>فاتورة متأخرة!</strong>
    استحقت في {{ $invoice->due_date->format('Y-m-d') }} ({{ (int) $invoice->due_date->diffInDays(now()) }} يوم تأخير)
</div>
@endif

<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="v-info"><h6>رقم الفاتورة</h6><div class="v"><code>{{ $invoice->number }}</code></div></div></div>
    <div class="col-md-3"><div class="v-info"><h6>التاريخ</h6><div class="v">{{ $invoice->invoice_date->format('Y-m-d') }}</div></div></div>
    <div class="col-md-3"><div class="v-info"><h6>الاستحقاق</h6><div class="v">{{ $invoice->due_date?->format('Y-m-d') ?: '—' }}</div></div></div>
    <div class="col-md-3"><div class="v-info">
        <h6>الحالة</h6>
        <div class="v">
            <span class="badge badge-st-{{ $invoice->status === 'cancelled' ? 'cancel' : $invoice->status }} px-3 py-2">
                {{ $invoice->status_label }}
            </span>
        </div>
    </div></div>

    <div class="col-md-6"><div class="v-info">
        <h6>المورد</h6>
        <div class="v">
            <a href="{{ route('admin.suppliers.show', $invoice->supplier) }}">
                <code class="text-primary">{{ $invoice->supplier->code }}</code> — {{ $invoice->supplier->name }}
            </a>
            <span class="badge bg-secondary ms-1">{{ $invoice->supplier->type_label }}</span>
        </div>
    </div></div>
    <div class="col-md-6"><div class="v-info">
        <h6>حساب المصروف</h6>
        <div class="v"><code class="text-primary">{{ $invoice->expenseAccount->code }}</code> — {{ $invoice->expenseAccount->name }}</div>
    </div></div>

    <div class="col-md-3"><div class="v-info">
        <h6>القيمة قبل الضريبة</h6>
        <div class="v">{{ number_format($invoice->amount, 2) }} <small>{{ $invoice->currency }}</small></div>
    </div></div>
    <div class="col-md-3"><div class="v-info">
        <h6>قيمة الضريبة</h6>
        <div class="v">{{ number_format($invoice->tax_amount, 2) }} <small>{{ $invoice->currency }}</small></div>
    </div></div>
    <div class="col-md-3"><div class="v-info">
        <h6>سعر الصرف</h6>
        <div class="v">{{ number_format($invoice->exchange_rate, 4) }}</div>
    </div></div>
    <div class="col-md-3"><div class="v-info v-total">
        <h6>الإجمالي</h6>
        <div class="v">
            {{ number_format($invoice->total, 2) }} {{ $invoice->currency }}
            @if($invoice->currency !== 'EGP')
                <small style="font-size:.85rem; opacity:.75;">({{ number_format($invoice->amount_egp, 2) }} ج.م)</small>
            @endif
        </div>
    </div></div>

    <div class="col-12"><div class="v-info">
        <h6>البيان</h6>
        <div>{{ $invoice->description }}</div>
        @if($invoice->supplier_reference)
            <div class="mt-2 text-muted small">
                <i class="bi bi-link-45deg"></i> رقم فاتورة المورد:
                <code dir="ltr">{{ $invoice->supplier_reference }}</code>
            </div>
        @endif
    </div></div>
</div>

@if($invoice->journalEntry)
<div class="je-link mb-3">
    <div class="d-flex justify-content-between align-items-center flex-wrap">
        <div>
            <i class="bi bi-link-45deg"></i>
            <strong>القيد المحاسبي:</strong>
            <code>{{ $invoice->journalEntry->number }}</code>
            — {{ $invoice->journalEntry->lines->count() }} سطر — متوازن
            ({{ number_format($invoice->journalEntry->total_debit, 2) }} ج.م)
        </div>
        <a href="{{ route('admin.accounting.journal.show', $invoice->journalEntry) }}" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-eye"></i> عرض القيد
        </a>
    </div>
</div>
@endif

{{-- Audit info --}}
<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="v-info">
            <h6>أُنشئت بواسطة</h6>
            <div class="v">{{ $invoice->creator?->name ?: '—' }}</div>
            <div class="text-muted small">{{ $invoice->created_at?->format('Y-m-d H:i') }}</div>
        </div>
    </div>
    @if($invoice->isPosted() || $invoice->isCancelled())
    <div class="col-md-4">
        <div class="v-info">
            <h6>رُحّلت بواسطة</h6>
            <div class="v">{{ $invoice->poster?->name ?: '—' }}</div>
            <div class="text-muted small">{{ $invoice->posted_at?->format('Y-m-d H:i') }}</div>
        </div>
    </div>
    @endif
    @if($invoice->isCancelled())
    <div class="col-md-4">
        <div class="v-info" style="background:#fef2f2; border-color:#fecaca;">
            <h6>أُلغيت بواسطة</h6>
            <div class="v">{{ $invoice->canceller?->name ?: '—' }}</div>
            <div class="text-muted small">{{ $invoice->cancelled_at?->format('Y-m-d H:i') }}</div>
            <div class="text-danger small mt-1"><strong>السبب:</strong> {{ $invoice->cancellation_reason }}</div>
        </div>
    </div>
    @endif
</div>

{{-- Actions --}}
<div class="card">
    <div class="card-body d-flex justify-content-between flex-wrap gap-2">
        <a href="{{ route('admin.supplier_invoices.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-right"></i> رجوع للقائمة
        </a>
        <div class="d-flex gap-2">
            @if($invoice->isDraft())
                @can('supplier_invoices.post')
                <form action="{{ route('admin.supplier_invoices.post', $invoice) }}" method="POST" class="d-inline"
                      onsubmit="return confirm('ترحيل الفاتورة نهائي — هينشأ قيد محاسبي مرحّل ومش هتقدر تعدل. متأكد؟');">
                    @csrf
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check2-all"></i> ترحيل الفاتورة
                    </button>
                </form>
                @endcan
                @can('supplier_invoices.create')
                <form action="{{ route('admin.supplier_invoices.destroy', $invoice) }}" method="POST" class="d-inline" id="deleteForm">
                    @csrf @method('DELETE')
                    <button type="button" class="btn btn-outline-danger" onclick="if(confirm('حذف هذه المسودة؟')) { document.getElementById('deleteForm').submit(); }">
                        <i class="bi bi-trash"></i> حذف المسودة
                    </button>
                </form>
                @endcan
            @elseif($invoice->isPosted())
                @can('supplier_invoices.cancel')
                <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#cancelModal">
                    <i class="bi bi-x-circle"></i> إلغاء الفاتورة
                </button>
                @endcan
            @endif
        </div>
    </div>
</div>

{{-- Cancel modal --}}
@if($invoice->isPosted())
@can('supplier_invoices.cancel')
<div class="modal fade" id="cancelModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('admin.supplier_invoices.cancel', $invoice) }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="bi bi-x-circle"></i> إلغاء فاتورة المورد</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>سيتم إلغاء الفاتورة <strong>{{ $invoice->number }}</strong> والقيد المحاسبي المرتبط بها.</p>
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
