@php
    /**
     * Reusable "Send WhatsApp" modal. Include on any show page where you
     * want to send a message tied to that record.
     *
     * Required variables:
     *   $waToPhone     — recipient phone (E.164 or local)
     *   $waRelatedType — polymorphic type: lead | religious_booking | domestic_booking | customer | opportunity
     *   $waRelatedId   — ULID of the related record
     *
     * Optional:
     *   $waDefaultText — pre-fills the text body
     */
    $waDefaultText ??= '';
@endphp

<div class="modal fade" id="whatsappSendModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background:linear-gradient(135deg, #25D366, #128C7E); color:#fff;">
                <h5 class="modal-title"><i class="bi bi-whatsapp"></i> إرسال رسالة WhatsApp</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">إلى</label>
                    <input type="text" id="waToPhone" class="form-control" dir="ltr" value="{{ $waToPhone }}" readonly>
                </div>

                <ul class="nav nav-tabs mb-3" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#waTextTab" type="button">
                            <i class="bi bi-chat-text"></i> نص حر
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#waTemplateTab" type="button">
                            <i class="bi bi-file-text"></i> قالب
                        </button>
                    </li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane fade show active" id="waTextTab">
                        <div class="alert alert-warning small mb-3">
                            <i class="bi bi-info-circle"></i> النص الحر يعمل فقط داخل نافذة الـ 24 ساعة (بعد آخر رسالة من العميل). خارج ذلك استخدم قالباً.
                        </div>
                        <label class="form-label">نص الرسالة</label>
                        <textarea id="waBody" class="form-control" rows="5" maxlength="4000" placeholder="اكتب رسالتك هنا...">{{ $waDefaultText }}</textarea>
                        <div class="text-end small text-muted mt-1"><span id="waCharCount">0</span> / 4000</div>
                    </div>

                    <div class="tab-pane fade" id="waTemplateTab">
                        <div class="alert alert-info small mb-3">
                            <i class="bi bi-info-circle"></i> القوالب يجب أن تكون معتمدة من Meta. اكتب اسم القالب كما هو مسجل في WhatsApp Manager.
                        </div>
                        <div class="mb-3">
                            <label class="form-label">اسم القالب <span class="text-danger">*</span></label>
                            <input type="text" id="waTemplateName" class="form-control" dir="ltr" placeholder="hello_world">
                        </div>
                        <label class="form-label">متغيرات القالب <span class="text-muted small">(عدد المتغيرات حسب القالب)</span></label>
                        <div id="waTemplateParams">
                            <div class="input-group mb-2">
                                <span class="input-group-text">{{ '{{1}}' }}</span>
                                <input type="text" class="form-control wa-tpl-param" placeholder="القيمة الأولى">
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="waAddParam">
                            <i class="bi bi-plus-circle"></i> إضافة متغير
                        </button>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">إلغاء</button>
                <button type="button" class="btn btn-success" id="waSendBtn">
                    <i class="bi bi-send"></i> إرسال
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(function () {
    const $modal = $('#whatsappSendModal');
    const $body  = $('#waBody');

    // Char counter
    $body.on('input', function () {
        $('#waCharCount').text($(this).val().length);
    }).trigger('input');

    // Add template param
    $('#waAddParam').on('click', function () {
        const idx = $('.wa-tpl-param').length + 1;
        const html = `
            <div class="input-group mb-2">
                <span class="input-group-text">{{ '{' }}{${'{'}${idx}${'}'}}{{ '}' }}</span>
                <input type="text" class="form-control wa-tpl-param" placeholder="القيمة ${idx}">
                <button type="button" class="btn btn-outline-danger wa-remove-param"><i class="bi bi-x-lg"></i></button>
            </div>
        `;
        $('#waTemplateParams').append(html);
    });

    $(document).on('click', '.wa-remove-param', function () {
        $(this).closest('.input-group').remove();
    });

    // Send
    $('#waSendBtn').on('click', function () {
        const isTemplate  = $('#waTemplateTab').hasClass('active');
        const phone       = $('#waToPhone').val();
        const $btn        = $(this);

        if (!phone) { alert('رقم الهاتف فارغ'); return; }

        const payload = {
            _token:       '{{ csrf_token() }}',
            to_phone:     phone,
            related_type: @json($waRelatedType ?? null),
            related_id:   @json($waRelatedId ?? null),
        };

        if (isTemplate) {
            payload.message_type  = 'template';
            payload.template_name = $('#waTemplateName').val().trim();
            payload.template_params = $('.wa-tpl-param').map(function(){ return $(this).val(); }).get().filter(v => v !== '');
            if (!payload.template_name) { alert('اسم القالب مطلوب'); return; }
        } else {
            payload.message_type = 'text';
            payload.body         = $body.val().trim();
            if (!payload.body) { alert('نص الرسالة مطلوب'); return; }
        }

        $btn.prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> جارٍ الإرسال...');

        $.post('{{ route('admin.crm.whatsapp.messages.send') }}', payload)
            .done(function (resp) {
                if (window.toastr) toastr.success(resp.message); else alert(resp.message);
                $modal.modal('hide');
            })
            .fail(function (xhr) {
                const msg = xhr.responseJSON?.message || 'فشل الإرسال';
                if (window.toastr) toastr.error(msg); else alert(msg);
            })
            .always(function () {
                $btn.prop('disabled', false).html('<i class="bi bi-send"></i> إرسال');
            });
    });
});
</script>
@endpush
