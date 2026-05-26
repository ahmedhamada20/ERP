@php
    $children = $byParent->get($node->id, collect());
    $hasChildren = $children->isNotEmpty();
    $childrenId = 'coa-' . $node->id;
@endphp

<div class="coa-node {{ $node->is_group ? 'is-group' : '' }} depth-{{ $depth }} {{ ! $node->is_active ? 'is-inactive' : '' }}">
    @if($hasChildren)
        <i class="bi bi-chevron-down coa-toggle" data-target="{{ $childrenId }}"></i>
    @else
        <span style="display:inline-block; width:1rem;"></span>
    @endif

    <span class="coa-code">{{ $node->code }}</span>
    <span class="coa-name">
        {{ $node->name }}
        @if($node->name_en)
            <small class="text-muted" dir="ltr">— {{ $node->name_en }}</small>
        @endif
    </span>

    <span class="type-badge type-{{ $node->type }}">{{ $node->type_label }}</span>

    @if($node->sub_type === 'cash')
        <span class="badge bg-warning text-dark" style="font-size:.65rem;"><i class="bi bi-cash"></i> خزينة</span>
    @elseif($node->sub_type === 'bank')
        <span class="badge bg-info text-white" style="font-size:.65rem;"><i class="bi bi-bank"></i> بنك</span>
    @endif

    @if($node->is_group)
        <span class="badge bg-secondary text-white" style="font-size:.65rem;"><i class="bi bi-folder2"></i> مجمّع</span>
    @endif

    @if(! $node->is_active)
        <span class="badge bg-danger text-white" style="font-size:.65rem;">متوقف</span>
    @endif

    @if($node->is_system)
        <span class="badge badge-system"><i class="bi bi-shield-lock"></i> نظام</span>
    @endif

    <div class="coa-actions ms-auto">
        @can('accounting.chart.manage')
            @if($node->is_group)
                <a href="{{ route('admin.accounting.accounts.create', ['parent_id' => $node->id]) }}"
                   class="btn btn-icon btn-sm btn-light-success" title="إضافة حساب فرعي">
                    <i class="bi bi-plus-lg"></i>
                </a>
            @endif
            <a href="{{ route('admin.accounting.accounts.edit', $node) }}"
               class="btn btn-icon btn-sm btn-light-info" title="تعديل">
                <i class="bi bi-pencil"></i>
            </a>
            @if(! $node->is_system && ! $hasChildren)
                <button type="button" class="btn btn-icon btn-sm btn-light-danger btn-delete-account"
                        data-url="{{ route('admin.accounting.accounts.destroy', $node) }}" title="حذف">
                    <i class="bi bi-trash"></i>
                </button>
            @endif
        @endcan
    </div>
</div>

@if($hasChildren)
    <div id="{{ $childrenId }}" class="coa-children">
        @foreach($children as $child)
            @include('admin.accounting.accounts._tree_node', ['node' => $child, 'depth' => $depth + 1])
        @endforeach
    </div>
@endif
