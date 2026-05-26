@extends('layouts.master')

@section('title', 'قيد ' . $entry->number)
@section('page_title', 'قيد يومية ' . $entry->number)
@section('page_subtitle', $entry->description)

@push('styles')
<style>
    .je-info-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 1.25rem; }
    .je-info-card h6 { color: #6b7280; font-weight: 600; font-size: .8rem; margin-bottom: .35rem; }
    .je-info-card .v { font-weight: 800; color: #0f172a; font-size: 1.05rem; }

    .lines-display th, .lines-display td { padding: .65rem .75rem; vertical-align: middle; }
    .lines-display thead { background: #f9fafb; color: #475569; font-weight: 700; font-size: .85rem; }
    .lines-display tfoot { background: #f1f5f9; font-weight: 800; }
    .lines-display .acc-code { font-family: 'JetBrains Mono', monospace; color: #4f46e5; }

    .badge-draft  { background: #fef3c7; color: #92400e; }
    .badge-posted { background: #dcfce7; color: #15803d; }
    .badge-cancel { background: #fee2e2; color: #b91c1c; }

    .meta-line { color: #6b7280; font-size: .85rem; }
    .meta-line .meta-label { color: #94a3b8; }
</style>
@endpush

@section('content')

{{-- Header info --}}
<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="je-info-card"><h6>رقم القيد</h6><div class="v"><code>{{ $entry->number }}</code></div></div></div>
    <div class="col-md-3"><div class="je-info-card"><h6>التاريخ</h6><div class="v">{{ $entry->date->format('Y-m-d') }}</div></div></div>
    <div class="col-md-3"><div class="je-info-card"><h6>المرجع</h6><div class="v">{{ $entry->reference ?: '—' }}</div></div></div>
    <div class="col-md-3">
        <div class="je-info-card">
            <h6>الحالة</h6>
            <div class="v">
                @if($entry->isDraft())
                    <span class="badge badge-draft px-3 py-2"><i class="bi bi-pencil"></i> مسودة</span>
                @elseif($entry->isPosted())
                    <span class="badge badge-posted px-3 py-2"><i class="bi bi-check-circle"></i> مرحّل</span>
                @else
                    <span class="badge badge-cancel px-3 py-2"><i class="bi bi-x-circle"></i> ملغي</span>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Lines --}}
<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0"><i class="bi bi-list-ul"></i> سطور القيد ({{ $entry->lines->count() }})</h6>
        <div class="meta-line">
            <span class="meta-label">المصدر:</span> {{ $entry->source_type === 'manual' ? 'يدوي' : $entry->source_type }}
        </div>
    </div>
    <div class="card-body p-0">
        <table class="table mb-0 lines-display">
            <thead>
                <tr>
                    <th width="40">#</th>
                    <th width="100">الكود</th>
                    <th>الحساب</th>
                    <th>البيان</th>
                    <th width="160" class="text-end">مدين</th>
                    <th width="160" class="text-end">دائن</th>
                </tr>
            </thead>
            <tbody>
                @foreach($entry->lines as $line)
                <tr>
                    <td>{{ $line->line_number }}</td>
                    <td><span class="acc-code">{{ $line->account?->code }}</span></td>
                    <td>{{ $line->account?->name }}</td>
                    <td class="text-muted">{{ $line->description ?: '—' }}</td>
                    <td class="text-end">{{ (float) $line->debit  > 0 ? number_format($line->debit,  2) : '—' }}</td>
                    <td class="text-end">{{ (float) $line->credit > 0 ? number_format($line->credit, 2) : '—' }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" class="text-end">الإجمالي</td>
                    <td class="text-end">{{ number_format($entry->total_debit,  2) }}</td>
                    <td class="text-end">{{ number_format($entry->total_credit, 2) }}</td>
                </tr>
                <tr>
                    <td colspan="4" class="text-end">الرصيد</td>
                    <td colspan="2" class="text-end">
                        @if($entry->isBalanced())
                            <span class="badge bg-success px-3 py-2"><i class="bi bi-check2-circle"></i> القيد متوازن</span>
                        @else
                            <span class="badge bg-danger px-3 py-2">
                                فرق: {{ number_format($entry->total_debit - $entry->total_credit, 2) }} ج.م
                            </span>
                        @endif
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

{{-- Audit info --}}
<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="je-info-card">
            <h6>تم الإنشاء بواسطة</h6>
            <div class="v">{{ $entry->creator?->name ?: '—' }}</div>
            <div class="meta-line">{{ $entry->created_at?->format('Y-m-d H:i') }}</div>
        </div>
    </div>
    @if($entry->isPosted() || $entry->isCancelled())
    <div class="col-md-4">
        <div class="je-info-card">
            <h6>تم الترحيل بواسطة</h6>
            <div class="v">{{ $entry->poster?->name ?: '—' }}</div>
            <div class="meta-line">{{ $entry->posted_at?->format('Y-m-d H:i') }}</div>
        </div>
    </div>
    @endif
    @if($entry->isCancelled())
    <div class="col-md-4">
        <div class="je-info-card" style="border-color:#fecaca; background:#fef2f2;">
            <h6>تم الإلغاء بواسطة</h6>
            <div class="v">{{ $entry->canceller?->name ?: '—' }}</div>
            <div class="meta-line">{{ $entry->cancelled_at?->format('Y-m-d H:i') }}</div>
            <div class="text-danger small mt-1"><strong>سبب الإلغاء:</strong> {{ $entry->cancellation_reason }}</div>
        </div>
    </div>
    @endif
</div>

{{-- Action toolbar --}}
<div class="card">
    <div class="card-body d-flex gap-2 flex-wrap justify-content-between align-items-center">
        <a href="{{ route('admin.accounting.journal.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-right"></i> رجوع للقائمة
        </a>

        <div class="d-flex gap-2">
            @if($entry->isDraft())
                @can('accounting.journal.create')
                <a href="{{ route('admin.accounting.journal.edit', $entry) }}" class="btn btn-outline-primary">
                    <i class="bi bi-pencil"></i> تعديل المسودة
                </a>
                @endcan
                @can('accounting.journal.post')
                <form action="{{ route('admin.accounting.journal.post', $entry) }}" method="POST" class="d-inline"
                      onsubmit="return confirm('ترحيل القيد نهائي — لن يمكن التعديل بعدها. متأكد؟');">
                    @csrf
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check2-all"></i> ترحيل القيد
                    </button>
                </form>
                @endcan
                @can('accounting.journal.delete')
                <form action="{{ route('admin.accounting.journal.destroy', $entry) }}" method="POST" class="d-inline" id="deleteForm">
                    @csrf @method('DELETE')
                    <button type="button" class="btn btn-outline-danger" onclick="if(confirm('حذف هذه المسودة؟')) { document.getElementById('deleteForm').submit(); }">
                        <i class="bi bi-trash"></i> حذف
                    </button>
                </form>
                @endcan
            @elseif($entry->isPosted())
                @can('accounting.journal.post')
                <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#cancelModal">
                    <i class="bi bi-x-circle"></i> إلغاء القيد
                </button>
                @endcan
            @endif
        </div>
    </div>
</div>

{{-- Cancel modal --}}
@if($entry->isPosted())
@can('accounting.journal.post')
<div class="modal fade" id="cancelModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('admin.accounting.journal.cancel', $entry) }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="bi bi-x-circle"></i> إلغاء القيد {{ $entry->number }}</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>إلغاء القيد سيستبعده من ميزان المراجعة وكل التقارير. السطور تبقى محفوظة للتاريخ.</p>
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
