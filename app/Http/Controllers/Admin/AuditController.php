<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;
use Yajra\DataTables\Facades\DataTables;

class AuditController extends Controller
{
    public function index()
    {
        return view('admin.audit.index');
    }

    public function data(Request $request)
    {
        $query = Activity::query()
            ->select(['id', 'log_name', 'description', 'subject_type', 'subject_id',
                      'causer_type', 'causer_id', 'event', 'properties', 'created_at'])
            ->with(['causer:id,name,email'])
            ->latest();

        if ($request->filled('log_name')) {
            $query->where('log_name', $request->log_name);
        }
        if ($request->filled('event')) {
            $query->where('event', $request->event);
        }
        if ($request->filled('causer_id')) {
            $query->where('causer_id', $request->causer_id);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        return DataTables::eloquent($query)
            ->editColumn('description', fn (Activity $a) =>
                '<div class="fw-bold small">' . e($a->description) . '</div>'
                . '<div class="text-muted" style="font-size:.72rem;">' . e($a->subject_type ? class_basename($a->subject_type) . ' #' . $a->subject_id : '-') . '</div>'
            )
            ->editColumn('event', function (Activity $a) {
                $colors = ['created' => 'success', 'updated' => 'info', 'deleted' => 'danger'];
                $labels = ['created' => 'إنشاء',   'updated' => 'تعديل', 'deleted' => 'حذف'];
                $c = $colors[$a->event] ?? 'secondary';
                $l = $labels[$a->event] ?? $a->event;
                return '<span class="badge bg-' . $c . '-soft">' . $l . '</span>';
            })
            ->editColumn('log_name', fn (Activity $a) =>
                '<span class="badge bg-secondary-soft">' . e($a->log_name ?: '-') . '</span>'
            )
            ->addColumn('causer_name', fn (Activity $a) =>
                $a->causer ? e($a->causer->name) . '<br><small class="text-muted">' . e($a->causer->email) . '</small>' : '<span class="text-muted">— نظام —</span>'
            )
            ->editColumn('created_at', fn (Activity $a) =>
                '<div class="small">' . $a->created_at?->format('Y-m-d H:i') . '</div>'
                . '<div class="text-muted" style="font-size:.7rem;">' . $a->created_at?->diffForHumans() . '</div>'
            )
            ->addColumn('changes', function (Activity $a) {
                $props = $a->properties;
                if (!$props || (empty($props['old']) && empty($props['attributes']))) {
                    return '<span class="text-muted small">—</span>';
                }
                $old = $props['old'] ?? [];
                $new = $props['attributes'] ?? [];
                $keys = array_unique(array_merge(array_keys($old), array_keys($new)));
                $html = '<div class="small" style="max-width:340px;">';
                foreach (array_slice($keys, 0, 5) as $k) {
                    $oldV = $old[$k] ?? null;
                    $newV = $new[$k] ?? null;
                    $html .= '<div><strong>' . e($k) . ':</strong> ';
                    if ($oldV !== null) $html .= '<span class="text-danger">' . e((string) $oldV) . '</span> → ';
                    $html .= '<span class="text-success">' . e((string) $newV) . '</span></div>';
                }
                if (count($keys) > 5) {
                    $html .= '<div class="text-muted">+ ' . (count($keys) - 5) . ' حقل آخر</div>';
                }
                $html .= '</div>';
                return $html;
            })
            ->rawColumns(['description', 'event', 'log_name', 'causer_name', 'created_at', 'changes'])
            ->make(true);
    }
}
