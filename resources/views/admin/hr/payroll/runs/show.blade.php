@extends('layouts.master')

@section('title', 'دورة رواتب ' . $run->run_code)
@section('page_title', 'دورة الرواتب ' . $run->run_code)
@section('page_subtitle', $run->period_label . ' — ' . ($run->branch?->name ?? '—'))

@push('styles')
<style>
    .hero-card { background:linear-gradient(135deg, var(--brand-navy), #4338ca); color:#fff; border-radius:16px; padding:1.5rem; margin-bottom:1.25rem; }
    .hero-card .code { font-size:1.4rem; font-weight:800; letter-spacing:.5px; }
    .hero-card .period { opacity:.85; }
    .hero-stats { display:grid; grid-template-columns:repeat(4,1fr); gap:1rem; margin-top:1rem; }
    .hero-stat { background:rgba(255,255,255,.12); border-radius:10px; padding:.75rem 1rem; }
    .hero-stat .lbl { font-size:.72rem; opacity:.8; }
    .hero-stat .val { font-size:1.25rem; font-weight:700; }

    .workflow { display:flex; gap:.5rem; flex-wrap:wrap; background:#fff; padding:1rem; border-radius:12px; box-shadow:0 1px 4px rgba(15,23,42,.04); margin-bottom:1rem; }
    .step-pill { display:inline-flex; align-items:center; gap:.5rem; padding:.6rem 1rem; border-radius:10px; font-weight:600; font-size:.88rem; }
    .step-pill.done { background:#dcfce7; color:#15803d; }
    .step-pill.current { background:#dbeafe; color:#1e40af; border:2px solid #1e40af; }
    .step-pill.pending { background:#f1f5f9; color:#94a3b8; }
    .step-pill i { font-size:1rem; }
    .step-arrow { color:#cbd5e1; font-size:1.1rem; align-self:center; }

    .bg-secondary-soft { background:#f1f5f9 !important; color:#475569 !important; }
    .bg-info-soft      { background:#dbeafe !important; color:#1e40af !important; }
    .bg-primary-soft   { background:#ede9fe !important; color:#6d28d9 !important; }
    .bg-success-soft   { background:#dcfce7 !important; color:#15803d !important; }
    .bg-danger-soft    { background:#fee2e2 !important; color:#b91c1c !important; }
    .bg-warning-soft   { background:#fef3c7 !important; color:#92400e !important; }

    .x-small { font-size:.7rem; }
    .totals-card { background:#f8fafc; border-radius:12px; padding:1rem; }
    .total-row { display:flex; justify-content:space-between; padding:.4rem 0; border-bottom:1px dashed #e2e8f0; }
    .total-row:last-child { border-bottom:none; font-weight:800; font-size:1.1rem; color:var(--brand-navy); padding-top:.7rem; }

    .avatar-sm { width:32px; height:32px; border-radius:50%; object-fit:cover; border:2px solid #fff; box-shadow:0 0 0 1px #e2e8f0; }
</style>
@endpush

@section('content')

{{-- Hero card --}}
<div class="hero-card">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
        <div>
            <div class="code"><i class="bi bi-cash-stack"></i> {{ $run->run_code }}</div>
            <div class="period mt-1">{{ $run->period_label }} — فرع {{ $run->branch?->name ?? '—' }}</div>
        </div>
        <span class="badge bg-{{ $run->status_badge }}-soft" style="font-size:.9rem; padding:.55rem 1rem;">
            {{ $run->status_label }}
        </span>
    </div>

    <div class="hero-stats">
        <div class="hero-stat">
            <div class="lbl">عدد الموظفين</div>
            <div class="val">{{ number_format($run->employees_count) }}</div>
        </div>
        <div class="hero-stat">
            <div class="lbl">إجمالي المستحق</div>
            <div class="val">{{ number_format((float) $run->total_earnings, 0) }} ج.م</div>
        </div>
        <div class="hero-stat">
            <div class="lbl">إجمالي الخصومات</div>
            <div class="val">{{ number_format((float) $run->total_deductions, 0) }} ج.م</div>
        </div>
        <div class="hero-stat">
            <div class="lbl">صافي الرواتب</div>
            <div class="val">{{ number_format((float) $run->total_net, 0) }} ج.م</div>
        </div>
    </div>
</div>

{{-- Workflow steps --}}
<div class="workflow">
    @php
        $statusOrder = ['draft' => 0, 'calculated' => 1, 'approved' => 2, 'posted' => 3, 'cancelled' => -1];
        $current = $statusOrder[$run->status] ?? 0;
    @endphp

    <div class="step-pill {{ $current >= 1 ? 'done' : ($current === 0 ? 'current' : 'pending') }}">
        <i class="bi bi-{{ $current >= 1 ? 'check-circle-fill' : '1-circle' }}"></i> إنشاء
    </div>
    <i class="bi bi-chevron-left step-arrow"></i>

    <div class="step-pill {{ $current >= 2 ? 'done' : ($current === 1 ? 'current' : 'pending') }}">
        <i class="bi bi-{{ $current >= 2 ? 'check-circle-fill' : 'calculator' }}"></i> احتساب
    </div>
    <i class="bi bi-chevron-left step-arrow"></i>

    <div class="step-pill {{ $current >= 3 ? 'done' : ($current === 2 ? 'current' : 'pending') }}">
        <i class="bi bi-{{ $current >= 3 ? 'check-circle-fill' : 'patch-check' }}"></i> اعتماد
    </div>
    <i class="bi bi-chevron-left step-arrow"></i>

    <div class="step-pill {{ $run->isPosted() ? 'done' : ($current === 3 ? 'current' : 'pending') }}">
        <i class="bi bi-{{ $run->isPosted() ? 'check-circle-fill' : 'book' }}"></i> ترحيل محاسبي
    </div>

    @if($run->isCancelled())
        <div class="ms-auto step-pill" style="background:#fee2e2; color:#b91c1c;">
            <i class="bi bi-x-circle-fill"></i> ملغاة
        </div>
    @endif
</div>

{{-- Action buttons --}}
<div class="card shadow-sm mb-3">
    <div class="card-body d-flex gap-2 flex-wrap align-items-center">
        <a href="{{ route('admin.hr.payroll.runs.index') }}" class="btn btn-light">
            <i class="bi bi-arrow-right"></i> العودة للقائمة
        </a>

        @can('payroll.process')
            @if($run->canCalculate())
                <form action="{{ route('admin.hr.payroll.runs.calculate', $run) }}" method="POST" class="d-inline"
                      onsubmit="return confirm('سيتم {{ $run->isCalculated() ? 'إعادة احتساب' : 'احتساب' }} رواتب جميع موظفي الفرع. متابعة؟')">
                    @csrf
                    <button class="btn btn-primary">
                        <i class="bi bi-calculator"></i>
                        {{ $run->isCalculated() ? 'إعادة الاحتساب' : 'احسب الدورة' }}
                    </button>
                </form>
            @endif
        @endcan

        @can('payroll.approve')
            @if($run->canApprove())
                <form action="{{ route('admin.hr.payroll.runs.approve', $run) }}" method="POST" class="d-inline"
                      onsubmit="return confirm('سيتم اعتماد الدورة. لن يمكن تعديل البايسليبات بعد الاعتماد. متابعة؟')">
                    @csrf
                    <button class="btn btn-success">
                        <i class="bi bi-patch-check"></i> اعتمد الدورة
                    </button>
                </form>
            @endif

            @if($run->canPost())
                <form action="{{ route('admin.hr.payroll.runs.post', $run) }}" method="POST" class="d-inline"
                      onsubmit="return confirm('سيتم إنشاء قيد محاسبي وترحيله. هل أنت متأكد؟')">
                    @csrf
                    <button class="btn btn-success">
                        <i class="bi bi-book"></i> رحّل للمحاسبة
                    </button>
                </form>
            @endif

            @if($run->isPosted())
                <button class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#cancelModal">
                    <i class="bi bi-x-octagon"></i> إلغاء الدورة المرحلة
                </button>
            @endif
        @endcan

        @if($run->isPosted() && $run->journalEntry)
            <a href="{{ route('admin.accounting.journal.show', $run->journalEntry) }}" class="btn btn-outline-info">
                <i class="bi bi-journal-text"></i> القيد {{ $run->journalEntry->number }}
            </a>
        @endif
    </div>
</div>

{{-- Audit trail --}}
@if($run->calculated_at || $run->approved_at || $run->posted_at)
<div class="card shadow-sm mb-3">
    <div class="card-body small">
        <div class="row g-3">
            @if($run->calculator)
                <div class="col-md-4">
                    <div class="text-muted x-small">احتسبها</div>
                    <div><i class="bi bi-person-circle text-primary"></i> {{ $run->calculator->name }}</div>
                    <div class="text-muted x-small">{{ $run->calculated_at?->format('Y-m-d H:i') }}</div>
                </div>
            @endif
            @if($run->approver)
                <div class="col-md-4">
                    <div class="text-muted x-small">اعتمدها</div>
                    <div><i class="bi bi-patch-check text-success"></i> {{ $run->approver->name }}</div>
                    <div class="text-muted x-small">{{ $run->approved_at?->format('Y-m-d H:i') }}</div>
                </div>
            @endif
            @if($run->poster)
                <div class="col-md-4">
                    <div class="text-muted x-small">رحّلها</div>
                    <div><i class="bi bi-book text-success"></i> {{ $run->poster->name }}</div>
                    <div class="text-muted x-small">{{ $run->posted_at?->format('Y-m-d H:i') }}</div>
                </div>
            @endif
        </div>
    </div>
</div>
@endif

{{-- Payslips table --}}
<div class="row g-3">
    <div class="col-lg-9">
        <div class="card shadow-sm">
            <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                <h6 class="m-0"><i class="bi bi-people text-primary"></i> قسائم الرواتب ({{ $run->payslips->count() }})</h6>
            </div>
            <div class="card-body p-0">
                @if($run->payslips->isEmpty())
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-inbox" style="font-size:3rem;"></i>
                        <p class="mt-3 mb-1">لم يتم احتساب البايسليبات بعد</p>
                        <small>اضغط "احسب الدورة" لإنشاء بايسليب لكل موظف نشط في الفرع</small>
                    </div>
                @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>الموظف</th>
                                <th class="text-end">الراتب الأساسي</th>
                                <th class="text-end">البدلات</th>
                                <th class="text-end">العمولات</th>
                                <th class="text-end">الخصومات</th>
                                <th class="text-end">صافي الراتب</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($run->payslips as $slip)
                                @php
                                    $allowances = (float) $slip->housing_allowance + (float) $slip->transport_allowance + (float) $slip->other_allowances;
                                @endphp
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <img src="{{ $slip->employee?->photo ? asset('storage/' . $slip->employee->photo) : asset('admin/img/user-placeholder.png') }}" class="avatar-sm" alt="">
                                            <div>
                                                <div><strong>{{ $slip->employee?->full_name ?? '—' }}</strong></div>
                                                <div class="text-muted x-small">{{ $slip->employee?->code }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-end"><strong>{{ number_format((float) $slip->basic_salary, 2) }}</strong></td>
                                    <td class="text-end text-muted">{{ number_format($allowances, 2) }}</td>
                                    <td class="text-end">
                                        @if((float) $slip->commission_amount > 0)
                                            <span class="badge bg-warning-soft">{{ number_format((float) $slip->commission_amount, 2) }}</span>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="text-end text-danger">{{ number_format((float) $slip->total_deductions, 2) }}</td>
                                    <td class="text-end"><strong class="text-success">{{ number_format((float) $slip->net_pay, 2) }}</strong></td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-light fw-bold">
                            <tr>
                                <td>الإجمالي</td>
                                <td class="text-end">{{ number_format($run->payslips->sum('basic_salary'), 2) }}</td>
                                <td class="text-end">{{ number_format($run->payslips->sum(fn($s) => (float)$s->housing_allowance + (float)$s->transport_allowance + (float)$s->other_allowances), 2) }}</td>
                                <td class="text-end">{{ number_format($run->payslips->sum('commission_amount'), 2) }}</td>
                                <td class="text-end text-danger">{{ number_format($run->payslips->sum('total_deductions'), 2) }}</td>
                                <td class="text-end text-success">{{ number_format($run->payslips->sum('net_pay'), 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Sidebar: totals breakdown --}}
    <div class="col-lg-3">
        <div class="card shadow-sm">
            <div class="card-header bg-white border-bottom">
                <h6 class="m-0"><i class="bi bi-receipt text-primary"></i> ملخص الدورة</h6>
            </div>
            <div class="card-body p-3">
                <div class="totals-card">
                    <div class="total-row">
                        <span class="text-muted">المرتبات الأساسية</span>
                        <span>{{ number_format($run->payslips->sum('basic_salary'), 2) }}</span>
                    </div>
                    <div class="total-row">
                        <span class="text-muted">إجمالي البدلات</span>
                        <span>{{ number_format($run->payslips->sum(fn($s) => (float)$s->housing_allowance + (float)$s->transport_allowance + (float)$s->other_allowances), 2) }}</span>
                    </div>
                    <div class="total-row">
                        <span class="text-muted">العمولات</span>
                        <span>{{ number_format((float) $run->total_commissions, 2) }}</span>
                    </div>
                    <div class="total-row text-danger">
                        <span>تأمينات</span>
                        <span>({{ number_format($run->payslips->sum('social_insurance'), 2) }})</span>
                    </div>
                    <div class="total-row text-danger">
                        <span>ضريبة كسب عمل</span>
                        <span>({{ number_format($run->payslips->sum('income_tax'), 2) }})</span>
                    </div>
                    <div class="total-row text-danger">
                        <span>خصم سلف</span>
                        <span>({{ number_format($run->payslips->sum('loan_deduction'), 2) }})</span>
                    </div>
                    <div class="total-row">
                        <span>صافي الرواتب</span>
                        <span>{{ number_format((float) $run->total_net, 2) }}</span>
                    </div>
                </div>

                <div class="mt-3 small text-muted">
                    <div><i class="bi bi-calendar3"></i> دورة: {{ $run->period_label }}</div>
                    @if($run->payment_date)
                        <div><i class="bi bi-cash"></i> صرف: {{ $run->payment_date->format('Y-m-d') }}</div>
                    @endif
                    @if($run->notes)
                        <div class="mt-2 p-2 bg-light rounded x-small">{{ $run->notes }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Cancel modal --}}
@if($run->isPosted())
<div class="modal fade" id="cancelModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('admin.hr.payroll.runs.cancel', $run) }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title text-danger"><i class="bi bi-x-octagon"></i> إلغاء دورة الرواتب</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning small mb-3">
                    <i class="bi bi-exclamation-triangle"></i>
                    سيتم عكس القيد المحاسبي وتغيير حالة الدورة إلى ملغاة.
                    <strong>لن يتم عكس أقساط السلف تلقائياً.</strong>
                </div>
                <label class="form-label fw-bold">سبب الإلغاء <span class="text-danger">*</span></label>
                <textarea name="reason" rows="3" class="form-control" required minlength="3"
                          placeholder="مثال: إعادة احتساب بعد اكتشاف خطأ في رواتب الفرع..."></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">إغلاق</button>
                <button class="btn btn-danger"><i class="bi bi-x-octagon"></i> تأكيد الإلغاء</button>
            </div>
        </form>
    </div>
</div>
@endif

@endsection
