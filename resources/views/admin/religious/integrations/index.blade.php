@extends('layouts.master')

@section('title', 'التكاملات الخارجية')
@section('page_title', 'التكاملات الخارجية للسياحة الدينية')
@section('page_subtitle', 'إدارة ربط النظام مع صفا وبوابة العمرة - حالة الخدمات، سجل المزامنات، الإحصائيات')

@push('styles')
<style>
    /* ── Provider cards ─────────────────────────────────────── */
    .provider-card {
        background:#fff; border-radius:16px; overflow:hidden;
        box-shadow:0 2px 8px rgba(15,23,42,.05);
        border:1px solid var(--brand-border);
        margin-bottom:1.25rem;
    }
    .provider-card .provider-head {
        padding:1.25rem 1.5rem;
        background:linear-gradient(135deg, #f8fafc 0%, #fff 100%);
        border-bottom:1px solid var(--brand-border);
        display:flex; align-items:center; gap:1rem; flex-wrap:wrap;
    }
    .provider-card .provider-icon {
        width:64px; height:64px; border-radius:14px;
        display:flex; align-items:center; justify-content:center;
        font-size:2rem; flex-shrink:0;
    }
    .provider-icon.success { background:#dcfce7; color:#15803d; }
    .provider-icon.primary { background:#dbeafe; color:#1d4ed8; }
    .provider-card .provider-title h5 { margin:0; color:var(--brand-navy); font-weight:800; }
    .provider-card .provider-title .name-en {
        font-size:.78rem; color:#94a3b8; font-family:'Cairo',monospace;
        margin-top:.15rem; direction:ltr;
    }
    .provider-card .provider-title .desc { font-size:.82rem; color:#64748b; margin-top:.25rem; }

    .provider-status {
        display:inline-flex; align-items:center; gap:.4rem;
        padding:.4rem .75rem; border-radius:8px;
        font-size:.78rem; font-weight:700;
    }
    .provider-status.live { background:#dcfce7; color:#15803d; }
    .provider-status.mock { background:#fef3c7; color:#92400e; }
    .provider-status .pulse {
        width:8px; height:8px; border-radius:50%;
        background:currentColor; animation:pulse 2s infinite;
    }
    @keyframes pulse {
        0%, 100% { opacity:1; transform:scale(1); }
        50%      { opacity:.5; transform:scale(.9); }
    }

    .provider-body { padding:1.25rem 1.5rem; }
    .provider-stats {
        display:grid; grid-template-columns:repeat(auto-fit, minmax(140px, 1fr));
        gap:.85rem; margin-bottom:1rem;
    }
    .stat-box {
        background:#f9fafb; border-radius:10px; padding:.85rem;
        border:1px solid #e5e7eb;
    }
    .stat-box .lbl { font-size:.7rem; color:#64748b; font-weight:600; margin-bottom:.3rem; }
    .stat-box .val { font-size:1.25rem; font-weight:800; color:var(--brand-navy); line-height:1; }
    .stat-box .val.success { color:#15803d; }
    .stat-box .val.danger { color:#b91c1c; }
    .stat-box .val.warning { color:#b45309; }
    .stat-box .sub { font-size:.65rem; color:#94a3b8; margin-top:.25rem; }

    .endpoint-display {
        background:#0f172a; color:#e2e8f0; padding:.6rem .9rem;
        border-radius:8px; font-family:'Courier New',monospace;
        font-size:.78rem; direction:ltr; text-align:left;
        margin-bottom:1rem;
        display:flex; align-items:center; justify-content:space-between;
    }
    .endpoint-display .copy-btn {
        background:transparent; border:none; color:#94a3b8;
        cursor:pointer; font-size:.85rem;
    }
    .endpoint-display .copy-btn:hover { color:#fff; }

    .provider-actions { display:flex; gap:.5rem; flex-wrap:wrap; }

    /* ── Logs table ─────────────────────────────────────────── */
    .logs-card { background:#fff; border-radius:14px; box-shadow:0 1px 3px rgba(15,23,42,.04); border:1px solid var(--brand-border); overflow:hidden; }
    .logs-card .head {
        padding:1rem 1.25rem; background:#f9fafb;
        border-bottom:1px solid var(--brand-border);
        display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:.5rem;
    }
    .logs-card .head h6 { margin:0; color:var(--brand-navy); font-weight:800; display:inline-flex; align-items:center; gap:.5rem; }

    .log-table th { background:#f9fafb; font-size:.78rem; color:#475569; font-weight:700; padding:.7rem .55rem; }
    .log-table td { padding:.7rem .55rem; font-size:.82rem; vertical-align:middle; }
    .log-table tbody tr:hover { background:#fafbff; }
    .log-table code { font-size:.75rem; }

    .status-badge {
        display:inline-flex; align-items:center; gap:.3rem;
        padding:.25rem .55rem; border-radius:6px;
        font-size:.7rem; font-weight:700;
    }
    .status-badge.success { background:#dcfce7; color:#15803d; }
    .status-badge.failed  { background:#fee2e2; color:#b91c1c; }
    .status-badge.pending { background:#fef3c7; color:#92400e; }

    /* ── Overall stats strip ─────────────────────────────────── */
    .overall-strip {
        display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr));
        gap:.85rem; margin-bottom:1.25rem;
    }
    .overall-tile { background:#fff; padding:1rem 1.15rem; border-radius:12px; box-shadow:0 1px 3px rgba(15,23,42,.04); border:1px solid var(--brand-border); }
    .overall-tile .lbl { font-size:.74rem; color:#64748b; font-weight:600; }
    .overall-tile .val { font-size:1.4rem; font-weight:800; color:var(--brand-navy); margin-top:.25rem; }

    @media (max-width: 768px) {
        .provider-card .provider-head { padding:1rem; }
        .provider-card .provider-icon { width:50px; height:50px; font-size:1.5rem; }
        .provider-card .provider-body { padding:1rem; }
        .stat-box .val { font-size:1.05rem; }
        .endpoint-display { font-size:.7rem; padding:.5rem .65rem; }
    }
</style>
@endpush

@section('content')

{{-- ════════════════════════════════════════════════════════════
     Overall stats strip
     ════════════════════════════════════════════════════════════ --}}
<div class="overall-strip">
    <div class="overall-tile">
        <div class="lbl"><i class="bi bi-activity"></i> إجمالي المزامنات</div>
        <div class="val">{{ number_format($overallStats->total ?? 0) }}</div>
    </div>
    <div class="overall-tile">
        <div class="lbl"><i class="bi bi-check-circle text-success"></i> الناجحة</div>
        <div class="val text-success">{{ number_format($overallStats->successful ?? 0) }}</div>
    </div>
    <div class="overall-tile">
        <div class="lbl"><i class="bi bi-x-circle text-danger"></i> الفاشلة</div>
        <div class="val text-danger">{{ number_format($overallStats->failed ?? 0) }}</div>
    </div>
    <div class="overall-tile">
        <div class="lbl"><i class="bi bi-calendar-day"></i> مكالمات اليوم</div>
        <div class="val">{{ number_format($overallStats->today_calls ?? 0) }}</div>
    </div>
</div>

{{-- ════════════════════════════════════════════════════════════
     Provider Cards
     ════════════════════════════════════════════════════════════ --}}
@foreach($providers as $key => $p)
<div class="provider-card">
    <div class="provider-head">
        <div class="provider-icon {{ $p['color'] }}">
            <i class="bi {{ $p['icon'] }}"></i>
        </div>
        <div class="provider-title flex-grow-1">
            <h5>{{ $p['name'] }}</h5>
            <div class="name-en">{{ $p['name_en'] }} integration</div>
            <div class="desc">{{ $p['description'] }}</div>
        </div>
        <div>
            <span class="provider-status {{ $p['is_mock'] ? 'mock' : 'live' }}">
                <span class="pulse"></span>
                {{ $p['is_mock'] ? 'وضع المحاكاة (Mock)' : 'متصل (Live)' }}
            </span>
        </div>
    </div>

    <div class="provider-body">

        {{-- Endpoint --}}
        <div class="endpoint-display">
            <span>{{ $p['endpoint'] }}</span>
            <button type="button" class="copy-btn" onclick="navigator.clipboard.writeText('{{ $p['endpoint'] }}'); toastr.info('تم النسخ', '', {timeOut: 1500})">
                <i class="bi bi-clipboard"></i>
            </button>
        </div>

        {{-- Stats grid --}}
        <div class="provider-stats">
            <div class="stat-box">
                <div class="lbl">إجمالي المكالمات</div>
                <div class="val">{{ number_format($p['stats']['total_calls']) }}</div>
            </div>
            <div class="stat-box">
                <div class="lbl">معدل النجاح</div>
                <div class="val success">{{ $p['stats']['success_rate'] }}%</div>
                <div class="sub">{{ $p['stats']['successful'] }} ناجح / {{ $p['stats']['failed'] }} فشل</div>
            </div>
            <div class="stat-box">
                <div class="lbl">متوسط زمن الاستجابة</div>
                <div class="val">{{ $p['stats']['avg_duration'] ? $p['stats']['avg_duration'] . ' ms' : '—' }}</div>
            </div>
            <div class="stat-box">
                <div class="lbl">حجوزات متزامنة</div>
                <div class="val success">{{ number_format($p['sync_stats']['synced_bookings']) }}</div>
            </div>
            <div class="stat-box">
                <div class="lbl">حجوزات معلقة</div>
                <div class="val warning">{{ number_format($p['sync_stats']['pending_bookings']) }}</div>
            </div>
            <div class="stat-box">
                <div class="lbl">آخر مزامنة</div>
                <div class="val" style="font-size:.85rem;">
                    @if($p['stats']['last_call_at'])
                        {{ \Carbon\Carbon::parse($p['stats']['last_call_at'])->diffForHumans() }}
                    @else
                        — لم تتم —
                    @endif
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="provider-actions">
            <form method="POST" action="{{ route('admin.religious.integrations.test') }}">
                @csrf
                <input type="hidden" name="provider" value="{{ $key }}">
                <button type="submit" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-broadcast"></i> اختبار الاتصال
                </button>
            </form>

            @if($p['sync_stats']['pending_bookings'] > 0)
            @canany(['religious_bookings.sync_safa', 'religious_bookings.sync_umrah_portal'])
            <form method="POST" action="{{ route('admin.religious.integrations.bulk_sync') }}"
                  onsubmit="return confirm('سيتم محاولة مزامنة حتى 50 حجز معلق. هل تريد المتابعة؟');">
                @csrf
                <input type="hidden" name="provider" value="{{ $key }}">
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="bi bi-arrow-repeat"></i> مزامنة جماعية ({{ $p['sync_stats']['pending_bookings'] }})
                </button>
            </form>
            @endcanany
            @endif

            <a href="{{ route('admin.religious.integrations.index', ['provider' => $key]) }}"
               class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-list-ul"></i> عرض السجل
            </a>
        </div>

        @if($p['is_mock'])
        <div class="alert alert-warning mt-3 mb-0 small">
            <i class="bi bi-info-circle"></i>
            <strong>وضع المحاكاة:</strong> هذا التكامل يعمل ببيانات تجريبية. سيتم استبدال البيانات الحقيقية بعد استلام بيانات الـ API من العميل.
            <br>
            <small>الكود موجود في: <code>app/Services/Religious/{{ $key === 'safa' ? 'SafaService' : 'UmrahPortalService' }}.php</code></small>
        </div>
        @endif
    </div>
</div>
@endforeach

{{-- ════════════════════════════════════════════════════════════
     Integration Logs Table
     ════════════════════════════════════════════════════════════ --}}
<div class="logs-card mt-4">
    <div class="head">
        <h6><i class="bi bi-journal-text"></i> سجل المزامنات</h6>

        <form method="GET" class="d-flex gap-2 flex-wrap align-items-center" style="font-size:.85rem;">
            <select name="provider" class="form-select form-select-sm" style="width:auto;" onchange="this.form.submit()">
                <option value="">— كل المزودين —</option>
                <option value="safa" @selected(request('provider') === 'safa')>صفا</option>
                <option value="umrah_portal" @selected(request('provider') === 'umrah_portal')>بوابة العمرة</option>
            </select>
            <select name="status" class="form-select form-select-sm" style="width:auto;" onchange="this.form.submit()">
                <option value="">— كل الحالات —</option>
                <option value="success" @selected(request('status') === 'success')>ناجح</option>
                <option value="failed" @selected(request('status') === 'failed')>فاشل</option>
            </select>
            @if(request()->hasAny(['provider', 'status']))
                <a href="{{ route('admin.religious.integrations.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-x"></i> إعادة تعيين
                </a>
            @endif
        </form>
    </div>

    <div class="table-responsive">
        <table class="table mb-0 log-table">
            <thead>
                <tr>
                    <th>التاريخ والوقت</th>
                    <th>المزود</th>
                    <th>العملية</th>
                    <th>الحجز</th>
                    <th>المستخدم</th>
                    <th>الحالة</th>
                    <th>المدة</th>
                    <th>التفاصيل</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                <tr>
                    <td class="small">
                        {{ $log->created_at?->format('Y-m-d') }}
                        <div class="text-muted" style="font-size:.7rem;">{{ $log->created_at?->format('H:i:s') }}</div>
                    </td>
                    <td><span class="badge bg-{{ $log->provider === 'safa' ? 'success-soft' : 'primary-soft' }}">{{ $log->provider_label }}</span></td>
                    <td>{{ $log->action_label }}</td>
                    <td>
                        @if($log->booking)
                            <a href="{{ route('admin.religious.bookings.show', $log->booking) }}"><code>{{ $log->booking->booking_number }}</code></a>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td class="small">{{ $log->trigger?->name ?: 'النظام' }}</td>
                    <td>
                        <span class="status-badge {{ $log->status }}">
                            <i class="bi bi-{{ $log->status === 'success' ? 'check-circle' : ($log->status === 'failed' ? 'x-circle' : 'clock') }}"></i>
                            {{ $log->status_label }}
                        </span>
                    </td>
                    <td class="small text-muted">{{ $log->duration_ms ? $log->duration_ms . ' ms' : '—' }}</td>
                    <td>
                        <a href="{{ route('admin.religious.integrations.log_detail', $log) }}"
                           class="btn btn-icon btn-sm btn-light-info" title="عرض التفاصيل">
                            <i class="bi bi-eye"></i>
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center text-muted py-4">
                        <i class="bi bi-inbox" style="font-size:2rem; opacity:.4;"></i>
                        <div class="mt-2">لا توجد سجلات بعد</div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($logs->hasPages())
    <div class="p-3 d-flex justify-content-center">
        {{ $logs->links() }}
    </div>
    @endif
</div>

{{-- ════════════════════════════════════════════════════════════
     Configuration Info
     ════════════════════════════════════════════════════════════ --}}
<div class="card mt-4">
    <div class="card-header">
        <h6 class="mb-0"><i class="bi bi-gear"></i> معلومات الإعداد</h6>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <h6 class="text-muted small">📍 موقع كود التكاملات</h6>
                <ul class="small mb-0">
                    <li><code>app/Services/Religious/SafaService.php</code></li>
                    <li><code>app/Services/Religious/UmrahPortalService.php</code></li>
                </ul>
            </div>
            <div class="col-md-6">
                <h6 class="text-muted small">⚙️ كيف ترفع التكامل للوضع الحقيقي</h6>
                <ol class="small mb-0">
                    <li>أضف <code>SAFA_API_KEY</code> و <code>UMRAH_PORTAL_API_KEY</code> في ملف <code>.env</code></li>
                    <li>استبدل body الدوال في الـ Services بـ HTTP calls حقيقية</li>
                    <li>عيّن <code>'is_mock' => false</code> في هذا الـ Controller</li>
                </ol>
            </div>
        </div>
    </div>
</div>

@endsection
