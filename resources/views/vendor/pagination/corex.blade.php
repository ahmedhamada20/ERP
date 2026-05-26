{{-- CoreX themed pagination — Bootstrap 5 RTL with gold/navy accents. --}}
@if ($paginator->hasPages())
<nav role="navigation" aria-label="Pagination" class="corex-pagination-wrap">
    <div class="corex-pagination-info">
        عرض
        <strong>{{ $paginator->firstItem() ?? 0 }}</strong>
        إلى
        <strong>{{ $paginator->lastItem() ?? 0 }}</strong>
        من
        <strong>{{ $paginator->total() }}</strong>
        سجل
    </div>

    <ul class="corex-pagination">
        {{-- Previous (← in RTL means "next page") --}}
        @if ($paginator->onFirstPage())
            <li class="page disabled" aria-disabled="true">
                <span><i class="bi bi-chevron-double-right"></i></span>
            </li>
        @else
            <li class="page">
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="السابق" title="السابق">
                    <i class="bi bi-chevron-double-right"></i>
                </a>
            </li>
        @endif

        {{-- Numeric links --}}
        @foreach ($elements as $element)
            {{-- "Three Dots" Separator --}}
            @if (is_string($element))
                <li class="page disabled" aria-disabled="true"><span>…</span></li>
            @endif

            {{-- Array Of Links --}}
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <li class="page active" aria-current="page"><span>{{ $page }}</span></li>
                    @else
                        <li class="page"><a href="{{ $url }}">{{ $page }}</a></li>
                    @endif
                @endforeach
            @endif
        @endforeach

        {{-- Next --}}
        @if ($paginator->hasMorePages())
            <li class="page">
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="التالي" title="التالي">
                    <i class="bi bi-chevron-double-left"></i>
                </a>
            </li>
        @else
            <li class="page disabled" aria-disabled="true">
                <span><i class="bi bi-chevron-double-left"></i></span>
            </li>
        @endif
    </ul>
</nav>

@once
<style>
    .corex-pagination-wrap {
        display: flex; align-items: center; justify-content: space-between;
        gap: 1rem; flex-wrap: wrap;
        margin-top: 1.25rem; padding: .85rem 1rem;
        background: #fff; border-radius: 14px;
        border: 1px solid #f1f5f9;
        box-shadow: 0 1px 4px rgba(15,23,42,.04);
    }
    .corex-pagination-info {
        font-size: .82rem; color: #64748b;
    }
    .corex-pagination-info strong { color: var(--brand-navy); font-weight: 800; }

    .corex-pagination {
        display: flex; align-items: center; gap: .35rem;
        list-style: none; padding: 0; margin: 0;
    }
    .corex-pagination .page > a,
    .corex-pagination .page > span {
        display: inline-flex; align-items: center; justify-content: center;
        min-width: 38px; height: 38px; padding: 0 .75rem;
        border-radius: 10px; font-weight: 700; font-size: .88rem;
        color: #475569; background: #f8fafc;
        border: 1px solid #e2e8f0;
        text-decoration: none;
        transition: all .2s cubic-bezier(.4,0,.2,1);
        cursor: pointer;
    }
    .corex-pagination .page > a:hover {
        background: linear-gradient(135deg, #fffbeb, #fef3c7);
        border-color: var(--brand-gold);
        color: #92400e;
        transform: translateY(-2px);
        box-shadow: 0 4px 10px rgba(212,164,55,.18);
    }
    .corex-pagination .page.active > span {
        background: linear-gradient(135deg, var(--brand-navy), #1e293b);
        color: #fff;
        border-color: var(--brand-navy);
        box-shadow: 0 4px 12px rgba(15,23,42,.25);
        cursor: default;
    }
    .corex-pagination .page.disabled > span {
        background: #f1f5f9; color: #cbd5e1;
        cursor: not-allowed; border-color: #f1f5f9;
    }
    @media (max-width: 575.98px) {
        .corex-pagination-wrap { flex-direction: column; align-items: stretch; text-align: center; }
        .corex-pagination { justify-content: center; }
        .corex-pagination .page > a,
        .corex-pagination .page > span {
            min-width: 34px; height: 34px; padding: 0 .55rem; font-size: .82rem;
        }
    }
</style>
@endonce
</nav>
@endif
