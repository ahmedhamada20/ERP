{{-- ════════════════════════════════════════════════════════════
     Modals for Booking Sub-Resources
     ════════════════════════════════════════════════════════════ --}}

{{-- ── Pilgrim Modal ──────────────────────────────────────── --}}
<div class="modal fade" id="pilgrimModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <form id="pilgrimForm"
              action="{{ route('admin.religious.bookings.pilgrims.store', $booking) }}"
              method="POST" enctype="multipart/form-data" class="modal-content">
            @csrf
            <input type="hidden" name="_method" id="pilgrimMethod" value="POST">

            <div class="modal-header">
                <h5 class="modal-title" id="pilgrimModalTitle"><i class="bi bi-person-plus"></i> إضافة معتمر</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="form-label">الاسم رباعي بالعربي *</label>
                        <input type="text" name="full_name" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">الاسم بالإنجليزية (للجواز)</label>
                        <input type="text" name="full_name_en" dir="ltr" class="form-control">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">الجنس *</label>
                        <select name="gender" class="form-select" required>
                            <option value="male">ذكر</option>
                            <option value="female">أنثى</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">الفئة العمرية *</label>
                        <select name="age_group" class="form-select" required>
                            <option value="adult">بالغ</option>
                            <option value="child">طفل</option>
                            <option value="infant">رضيع</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">صلة القرابة *</label>
                        <select name="relationship_to_main" class="form-select" required>
                            <option value="self">نفسه</option>
                            <option value="spouse">زوج/زوجة</option>
                            <option value="parent">أب/أم</option>
                            <option value="child">ابن/ابنة</option>
                            <option value="sibling">أخ/أخت</option>
                            <option value="other">أخرى</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">الرقم القومي</label>
                        <input type="text" name="national_id" class="form-control" maxlength="20">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">رقم الجواز</label>
                        <input type="text" name="passport_number" class="form-control" dir="ltr">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">تاريخ الميلاد</label>
                        <input type="date" name="birth_date" class="form-control">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">تاريخ إصدار الجواز</label>
                        <input type="date" name="passport_issue_date" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">تاريخ انتهاء الجواز</label>
                        <input type="date" name="passport_expiry_date" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">الجنسية</label>
                        <input type="text" name="nationality" class="form-control" value="مصري">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">رقم الغرفة</label>
                        <input type="text" name="room_assignment" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">رقم السرير</label>
                        <input type="number" name="bed_number" class="form-control" min="1" max="10">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">باركود صفا</label>
                        <input type="text" name="safa_barcode" class="form-control" dir="ltr">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">رقم التأشيرة</label>
                        <input type="text" name="visa_number" class="form-control" dir="ltr">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">حالة التأشيرة *</label>
                        <select name="visa_status" class="form-select" required>
                            <option value="pending">قيد الانتظار</option>
                            <option value="requested">مطلوبة</option>
                            <option value="issued">صادرة</option>
                            <option value="rejected">مرفوضة</option>
                            <option value="cancelled">ملغية</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">تاريخ إصدار التأشيرة</label>
                        <input type="date" name="visa_issued_date" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">تاريخ انتهاء التأشيرة</label>
                        <input type="date" name="visa_expiry_date" class="form-control">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">صورة الجواز</label>
                        <input type="file" name="passport_image" accept="image/*" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">صورة شخصية</label>
                        <input type="file" name="photo" accept="image/*" class="form-control">
                    </div>

                    <div class="col-12">
                        <label class="form-label">ملاحظات</label>
                        <textarea name="notes" rows="2" class="form-control"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">إلغاء</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> حفظ</button>
            </div>
        </form>
    </div>
</div>

