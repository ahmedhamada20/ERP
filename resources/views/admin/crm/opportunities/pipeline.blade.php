@extends('layouts.master')

@section('title', 'قمع الصفقات')
@section('page_title', 'قمع الصفقات (Pipeline)')
@section('page_subtitle', 'اسحب الصفقات بين المراحل لتغييرها')

@push('styles')
<style>
    .filter-bar { background:#fff; border-radius:12px; padding:1rem; box-shadow:0 1px 3px rgba(15,23,42,.04); border:1px solid var(--brand-border); margin-bottom:1rem; }
    .view-toggle { display:flex; background:#f1f5f9; padding:3px; border-radius:9px; }
    .view-toggle a { padding:.45rem .85rem; border-radius:7px; font-size:.82rem; font-weight:700; color:#64748b; text-decoration:none; }
    .view-toggle a.active { background:#fff; color:var(--brand-navy); box-shadow:0 1px 3px rgba(15,23,42,.1); }

    .pipeline-board {
        display:grid; grid-template-columns:repeat(6, 1fr); gap:.85rem;
        overflow-x:auto; padding-bottom:1rem; min-height:60vh;
    }
    .pipeline-col {
        background:#f8fafc; border-radius:12px; padding:.75rem;
        display:flex; flex-direction:column; min-height:60vh;
    }
    .pipeline-head {
        display:flex; align-items:center; justify-content:space-between;
        padding:.5rem .65rem; margin-bottom:.65rem;
        border-radius:8px; font-weight:800; font-size:.82rem;
    }
    .pipeline-head .count { background:rgba(255,255,255,.7); padding:.15rem .55rem; border-radius:12px; font-size:.72rem; font-weight:700; }

    .h-prospecting   { background:#f1f5f9; color:#475569; }
    .h-qualification { background:#dbeafe; color:#1e40af; }
    .h-proposal      { background:#e0e7ff; color:#4338ca; }
    .h-negotiation   { background:#fef3c7; color:#92400e; }
    .h-closed_won    { background:#dcfce7; color:#15803d; }
    .h-closed_lost   { background:#fee2e2; color:#b91c1c; }

    .pipeline-cards { flex:1; display:flex; flex-direction:column; gap:.5rem; min-height:80px; }
    .pipeline-cards.dragging-over { background:#fff; outline:2px dashed var(--brand-gold); border-radius:8px; }

    .opp-card {
        background:#fff; border-radius:10px; padding:.7rem .8rem;
        box-shadow:0 1px 3px rgba(15,23,42,.06); border:1px solid #f1f5f9;
        cursor:grab; transition:all .15s; user-select:none;
    }
    .opp-card:hover { box-shadow:0 6px 16px rgba(15,23,42,.10); transform:translateY(-2px); border-color:var(--brand-gold); }
    .opp-card.dragging { opacity:.5; }
    .opp-card.converted { cursor:default; opacity:.7; }
    .opp-card .title { font-weight:800; font-size:.84rem; color:var(--brand-navy); margin-bottom:.25rem; }
    .opp-card .meta { font-size:.7rem; color:#64748b; display:flex; align-items:center; gap:.3rem; margin-bottom:.15rem; }
    .opp-card .value { font-size:.82rem; font-weight:700; color:#15803d; margin-top:.4rem; display:flex; justify-content:space-between; align-items:center; }
    .opp-card .probability { background:#f1f5f9; padding:.1rem .4rem; border-radius:5px; font-size:.65rem; color:#475569; }
    .opp-card a.opp-link { color:inherit; text-decoration:none; display:block; }
    .opp-card .btype { font-size:.62rem; padding:.1rem .45rem; border-radius:5px; }
    .opp-card .btype.religious { background:#dcfce7; color:#15803d; }
    .opp-card .btype.domestic  { background:#e0e7ff; color:#4338ca; }

    .empty-col { font-size:.72rem; color:#94a3b8; text-align:center; padding:1.5rem .5rem; }

    @media (max-width:1400px) { .pipeline-board { grid-template-columns:repeat(3, minmax(220px, 1fr)); } }
    @media (max-width:768px)  { .pipeline-board { grid-template-columns:repeat(2, minmax(220px, 1fr)); } }
</style>
@endpush

@section('content')

<div class="filter-bar">
    <div class="d-flex gap-2 flex-wrap align-items-center">
        <div class="view-toggle">
            <a href="{{ route('admin.crm.opportunities.index') }}"><i class="bi bi-table"></i> جدول</a>
            <a href="{{ route('admin.crm.opportunities.pipeline') }}" class="active"><i class="bi bi-kanban"></i> قمع</a>
        </div>

        <form method="GET" class="d-flex gap-2 align-items-center">
            <select name="assignee_id" class="form-select form-select-sm" style="min-width:160px;" onchange="this.form.submit()">
                <option value="">كل المسؤولين</option>
                @foreach($assignees as $u)
                    <option value="{{ $u->id }}" {{ request('assignee_id') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                @endforeach
            </select>
            <select name="booking_type" class="form-select form-select-sm" style="min-width:140px;" onchange="this.form.submit()">
                <option value="">كل الأنواع</option>
                <option value="religious" {{ request('booking_type') === 'religious' ? 'selected' : '' }}>سياحة دينية</option>
                <option value="domestic"  {{ request('booking_type') === 'domestic'  ? 'selected' : '' }}>سياحة داخلية</option>
            </select>
            @if(request()->hasAny(['assignee_id', 'booking_type']))
                <a href="{{ route('admin.crm.opportunities.pipeline') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-x-lg"></i></a>
            @endif
        </form>

        <div class="ms-auto d-flex gap-2 flex-wrap">
            @can('opportunities.create')
            <a href="{{ route('admin.crm.opportunities.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-circle"></i> صفقة جديدة
            </a>
            @endcan
        </div>
    </div>
</div>

@php
    $stages = [
        'prospecting'   => ['استكشاف',  'h-prospecting',   'search'],
        'qualification' => ['تأهيل',     'h-qualification', 'check-square'],
        'proposal'      => ['عرض مقدم',  'h-proposal',      'file-earmark-text'],
        'negotiation'   => ['تفاوض',     'h-negotiation',   'chat-square-quote'],
        'closed_won'    => ['فوز',       'h-closed_won',    'trophy'],
        'closed_lost'   => ['خسارة',     'h-closed_lost',   'x-circle'],
    ];
@endphp

<div class="pipeline-board">
    @foreach($stages as $key => $info)
        @php
            $colOpps   = $opportunities[$key] ?? collect();
            $colValue  = $colOpps->sum('estimated_value');
            $weighted  = $colOpps->sum(fn ($o) => $o->estimated_value * ($o->probability / 100));
        @endphp
        <div class="pipeline-col">
            <div class="pipeline-head {{ $info[1] }}">
                <span><i class="bi bi-{{ $info[2] }}"></i> {{ $info[0] }}</span>
                <span class="count">{{ $colOpps->count() }}</span>
            </div>

            @if($colValue > 0)
            <div class="text-muted x-small text-center mb-2">
                <div><i class="bi bi-cash-coin"></i> {{ number_format($colValue, 0) }} ج.م</div>
                @if(!in_array($key, ['closed_won','closed_lost']))
                    <div>مرجح: {{ number_format($weighted, 0) }}</div>
                @endif
            </div>
            @endif

            <div class="pipeline-cards" data-stage="{{ $key }}">
                @forelse($colOpps as $opp)
                    <div class="opp-card {{ $opp->isConverted() ? 'converted' : '' }}"
                         data-opp-id="{{ $opp->id }}"
                         {{ $opp->isConverted() ? '' : 'draggable="true"' }}>
                        <a href="{{ route('admin.crm.opportunities.show', $opp) }}" class="opp-link">
                            <div class="title">{{ $opp->title }}</div>
                            <div class="meta">
                                <span class="btype {{ $opp->booking_type }}">{{ $opp->booking_type_label }}</span>
                                @if($opp->destination)
                                    <span><i class="bi bi-geo-alt"></i> {{ $opp->destination }}</span>
                                @endif
                            </div>
                            @if($opp->customer || $opp->lead)
                                <div class="meta">
                                    <i class="bi bi-person"></i>
                                    {{ $opp->customer?->full_name ?? $opp->lead?->full_name }}
                                </div>
                            @endif
                            <div class="value">
                                <span>{{ number_format($opp->estimated_value, 0) }} ج.م</span>
                                <span class="probability">{{ $opp->probability }}%</span>
                            </div>
                            @if($opp->isConverted())
                                <div class="text-success x-small mt-1"><i class="bi bi-check-circle-fill"></i> محوّل لحجز</div>
                            @endif
                        </a>
                    </div>
                @empty
                    <div class="empty-col">لا توجد صفقات</div>
                @endforelse
            </div>
        </div>
    @endforeach
</div>

@endsection

@push('scripts')
<script>
@can('opportunities.update')
$(function () {
    let draggedCard = null;

    $('.pipeline-board')
        .on('dragstart', '.opp-card:not(.converted)', function (e) {
            draggedCard = this;
            this.classList.add('dragging');
            e.originalEvent.dataTransfer.effectAllowed = 'move';
            e.originalEvent.dataTransfer.setData('text/plain', this.dataset.oppId);
        })
        .on('dragend', '.opp-card', function () {
            this.classList.remove('dragging');
            $('.pipeline-cards').removeClass('dragging-over');
            draggedCard = null;
        })
        .on('dragover', '.pipeline-cards', function (e) {
            e.preventDefault();
            $(this).addClass('dragging-over');
        })
        .on('dragleave', '.pipeline-cards', function () {
            $(this).removeClass('dragging-over');
        })
        .on('drop', '.pipeline-cards', function (e) {
            e.preventDefault();
            $(this).removeClass('dragging-over');
            if (!draggedCard) return;

            const newStage = $(this).data('stage');
            const oppId    = draggedCard.dataset.oppId;
            const $card    = $(draggedCard);

            let lostReason = null;
            if (newStage === 'closed_lost') {
                lostReason = prompt('سبب الخسارة (اختياري):');
            }

            $(this).append($card);

            $.ajax({
                url: '/admin/crm/opportunities/' + oppId + '/stage',
                method: 'POST',
                data: { _token: '{{ csrf_token() }}', stage: newStage, lost_reason: lostReason },
                success: function (resp) {
                    if (window.toastr) toastr.success(resp.message || 'تم التحديث');
                    setTimeout(() => window.location.reload(), 600);
                },
                error: function (xhr) {
                    const msg = xhr.responseJSON?.message || 'فشل التحديث';
                    if (window.toastr) toastr.error(msg); else alert(msg);
                    window.location.reload();
                },
            });
        });
});
@endcan
</script>
@endpush
