@extends('layouts.master')

@section('title', 'كشف حساب مورد')
@section('page_title', 'كشف حساب مورد')
@section('page_subtitle', 'اختر مورد وفترة لعرض كل فواتيره وسداداته مع الرصيد المتراكم')

@push('styles')
<style>
    .picker-card { background:#fff; border:1px solid #e5e7eb; border-radius:14px; padding:2rem; max-width:700px; margin:1rem auto; }
    .picker-card h5 { color:#0f172a; font-weight:800; margin-bottom:1.5rem; }
</style>
@endpush

@section('content')
<div class="picker-card">
    <h5><i class="bi bi-search"></i> اختر مورد</h5>

    <form method="GET" action="{{ route('admin.suppliers.statement') }}">
        <div class="mb-3">
            <label class="form-label">المورد *</label>
            <select name="supplier_id" class="form-select" required>
                <option value="">— اختر المورد —</option>
                @foreach($suppliers->groupBy('type') as $type => $items)
                    @php $label = ['hotel'=>'فنادق','airline'=>'طيران','transport'=>'نقل','visa'=>'تأشيرات','other'=>'أخرى'][$type] ?? $type; @endphp
                    <optgroup label="{{ $label }}">
                        @foreach($items as $s)
                            <option value="{{ $s->id }}">{{ $s->code }} — {{ $s->name }}</option>
                        @endforeach
                    </optgroup>
                @endforeach
            </select>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label class="form-label">من تاريخ</label>
                <input type="date" name="from" class="form-control" value="{{ now()->startOfMonth()->format('Y-m-d') }}">
            </div>
            <div class="col-md-6">
                <label class="form-label">إلى تاريخ</label>
                <input type="date" name="to" class="form-control" value="{{ now()->endOfMonth()->format('Y-m-d') }}">
            </div>
        </div>

        <div class="d-grid">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-search"></i> عرض كشف الحساب
            </button>
        </div>
    </form>
</div>
@endsection
