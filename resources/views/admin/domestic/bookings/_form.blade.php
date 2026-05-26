@php
    /** @var \App\Models\DomesticBooking|null $booking */
    $booking ??= null;
    $isEdit = $booking && $booking->exists;
@endphp

<style>
    .form-wrap { background: linear-gradient(180deg, #f8fafc 0%, #fff 100%); border-radius: 18px; padding: 1.5rem; box-shadow: 0 2px 8px rgba(15,23,42,.05); margin-bottom: 1rem; }
    .section-card { background: #fff; border-radius: 14px; border: 1px solid #f1f5f9; margin-bottom: 1rem; overflow: hidden; }
    .section-card .head { padding: 1rem 1.25rem; background: linear-gradient(135deg, #fafbff, #f8fafc); border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; gap: .75rem; }
    .section-card .head .sec-icon { width: 38px; height: 38px; border-radius: 10px; background: linear-gradient(135deg, #dbeafe, #bfdbfe); color: #1d4ed8; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; flex-shrink: 0; }
    .section-card .head h6 { margin: 0; color: var(--brand-navy); font-weight: 800; }
    .section-card .head .sub { font-size: .72rem; color: #64748b; margin-top: 1px; }
    .section-card .body { padding: 1.25rem; }
    .form-label { font-size: .82rem; font-weight: 700; color: #475569; margin-bottom: .4rem; }
    .form-label .req { color: #dc2626; font-weight: 900; }
    .form-label .hint { font-size: .68rem; color: #94a3b8; font-weight: 500; margin-right: .35rem; }
    .form-control, .form-select { height: 44px; font-size: .9rem; border-radius: 11px; border: 1.5px solid #e2e8f0; transition: all .15s; }
    .form-control:focus, .form-select:focus { border-color: var(--brand-gold); box-shadow: 0 0 0 .2rem rgba(212,164,55,.15); }
    textarea.form-control { height: auto; min-height: 90px; }

    .opt-grid { display: grid; gap: .6rem; grid-template-columns: repeat(auto-fit, minmax(115px, 1fr)); }
    .opt-card { position: relative; overflow: hidden; background: #fff; border: 2px solid #e2e8f0; border-radius: 11px; padding: .75rem .5rem; cursor: pointer; text-align: center; transition: all .25s; user-select: none; min-height: 70px; display: flex; flex-direction: column; justify-content: center; align-items: center; }
    .opt-card:hover { border-color: var(--brand-gold); transform: translateY(-2px); box-shadow: 0 6px 14px rgba(15,23,42,.08); }
    .opt-card input[type=radio] { position: absolute; opacity: 0; pointer-events: none; }
    .opt-card .ic { font-size: 1.1rem; color: #94a3b8; margin-bottom: .2rem; width: 32px; height: 32px; border-radius: 9px; background: #f8fafc; display: inline-flex; align-items: center; justify-content: center; transition: all .25s; }
    .opt-card .tt { font-weight: 800; font-size: .76rem; color: #1f2937; }
    .opt-card.selected { border-color: var(--brand-gold); background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%); box-shadow: 0 6px 16px rgba(212,164,55,.25); transform: translateY(-2px); }
    .opt-card.selected .ic { color: #fff; background: linear-gradient(135deg, #d4a437, #b8860b); transform: scale(1.05); }
    .opt-card.selected .tt { color: #92400e; }
    .opt-card.selected::after { content: '\F26B'; font-family: 'bootstrap-icons'; position: absolute; top: 3px; left: 5px; color: #fff; background: #15803d; width: 16px; height: 16px; border-radius: 50%; font-size: .6rem; font-weight: 900; display: flex; align-items: center; justify-content: center; }

    .type-grid { display: grid; gap: .8rem; grid-template-columns: repeat(3, 1fr); }
    .type-card { position: relative; overflow: hidden; background: #fff; border: 2px solid #e2e8f0; border-radius: 14px; padding: 1rem .7rem; text-align: center; cursor: pointer; transition: all .25s; }
    .type-card:hover { transform: translateY(-3px); border-color: var(--brand-gold); box-shadow: 0 10px 24px rgba(15,23,42,.10); }
    .type-card input { position: absolute; opacity: 0; pointer-events: none; }
    .type-card .big-icon { font-size: 1.6rem; background: linear-gradient(135deg, #f1f5f9, #e2e8f0); color: #94a3b8; width: 52px; height: 52px; border-radius: 14px; display: flex; align-items: center; justify-content: center; margin: 0 auto .5rem; transition: all .25s; }
    .type-card .ti { font-size: .88rem; font-weight: 900; color: var(--brand-navy); }
    .type-card.selected { border-color: var(--brand-gold); background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%); }
    .type-card.selected .big-icon { background: linear-gradient(135deg, #d4a437, #b45309); color: #fff; transform: scale(1.05) rotate(-3deg); }
    .type-card.selected .ti { color: #92400e; }
    .type-card.selected::after { content: '\F26B'; font-family: 'bootstrap-icons'; position: absolute; top: 6px; left: 8px; color: #fff; background: #15803d; width: 20px; height: 20px; border-radius: 50%; font-size: .7rem; font-weight: 900; display: flex; align-items: center; justify-content: center; }

    .form-footer { background: #fff; border-top: 1px solid #f1f5f9; padding: 1rem 1.25rem; border-radius: 0 0 14px 14px; display: flex; justify-content: flex-end; gap: .65rem; flex-wrap: wrap; }
    .form-footer .btn { min-width: 140px; }

    .price-input { font-size: 1.3rem !important; font-weight: 800 !important; color: var(--brand-navy) !important; }
    .meta-card { background: #eef2ff; border-radius: 12px; padding: 1rem; font-size: .82rem; border-right: 4px solid #4338ca; }
    .meta-card .meta-kv { display: flex; justify-content: space-between; padding: .35rem 0; border-bottom: 1px dashed #c7d2fe; }
    .meta-card .meta-kv:last-child { border-bottom: none; }
    .meta-card .meta-kv .k { color: #6366f1; font-weight: 600; }
    .meta-card .meta-kv .v { color: #1e293b; font-weight: 700; }

    @media (max-width: 768px) {
        .form-wrap { padding: 1rem; }
        .section-card .body { padding: 1rem; }
        .type-grid { grid-template-columns: 1fr 1fr; }
        .opt-grid { grid-template-columns: repeat(2, 1fr); }
    }
</style>

<div class="form-wrap">
    <div class="row g-3">
        <div class="col-lg-8">
            {{-- Section 1: Customer + Program --}}
            <div class="section-card">
                <div class="head">
                    <div class="sec-icon"><i class="bi bi-person-vcard"></i></div>
                    <div>
                        <h6>العميل والبرنامج</h6>
                        <div class="sub">اختر العميل والبرنامج (اختياري)</div>
                    </div>
                </div>
                <div class="body">
                    <div class="row g-3">
                        <div class="col-md-7">
                            <label class="form-label">العميل <span class="req">*</span></label>
                            <select name="customer_id" class="form-select select2 @error('customer_id') is-invalid @enderror" required>
                                <option value="">— اختر عميل —</option>
                                @foreach($customers as $c)
                                    <option value="{{ $c->id }}" {{ old('customer_id', $booking?->customer_id ?? request('customer_id')) == $c->id ? 'selected' : '' }}>
                                        {{ $c->full_name }} — {{ $c->phone }} ({{ $c->code }})
                                    </option>
                                @endforeach
                            </select>
                            @error('customer_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">البرنامج <span class="hint">اختياري — يُملأ الإعدادات تلقائياً</span></label>
                            <select name="program_id" id="programSelect" class="form-select select2">
                                <option value="">— بدون برنامج —</option>
                                @foreach($programs as $p)
                                    <option value="{{ $p->id }}"
                                        data-type="{{ $p->type }}"
                                        data-city="{{ $p->destination_city }}"
                                        data-days="{{ $p->duration_days }}"
                                        data-nights="{{ $p->duration_nights }}"
                                        data-grade="{{ $p->default_accommodation_grade }}"
                                        data-transport="{{ $p->default_transport_type }}"
                                        data-meal="{{ $p->default_meal_plan }}"
                                        data-price="{{ $p->base_price_per_person }}"
                                        {{ old('program_id', $booking?->program_id ?? '') == $p->id ? 'selected' : '' }}>
                                        {{ $p->code }} — {{ $p->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Section 2: Type --}}
            <div class="section-card">
                <div class="head">
                    <div class="sec-icon" style="background:linear-gradient(135deg,#fef3c7,#fde68a); color:#b45309;">
                        <i class="bi bi-tag"></i>
                    </div>
                    <div>
                        <h6>نوع الرحلة</h6>
                        <div class="sub">اختر طبيعة الحجز</div>
                    </div>
                </div>
                <div class="body">
                    <div class="type-grid" data-options="type">
                        @php
                            $currentType = old('type', $booking?->type ?? 'package');
                            $types = [
                                'package'    => ['باكدج كامل',  'bag-check'],
                                'hotel_only' => ['إقامة فندقية', 'building'],
                                'day_trip'   => ['رحلة يوم',     'sun'],
                                'cruise'     => ['نيلية/بحرية',  'water'],
                                'camp'       => ['مخيم',         'tree'],
                                'event'      => ['فعالية',       'calendar-event'],
                            ];
                        @endphp
                        @foreach($types as $v => $info)
                        <label class="type-card {{ $currentType === $v ? 'selected' : '' }}">
                            <input type="radio" name="type" value="{{ $v }}" {{ $currentType === $v ? 'checked' : '' }} required>
                            <div class="big-icon"><i class="bi bi-{{ $info[1] }}"></i></div>
                            <div class="ti">{{ $info[0] }}</div>
                        </label>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Section 3: Destination + Dates --}}
            <div class="section-card">
                <div class="head">
                    <div class="sec-icon" style="background:linear-gradient(135deg,#fee2e2,#fecaca); color:#b91c1c;">
                        <i class="bi bi-geo-alt"></i>
                    </div>
                    <div>
                        <h6>الوجهة والتواريخ</h6>
                        <div class="sub">المدينة، الفندق، تواريخ السفر</div>
                    </div>
                </div>
                <div class="body">
                    <div class="row g-3">
                        <div class="col-md-5">
                            <label class="form-label">المدينة <span class="req">*</span></label>
                            <input type="text" name="destination_city" id="destCity" class="form-control @error('destination_city') is-invalid @enderror"
                                   list="cityList" placeholder="مثال: الغردقة"
                                   value="{{ old('destination_city', $booking?->destination_city ?? '') }}" required>
                            <datalist id="cityList">
                                <option value="الغردقة"><option value="شرم الشيخ"><option value="مرسى علم"><option value="دهب">
                                <option value="الإسكندرية"><option value="مرسى مطروح"><option value="الساحل الشمالي"><option value="رأس البر">
                                <option value="الأقصر"><option value="أسوان"><option value="القاهرة"><option value="السخنة">
                                <option value="رأس سدر"><option value="نويبع"><option value="طابا"><option value="سيوة">
                            </datalist>
                            @error('destination_city') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">المنطقة <span class="hint">اختياري</span></label>
                            <input type="text" name="destination_area" class="form-control"
                                   placeholder="السهل / مارينا"
                                   value="{{ old('destination_area', $booking?->destination_area ?? '') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">الفندق <span class="hint">اختياري</span></label>
                            <select name="hotel_id" class="form-select select2">
                                <option value="">— بدون فندق محدد —</option>
                                @foreach($hotels as $h)
                                    <option value="{{ $h->id }}" {{ old('hotel_id', $booking?->hotel_id ?? '') == $h->id ? 'selected' : '' }}>
                                        {{ $h->name }} @if($h->city) — {{ $h->city }}@endif
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">تاريخ الحجز <span class="req">*</span></label>
                            <input type="date" name="booking_date" class="form-control"
                                   value="{{ old('booking_date', $booking?->booking_date?->format('Y-m-d') ?? now()->toDateString()) }}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">تاريخ الوصول <span class="req">*</span></label>
                            <input type="date" name="trip_date" id="tripDate" class="form-control"
                                   value="{{ old('trip_date', $booking?->trip_date?->format('Y-m-d') ?? '') }}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">تاريخ المغادرة</label>
                            <input type="date" name="return_date" id="returnDate" class="form-control"
                                   value="{{ old('return_date', $booking?->return_date?->format('Y-m-d') ?? '') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">المدة (أيام) <span class="req">*</span></label>
                            <input type="number" name="duration_days" id="durationDays" min="1" max="90"
                                   class="form-control" value="{{ old('duration_days', $booking?->duration_days ?? 3) }}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">عدد الليالي</label>
                            <input type="number" name="duration_nights" id="durationNights" min="0" max="89"
                                   class="form-control" value="{{ old('duration_nights', $booking?->duration_nights ?? 2) }}">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Section 4: Guests --}}
            <div class="section-card">
                <div class="head">
                    <div class="sec-icon" style="background:linear-gradient(135deg,#dcfce7,#bbf7d0); color:#15803d;">
                        <i class="bi bi-people"></i>
                    </div>
                    <div>
                        <h6>الضيوف</h6>
                        <div class="sub">عدد البالغين والأطفال</div>
                    </div>
                </div>
                <div class="body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">عدد البالغين <span class="req">*</span></label>
                            <input type="number" name="adults_count" min="1" max="500"
                                   class="form-control" value="{{ old('adults_count', $booking?->adults_count ?? 2) }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">عدد الأطفال <span class="hint">2-12 سنة</span></label>
                            <input type="number" name="children_count" min="0" max="200"
                                   class="form-control" value="{{ old('children_count', $booking?->children_count ?? 0) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">الرضع <span class="hint">أقل من سنتين</span></label>
                            <input type="number" name="infants_count" min="0" max="50"
                                   class="form-control" value="{{ old('infants_count', $booking?->infants_count ?? 0) }}">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Section 5: Room + Trip config --}}
            <div class="section-card">
                <div class="head">
                    <div class="sec-icon" style="background:linear-gradient(135deg,#e0e7ff,#c7d2fe); color:#4338ca;">
                        <i class="bi bi-house-gear"></i>
                    </div>
                    <div>
                        <h6>تفاصيل السكن والخدمات</h6>
                        <div class="sub">نوع الغرفة، المستوى، الوجبات، النقل</div>
                    </div>
                </div>
                <div class="body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">نوع الغرفة <span class="req">*</span></label>
                            <div class="opt-grid" data-options="accommodation_type">
                                @php
                                    $rooms = [
                                        'single'      => ['فردي',      'person'],
                                        'double'      => ['ثنائي',     'people'],
                                        'triple'      => ['ثلاثي',     'person-arms-up'],
                                        'quad'        => ['رباعي',     'people-fill'],
                                        'family_room' => ['عائلية',    'house-heart'],
                                        'suite'       => ['جناح',      'gem'],
                                    ];
                                    $currentRoom = old('accommodation_type', $booking?->accommodation_type ?? 'double');
                                @endphp
                                @foreach($rooms as $v => $info)
                                <label class="opt-card {{ $currentRoom === $v ? 'selected' : '' }}">
                                    <input type="radio" name="accommodation_type" value="{{ $v }}" {{ $currentRoom === $v ? 'checked' : '' }} required>
                                    <div class="ic"><i class="bi bi-{{ $info[1] }}"></i></div>
                                    <div class="tt">{{ $info[0] }}</div>
                                </label>
                                @endforeach
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">عدد الغرف <span class="req">*</span></label>
                            <input type="number" name="rooms_count" min="1" max="500"
                                   class="form-control" value="{{ old('rooms_count', $booking?->rooms_count ?? 1) }}" required>
                        </div>

                        <div class="col-12">
                            <label class="form-label">مستوى السكن <span class="req">*</span></label>
                            <div class="opt-grid" data-options="accommodation_grade">
                                @php
                                    $grades = [
                                        'economy' => ['اقتصادي', 'star-half'],
                                        '3_stars' => ['3 نجوم',  'star'],
                                        '4_stars' => ['4 نجوم',  'star-fill'],
                                        '5_stars' => ['5 نجوم',  'stars'],
                                        'resort'  => ['منتجع',   'sun'],
                                    ];
                                    $currentGrade = old('accommodation_grade', $booking?->accommodation_grade ?? '4_stars');
                                @endphp
                                @foreach($grades as $v => $info)
                                <label class="opt-card {{ $currentGrade === $v ? 'selected' : '' }}">
                                    <input type="radio" name="accommodation_grade" value="{{ $v }}" {{ $currentGrade === $v ? 'checked' : '' }} required>
                                    <div class="ic"><i class="bi bi-{{ $info[1] }}"></i></div>
                                    <div class="tt">{{ $info[0] }}</div>
                                </label>
                                @endforeach
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">وسيلة النقل <span class="req">*</span></label>
                            <div class="opt-grid" data-options="transport_type">
                                @php
                                    $transports = [
                                        'none'        => ['بدون',       'x-circle'],
                                        'bus'         => ['أتوبيس',     'bus-front'],
                                        'minivan'     => ['ميكروباص',   'truck-front'],
                                        'private_car' => ['سيارة خاصة', 'car-front'],
                                        'train'       => ['قطار',       'train-front'],
                                        'flight'      => ['طيران',      'airplane'],
                                    ];
                                    $currentTr = old('transport_type', $booking?->transport_type ?? 'bus');
                                @endphp
                                @foreach($transports as $v => $info)
                                <label class="opt-card {{ $currentTr === $v ? 'selected' : '' }}">
                                    <input type="radio" name="transport_type" value="{{ $v }}" {{ $currentTr === $v ? 'checked' : '' }} required>
                                    <div class="ic"><i class="bi bi-{{ $info[1] }}"></i></div>
                                    <div class="tt">{{ $info[0] }}</div>
                                </label>
                                @endforeach
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">نظام الإقامة <span class="req">*</span></label>
                            <div class="opt-grid" data-options="meal_plan">
                                @php
                                    $meals = [
                                        'ro' => ['بدون وجبات',    'cup'],
                                        'bb' => ['إفطار',         'cup-hot'],
                                        'hb' => ['نصف إقامة',     'egg-fried'],
                                        'fb' => ['إقامة كاملة',   'basket'],
                                        'ai' => ['شامل كل شيء',   'gift'],
                                    ];
                                    $currentMeal = old('meal_plan', $booking?->meal_plan ?? 'bb');
                                @endphp
                                @foreach($meals as $v => $info)
                                <label class="opt-card {{ $currentMeal === $v ? 'selected' : '' }}">
                                    <input type="radio" name="meal_plan" value="{{ $v }}" {{ $currentMeal === $v ? 'checked' : '' }} required>
                                    <div class="ic"><i class="bi bi-{{ $info[1] }}"></i></div>
                                    <div class="tt">{{ $info[0] }}</div>
                                </label>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Section 6: Notes --}}
            <div class="section-card">
                <div class="head">
                    <div class="sec-icon" style="background:linear-gradient(135deg,#f3e8ff,#e9d5ff); color:#6b21a8;">
                        <i class="bi bi-sticky"></i>
                    </div>
                    <div>
                        <h6>ملاحظات</h6>
                        <div class="sub">طلبات خاصة من العميل</div>
                    </div>
                </div>
                <div class="body">
                    <textarea name="notes" rows="3" class="form-control" placeholder="طلبات خاصة، تعليمات للفندق، ملاحظات للموظف...">{{ old('notes', $booking?->notes ?? '') }}</textarea>
                </div>
            </div>
        </div>

        {{-- Sidebar column --}}
        <div class="col-lg-4">
            {{-- Pricing --}}
            <div class="section-card">
                <div class="head">
                    <div class="sec-icon" style="background:linear-gradient(135deg,#fef3c7,#fde68a); color:#92400e;">
                        <i class="bi bi-cash-stack"></i>
                    </div>
                    <div>
                        <h6>التسعير</h6>
                        <div class="sub">سعر البيع الإجمالي</div>
                    </div>
                </div>
                <div class="body">
                    <label class="form-label">سعر البيع الإجمالي <span class="req">*</span></label>
                    <div class="input-group input-group-lg mb-3">
                        <input type="number" name="selling_price" min="0" step="0.01"
                               class="form-control form-control-lg price-input @error('selling_price') is-invalid @enderror"
                               value="{{ old('selling_price', $booking?->selling_price ?? '') }}" required>
                        <span class="input-group-text" style="background:#fef3c7; color:#92400e; font-weight:800;">ج.م</span>
                    </div>
                    @error('selling_price') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    <div class="alert alert-info small mb-0">
                        <i class="bi bi-info-circle"></i>
                        التكاليف تُضاف لاحقاً من شاشة تفاصيل الحجز. صافي الربح يُحسب تلقائياً.
                    </div>
                </div>
            </div>

            {{-- Responsibility --}}
            <div class="section-card">
                <div class="head">
                    <div class="sec-icon" style="background:linear-gradient(135deg,#ccfbf1,#a7f3d0); color:#0f766e;">
                        <i class="bi bi-person-workspace"></i>
                    </div>
                    <div>
                        <h6>المسؤولية</h6>
                        <div class="sub">الموظف والمدير</div>
                    </div>
                </div>
                <div class="body">
                    <div class="mb-3">
                        <label class="form-label">الموظف المسؤول</label>
                        <select name="responsible_employee_id" class="form-select select2">
                            <option value="">— غير محدد —</option>
                            @foreach($employees as $u)
                                <option value="{{ $u->id }}"
                                    {{ old('responsible_employee_id', $booking?->responsible_employee_id ?? auth()->id()) == $u->id ? 'selected' : '' }}>
                                    {{ $u->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label">المدير المسؤول</label>
                        <select name="responsible_manager_id" class="form-select select2">
                            <option value="">— غير محدد —</option>
                            @foreach($employees as $u)
                                <option value="{{ $u->id }}"
                                    {{ old('responsible_manager_id', $booking?->responsible_manager_id ?? '') == $u->id ? 'selected' : '' }}>
                                    {{ $u->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
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
                        <div class="sub">رقم الحجز وحالته</div>
                    </div>
                </div>
                <div class="body">
                    <div class="meta-card">
                        <div class="meta-kv">
                            <span class="k">رقم الحجز</span>
                            <span class="v" dir="ltr"><code>{{ $booking->booking_number }}</code></span>
                        </div>
                        <div class="meta-kv">
                            <span class="k">الحالة</span>
                            <span class="v">{{ $booking->status_label }}</span>
                        </div>
                        <div class="meta-kv">
                            <span class="k">المرحلة</span>
                            <span class="v">{{ $booking->workflow_label }}</span>
                        </div>
                        <div class="meta-kv">
                            <span class="k">تاريخ الإنشاء</span>
                            <span class="v">{{ $booking->created_at?->format('Y-m-d') }}</span>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>

    <div class="form-footer">
        <a href="{{ route('admin.domestic.bookings.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-x-circle"></i> إلغاء
        </a>
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-circle"></i> {{ $isEdit ? 'حفظ التعديلات' : 'إنشاء الحجز' }}
        </button>
    </div>
</div>

@push('scripts')
<script>
$(function () {
    if ($.fn.select2) {
        $('.select2').select2({ width: '100%', dir: 'rtl', language: 'ar' });
    }

    // Visual radio cards
    $(document).on('click', '.opt-card, .type-card', function () {
        const $input = $(this).find('input[type=radio]');
        if (!$input.length) return;
        $(this).siblings().removeClass('selected');
        $(this).addClass('selected');
        $input.prop('checked', true).trigger('change');
    });

    // Auto-fill from program selection
    $('#programSelect').on('change', function () {
        const $opt = $(this).find(':selected');
        if (!$opt.val()) return;

        const setRadio = (name, value) => {
            const $r = $('input[name="' + name + '"][value="' + value + '"]');
            if ($r.length) {
                $r.prop('checked', true).trigger('change');
                $r.closest('.opt-card, .type-card').siblings().removeClass('selected');
                $r.closest('.opt-card, .type-card').addClass('selected');
            }
        };

        const type     = $opt.data('type');
        const city     = $opt.data('city');
        const days     = $opt.data('days');
        const nights   = $opt.data('nights');
        const grade    = $opt.data('grade');
        const trans    = $opt.data('transport');
        const meal     = $opt.data('meal');
        const price    = $opt.data('price');

        if (type)   setRadio('type', type);
        if (grade)  setRadio('accommodation_grade', grade);
        if (trans)  setRadio('transport_type', trans);
        if (meal)   setRadio('meal_plan', meal);

        if (city && !$('#destCity').val()) $('#destCity').val(city);
        if (days)   $('#durationDays').val(days);
        if (nights) $('#durationNights').val(nights);
        if (price && !$('input[name="selling_price"]').val()) {
            const adults = parseInt($('input[name="adults_count"]').val() || 0);
            const kids   = parseInt($('input[name="children_count"]').val() || 0);
            $('input[name="selling_price"]').val(price * (adults + kids * 0.5));
        }
    });

    // Auto-compute nights and return_date from trip_date + duration
    function recomputeReturnDate() {
        const trip  = $('#tripDate').val();
        const days  = parseInt($('#durationDays').val() || 0);
        if (trip && days > 0) {
            const d = new Date(trip);
            d.setDate(d.getDate() + days - 1);
            const yyyy = d.getFullYear();
            const mm   = String(d.getMonth() + 1).padStart(2, '0');
            const dd   = String(d.getDate()).padStart(2, '0');
            $('#returnDate').val(yyyy + '-' + mm + '-' + dd);
            $('#durationNights').val(Math.max(0, days - 1));
        }
    }
    $('#tripDate, #durationDays').on('change', recomputeReturnDate);
});
</script>
@endpush
