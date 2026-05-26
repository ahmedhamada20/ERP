@extends('layouts.master')

@section('title', 'أسعار الصرف')
@section('page_title', 'أسعار صرف العملات')
@section('page_subtitle', 'تاريخ أسعار صرف الريال والدولار - يُستخدم لحساب التكاليف بدقة لكل حجز')

@push('styles')
<style>
    .bg-success-soft { background:#dcfce7 !important; color:#15803d !important; }
    .bg-secondary-soft { background:#f1f5f9 !important; color:#475569 !important; }
    .bg-info-soft { background:#dbeafe !important; color:#1d4ed8 !important; }
    .bg-danger-soft { background:#fee2e2 !important; color:#b91c1c !important; }
    .btn-light-danger { background:#fee2e2; color:#b91c1c; border:none; }
</style>
@endpush

@section('content')

<div class="row g-3 mb-3">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="bi bi-currency-exchange"></i> سجل أسعار الصرف</h6>
                <div>
                    <select id="currencyFilter" class="form-select form-select-sm" style="width:auto;">
                        <option value="">— الكل —</option>
                        <option value="SAR">SAR (ريال)</option>
                        <option value="USD">USD (دولار)</option>
                        <option value="EUR">EUR (يورو)</option>
                    </select>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="ratesTable" class="table pretty-table" style="width:100%;">
                        <thead>
                            <tr>
                                <th width="60">#</th>
                                <th>زوج العملات</th>
                                <th>السعر</th>
                                <th>تاريخ السريان</th>
                                <th>الحالة</th>
                                <th>الملاحظات</th>
                                <th width="80">إجراء</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        @can('exchange_rates.manage')
        <div class="card">
            <div class="card-header"><h6 class="mb-0"><i class="bi bi-plus-circle"></i> إضافة سعر جديد</h6></div>
            <div class="card-body">
                <form action="{{ route('admin.religious.exchange_rates.store') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">العملة المصدر *</label>
                        <select name="from_currency" class="form-select" required>
                            <option value="SAR">SAR (ريال سعودي)</option>
                            <option value="USD">USD (دولار)</option>
                            <option value="EUR">EUR (يورو)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">العملة الهدف *</label>
                        <select name="to_currency" class="form-select" required>
                            <option value="EGP">EGP (جنيه مصري)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">سعر الصرف *</label>
                        <input type="number" name="rate" step="0.0001" min="0" class="form-control" placeholder="14.0000" required>
                        <div class="form-text">كم جنيه يساوي 1 من العملة المصدر</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">تاريخ السريان *</label>
                        <input type="date" name="effective_date" class="form-control" value="{{ now()->toDateString() }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">ملاحظات</label>
                        <input type="text" name="notes" class="form-control" placeholder="اختياري">
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-check-circle"></i> حفظ
                    </button>
                </form>
            </div>
        </div>
        @endcan

        @can('exchange_rates.manage')
        <div class="card mt-3">
            <div class="card-header"><h6 class="mb-0"><i class="bi bi-arrow-repeat"></i> الأسعار اللحظية</h6></div>
            <div class="card-body">
                <p class="small text-muted mb-2">
                    سحب أسعار الصرف الفورية من <strong>open.er-api.com</strong>.
                    يحدث تلقائياً يومياً 8 ص، وتقدر تضغط الزر لتحديث فوري.
                </p>
                <div id="lastSyncInfo" class="small mb-3">
                    @if(!empty($lastSync))
                        <i class="bi bi-clock-history text-success"></i>
                        آخر تحديث ناجح: <strong>{{ $lastSync->diffForHumans() }}</strong>
                        <span class="text-muted">({{ $lastSync->format('Y-m-d H:i') }})</span>
                    @else
                        <i class="bi bi-exclamation-circle text-warning"></i>
                        لم يتم سحب أسعار لحظية بعد
                    @endif
                </div>
                <button type="button" id="btnSyncRates" class="btn btn-success w-100">
                    <i class="bi bi-cloud-download"></i> <span class="lbl">تحديث الأسعار الآن</span>
                </button>
            </div>
        </div>
        @endcan

        <div class="alert alert-info mt-3">
            <strong><i class="bi bi-info-circle"></i> معلومة:</strong>
            عند إنشاء أي حجز ديني، يتم حفظ سعر الصرف الساري وقت الإنشاء، حتى تظل تقارير الأرباح دقيقة تاريخياً.
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
$(function () {
    let filter = { currency_filter: '' };

    var table = $('#ratesTable').DataTable({
        processing: true, serverSide: true, responsive: true,
        autoWidth: false, language: window.dtArabic,
        order: [[3,'desc']], pageLength: 25,
        ajax: {
            url: '{{ route('admin.religious.exchange_rates.data') }}',
            data: d => Object.assign(d, filter)
        },
        columns: [
            { data: 'id', name: 'id', visible: false },
            { data: 'pair', name: 'from_currency' },
            { data: 'rate', name: 'rate' },
            { data: 'effective_date', name: 'effective_date' },
            { data: 'is_active', name: 'is_active' },
            { data: 'notes', name: 'notes' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ]
    });

    $('#currencyFilter').on('change', function () {
        filter.currency_filter = $(this).val();
        table.ajax.reload();
    });

    $(document).on('click', '.btn-delete', function () {
        CoreX.ajaxDelete($(this).data('url'), table);
    });

    @can('exchange_rates.manage')
    $('#btnSyncRates').on('click', function () {
        var $btn = $(this);
        var $lbl = $btn.find('.lbl');
        var originalText = $lbl.text();

        $btn.prop('disabled', true);
        $lbl.html('<span class="spinner-border spinner-border-sm"></span> جاري السحب...');

        $.ajax({
            url: '{{ route('admin.religious.exchange_rates.sync') }}',
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        }).done(function (res) {
            var msg = res.message + '<br>' + (res.lines || []).join(' &nbsp;|&nbsp; ');
            if (window.Swal) {
                Swal.fire({ icon: 'success', title: 'تم التحديث', html: msg, timer: 4000 });
            } else {
                alert(res.message);
            }
            table.ajax.reload(null, false);
            $('#lastSyncInfo').html(
                '<i class="bi bi-clock-history text-success"></i> آخر تحديث ناجح: <strong>الآن</strong>'
            );
        }).fail(function (xhr) {
            var err = xhr.responseJSON && xhr.responseJSON.message
                ? xhr.responseJSON.message
                : 'تعذّر سحب الأسعار. حاول لاحقاً.';
            if (window.Swal) {
                Swal.fire({ icon: 'error', title: 'خطأ', text: err });
            } else {
                alert(err);
            }
        }).always(function () {
            $btn.prop('disabled', false);
            $lbl.text(originalText);
        });
    });
    @endcan
});
</script>
@endpush
