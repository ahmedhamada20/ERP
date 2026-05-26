@extends('layouts.master')

@section('title', 'دفتر الأستاذ')
@section('page_title', 'دفتر الأستاذ التفصيلي')
@section('page_subtitle', 'اختر حساب وفترة لعرض كل حركاته مع الرصيد التراكمي')

@push('styles')
<style>
    .picker-card { background:#fff; border:1px solid #e5e7eb; border-radius:14px; padding:2rem; max-width:700px; margin:1rem auto; }
    .picker-card h5 { color:#0f172a; font-weight:800; margin-bottom:1.5rem; }
    .type-pill { font-size:.72rem; padding:.15rem .55rem; border-radius:6px; font-weight:700; }
    .t-asset    { background:#dbeafe; color:#1e40af; }
    .t-liability{ background:#fee2e2; color:#b91c1c; }
    .t-equity   { background:#fef3c7; color:#92400e; }
    .t-revenue  { background:#dcfce7; color:#15803d; }
    .t-expense  { background:#fce7f3; color:#9d174d; }
</style>
@endpush

@section('content')
<div class="picker-card">
    <h5><i class="bi bi-search"></i> اختر حساب لعرض دفتر الأستاذ</h5>

    <form method="GET" action="{{ route('admin.accounting.reports.general_ledger') }}">
        <div class="mb-3">
            <label class="form-label">الحساب *</label>
            <select name="account_id" class="form-select" required>
                <option value="">— اختر الحساب —</option>
                @foreach($accounts->groupBy('type') as $type => $items)
                    @php $label = ['asset'=>'الأصول','liability'=>'الخصوم','equity'=>'حقوق الملكية','revenue'=>'الإيرادات','expense'=>'المصروفات'][$type] ?? $type; @endphp
                    <optgroup label="{{ $label }}">
                        @foreach($items as $a)
                            <option value="{{ $a->id }}">{{ $a->code }} — {{ $a->name }}</option>
                        @endforeach
                    </optgroup>
                @endforeach
            </select>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label class="form-label">من تاريخ</label>
                <input type="date" name="from" class="form-control"
                       value="{{ now()->startOfMonth()->format('Y-m-d') }}">
            </div>
            <div class="col-md-6">
                <label class="form-label">إلى تاريخ</label>
                <input type="date" name="to" class="form-control"
                       value="{{ now()->endOfMonth()->format('Y-m-d') }}">
            </div>
        </div>

        <div class="d-grid">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-search"></i> عرض دفتر الأستاذ
            </button>
        </div>
    </form>
</div>
@endsection
