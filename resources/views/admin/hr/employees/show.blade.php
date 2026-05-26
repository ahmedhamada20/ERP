@extends('layouts.master')

@section('title', $employee->full_name)
@section('page_title', $employee->full_name)
@section('page_subtitle', 'موظف ' . $employee->code)

@push('styles')
<style>
    .emp-hero { background:linear-gradient(135deg, #1e3a8a 0%, #4338ca 100%); color:#fff; border-radius:18px; padding:1.6rem; margin-bottom:1rem; box-shadow:0 10px 25px rgba(30, 58, 138, 0.2); }
    .emp-hero .photo { width:110px; height:110px; border-radius:50%; object-fit:cover; border:4px solid rgba(255,255,255,.3); box-shadow:0 4px 14px rgba(0,0,0,.2); }
    .emp-hero h3 { margin:0; font-weight:800; font-size:1.55rem; }
    .emp-hero .meta { display:flex; gap:.6rem; margin-top:.85rem; flex-wrap:wrap; }
    .emp-hero .badge-mega { font-size:.78rem; padding:.4rem .85rem; border-radius:8px; font-weight:700; background:rgba(255,255,255,.18); color:#fff; }
    .emp-hero .badge-status { font-size:.78rem; padding:.4rem .85rem; border-radius:8px; font-weight:700; }
    .emp-hero .badge-active     { background:#bbf7d0 !important; color:#14532d !important; }
    .emp-hero .badge-on_leave   { background:#bfdbfe !important; color:#1e3a8a !important; }
    .emp-hero .badge-terminated { background:#fecaca !important; color:#7f1d1d !important; }
    .emp-hero .badge-suspended  { background:#fde68a !important; color:#78350f !important; }

    .info-card { background:#fff; border-radius:14px; box-shadow:0 1px 3px rgba(15,23,42,.04); border:1px solid var(--brand-border); overflow:hidden; margin-bottom:1rem; }
    .info-card .head { padding:.85rem 1.1rem; border-bottom:1px solid var(--brand-border); background:linear-gradient(180deg,#fafbff,#f1f5f9); }
    .info-card .head h6 { margin:0; color:var(--brand-navy); font-weight:800; }
    .info-card .body { padding:1.1rem; }
    .kv { display:flex; justify-content:space-between; padding:.5rem 0; border-bottom:1px dashed #e2e8f0; font-size:.86rem; }
    .kv:last-child { border-bottom:none; }
    .kv .k { color:#64748b; font-weight:600; }
    .kv .v { color:#0f172a; font-weight:700; text-align:end; }

    .nav-pills .nav-link { color:#475569; font-weight:600; border-radius:10px; padding:.55rem 1rem; margin-inline-end:.35rem; }
    .nav-pills .nav-link.active { background:var(--brand-navy); color:#fff; }

    .salary-row { display:flex; justify-content:space-between; padding:.6rem 0; border-bottom:1px dashed #e2e8f0; font-size:.9rem; }
    .salary-row:last-child { border-bottom:none; padding-top:.85rem; border-top:2px solid #15803d; font-size:1.05rem; font-weight:900; color:#15803d; }
    .salary-row .src { font-size:.68rem; color:#64748b; font-weight:500; margin-top:.15rem; }
    .salary-row .src.inherited { color:#b45309; }
    .salary-row .src.override  { color:#15803d; }

    .org-node { background:#fff; border:1.5px solid #e2e8f0; border-radius:12px; padding:.85rem 1rem; margin-bottom:.5rem; display:flex; align-items:center; gap:.85rem; transition:all .2s; }
    .org-node:hover { border-color:var(--brand-gold); transform:translateY(-1px); }
    .org-node img { width:42px; height:42px; border-radius:50%; object-fit:cover; }
    .org-node .info strong { display:block; color:var(--brand-navy); font-size:.92rem; }
    .org-node .info small { color:#64748b; }

    .empty-state { text-align:center; padding:2rem; color:#94a3b8; }
    .empty-state i { font-size:2.5rem; opacity:.5; }
</style>
@endpush

@section('content')

<div class="emp-hero">
    <div class="d-flex align-items-center flex-wrap gap-3">
        <img src="{{ $employee->photo_url }}" alt="" class="photo">
        <div class="flex-grow-1">
            <h3>{{ $employee->full_name }}</h3>
            <div class="meta">
                <span class="badge-mega"><i class="bi bi-hash"></i> {{ $employee->code }}</span>
                <span class="badge-status badge-{{ $employee->status }}">
                    <i class="bi bi-circle-fill" style="font-size:.55rem;"></i> {{ $employee->status_label }}
                </span>
                @if($employee->position)
                    <span class="badge-mega"><i class="bi bi-briefcase"></i> {{ $employee->position->title }}</span>
                @endif
                @if($employee->department)
                    <span class="badge-mega"><i class="bi bi-diagram-3"></i> {{ $employee->department->name }}</span>
                @endif
                @if($employee->branch)
                    <span class="badge-mega"><i class="bi bi-buildings"></i> {{ $employee->branch->name }}</span>
                @endif
                @if($employee->hire_date)
                    <span class="badge-mega"><i class="bi bi-calendar-check"></i> {{ number_format($employee->years_of_service, 1) }} سنة</span>
                @endif
            </div>
        </div>
        <div class="d-flex gap-2">
            @can('employees.update')
            <a href="{{ route('admin.hr.employees.edit', $employee) }}" class="btn btn-light">
                <i class="bi bi-pencil"></i> تعديل
            </a>
            @endcan
        </div>
    </div>
</div>

<ul class="nav nav-pills mb-3" id="empTabs" role="tablist">
    <li class="nav-item"><a class="nav-link active" data-bs-toggle="pill" href="#tab-overview"><i class="bi bi-info-circle"></i> نظرة عامة</a></li>
    @if($canSeeSalary)
    <li class="nav-item"><a class="nav-link" data-bs-toggle="pill" href="#tab-salary"><i class="bi bi-cash-coin"></i> الراتب والعمولة</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="pill" href="#tab-payment"><i class="bi bi-bank"></i> الدفع</a></li>
    @endif
    <li class="nav-item"><a class="nav-link" data-bs-toggle="pill" href="#tab-org"><i class="bi bi-diagram-3"></i> الهيكل التنظيمي</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="pill" href="#tab-docs"><i class="bi bi-file-earmark"></i> المستندات</a></li>
</ul>

<div class="tab-content">

    {{-- ════════ Overview ════════ --}}
    <div class="tab-pane fade show active" id="tab-overview">
        <div class="row g-3">
            <div class="col-lg-6">
                <div class="info-card">
                    <div class="head"><h6><i class="bi bi-person-vcard"></i> البيانات الشخصية</h6></div>
                    <div class="body">
                        <div class="kv"><span class="k">الكود</span><span class="v"><code>{{ $employee->code }}</code></span></div>
                        <div class="kv"><span class="k">الاسم</span><span class="v">{{ $employee->full_name }}</span></div>
                        @if($employee->full_name_en)
                        <div class="kv"><span class="k">بالإنجليزية</span><span class="v" dir="ltr">{{ $employee->full_name_en }}</span></div>
                        @endif
                        <div class="kv"><span class="k">الرقم القومي</span><span class="v" dir="ltr">{{ $employee->national_id ?? '—' }}</span></div>
                        <div class="kv"><span class="k">رقم الجواز</span><span class="v" dir="ltr">{{ $employee->passport_number ?? '—' }}</span></div>
                        <div class="kv"><span class="k">تاريخ الميلاد</span><span class="v">{{ $employee->birth_date?->format('Y-m-d') ?? '—' }}</span></div>
                        <div class="kv"><span class="k">النوع</span><span class="v">{{ $employee->gender === 'male' ? 'ذكر' : ($employee->gender === 'female' ? 'أنثى' : '—') }}</span></div>
                        <div class="kv"><span class="k">الجنسية</span><span class="v">{{ $employee->nationality ?? '—' }}</span></div>
                    </div>
                </div>

                <div class="info-card">
                    <div class="head"><h6><i class="bi bi-telephone"></i> التواصل</h6></div>
                    <div class="body">
                        <div class="kv"><span class="k">الهاتف</span><span class="v" dir="ltr">{{ $employee->phone ?? '—' }}</span></div>
                        <div class="kv"><span class="k">واتساب</span><span class="v" dir="ltr">{{ $employee->whatsapp ?? '—' }}</span></div>
                        <div class="kv"><span class="k">البريد</span><span class="v" dir="ltr">{{ $employee->email ?? '—' }}</span></div>
                        @if($employee->emergency_contact_name)
                        <div class="kv"><span class="k">جهة الطوارئ</span><span class="v">{{ $employee->emergency_contact_name }}</span></div>
                        <div class="kv"><span class="k">هاتف الطوارئ</span><span class="v" dir="ltr">{{ $employee->emergency_contact_phone ?? '—' }}</span></div>
                        @endif
                        @if($employee->address)
                        <div class="kv"><span class="k">العنوان</span><span class="v">{{ $employee->city ? $employee->city . ' — ' : '' }}{{ $employee->address }}</span></div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="info-card">
                    <div class="head"><h6><i class="bi bi-briefcase"></i> الوظيفة والتعيين</h6></div>
                    <div class="body">
                        <div class="kv"><span class="k">الوظيفة</span><span class="v">{{ $employee->position?->title ?? '—' }}</span></div>
                        <div class="kv"><span class="k">القسم</span><span class="v">{{ $employee->department?->name ?? '—' }}</span></div>
                        <div class="kv"><span class="k">الفرع</span><span class="v">{{ $employee->branch?->name ?? '—' }}</span></div>
                        <div class="kv">
                            <span class="k">المدير المباشر</span>
                            <span class="v">
                                @if($employee->manager)
                                    <a href="{{ route('admin.hr.employees.show', $employee->manager) }}">{{ $employee->manager->full_name }}</a>
                                @else — @endif
                            </span>
                        </div>
                        <div class="kv"><span class="k">تاريخ التعيين</span><span class="v">{{ $employee->hire_date?->format('Y-m-d') ?? '—' }}</span></div>
                        <div class="kv"><span class="k">سنوات الخدمة</span><span class="v">{{ number_format($employee->years_of_service, 1) }}</span></div>
                        <div class="kv"><span class="k">نوع التعاقد</span><span class="v">{{ $employee->employment_type_label }}</span></div>
                        @if($employee->termination_date)
                        <div class="kv"><span class="k">انتهاء الخدمة</span><span class="v text-danger">{{ $employee->termination_date->format('Y-m-d') }}</span></div>
                        @endif
                    </div>
                </div>

                <div class="info-card">
                    <div class="head"><h6><i class="bi bi-shield-lock"></i> حساب الدخول</h6></div>
                    <div class="body">
                        @if($employee->user)
                            <div class="kv"><span class="k">الاسم</span><span class="v">{{ $employee->user->name }}</span></div>
                            <div class="kv"><span class="k">البريد</span><span class="v" dir="ltr">{{ $employee->user->email }}</span></div>
                            <a href="{{ route('admin.users.edit', $employee->user) }}" class="btn btn-sm btn-outline-primary mt-2">
                                <i class="bi bi-box-arrow-up-right"></i> فتح حساب الدخول
                            </a>
                        @else
                            <div class="text-muted small">
                                <i class="bi bi-info-circle"></i> هذا الموظف غير مرتبط بحساب دخول للنظام.
                            </div>
                        @endif
                    </div>
                </div>

                @if($employee->notes)
                <div class="info-card">
                    <div class="head"><h6><i class="bi bi-sticky"></i> ملاحظات</h6></div>
                    <div class="body small">{{ $employee->notes }}</div>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ════════ Salary (gated) ════════ --}}
    @if($canSeeSalary)
    <div class="tab-pane fade" id="tab-salary">
        <div class="row g-3">
            <div class="col-lg-7">
                <div class="info-card">
                    <div class="head"><h6><i class="bi bi-cash-coin"></i> تفاصيل الراتب</h6></div>
                    <div class="body">
                        @php
                            $basicSrc     = (float) $employee->basic_salary > 0 ? 'override' : 'inherited';
                            $housingSrc   = (float) $employee->housing_allowance > 0 ? 'override' : 'inherited';
                            $transportSrc = (float) $employee->transport_allowance > 0 ? 'override' : 'inherited';
                            $otherSrc     = (float) $employee->other_allowances > 0 ? 'override' : 'inherited';
                        @endphp

                        <div class="salary-row">
                            <div>
                                <span class="k">الراتب الأساسي</span>
                                <div class="src {{ $basicSrc }}">
                                    {{ $basicSrc === 'override' ? '✓ مخصص للموظف' : 'من الوظيفة' }}
                                </div>
                            </div>
                            <span class="v">{{ number_format($employee->effectiveBasicSalary(), 2) }} ج.م</span>
                        </div>
                        <div class="salary-row">
                            <div>
                                <span class="k">بدل السكن</span>
                                <div class="src {{ $housingSrc }}">
                                    {{ $housingSrc === 'override' ? '✓ مخصص للموظف' : 'من الوظيفة' }}
                                </div>
                            </div>
                            <span class="v">{{ number_format($employee->effectiveHousingAllowance(), 2) }} ج.م</span>
                        </div>
                        <div class="salary-row">
                            <div>
                                <span class="k">بدل الانتقال</span>
                                <div class="src {{ $transportSrc }}">
                                    {{ $transportSrc === 'override' ? '✓ مخصص للموظف' : 'من الوظيفة' }}
                                </div>
                            </div>
                            <span class="v">{{ number_format($employee->effectiveTransportAllowance(), 2) }} ج.م</span>
                        </div>
                        <div class="salary-row">
                            <div>
                                <span class="k">بدلات أخرى</span>
                                <div class="src {{ $otherSrc }}">
                                    {{ $otherSrc === 'override' ? '✓ مخصص للموظف' : 'من الوظيفة' }}
                                </div>
                            </div>
                            <span class="v">{{ number_format($employee->effectiveOtherAllowances(), 2) }} ج.م</span>
                        </div>
                        <div class="salary-row">
                            <span class="k">إجمالي الراتب</span>
                            <span class="v">{{ number_format($employee->effectiveGrossSalary(), 2) }} ج.م</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="info-card">
                    <div class="head"><h6><i class="bi bi-percent"></i> العمولة</h6></div>
                    <div class="body">
                        @php
                            $rate     = $employee->effectiveCommissionRate();
                            $basis    = $employee->effectiveCommissionBasis();
                            $rateSrc  = ! is_null($employee->commission_rate) ? 'مخصصة للموظف' : 'من الوظيفة';
                        @endphp

                        @if($rate > 0)
                            <div class="kv">
                                <span class="k">النسبة</span>
                                <span class="v text-warning">{{ number_format($rate, 2) }}%</span>
                            </div>
                            <div class="kv">
                                <span class="k">المصدر</span>
                                <span class="v small">{{ $rateSrc }}</span>
                            </div>
                            <div class="kv">
                                <span class="k">الأساس</span>
                                <span class="v">{{ \App\Models\Position::COMMISSION_BASIS_LABELS[$basis] ?? $basis }}</span>
                            </div>
                        @else
                            <div class="empty-state">
                                <i class="bi bi-info-circle"></i>
                                <div class="mt-2 small">لا توجد عمولة محددة لهذا الموظف ولا لوظيفته</div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ════════ Payment ════════ --}}
    <div class="tab-pane fade" id="tab-payment">
        <div class="info-card">
            <div class="head"><h6><i class="bi bi-bank"></i> طريقة الدفع</h6></div>
            <div class="body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="kv"><span class="k">الطريقة</span><span class="v">{{ $employee->payment_method_label }}</span></div>
                        @if($employee->payment_method === 'bank_transfer')
                            <div class="kv"><span class="k">البنك</span><span class="v">{{ $employee->bank_name ?? '—' }}</span></div>
                            <div class="kv"><span class="k">رقم الحساب</span><span class="v" dir="ltr">{{ $employee->bank_account ?? '—' }}</span></div>
                            @if($employee->iban)
                            <div class="kv"><span class="k">IBAN</span><span class="v" dir="ltr">{{ $employee->iban }}</span></div>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- ════════ Org tree ════════ --}}
    <div class="tab-pane fade" id="tab-org">
        <div class="row g-3">
            <div class="col-lg-6">
                <div class="info-card">
                    <div class="head"><h6><i class="bi bi-arrow-up-circle"></i> المدير المباشر</h6></div>
                    <div class="body">
                        @if($employee->manager)
                            <a href="{{ route('admin.hr.employees.show', $employee->manager) }}" class="text-decoration-none">
                                <div class="org-node">
                                    <img src="{{ $employee->manager->photo_url }}" alt="">
                                    <div class="info">
                                        <strong>{{ $employee->manager->full_name }}</strong>
                                        <small>{{ $employee->manager->position?->title ?? '—' }} · {{ $employee->manager->code }}</small>
                                    </div>
                                </div>
                            </a>
                        @else
                            <div class="empty-state">
                                <i class="bi bi-person-x"></i>
                                <div class="mt-2 small">لا يوجد مدير مباشر</div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="info-card">
                    <div class="head"><h6><i class="bi bi-arrow-down-circle"></i> المرؤوسون ({{ $employee->subordinates->count() }})</h6></div>
                    <div class="body">
                        @forelse($employee->subordinates as $sub)
                            <a href="{{ route('admin.hr.employees.show', $sub) }}" class="text-decoration-none">
                                <div class="org-node">
                                    <img src="{{ $sub->photo_url }}" alt="">
                                    <div class="info">
                                        <strong>{{ $sub->full_name }}</strong>
                                        <small>{{ $sub->position?->title ?? '—' }} · {{ $sub->code }}</small>
                                    </div>
                                </div>
                            </a>
                        @empty
                            <div class="empty-state">
                                <i class="bi bi-people"></i>
                                <div class="mt-2 small">لا يوجد مرؤوسون مرتبطون بهذا الموظف</div>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ════════ Documents (placeholder for Sub-step 3.5) ════════ --}}
    <div class="tab-pane fade" id="tab-docs">
        <div class="info-card">
            <div class="head"><h6><i class="bi bi-file-earmark"></i> المستندات ({{ $employee->documents->count() }})</h6></div>
            <div class="body">
                @if($employee->documents->isNotEmpty())
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr><th>النوع</th><th>الاسم</th><th>تاريخ الانتهاء</th><th>الإجراء</th></tr>
                            </thead>
                            <tbody>
                                @foreach($employee->documents as $doc)
                                <tr>
                                    <td><i class="bi bi-{{ $doc->type_icon }}"></i> {{ $doc->type_label }}</td>
                                    <td>{{ $doc->title ?? '—' }}</td>
                                    <td>
                                        @if($doc->expiry_date)
                                            {{ $doc->expiry_date->format('Y-m-d') }}
                                            @if($doc->isExpired())
                                                <span class="badge bg-danger-soft x-small">منتهي</span>
                                            @elseif($doc->isExpiringSoon())
                                                <span class="badge bg-warning-soft x-small">قارب على الانتهاء</span>
                                            @endif
                                        @else — @endif
                                    </td>
                                    <td>
                                        @if($doc->file_path)
                                        <a href="{{ $doc->file_url }}" target="_blank" class="btn btn-sm btn-light">
                                            <i class="bi bi-download"></i>
                                        </a>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="empty-state">
                        <i class="bi bi-folder2-open"></i>
                        <div class="mt-2 small">لم يتم رفع أي مستندات لهذا الموظف بعد</div>
                        <div class="text-muted x-small mt-1">سيتم إضافة شاشة الرفع في الخطوة التالية</div>
                    </div>
                @endif
            </div>
        </div>
    </div>

</div>

<a href="{{ route('admin.hr.employees.index') }}" class="btn btn-outline-secondary mt-3">
    <i class="bi bi-arrow-right"></i> العودة لقائمة الموظفين
</a>

@endsection
