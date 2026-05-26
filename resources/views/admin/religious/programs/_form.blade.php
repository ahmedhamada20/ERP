@php
    /** @var \App\Models\ReligiousProgram|null $program */
    $program ??= null;
    $isEdit = $program && $program->exists;
@endphp

<style>
    /* ── Professional program form ─────────────────────────── */
    .form-wrap {
        background: linear-gradient(180deg, #f8fafc 0%, #fff 100%);
        border-radius: 18px; padding: 1.5rem;
        box-shadow: 0 2px 8px rgba(15,23,42,.05);
        margin-bottom: 1rem;
    }

    .section-card {
        background: #fff; border-radius: 14px;
        border: 1px solid #f1f5f9; margin-bottom: 1rem;
        overflow: hidden;
    }
    .section-card .head {
        padding: 1rem 1.25rem;
        background: linear-gradient(135deg, #fafbff, #f8fafc);
        border-bottom: 1px solid #f1f5f9;
        display: flex; align-items: center; gap: .75rem;
    }
    .section-card .head .sec-icon {
        width: 38px; height: 38px; border-radius: 10px;
        background: linear-gradient(135deg, #fef3c7, #fde68a);
        color: #92400e; display: flex; align-items: center; justify-content: center;
        font-size: 1.1rem; flex-shrink: 0;
    }
    .section-card .head h6 { margin: 0; color: var(--brand-navy); font-weight: 800; }
    .section-card .head .sub { font-size: .72rem; color: #64748b; margin-top: 1px; }
    .section-card .body { padding: 1.25rem; }

    .form-label { font-size: .82rem; font-weight: 700; color: #475569; margin-bottom: .4rem; }
    .form-label .req { color: #dc2626; font-weight: 900; }
    .form-label .hint { font-size: .68rem; color: #94a3b8; font-weight: 500; margin-right: .35rem; }

    .form-control, .form-select {
        height: 44px; font-size: .9rem; border-radius: 11px;
        border: 1.5px solid #e2e8f0; transition: all .15s;
    }
    .form-control:focus, .form-select:focus {
        border-color: var(--brand-gold);
        box-shadow: 0 0 0 .2rem rgba(212,164,55,.15);
    }
    textarea.form-control { height: auto; min-height: 90px; }

    /* Visual card selection (small) */
    .opt-grid {
        display: grid; gap: .6rem;
        grid-template-columns: repeat(auto-fit, minmax(125px, 1fr));
    }
    .opt-card {
        position: relative; overflow: hidden;
        background: #fff; border: 2px solid #e2e8f0;
        border-radius: 12px; padding: .85rem .55rem;
        cursor: pointer; text-align: center;
        transition: all .25s cubic-bezier(.4,0,.2,1);
        user-select: none; min-height: 78px;
        display: flex; flex-direction: column; justify-content: center; align-items: center;
    }
    .opt-card:hover {
        border-color: var(--brand-gold);
        transform: translateY(-2px);
        box-shadow: 0 6px 14px rgba(15,23,42,.08);
    }
    .opt-card input[type=radio] { position: absolute; opacity: 0; pointer-events: none; }
    .opt-card .ic {
        font-size: 1.35rem; color: #94a3b8; margin-bottom: .3rem;
        width: 38px; height: 38px; border-radius: 10px;
        background: #f8fafc;
        display: inline-flex; align-items: center; justify-content: center;
        transition: all .25s;
    }
    .opt-card .tt { font-weight: 800; font-size: .82rem; color: #1f2937; }
    .opt-card.selected {
        border-color: var(--brand-gold);
        background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
        box-shadow: 0 6px 16px rgba(212,164,55,.25);
        transform: translateY(-2px);
    }
    .opt-card.selected .ic {
        color: #fff;
        background: linear-gradient(135deg, #d4a437, #b8860b);
        box-shadow: 0 4px 10px rgba(212,164,55,.4);
        transform: scale(1.05);
    }
    .opt-card.selected .tt { color: #92400e; }
    .opt-card.selected::after {
        content: '\F26B'; font-family: 'bootstrap-icons';
        position: absolute; top: 5px; left: 7px;
        color: #fff; background: #15803d;
        width: 18px; height: 18px; border-radius: 50%;
        font-size: .65rem; font-weight: 900;
        display: flex; align-items: center; justify-content: center;
        box-shadow: 0 2px 4px rgba(21,128,61,.35);
        animation: pgPop .3s cubic-bezier(.4,0,.2,1);
    }
    @keyframes pgPop {
        0% { transform: scale(0); }
        70% { transform: scale(1.25); }
        100% { transform: scale(1); }
    }

    /* Type cards (big) */
    .type-grid { display: grid; gap: 1rem; grid-template-columns: 1fr 1fr; }
    .type-card {
        position: relative; overflow: hidden;
        background: #fff; border: 2px solid #e2e8f0;
        border-radius: 16px; padding: 1.4rem 1rem;
        text-align: center;
        cursor: pointer; transition: all .3s cubic-bezier(.4,0,.2,1);
    }
    .type-card::before {
        content: ''; position: absolute; inset: 0;
        background: linear-gradient(135deg, rgba(212,164,55,0) 0%, rgba(212,164,55,.06) 100%);
        opacity: 0; transition: opacity .3s; pointer-events: none;
    }
    .type-card:hover {
        transform: translateY(-4px); border-color: var(--brand-gold);
        box-shadow: 0 12px 28px rgba(15,23,42,.10);
    }
    .type-card:hover::before { opacity: 1; }
    .type-card input { position: absolute; opacity: 0; pointer-events: none; }
    .type-card .big-icon {
        font-size: 2.4rem;
        background: linear-gradient(135deg, #f1f5f9, #e2e8f0);
        color: #94a3b8;
        width: 76px; height: 76px; border-radius: 20px;
        display: flex; align-items: center; justify-content: center;
        margin: 0 auto .75rem;
        transition: all .3s cubic-bezier(.4,0,.2,1);
        line-height: 1;
    }
    .type-card .ti {
        font-size: 1.15rem; font-weight: 900; color: var(--brand-navy);
        margin-top: .25rem;
    }
    .type-card .td { font-size: .78rem; color: #64748b; margin-top: .3rem; }
    .type-card.selected {
        border-color: var(--brand-gold);
        background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
        box-shadow: 0 10px 28px rgba(212,164,55,.30);
        transform: translateY(-3px);
    }
    .type-card.selected .big-icon {
        background: linear-gradient(135deg, #d4a437, #b45309);
        color: #fff;
        box-shadow: 0 8px 18px rgba(212,164,55,.45);
        transform: scale(1.05) rotate(-3deg);
    }
    .type-card.selected .ti { color: #92400e; }
    .type-card.selected::after {
        content: '\F26B'; font-family: 'bootstrap-icons';
        position: absolute; top: 10px; left: 12px;
        color: #fff; background: #15803d;
        width: 28px; height: 28px; border-radius: 50%;
        font-size: 1rem; font-weight: 900;
        display: flex; align-items: center; justify-content: center;
        box-shadow: 0 4px 10px rgba(21,128,61,.35);
        animation: pgPop .4s cubic-bezier(.4,0,.2,1);
    }

    /* Cover upload */
    .cover-wrap {
        position: relative;
        border: 2px dashed #cbd5e1;
        border-radius: 12px;
        background: #f8fafc;
        padding: 1rem; text-align: center;
        transition: all .2s;
    }
    .cover-wrap:hover { border-color: var(--brand-gold); background: #fffbeb; }
    .cover-preview {
        width: 100%; max-width: 240px;
        aspect-ratio: 16/10; border-radius: 10px;
        object-fit: cover; border: 1px solid #e2e8f0;
        background: #f1f5f9; margin: 0 auto .65rem;
        display: block;
    }
    .cover-wrap .file-input-wrap {
        background: var(--brand-navy); color: #fff;
        padding: .55rem 1.25rem; border-radius: 10px;
        display: inline-block; cursor: pointer;
        font-weight: 700; font-size: .85rem;
        transition: background .15s;
    }
    .cover-wrap .file-input-wrap:hover { background: var(--brand-navy-2); }
    .cover-wrap input[type=file] {
        position: absolute; opacity: 0; pointer-events: none;
    }

    /* Toggle switch enhanced */
    .toggle-card {
        display: flex; align-items: center; gap: .85rem;
        background: #f8fafc; border: 1.5px solid #e2e8f0;
        padding: .85rem 1rem; border-radius: 11px;
        cursor: pointer; transition: all .2s;
    }
    .toggle-card:hover { background: #f1f5f9; }
    .toggle-card.active {
        background: #ecfdf5; border-color: #86efac;
    }
    .toggle-card .form-check-input {
        margin: 0; flex-shrink: 0;
        width: 2.5em; height: 1.4em;
    }
    .toggle-card .toggle-meta { flex: 1; }
    .toggle-card .toggle-meta strong { font-size: .9rem; color: var(--brand-navy); }
    .toggle-card .toggle-meta div { font-size: .72rem; color: #64748b; }

    /* Form footer (sticky on desktop) */
    .form-footer {
        background: #fff; border-top: 1px solid #f1f5f9;
        padding: 1rem 1.25rem;
        border-radius: 0 0 14px 14px;
        display: flex; justify-content: flex-end; gap: .65rem;
        flex-wrap: wrap;
    }
    .form-footer .btn { min-width: 140px; }

    .meta-card {
        background: #eef2ff; border-radius: 12px;
        padding: 1rem; font-size: .82rem;
        border-right: 4px solid #4338ca;
    }
    .meta-card .meta-kv { display: flex; justify-content: space-between; padding: .35rem 0; border-bottom: 1px dashed #c7d2fe; }
    .meta-card .meta-kv:last-child { border-bottom: none; }
    .meta-card .meta-kv .k { color: #6366f1; font-weight: 600; }
    .meta-card .meta-kv .v { color: #1e293b; font-weight: 700; }

    @media (max-width: 768px) {
        .form-wrap { padding: 1rem; }
        .section-card .body { padding: 1rem; }
        .type-grid { grid-template-columns: 1fr; }
        .opt-grid { grid-template-columns: repeat(2, 1fr); }
    }
</style>

<div class="form-wrap">
    <div class="row g-3">
        <div class="col-lg-8">
            {{-- ── Section 1: Type & Basic info ──────────── --}}
            <div class="section-card">
                <div class="head">
                    <div class="sec-icon"><i class="bi bi-info-circle"></i></div>
                    <div>
                        <h6>البيانات الأساسية</h6>
                        <div class="sub">نوع البرنامج واسمه والموسم</div>
                    </div>
                </div>
                <div class="body">
                    <div class="mb-3">
                        <label class="form-label">نوع البرنامج <span class="req">*</span></label>
                        <div class="type-grid" data-options="type">
                            <label class="type-card {{ old('type', $program?->type ?? '') === 'umrah' ? 'selected' : '' }}">
                                <input type="radio" name="type" value="umrah" {{ old('type', $program?->type ?? '') === 'umrah' ? 'checked' : '' }} required>
                                <div class="big-icon"><i class="bi bi-moon-stars"></i></div>
                                <div class="ti">عمرة</div>
                                <div class="td">برنامج عمرة 10-14 يوم</div>
                            </label>
                            <label class="type-card {{ old('type', $program?->type ?? '') === 'hajj' ? 'selected' : '' }}">
                                <input type="radio" name="type" value="hajj" {{ old('type', $program?->type ?? '') === 'hajj' ? 'checked' : '' }} required>
                                <div class="big-icon" style="font-family:'Apple Color Emoji','Segoe UI Emoji','Noto Color Emoji',sans-serif;">🕋</div>
                                <div class="ti">حج</div>
                                <div class="td">برنامج حج 21-25 يوم</div>
                            </label>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">اسم البرنامج <span class="req">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                   placeholder="مثال: عمرة رمضان VIP 2026"
                                   value="{{ old('name', $program?->name ?? '') }}" required>
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">الاسم بالإنجليزية <span class="hint">للنشر في الموقع</span></label>
                            <input type="text" name="name_en" class="form-control" dir="ltr"
                                   placeholder="Ramadan Umrah VIP 2026"
                                   value="{{ old('name_en', $program?->name_en ?? '') }}">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">الموسم</label>
                            <input type="text" name="season" class="form-control" placeholder="2026-Ramadan"
                                   value="{{ old('season', $program?->season ?? '') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">تاريخ البداية</label>
                            <input type="date" name="start_date" class="form-control"
                                   value="{{ old('start_date', $program?->start_date?->format('Y-m-d') ?? '') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">تاريخ النهاية</label>
                            <input type="date" name="end_date" class="form-control"
                                   value="{{ old('end_date', $program?->end_date?->format('Y-m-d') ?? '') }}">
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">
                                مدة الرحلة (بالأيام) <span class="req">*</span>
                                <span class="hint">إجمالي عدد الأيام بما فيها يوم الوصول والمغادرة</span>
                            </label>
                            <input type="number" name="duration_days" min="1" max="90"
                                   class="form-control @error('duration_days') is-invalid @enderror"
                                   value="{{ old('duration_days', $program?->duration_days ?? 10) }}" required>
                            @error('duration_days') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── Section 2: Defaults ───────────────────── --}}
            <div class="section-card">
                <div class="head">
                    <div class="sec-icon" style="background:linear-gradient(135deg,#dbeafe,#bfdbfe); color:#1d4ed8;">
                        <i class="bi bi-gear"></i>
                    </div>
                    <div>
                        <h6>الإعدادات الافتراضية</h6>
                        <div class="sub">هذه الإعدادات تُملأ تلقائياً عند إنشاء حجز من هذا البرنامج</div>
                    </div>
                </div>
                <div class="body">
                    <div class="mb-3">
                        <label class="form-label">نوع التأشيرة <span class="req">*</span></label>
                        <div class="opt-grid" data-options="default_visa_type">
                            @foreach(['standard'=>['عادية','passport'],'haram'=>['حرم','mosque'],'kaaba'=>['كعبة','star']] as $v=>$info)
                            <label class="opt-card {{ old('default_visa_type', $program?->default_visa_type ?? 'standard') === $v ? 'selected' : '' }}">
                                <input type="radio" name="default_visa_type" value="{{ $v }}" {{ old('default_visa_type', $program?->default_visa_type ?? 'standard') === $v ? 'checked' : '' }} required>
                                <div class="ic"><i class="bi bi-{{ $info[1] }}"></i></div>
                                <div class="tt">{{ $info[0] }}</div>
                            </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">مستوى السكن <span class="req">*</span></label>
                        <div class="opt-grid" data-options="default_accommodation_grade">
                            @foreach(['economy'=>['اقتصادي','star-half'],'4_stars'=>['4 نجوم','star'],'5_stars'=>['5 نجوم','stars']] as $v=>$info)
                            <label class="opt-card {{ old('default_accommodation_grade', $program?->default_accommodation_grade ?? 'economy') === $v ? 'selected' : '' }}">
                                <input type="radio" name="default_accommodation_grade" value="{{ $v }}" {{ old('default_accommodation_grade', $program?->default_accommodation_grade ?? 'economy') === $v ? 'checked' : '' }} required>
                                <div class="ic"><i class="bi bi-{{ $info[1] }}"></i></div>
                                <div class="tt">{{ $info[0] }}</div>
                            </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">وسيلة النقل <span class="req">*</span></label>
                            <div class="opt-grid" data-options="default_transport_type">
                                @foreach(['flight'=>['طيران','airplane'],'bus'=>['باص','bus-front'],'train'=>['قطار','train-front'],'vip'=>['VIP','car-front']] as $v=>$info)
                                <label class="opt-card {{ old('default_transport_type', $program?->default_transport_type ?? 'flight') === $v ? 'selected' : '' }}">
                                    <input type="radio" name="default_transport_type" value="{{ $v }}" {{ old('default_transport_type', $program?->default_transport_type ?? 'flight') === $v ? 'checked' : '' }} required>
                                    <div class="ic"><i class="bi bi-{{ $info[1] }}"></i></div>
                                    <div class="tt">{{ $info[0] }}</div>
                                </label>
                                @endforeach
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">نظام الإقامة <span class="req">*</span></label>
                            <div class="opt-grid" data-options="default_meal_plan">
                                <label class="opt-card {{ old('default_meal_plan', $program?->default_meal_plan ?? 'hp') === 'hp' ? 'selected' : '' }}">
                                    <input type="radio" name="default_meal_plan" value="hp" {{ old('default_meal_plan', $program?->default_meal_plan ?? 'hp') === 'hp' ? 'checked' : '' }} required>
                                    <div class="ic"><i class="bi bi-cup-hot"></i></div>
                                    <div class="tt">H.P</div>
                                </label>
                                <label class="opt-card {{ old('default_meal_plan', $program?->default_meal_plan ?? '') === 'pp' ? 'selected' : '' }}">
                                    <input type="radio" name="default_meal_plan" value="pp" {{ old('default_meal_plan', $program?->default_meal_plan ?? '') === 'pp' ? 'checked' : '' }} required>
                                    <div class="ic"><i class="bi bi-cup-straw"></i></div>
                                    <div class="tt">P.P</div>
                                </label>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label">مستوى المطوف <span class="req">*</span></label>
                            <div class="opt-grid" data-options="default_mutawif_grade">
                                @foreach(['economy'=>['اقتصادي','star-half'],'land'=>['بري','signpost-2'],'5_stars'=>['5 نجوم','stars']] as $v=>$info)
                                <label class="opt-card {{ old('default_mutawif_grade', $program?->default_mutawif_grade ?? 'economy') === $v ? 'selected' : '' }}">
                                    <input type="radio" name="default_mutawif_grade" value="{{ $v }}" {{ old('default_mutawif_grade', $program?->default_mutawif_grade ?? 'economy') === $v ? 'checked' : '' }} required>
                                    <div class="ic"><i class="bi bi-{{ $info[1] }}"></i></div>
                                    <div class="tt">{{ $info[0] }}</div>
                                </label>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── Section 3: Pricing & Capacity ────────── --}}
            <div class="section-card">
                <div class="head">
                    <div class="sec-icon" style="background:linear-gradient(135deg,#fef3c7,#fde68a); color:#92400e;">
                        <i class="bi bi-cash-stack"></i>
                    </div>
                    <div>
                        <h6>التسعير والطاقة الاستيعابية</h6>
                        <div class="sub">السعر الأساسي والحد الأدنى والأقصى للمعتمرين</div>
                    </div>
                </div>
                <div class="body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">
                                السعر الأساسي للفرد <span class="req">*</span>
                                <span class="hint">يمكن تعديله لكل حجز على حدة</span>
                            </label>
                            <div class="input-group input-group-lg">
                                <input type="number" name="base_price_per_person" min="0" step="0.01"
                                       class="form-control form-control-lg @error('base_price_per_person') is-invalid @enderror"
                                       style="font-size:1.3rem; font-weight:800; color:var(--brand-navy);"
                                       value="{{ old('base_price_per_person', $program?->base_price_per_person ?? '') }}" required>
                                <span class="input-group-text" style="background:#fef3c7; color:#92400e; font-weight:800;">ج.م</span>
                            </div>
                            @error('base_price_per_person') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">الحد الأدنى للمعتمرين <span class="req">*</span></label>
                            <input type="number" name="min_pilgrims" min="1" max="1000"
                                   class="form-control" value="{{ old('min_pilgrims', $program?->min_pilgrims ?? 1) }}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">الحد الأقصى <span class="req">*</span></label>
                            <input type="number" name="max_pilgrims" min="1" max="5000"
                                   class="form-control" value="{{ old('max_pilgrims', $program?->max_pilgrims ?? 100) }}" required>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── Section 4: Description ──────────────── --}}
            <div class="section-card">
                <div class="head">
                    <div class="sec-icon" style="background:linear-gradient(135deg,#f3e8ff,#e9d5ff); color:#6b21a8;">
                        <i class="bi bi-card-text"></i>
                    </div>
                    <div>
                        <h6>التفاصيل والمحتوى</h6>
                        <div class="sub">شرح البرنامج وما يشمله وما لا يشمله</div>
                    </div>
                </div>
                <div class="body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label"><i class="bi bi-check-circle text-success"></i> ما يشمله البرنامج</label>
                            <textarea name="inclusions" rows="4" class="form-control" placeholder="• السكن في مكة والمدينة&#10;• النقل بين المدن&#10;• الإفطار والعشاء&#10;• الإشراف الديني">{{ old('inclusions', $program?->inclusions ?? '') }}</textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><i class="bi bi-x-circle text-danger"></i> ما لا يشمله البرنامج</label>
                            <textarea name="exclusions" rows="4" class="form-control" placeholder="• الوجبات الإضافية&#10;• الإكراميات&#10;• المصاريف الشخصية">{{ old('exclusions', $program?->exclusions ?? '') }}</textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">وصف البرنامج</label>
                            <textarea name="description" rows="3" class="form-control" placeholder="وصف موجز يظهر للعملاء...">{{ old('description', $program?->description ?? '') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ────────────────────────────────────────────── --}}
        <div class="col-lg-4">
            {{-- Cover image --}}
            <div class="section-card">
                <div class="head">
                    <div class="sec-icon" style="background:linear-gradient(135deg,#ccfbf1,#a7f3d0); color:#0f766e;">
                        <i class="bi bi-image"></i>
                    </div>
                    <div>
                        <h6>صورة البرنامج</h6>
                        <div class="sub">تظهر في الكتالوج</div>
                    </div>
                </div>
                <div class="body">
                    <div class="cover-wrap">
                        <img id="coverPreview" class="cover-preview"
                             src="{{ $isEdit ? $program->cover_url : '' }}"
                             onerror="this.src='data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 200 125%22><rect width=%22100%25%22 height=%22100%25%22 fill=%22%23eef2ff%22/><text x=%2250%25%22 y=%2250%25%22 text-anchor=%22middle%22 dominant-baseline=%22middle%22 font-family=%22Cairo%22 font-size=%2214%22 fill=%22%231e3a8a%22>📿 صورة البرنامج</text></svg>'">
                        <label class="file-input-wrap">
                            <i class="bi bi-cloud-upload"></i> اختر صورة
                            <input type="file" name="cover_image" accept="image/*" onchange="previewImage(this, '#coverPreview')">
                        </label>
                        <div class="small text-muted mt-2">JPG / PNG / WEBP — حد أقصى 4MB</div>
                    </div>
                </div>
            </div>

            {{-- Publishing toggles --}}
            <div class="section-card">
                <div class="head">
                    <div class="sec-icon" style="background:linear-gradient(135deg,#dcfce7,#bbf7d0); color:#15803d;">
                        <i class="bi bi-broadcast"></i>
                    </div>
                    <div>
                        <h6>حالة النشر</h6>
                        <div class="sub">التحكم في ظهور البرنامج</div>
                    </div>
                </div>
                <div class="body">
                    <label class="toggle-card mb-2 {{ old('is_active', $program?->is_active ?? true) ? 'active' : '' }}" id="activeLabel">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" id="is_active" class="form-check-input"
                               role="switch" @checked(old('is_active', $program?->is_active ?? true))>
                        <div class="toggle-meta">
                            <strong>نشط داخلياً</strong>
                            <div>يظهر في قائمة اختيار البرامج عند إنشاء حجز</div>
                        </div>
                    </label>

                    <label class="toggle-card {{ old('is_published', $program?->is_published ?? false) ? 'active' : '' }}" id="publishedLabel">
                        <input type="hidden" name="is_published" value="0">
                        <input type="checkbox" name="is_published" value="1" id="is_published" class="form-check-input"
                               role="switch" @checked(old('is_published', $program?->is_published ?? false))>
                        <div class="toggle-meta">
                            <strong>منشور للعملاء</strong>
                            <div>متاح للحجز عبر الموقع الخارجي</div>
                        </div>
                    </label>
                </div>
            </div>

            @if($isEdit)
            <div class="section-card">
                <div class="head">
                    <div class="sec-icon" style="background:linear-gradient(135deg,#e0e7ff,#c7d2fe); color:#4338ca;">
                        <i class="bi bi-hash"></i>
                    </div>
                    <div>
                        <h6>معلومات النظام</h6>
                        <div class="sub">بيانات الإنشاء والتعديل</div>
                    </div>
                </div>
                <div class="body">
                    <div class="meta-card">
                        <div class="meta-kv">
                            <span class="k">كود البرنامج</span>
                            <span class="v" dir="ltr"><code>{{ $program->code }}</code></span>
                        </div>
                        <div class="meta-kv">
                            <span class="k">تاريخ الإنشاء</span>
                            <span class="v">{{ $program->created_at?->format('Y-m-d') }}</span>
                        </div>
                        <div class="meta-kv">
                            <span class="k">آخر تعديل</span>
                            <span class="v">{{ $program->updated_at?->diffForHumans() }}</span>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>

    {{-- Form footer --}}
    <div class="form-footer">
        <a href="{{ route('admin.religious.programs.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-x-circle"></i> إلغاء
        </a>
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-circle"></i> {{ $isEdit ? 'حفظ التعديلات' : 'إنشاء البرنامج' }}
        </button>
    </div>
</div>

@push('scripts')
<script>
function previewImage(input, target) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => document.querySelector(target).src = e.target.result;
        reader.readAsDataURL(input.files[0]);
    }
}

$(function () {
    // Visual card selection (radio)
    $(document).on('click', '.opt-card, .type-card', function () {
        const $input = $(this).find('input[type=radio]');
        if (!$input.length) return;
        $(this).siblings().removeClass('selected');
        $(this).addClass('selected');
        $input.prop('checked', true).trigger('change');
    });

    // Toggle card visual state
    $('#is_active').on('change', function () {
        $('#activeLabel').toggleClass('active', $(this).is(':checked'));
    });
    $('#is_published').on('change', function () {
        $('#publishedLabel').toggleClass('active', $(this).is(':checked'));
    });
});
</script>
@endpush
