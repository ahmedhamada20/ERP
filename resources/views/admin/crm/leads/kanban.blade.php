@extends('layouts.master')

@section('title', 'قمع المبيعات - العملاء المحتملون')
@section('page_title', 'قمع المبيعات (Kanban)')
@section('page_subtitle', 'اسحب الـ Leads بين الأعمدة لتغيير الحالة')

@push('styles')
<style>
    .filter-bar { background:#fff; border-radius:12px; padding:1rem; box-shadow:0 1px 3px rgba(15,23,42,.04); border:1px solid var(--brand-border); margin-bottom:1rem; }
    .view-toggle { display:flex; background:#f1f5f9; padding:3px; border-radius:9px; }
    .view-toggle a { padding:.45rem .85rem; border-radius:7px; font-size:.82rem; font-weight:700; color:#64748b; text-decoration:none; transition:all .15s; }
    .view-toggle a.active { background:#fff; color:var(--brand-navy); box-shadow:0 1px 3px rgba(15,23,42,.1); }

    .kanban-board {
        display:grid; grid-template-columns:repeat(6, 1fr); gap:.85rem;
        overflow-x:auto; padding-bottom:1rem; min-height:60vh;
    }
    .kanban-col {
        background:#f8fafc; border-radius:12px; padding:.75rem;
        display:flex; flex-direction:column; min-height:60vh;
    }
    .kanban-head {
        display:flex; align-items:center; justify-content:space-between;
        padding:.5rem .65rem; margin-bottom:.65rem;
        border-radius:8px; font-weight:800; font-size:.82rem;
    }
    .kanban-head .count {
        background:rgba(255,255,255,.7); padding:.15rem .55rem; border-radius:12px;
        font-size:.72rem; font-weight:700;
    }

    /* Status-specific header colors */
    .h-new       { background:#f1f5f9; color:#475569; }
    .h-contacted { background:#dbeafe; color:#1e40af; }
    .h-qualified { background:#e0e7ff; color:#4338ca; }
    .h-proposal  { background:#fef3c7; color:#92400e; }
    .h-won       { background:#dcfce7; color:#15803d; }
    .h-lost      { background:#fee2e2; color:#b91c1c; }

    .kanban-cards { flex:1; display:flex; flex-direction:column; gap:.5rem; min-height:80px; }
    .kanban-cards.dragging-over { background:#fff; outline:2px dashed var(--brand-gold); border-radius:8px; }

    .lead-card {
        background:#fff; border-radius:10px; padding:.7rem .8rem;
        box-shadow:0 1px 3px rgba(15,23,42,.06); border:1px solid #f1f5f9;
        cursor:grab; transition:all .15s; user-select:none;
    }
    .lead-card:hover { box-shadow:0 6px 16px rgba(15,23,42,.10); transform:translateY(-2px); border-color:var(--brand-gold); }
    .lead-card:active { cursor:grabbing; }
    .lead-card.dragging { opacity:.5; }
    .lead-card .name { font-weight:800; font-size:.85rem; color:var(--brand-navy); margin-bottom:.2rem; }
    .lead-card .meta { font-size:.7rem; color:#64748b; display:flex; align-items:center; gap:.3rem; margin-bottom:.15rem; }
    .lead-card .meta .badge { font-size:.62rem; padding:.15rem .4rem; }
    .lead-card .value { font-size:.78rem; font-weight:700; color:#15803d; margin-top:.35rem; }
    .lead-card .source-tag { font-size:.65rem; padding:.1rem .5rem; border-radius:6px; background:#f1f5f9; color:#475569; }
    .lead-card a.lead-link { color:inherit; text-decoration:none; display:block; }

    .empty-col { font-size:.72rem; color:#94a3b8; text-align:center; padding:1.5rem .5rem; }

    @media (max-width:1400px) {
        .kanban-board { grid-template-columns:repeat(3, minmax(220px, 1fr)); }
    }
    @media (max-width:768px) {
        .kanban-board { grid-template-columns:repeat(2, minmax(220px, 1fr)); }
    }
</style>
@endpush

@section('content')

<div class="filter-bar">
    <div class="d-flex gap-2 flex-wrap align-items-center">
        <div class="view-toggle">
            <a href="{{ route('admin.crm.leads.index') }}"><i class="bi bi-table"></i> جدول</a>
            <a href="{{ route('admin.crm.leads.kanban') }}" class="active"><i class="bi bi-kanban"></i> قمع</a>
        </div>

        <form method="GET" class="d-flex gap-2 align-items-center">
            <select name="assignee_id" class="form-select form-select-sm" style="min-width:160px;" onchange="this.form.submit()">
                <option value="">كل المسؤولين</option>
                @foreach($assignees as $u)
                    <option value="{{ $u->id }}" {{ request('assignee_id') == $u->id ? 'selected' : '' }}>
                        {{ $u->name }}
                    </option>
                @endforeach
            </select>
            <select name="interest" class="form-select form-select-sm" style="min-width:140px;" onchange="this.form.submit()">
                <option value="">كل الاهتمامات</option>
                @foreach(\App\Models\Lead::INTEREST_LABELS as $val => $label)
                    <option value="{{ $val }}" {{ request('interest') === $val ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            @if(request()->hasAny(['assignee_id', 'interest']))
                <a href="{{ route('admin.crm.leads.kanban') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-x-lg"></i>
                </a>
            @endif
        </form>

        <div class="ms-auto d-flex gap-2 flex-wrap">
            @can('leads.create')
            <a href="{{ route('admin.crm.leads.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-circle"></i> Lead جديد
            </a>
            @endcan
        </div>
    </div>
</div>

@php
    $stages = [
        'new'       => ['جديد',      'h-new',       'stars'],
        'contacted' => ['تم التواصل', 'h-contacted', 'telephone'],
        'qualified' => ['مؤهل',       'h-qualified', 'check-square'],
        'proposal'  => ['عرض مقدم',   'h-proposal',  'file-earmark-text'],
        'won'       => ['فائز',       'h-won',       'trophy'],
        'lost'      => ['خاسر',       'h-lost',      'x-circle'],
    ];
@endphp

<div class="kanban-board">
    @foreach($stages as $key => $info)
        @php
            $colLeads = $leads[$key] ?? collect();
            $colValue = $colLeads->sum('estimated_value');
        @endphp
        <div class="kanban-col">
            <div class="kanban-head {{ $info[1] }}">
                <span><i class="bi bi-{{ $info[2] }}"></i> {{ $info[0] }}</span>
                <span class="count">{{ $colLeads->count() }}</span>
            </div>

            @if($colValue > 0)
            <div class="text-muted x-small text-center mb-2">
                <i class="bi bi-cash-coin"></i> {{ number_format($colValue, 0) }} ج.م
            </div>
            @endif

            <div class="kanban-cards" data-status="{{ $key }}">
                @forelse($colLeads as $lead)
                    <div class="lead-card" data-lead-id="{{ $lead->id }}" draggable="true">
                        <a href="{{ route('admin.crm.leads.show', $lead) }}" class="lead-link">
                            <div class="name">{{ $lead->full_name }}</div>
                            <div class="meta">
                                <i class="bi bi-telephone"></i> <span dir="ltr">{{ $lead->phone }}</span>
                            </div>
                            <div class="meta">
                                <span class="source-tag">{{ $lead->source_label }}</span>
                                <span class="badge bg-info-soft">{{ $lead->interest_label }}</span>
                            </div>
                            @if($lead->estimated_value > 0)
                                <div class="value"><i class="bi bi-cash-coin"></i> {{ number_format($lead->estimated_value, 0) }} ج.م</div>
                            @endif
                            @if($lead->assignee)
                                <div class="meta mt-1">
                                    <i class="bi bi-person"></i> {{ $lead->assignee->name }}
                                </div>
                            @endif
                        </a>
                    </div>
                @empty
                    <div class="empty-col">لا توجد leads</div>
                @endforelse
            </div>
        </div>
    @endforeach
</div>

@endsection

@push('scripts')
<script>
@can('leads.update')
// Native HTML5 drag-drop (no library needed)
$(function () {
    let draggedCard = null;

    $('.kanban-board')
        .on('dragstart', '.lead-card', function (e) {
            draggedCard = this;
            this.classList.add('dragging');
            e.originalEvent.dataTransfer.effectAllowed = 'move';
            e.originalEvent.dataTransfer.setData('text/plain', this.dataset.leadId);
        })
        .on('dragend', '.lead-card', function () {
            this.classList.remove('dragging');
            $('.kanban-cards').removeClass('dragging-over');
            draggedCard = null;
        })
        .on('dragover', '.kanban-cards', function (e) {
            e.preventDefault();
            $(this).addClass('dragging-over');
        })
        .on('dragleave', '.kanban-cards', function () {
            $(this).removeClass('dragging-over');
        })
        .on('drop', '.kanban-cards', function (e) {
            e.preventDefault();
            $(this).removeClass('dragging-over');
            if (!draggedCard) return;

            const newStatus = $(this).data('status');
            const leadId    = draggedCard.dataset.leadId;
            const $card     = $(draggedCard);

            // If "lost" — prompt for reason
            let lostReason = null;
            if (newStatus === 'lost') {
                lostReason = prompt('سبب الخسارة (اختياري):');
            }

            // Optimistic UI: move the card immediately
            $(this).append($card);

            $.ajax({
                url: '/admin/crm/leads/' + leadId + '/status',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    status: newStatus,
                    lost_reason: lostReason,
                },
                success: function (resp) {
                    if (window.toastr) {
                        toastr.success(resp.message || 'تم تحديث الحالة');
                    }
                    // Update counts after a brief delay
                    setTimeout(() => window.location.reload(), 600);
                },
                error: function (xhr) {
                    if (window.toastr) {
                        toastr.error(xhr.responseJSON?.message || 'فشل التحديث');
                    } else {
                        alert(xhr.responseJSON?.message || 'فشل التحديث');
                    }
                    window.location.reload();
                },
            });
        });
});
@endcan
</script>
@endpush
