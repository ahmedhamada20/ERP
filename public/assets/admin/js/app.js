/* ============================================================
   CoreX ERP — Application JS
   ============================================================ */

window.CoreX = (function ($) {
    'use strict';

    // CSRF token for all AJAX
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // ---------- Toastr defaults ----------
    if (typeof toastr !== 'undefined') {
        toastr.options = {
            closeButton: true,
            progressBar: true,
            positionClass: 'toast-top-left',
            rtl: true,
            timeOut: 4000,
            extendedTimeOut: 2000,
            showMethod: 'slideDown',
            hideMethod: 'slideUp'
        };
    }

    // ---------- SweetAlert defaults helper ----------
    function confirmDelete(callback, options) {
        options = options || {};
        if (typeof Swal === 'undefined') {
            if (confirm(options.text || 'هل أنت متأكد من الحذف؟')) callback();
            return;
        }
        Swal.fire({
            title: options.title || 'هل أنت متأكد؟',
            text: options.text || 'لا يمكن التراجع عن هذا الإجراء',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'نعم، احذف',
            cancelButtonText: 'إلغاء',
            reverseButtons: true,
            customClass: { popup: 'rtl' }
        }).then(function (result) {
            if (result.isConfirmed) callback();
        });
    }

    // ---------- DataTables default config (Arabic) ----------
    var dtArabic = {
        sEmptyTable:     'لا توجد بيانات',
        sInfo:           'عرض _START_ إلى _END_ من إجمالي _TOTAL_ سجل',
        sInfoEmpty:      'عرض 0 إلى 0 من 0 سجل',
        sInfoFiltered:   '(مفلتر من إجمالي _MAX_ سجل)',
        sInfoPostFix:    '',
        sInfoThousands:  ',',
        sLengthMenu:     'عرض _MENU_ سجل',
        sLoadingRecords: 'جاري التحميل...',
        sProcessing:     'جاري المعالجة...',
        sSearch:         'بحث:',
        sZeroRecords:    'لا توجد سجلات مطابقة',
        oPaginate: {
            sFirst:    'الأول',
            sLast:     'الأخير',
            sNext:     'التالي',
            sPrevious: 'السابق'
        },
        oAria: {
            sSortAscending:  ': تفعيل لترتيب العمود تصاعدياً',
            sSortDescending: ': تفعيل لترتيب العمود تنازلياً'
        }
    };

    function initDataTable(selector, options) {
        options = options || {};
        var defaults = {
            processing: true,
            serverSide: true,
            responsive: true,
            order: [[0, 'desc']],
            language: dtArabic,
            dom: '<"row"<"col-md-6"l><"col-md-6"f>>' +
                 '<"row"<"col-12"tr>>' +
                 '<"row"<"col-md-5"i><"col-md-7"p>>',
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'الكل']]
        };
        return $(selector).DataTable($.extend(true, defaults, options));
    }

    // ---------- Loading overlay ----------
    function showLoading() { $('.loading-overlay').addClass('active'); }
    function hideLoading() { $('.loading-overlay').removeClass('active'); }

    // ---------- Form submission helper (AJAX) ----------
    function submitForm(formSelector, successCallback) {
        var $form = $(formSelector);
        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('.invalid-feedback').remove();

        var formData = new FormData($form[0]);
        showLoading();

        $.ajax({
            url: $form.attr('action'),
            type: $form.attr('method') || 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (response) {
                hideLoading();
                if (response && response.message) toastr.success(response.message);
                if (typeof successCallback === 'function') successCallback(response);
            },
            error: function (xhr) {
                hideLoading();
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    $.each(xhr.responseJSON.errors, function (field, messages) {
                        var $input = $form.find('[name="' + field + '"]');
                        $input.addClass('is-invalid');
                        $input.after('<div class="invalid-feedback">' + messages[0] + '</div>');
                    });
                    toastr.error('يوجد أخطاء في النموذج، تحقق من الحقول');
                } else {
                    toastr.error((xhr.responseJSON && xhr.responseJSON.message) || 'حدث خطأ غير متوقع');
                }
            }
        });
    }

    // ---------- Delete via AJAX ----------
    function ajaxDelete(url, table) {
        confirmDelete(function () {
            showLoading();
            $.ajax({
                url: url,
                type: 'DELETE',
                success: function (res) {
                    hideLoading();
                    toastr.success((res && res.message) || 'تم الحذف بنجاح');
                    if (table && typeof table.ajax === 'object') table.ajax.reload(null, false);
                },
                error: function (xhr) {
                    hideLoading();
                    toastr.error((xhr.responseJSON && xhr.responseJSON.message) || 'فشل الحذف');
                }
            });
        });
    }

    // ---------- Flash messages from server-rendered pages ----------
    $(function () {
        var $flash = $('#server-flash');
        if ($flash.length) {
            var success = $flash.data('success');
            var error = $flash.data('error');
            var info = $flash.data('info');
            if (success) toastr.success(success);
            if (error)   toastr.error(error);
            if (info)    toastr.info(info);
        }

        // Initialize Select2 with RTL
        if ($.fn.select2) {
            $('.select2').each(function () {
                $(this).select2({
                    dir: 'rtl',
                    placeholder: $(this).data('placeholder') || 'اختر',
                    allowClear: true,
                    width: '100%'
                });
            });
        }
    });

    return {
        initDataTable: initDataTable,
        confirmDelete: confirmDelete,
        submitForm:    submitForm,
        ajaxDelete:    ajaxDelete,
        showLoading:   showLoading,
        hideLoading:   hideLoading
    };

})(jQuery);
