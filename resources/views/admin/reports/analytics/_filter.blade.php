@php
    /** @var \Illuminate\Support\Carbon $from */
    /** @var \Illuminate\Support\Carbon $to */
@endphp
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-3">
        <form method="get" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label fw-bold small text-muted mb-1">
                    <i class="bi bi-calendar-event"></i> من تاريخ
                </label>
                <input type="date" name="from" value="{{ $from->toDateString() }}" class="form-control form-control-sm">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold small text-muted mb-1">
                    <i class="bi bi-calendar-check"></i> إلى تاريخ
                </label>
                <input type="date" name="to" value="{{ $to->toDateString() }}" class="form-control form-control-sm">
            </div>
            <div class="col-md-6 text-end">
                <button type="submit" class="btn btn-primary btn-sm fw-bold">
                    <i class="bi bi-funnel"></i> تطبيق الفلتر
                </button>
                <a href="{{ url()->current() }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-x-circle"></i> إعادة تعيين
                </a>
                <button type="button" onclick="window.print()" class="btn btn-outline-info btn-sm">
                    <i class="bi bi-printer"></i> طباعة
                </button>
            </div>
        </form>
    </div>
</div>