{{-- ── Cost Modal ─────────────────────────────────────────── --}}
<div class="modal fade" id="costModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form id="costForm"
              action="{{ route('admin.religious.bookings.costs.store', $booking) }}"
              method="POST" class="modal-content">
            @csrf
            <input type="hidden" name="_method" id="costMethod" value="POST">

            <div class="modal-header">
                <h5 class="modal-title" id="costModalTitle"><i class="bi bi-receipt"></i> إضافة بند تكلفة</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="form-label">البند *</label>
                        <select name="category" class="form-select" required>
                            <option value="visa">تأشيرة</option>
                            <option value="room">مصاريف الغرفة</option>
                            <option value="transport">نقل (باص/قطار/VIP)</option>
                            <option value="flight">طيران</option>
                            <option value="miscellaneous">نثريات</option>
                            <option value="supervision">إشراف</option>
                            <option value="tax">ضرائب</option>
                            <option value="activation">تنشيط</option>
                            <option value="profit">ربح</option>
                            <option value="gifts">هدايا</option>
                            <option value="mutawif">المطوف</option>
                            <option value="commission">عمولة موظف</option>
                            <option value="bank_fee">رسوم بنكية</option>
                            <option value="insurance">تأمين سفر</option>
                            <option value="other">أخرى</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">الوصف</label>
                        <input type="text" name="description" class="form-control" placeholder="تفاصيل إضافية">
                    </div>

                    <div class="col-md-12">
                        <label class="form-label">المورد <small class="text-muted">(اختياري — يربط البند بكشف حساب المورد)</small></label>
                        <select name="supplier_id" class="form-select">
                            <option value="">— بدون مورد محدد —</option>
                            @foreach(($suppliers ?? []) as $sup)
                                <option value="{{ $sup->id }}">
                                    [{{ $sup->code }}] {{ $sup->name }} ({{ $sup->type_label ?? $sup->type }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">العملة *</label>
                        <select name="currency" class="form-select" required>
                            <option value="EGP">جنيه (EGP)</option>
                            <option value="SAR">ريال (SAR)</option>
                            <option value="USD">دولار (USD)</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">المبلغ *</label>
                        <input type="number" name="amount" step="0.01" min="0" class="form-control" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">الكمية *</label>
                        <input type="number" name="quantity" min="1" value="1" class="form-control" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">سعر الصرف</label>
                        <input type="number" name="exchange_rate" step="0.0001" min="0" class="form-control" placeholder="تلقائي">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">الوحدة *</label>
                        <select name="per_unit" class="form-select" required>
                            <option value="total">إجمالي</option>
                            <option value="per_person">للفرد</option>
                            <option value="per_room">للغرفة</option>
                            <option value="per_night">لليلة</option>
                            <option value="per_trip">للرحلة</option>
                        </select>
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                        <div class="form-check form-switch">
                            <input type="hidden" name="is_revenue" value="0">
                            <input type="checkbox" name="is_revenue" id="costIsRevenue" value="1" class="form-check-input">
                            <label class="form-check-label" for="costIsRevenue">هذا بند إيراد (وليس تكلفة)</label>
                        </div>
                    </div>

                    <div class="col-12">
                        <label class="form-label">ملاحظات</label>
                        <textarea name="notes" rows="2" class="form-control"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">إلغاء</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> حفظ</button>
            </div>
        </form>
    </div>
</div>

{{-- ── Payment Modal ──────────────────────────────────────── --}}
<div class="modal fade" id="paymentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form action="{{ route('admin.religious.bookings.payments.store', $booking) }}"
              method="POST" enctype="multipart/form-data" class="modal-content" id="paymentForm">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-cash-stack"></i> تسجيل دفعة جديدة</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-2">
                    <div class="col-md-4">
                        <label class="form-label">تاريخ الدفع *</label>
                        <input type="date" name="payment_date" class="form-control" value="{{ now()->toDateString() }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">نوع الدفعة *</label>
                        <select name="payment_type" id="paymentTypeSelect" class="form-select" required>
                            <option value="installment">قسط</option>
                            <option value="deposit">مقدم</option>
                            <option value="final">دفعة نهائية</option>
                            <option value="refund">طلب استرداد</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">طريقة الدفع *</label>
                        <select name="method" id="paymentMethodSelect" class="form-select" required>
                            <option value="cash">نقدي</option>
                            <option value="bank_transfer">تحويل بنكي</option>
                            <option value="credit_card">بطاقة ائتمان</option>
                            <option value="cheque">شيك</option>
                            <option value="instapay">إنستا باي</option>
                            <option value="vodafone_cash">فودافون كاش</option>
                        </select>
                    </div>

                    <div class="col-md-8">
                        <label class="form-label">حساب الخزينة / البنك *</label>
                        <select name="cash_account_id" id="cashAccountSelect" class="form-select" required>
                            <option value="">-- اختر الحساب --</option>
                            @foreach($cashAccounts as $acc)
                                <option value="{{ $acc->id }}" data-subtype="{{ $acc->sub_type }}">
                                    [{{ $acc->code }}] {{ $acc->name }}
                                    ({{ $acc->sub_type === 'cash' ? 'خزينة' : 'بنك' }})
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted">يتم تصفية الحسابات حسب طريقة الدفع المختارة.</small>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">العملة *</label>
                        <select name="currency" class="form-select" required>
                            <option value="EGP">جنيه</option>
                            <option value="SAR">ريال</option>
                            <option value="USD">دولار</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">المبلغ *</label>
                        <input type="number" name="amount" step="0.01" min="0" class="form-control" required>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">سعر الصرف (تلقائي للعملات الأجنبية)</label>
                        <input type="number" name="exchange_rate" step="0.0001" min="0" class="form-control" placeholder="تلقائي">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">اسم البنك</label>
                        <input type="text" name="bank_name" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">المرجع/رقم العملية</label>
                        <input type="text" name="transaction_reference" class="form-control" dir="ltr">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">رقم الشيك</label>
                        <input type="text" name="cheque_number" class="form-control" dir="ltr">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">تاريخ استحقاق الشيك</label>
                        <input type="date" name="cheque_due_date" class="form-control">
                    </div>

                    <div class="col-md-12">
                        <label class="form-label">صورة الإيصال</label>
                        <input type="file" name="attachment" accept="image/*,.pdf" class="form-control">
                    </div>

                    <div class="col-12">
                        <label class="form-label">ملاحظات</label>
                        <textarea name="notes" rows="2" class="form-control"></textarea>
                    </div>

                    {{-- Refund-only fields — toggled by JS based on payment_type --}}
                    <div class="col-12 refund-only" style="display:none;">
                        <div class="alert alert-warning mb-2 py-2">
                            <i class="bi bi-info-circle"></i>
                            طلب الاسترداد يحتاج موافقة المدير قبل صرفه للعميل.
                        </div>
                    </div>
                    <div class="col-md-12 refund-only" style="display:none;">
                        <label class="form-label">سبب الاسترداد *</label>
                        <textarea name="refund_reason" rows="2" class="form-control" placeholder="مثال: إلغاء الرحلة بسبب ظروف صحية"></textarea>
                    </div>
                    <div class="col-md-12 refund-only" style="display:none;">
                        <label class="form-label">الدفعة الأصلية (اختياري — لربط الاسترداد)</label>
                        <select name="refunded_payment_id" class="form-select">
                            <option value="">— غير محدد —</option>
                            @foreach($booking->payments->where('payment_type', '!=', 'refund') as $orig)
                                <option value="{{ $orig->id }}">
                                    {{ $orig->receipt_number }} — {{ number_format($orig->amount, 2) }} {{ $orig->currency }} ({{ $orig->payment_date?->format('Y-m-d') }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">إلغاء</button>
                <button type="submit" class="btn btn-primary" id="paymentSubmitBtn"><i class="bi bi-check-circle"></i> تسجيل الدفعة</button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    const sel  = document.getElementById('paymentTypeSelect');
    const btn  = document.getElementById('paymentSubmitBtn');
    const form = document.getElementById('paymentForm');
    if (!sel || !form) return;

    const toggle = () => {
        const isRefund = sel.value === 'refund';
        form.querySelectorAll('.refund-only').forEach(el => el.style.display = isRefund ? '' : 'none');
        const reasonInput = form.querySelector('textarea[name="refund_reason"]');
        if (reasonInput) reasonInput.required = isRefund;
        if (btn) btn.innerHTML = isRefund
            ? '<i class="bi bi-arrow-counterclockwise"></i> إرسال طلب الاسترداد'
            : '<i class="bi bi-check-circle"></i> تسجيل الدفعة';
    };
    sel.addEventListener('change', toggle);
    toggle();

    // فلترة dropdown الحساب حسب method (cash → خزينة، باقي → بنك)
    const methodSel = document.getElementById('paymentMethodSelect');
    const accSel    = document.getElementById('cashAccountSelect');
    if (methodSel && accSel) {
        const filterAccounts = () => {
            const expected = methodSel.value === 'cash' ? 'cash' : 'bank';
            let firstMatch = null;
            Array.from(accSel.options).forEach(opt => {
                if (!opt.value) { opt.hidden = false; return; }
                const ok = opt.dataset.subtype === expected;
                opt.hidden = !ok;
                if (ok && !firstMatch) firstMatch = opt;
            });
            // إذا الاختيار الحالي غير متوافق، اختر أول حساب مناسب
            const current = accSel.selectedOptions[0];
            if (!current || current.hidden || !current.value) {
                accSel.value = firstMatch ? firstMatch.value : '';
            }
        };
        methodSel.addEventListener('change', filterAccounts);
        filterAccounts();
    }
})();
</script>

{{-- ── Accommodation Modal ────────────────────────────────── --}}
<div class="modal fade" id="accomModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form action="{{ route('admin.religious.bookings.accommodations.store', $booking) }}"
              method="POST" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-building"></i> إضافة سكن</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-2">
                    <div class="col-md-4">
                        <label class="form-label">المدينة *</label>
                        <select name="city" class="form-select" required>
                            <option value="mecca">مكة المكرمة</option>
                            <option value="medina">المدينة المنورة</option>
                            <option value="jeddah">جدة</option>
                            <option value="other">أخرى</option>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">اسم الفندق *</label>
                        <input type="text" name="hotel_name" class="form-control" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">المستوى *</label>
                        <select name="hotel_grade" class="form-select" required>
                            <option value="economy">اقتصادي</option>
                            <option value="4_stars">4 نجوم</option>
                            <option value="5_stars">5 نجوم</option>
                        </select>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">ربط بفندق من الماستر <small class="text-muted">(اختياري — يتيح تتبع العقود)</small></label>
                        <select name="hotel_id" class="form-select">
                            <option value="">— بدون ربط —</option>
                            @foreach(($hotels ?? []) as $h)
                                <option value="{{ $h->id }}">[{{ $h->city ?? '' }}] {{ $h->name }}{{ $h->grade ? ' ('.$h->grade.')' : '' }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">تاريخ الدخول *</label>
                        <input type="date" name="check_in_date" class="form-control" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">تاريخ الخروج *</label>
                        <input type="date" name="check_out_date" class="form-control" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">عدد الليالي *</label>
                        <input type="number" name="nights" min="1" max="60" class="form-control" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">عدد الغرف *</label>
                        <input type="number" name="rooms_count" min="1" class="form-control" value="1" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">المسافة من الحرم (م)</label>
                        <input type="text" name="hotel_distance_meters" class="form-control" placeholder="500">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">نوع الغرفة *</label>
                        <select name="room_type" class="form-select" required>
                            <option value="single">فردي (1)</option>
                            <option value="double">ثنائي (2)</option>
                            <option value="triple">ثلاثي (3)</option>
                            <option value="quad" selected>رباعي (4)</option>
                            <option value="quintuple">خماسي (5)</option>
                            <option value="sextuple">سداسي (6)</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">عدد الأفراد بالغرفة *</label>
                        <input type="number" name="pax_per_room" min="1" max="6" value="4" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">نظام الإقامة *</label>
                        <select name="meal_plan" class="form-select" required>
                            <option value="hp">H.P (نصف إقامة)</option>
                            <option value="pp">P.P (إقامة كاملة)</option>
                            <option value="bb">إفطار فقط</option>
                            <option value="ro">بدون وجبات</option>
                            <option value="hb">Half Board</option>
                            <option value="fb">Full Board</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">سعر الغرفة لليلة (SAR) *</label>
                        <input type="number" name="room_price_per_night_sar" step="0.01" min="0" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">سعر صرف الريال (تلقائي)</label>
                        <input type="number" name="exchange_rate" step="0.0001" min="0" class="form-control" placeholder="تلقائي">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">رقم تأكيد الحجز</label>
                        <input type="text" name="confirmation_number" class="form-control" dir="ltr">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">ملاحظات</label>
                        <input type="text" name="notes" class="form-control">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">إلغاء</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> حفظ</button>
            </div>
        </form>
    </div>
</div>

{{-- ── Transportation Modal ──────────────────────────────── --}}
<div class="modal fade" id="transModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form action="{{ route('admin.religious.bookings.transportation.store', $booking) }}"
              method="POST" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-airplane"></i> إضافة وسيلة نقل</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-2">
                    <div class="col-md-3">
                        <label class="form-label">النوع *</label>
                        <select name="type" class="form-select" required>
                            <option value="flight">طيران</option>
                            <option value="bus">باص</option>
                            <option value="train">قطار</option>
                            <option value="vip">VIP</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">الاتجاه *</label>
                        <select name="direction" class="form-select" required>
                            <option value="outbound">ذهاب</option>
                            <option value="inbound">عودة</option>
                            <option value="internal">داخلي</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">القطاع *</label>
                        <select name="segment" class="form-select" required>
                            <option value="cai_jed">القاهرة → جدة</option>
                            <option value="jed_cai">جدة → القاهرة</option>
                            <option value="jed_mec">جدة → مكة</option>
                            <option value="mec_med">مكة → المدينة</option>
                            <option value="med_jed">المدينة → جدة</option>
                            <option value="other">أخرى</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">العملة *</label>
                        <select name="currency" class="form-select" required>
                            <option value="EGP">جنيه</option>
                            <option value="SAR">ريال</option>
                            <option value="USD">دولار</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">اسم الناقل</label>
                        <input type="text" name="carrier_name" class="form-control" placeholder="مصر للطيران، الخطوط السعودية...">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">المرجع (PNR/رقم التذكرة)</label>
                        <input type="text" name="reference" class="form-control" dir="ltr">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">شركة طيران من الماستر <small class="text-muted">(للرحلات الجوية)</small></label>
                        <select name="airline_id" class="form-select">
                            <option value="">— بدون ربط —</option>
                            @foreach(($airlines ?? []) as $a)
                                <option value="{{ $a->id }}">{{ $a->airline_code ? '['.$a->airline_code.'] ' : '' }}{{ $a->airline_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">مزود نقل من الماستر <small class="text-muted">(للحافلات/VIP)</small></label>
                        <select name="transport_provider_id" class="form-select">
                            <option value="">— بدون ربط —</option>
                            @foreach(($transportProviders ?? []) as $tp)
                                <option value="{{ $tp->id }}">{{ $tp->name }} ({{ $tp->type }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">من</label>
                        <input type="text" name="departure_location" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">إلى</label>
                        <input type="text" name="arrival_location" class="form-control">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">تاريخ ووقت المغادرة</label>
                        <input type="datetime-local" name="departure_at" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">تاريخ ووقت الوصول</label>
                        <input type="datetime-local" name="arrival_at" class="form-control">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">سعر للفرد *</label>
                        <input type="number" name="cost_per_person" step="0.01" min="0" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">عدد الأفراد *</label>
                        <input type="number" name="pax_count" min="1" value="1" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">سعر الصرف</label>
                        <input type="number" name="exchange_rate" step="0.0001" min="0" class="form-control" placeholder="تلقائي">
                    </div>

                    <div class="col-12">
                        <label class="form-label">ملاحظات</label>
                        <input type="text" name="notes" class="form-control">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">إلغاء</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> حفظ</button>
            </div>
        </form>
    </div>
</div>

{{-- ── Document Modal ──────────────────────────────────── --}}
<div class="modal fade" id="documentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form action="{{ route('admin.religious.bookings.documents.store', $booking) }}"
              method="POST" enctype="multipart/form-data" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-cloud-upload"></i> رفع وثيقة جديدة</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="form-label">تصنيف الوثيقة *</label>
                        <select name="category" class="form-select" required>
                            <option value="passport">جواز سفر</option>
                            <option value="national_id">بطاقة قومية</option>
                            <option value="visa">تأشيرة</option>
                            <option value="vaccination">شهادة تطعيم</option>
                            <option value="medical">تقرير طبي</option>
                            <option value="insurance">تأمين سفر</option>
                            <option value="ticket">تذكرة</option>
                            <option value="contract">عقد موقع</option>
                            <option value="receipt">إيصال</option>
                            <option value="photo">صورة شخصية</option>
                            <option value="mahram">وثيقة محرم</option>
                            <option value="other">أخرى</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">للمعتمر (اختياري)</label>
                        <select name="pilgrim_id" class="form-select">
                            <option value="">— وثيقة للحجز كامل —</option>
                            @foreach($booking->pilgrims as $pilgrim)
                                <option value="{{ $pilgrim->id }}">{{ $pilgrim->full_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label">عنوان الوثيقة *</label>
                        <input type="text" name="title" class="form-control" placeholder="مثال: جواز سفر أحمد محمد - مصري" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">تاريخ الإصدار</label>
                        <input type="date" name="issue_date" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">تاريخ الانتهاء</label>
                        <input type="date" name="expiry_date" class="form-control">
                    </div>

                    <div class="col-12">
                        <label class="form-label">الملف *</label>
                        <input type="file" name="file" accept="image/*,.pdf,.doc,.docx" class="form-control" required>
                        <div class="form-text">الصيغ المدعومة: JPG، PNG، WEBP، PDF، DOC، DOCX — حد أقصى 10MB</div>
                    </div>

                    <div class="col-12">
                        <label class="form-label">وصف</label>
                        <textarea name="description" rows="2" class="form-control"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">إلغاء</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-cloud-upload"></i> رفع الوثيقة</button>
            </div>
        </form>
    </div>
</div>

{{-- ── Cancel Modal ──────────────────────────────────────── --}}
<div class="modal fade" id="cancelModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('admin.religious.bookings.transition', $booking) }}"
              method="POST" class="modal-content">
            @csrf
            <input type="hidden" name="action" value="cancel">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="bi bi-x-circle"></i> إلغاء الحجز</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>سيتم إلغاء الحجز <strong>{{ $booking->booking_number }}</strong>. هذا الإجراء يحتاج لسبب مكتوب.</p>
                <label class="form-label">سبب الإلغاء *</label>
                <textarea name="reason" rows="3" class="form-control" required></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">تراجع</button>
                <button type="submit" class="btn btn-danger"><i class="bi bi-x-circle"></i> تأكيد الإلغاء</button>
            </div>
        </form>
    </div>
</div>
