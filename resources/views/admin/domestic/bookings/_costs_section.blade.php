@php
    /** @var \App\Models\DomesticBooking $booking */
    $isClosed = $booking->workflow_stage === 'closed';
    $canManage = auth()->user()?->can('domestic_bookings.manage_costs');
    $categoryLabels = \App\Models\DomesticBookingCost::CATEGORY_LABELS;
    $costSummary = $booking->costs->groupBy('category')->map(fn ($g) => $g->sum('amount_egp'));
@endphp

<div class="info-card">
    <div class="head">
        <h6>
            <i class="bi bi-cash-stack text-primary"></i>
            بنود التكلفة
            <span class="badge bg-light text-dark ms-2">{{ $booking->costs->count() }} بند</span>
        </h6>
        @if(!$isClosed && $canManage)
        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#costModal"
                onclick="resetCostForm()">
            <i class="bi bi-plus-circle"></i> إضافة بند
        </button>
        @endif
    </div>
    <div class="body">
        @if($booking->costs->isEmpty())
            <div class="text-center py-4 text-muted">
                <i class="bi bi-inbox" style="font-size:2rem;"></i>
                <p class="mt-2 mb-0">لا توجد بنود تكلفة بعد</p>
                @if(!$isClosed && $canManage)
                    <small>اضغط "إضافة بند" لتسجيل أول مصروف</small>
                @endif
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>الفئة</th>
                            <th>الوصف</th>
                            <th>الكمية</th>
                            <th>السعر</th>
                            <th>بالجنيه</th>
                            <th>الحالة</th>
                            @if(!$isClosed && $canManage)<th width="80">إجراء</th>@endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($booking->costs as $cost)
                            <tr class="{{ $cost->is_revenue ? 'table-success' : '' }}">
                                <td>
                                    @if($cost->is_revenue)
                                        <span class="badge bg-success"><i class="bi bi-graph-up-arrow"></i> {{ $cost->category_label }}</span>
                                    @else
                                        <span class="badge bg-light text-dark">{{ $cost->category_label }}</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="small">{{ $cost->description ?? '—' }}</div>
                                    @if($cost->notes)
                                        <div class="text-muted x-small"><i class="bi bi-sticky"></i> {{ $cost->notes }}</div>
                                    @endif
                                </td>
                                <td class="small">
                                    {{ $cost->quantity }}
                                    @switch($cost->per_unit)
                                        @case('per_person') /شخص @break
                                        @case('per_room')   /غرفة @break
                                        @case('per_night')  /ليلة @break
                                        @case('per_trip')   /رحلة @break
                                    @endswitch
                                </td>
                                <td class="small">
                                    {{ number_format($cost->amount, 2) }} {{ $cost->currency }}
                                    @if($cost->currency !== 'EGP')
                                        <div class="text-muted x-small">× {{ $cost->exchange_rate }}</div>
                                    @endif
                                </td>
                                <td>
                                    <strong class="{{ $cost->is_revenue ? 'text-success' : 'text-danger' }}">
                                        {{ $cost->is_revenue ? '+' : '' }}{{ number_format($cost->amount_egp, 2) }}
                                    </strong>
                                </td>
                                <td>
                                    @if($cost->is_locked)
                                        <span class="badge bg-secondary"><i class="bi bi-lock"></i> مقفل</span>
                                    @else
                                        <span class="badge bg-light text-dark">قابل للتعديل</span>
                                    @endif
                                </td>
                                @if(!$isClosed && $canManage)
                                <td>
                                    @if(!$cost->is_locked)
                                    <button type="button" class="btn btn-sm btn-light-info btn-icon"
                                            data-bs-toggle="modal" data-bs-target="#costModal"
                                            onclick='editCost(@json($cost))' title="تعديل">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-light-danger btn-icon btn-delete-cost"
                                            data-url="{{ route('admin.domestic.bookings.costs.destroy', [$booking, $cost]) }}"
                                            title="حذف">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                    @endif
                                </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <th colspan="4" class="text-end">إجمالي التكلفة:</th>
                            <th class="text-danger">{{ number_format($booking->total_cost, 2) }} ج.م</th>
                            <th colspan="{{ (!$isClosed && $canManage) ? 2 : 1 }}"></th>
                        </tr>
                    </tfoot>
                </table>
            </div>

            @if($costSummary->isNotEmpty())
            <div class="mt-3">
                <small class="text-muted d-block mb-2"><i class="bi bi-pie-chart"></i> توزيع التكاليف حسب الفئة:</small>
                <div class="d-flex gap-2 flex-wrap">
                    @foreach($costSummary as $cat => $sum)
                        <span class="badge bg-light text-dark border">
                            {{ $categoryLabels[$cat] ?? $cat }}:
                            <strong>{{ number_format($sum, 0) }}</strong> ج.م
                        </span>
                    @endforeach
                </div>
            </div>
            @endif
        @endif
    </div>
</div>

@push('scripts')
<script>
$(function () {
    $(document).on('click', '.btn-delete-cost', function () {
        CoreX.ajaxDelete($(this).data('url'), null, () => window.location.reload());
    });
});
</script>
@endpush
