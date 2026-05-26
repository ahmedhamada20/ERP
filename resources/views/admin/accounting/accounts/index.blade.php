@extends('layouts.master')

@section('title', 'دليل الحسابات')
@section('page_title', 'دليل الحسابات')
@section('page_subtitle', 'الهيكل الشجري لحسابات الشركة المالية')

@push('styles')
<style>
    .stat-mini {
        background: #fff; border-radius: 12px; padding: 1rem 1.25rem;
        border: 1px solid #e5e7eb; display: flex; align-items: center; gap: .9rem;
    }
    .stat-mini .si {
        width: 44px; height: 44px; border-radius: 10px;
        display: flex; align-items: center; justify-content: center; font-size: 1.3rem;
    }
    .stat-mini .sv { font-weight: 800; font-size: 1.35rem; color: #0f172a; line-height: 1; }
    .stat-mini .sl { font-size: .78rem; color: #6b7280; margin-top: .25rem; }

    /* Tree */
    .coa-tree { font-family: 'Cairo', sans-serif; }
    .coa-node {
        background: #fff; border-radius: 10px; border: 1px solid #e5e7eb;
        padding: .55rem .75rem; margin-bottom: .35rem;
        display: flex; align-items: center; gap: .6rem;
        transition: all .15s;
    }
    .coa-node:hover { border-color: #6366f1; box-shadow: 0 2px 8px rgba(99,102,241,.08); }
    .coa-node.is-group { background: #f8fafc; border-color: #cbd5e1; font-weight: 700; }
    .coa-node.is-group.depth-0 { background: linear-gradient(90deg, #eef2ff, #fff); border-color: #c7d2fe; }
    .coa-node.is-inactive { opacity: .55; }

    .coa-children { padding-inline-start: 2rem; border-inline-start: 2px dashed #e5e7eb; margin-inline-start: 1rem; }
    .coa-toggle { cursor: pointer; color: #6b7280; transition: transform .15s; }
    .coa-toggle.collapsed { transform: rotate(-90deg); }
    .coa-code { font-family: 'JetBrains Mono', monospace; color: #4f46e5; font-weight: 700; min-width: 60px; }
    .coa-name { flex: 1; }

    .type-badge {
        padding: .15rem .55rem; border-radius: 6px; font-size: .72rem; font-weight: 700;
    }
    .type-asset    { background: #dbeafe; color: #1e40af; }
    .type-liability{ background: #fee2e2; color: #b91c1c; }
    .type-equity   { background: #fef3c7; color: #92400e; }
    .type-revenue  { background: #dcfce7; color: #15803d; }
    .type-expense  { background: #fce7f3; color: #9d174d; }

    .coa-actions { display: flex; gap: .3rem; }
    .btn-light-info { background: #dbeafe; color: #1e40af; border: none; }
    .btn-light-info:hover { background: #bfdbfe; color: #1e40af; }
    .btn-light-danger { background: #fee2e2; color: #b91c1c; border: none; }
    .btn-light-danger:hover { background: #fecaca; color: #b91c1c; }
    .btn-light-success { background: #dcfce7; color: #15803d; border: none; }
    .btn-light-success:hover { background: #bbf7d0; color: #15803d; }

    .badge-system { background: #f1f5f9; color: #475569; font-size: .65rem; }
</style>
@endpush

@section('content')

{{-- Mini stats --}}
<div class="row g-2 mb-3">
    <div class="col-6 col-md-3">
        <div class="stat-mini">
            <div class="si" style="background:#eef2ff; color:#4f46e5;"><i class="bi bi-collection"></i></div>
            <div><div class="sv">{{ $counts['total'] }}</div><div class="sl">إجمالي الحسابات</div></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-mini">
            <div class="si" style="background:#dcfce7; color:#15803d;"><i class="bi bi-check2-circle"></i></div>
            <div><div class="sv">{{ $counts['active'] }}</div><div class="sl">حسابات نشطة</div></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-mini">
            <div class="si" style="background:#fef3c7; color:#92400e;"><i class="bi bi-folder2"></i></div>
            <div><div class="sv">{{ $counts['groups'] }}</div><div class="sl">حسابات مجمّعة</div></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-mini">
            <div class="si" style="background:#dbeafe; color:#1e40af;"><i class="bi bi-file-earmark-text"></i></div>
            <div><div class="sv">{{ $counts['postable'] }}</div><div class="sl">حسابات تفصيلية</div></div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h6 class="mb-0"><i class="bi bi-diagram-3"></i> الهيكل الشجري</h6>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-sm btn-outline-secondary" id="expandAll">
                <i class="bi bi-arrows-expand"></i> فرد الكل
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="collapseAll">
                <i class="bi bi-arrows-collapse"></i> طي الكل
            </button>
            @can('accounting.chart.manage')
            <a href="{{ route('admin.accounting.accounts.create') }}" class="btn btn-sm btn-primary">
                <i class="bi bi-plus-lg"></i> إضافة حساب
            </a>
            @endcan
        </div>
    </div>
    <div class="card-body">
        @if($roots->isEmpty())
            <div class="text-center text-muted py-5">
                <i class="bi bi-folder2-open" style="font-size:3rem; opacity:.3;"></i>
                <p class="mt-3 mb-0">دليل الحسابات فارغ. شغّل seeder الحسابات الافتراضي.</p>
            </div>
        @else
            <div class="coa-tree">
                @foreach($roots as $root)
                    @include('admin.accounting.accounts._tree_node', ['node' => $root, 'depth' => 0])
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Expand/collapse single node
    document.querySelectorAll('.coa-toggle').forEach(el => {
        el.addEventListener('click', () => {
            const target = document.getElementById(el.dataset.target);
            if (!target) return;
            target.classList.toggle('d-none');
            el.classList.toggle('collapsed');
        });
    });

    // Expand all
    document.getElementById('expandAll')?.addEventListener('click', () => {
        document.querySelectorAll('.coa-children').forEach(c => c.classList.remove('d-none'));
        document.querySelectorAll('.coa-toggle').forEach(t => t.classList.remove('collapsed'));
    });

    // Collapse all
    document.getElementById('collapseAll')?.addEventListener('click', () => {
        document.querySelectorAll('.coa-children').forEach(c => c.classList.add('d-none'));
        document.querySelectorAll('.coa-toggle').forEach(t => t.classList.add('collapsed'));
    });

    // Delete with AJAX
    document.querySelectorAll('.btn-delete-account').forEach(btn => {
        btn.addEventListener('click', async (e) => {
            e.preventDefault();
            if (!confirm('هل أنت متأكد من حذف هذا الحساب؟')) return;

            const url = btn.dataset.url;
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
            try {
                const res = await fetch(url, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                });
                const body = await res.json().catch(() => ({}));
                if (res.ok) {
                    location.reload();
                } else {
                    alert(body.message || 'فشل الحذف');
                }
            } catch (err) {
                alert('خطأ في الاتصال');
            }
        });
    });
});
</script>
@endpush
