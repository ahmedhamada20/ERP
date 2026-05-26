<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CustomerRequest;
use App\Models\Customer;
use App\Traits\HandlesImageUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class CustomerController extends Controller
{
    use HandlesImageUpload;

    /** Cache key for KPI stats — flushed via Customer model observer. */
    private const STATS_CACHE_KEY = 'customers.kpi_stats';
    private const STATS_TTL       = 300;     // 5 minutes
    private const LISTS_TTL       = 1800;    // 30 minutes for nationalities/cities

    public function index()
    {
        $stats = Cache::remember(self::STATS_CACHE_KEY, self::STATS_TTL, function () {
            // Single round-trip aggregation — far cheaper than 6 separate COUNT queries
            // when the table has 1M+ rows.
            $row = DB::table('customers')
                ->selectRaw("
                    COUNT(*) AS total,
                    SUM(CASE WHEN status = 'active'       THEN 1 ELSE 0 END) AS active,
                    SUM(CASE WHEN type   = 'agency'       THEN 1 ELSE 0 END) AS agencies,
                    SUM(CASE WHEN status = 'blacklisted'  THEN 1 ELSE 0 END) AS blacklisted,
                    SUM(CASE WHEN created_at >= ? THEN 1 ELSE 0 END) AS new_month,
                    SUM(CASE WHEN passport_expiry_date BETWEEN ? AND ? THEN 1 ELSE 0 END) AS expiring
                ", [
                    now()->startOfMonth(),
                    now(),
                    now()->addMonths(6),
                ])
                ->whereNull('deleted_at')
                ->first();

            return [
                'total'       => (int) ($row->total ?? 0),
                'active'      => (int) ($row->active ?? 0),
                'agencies'    => (int) ($row->agencies ?? 0),
                'new_month'   => (int) ($row->new_month ?? 0),
                'expiring'    => (int) ($row->expiring ?? 0),
                'blacklisted' => (int) ($row->blacklisted ?? 0),
            ];
        });

        $nationalities = Cache::remember('customers.nationalities', self::LISTS_TTL, fn () =>
            Customer::whereNotNull('nationality')->where('nationality', '!=', '')
                ->distinct()->orderBy('nationality')
                ->pluck('nationality')
        );

        $cities = Cache::remember('customers.cities', self::LISTS_TTL, fn () =>
            Customer::whereNotNull('city')->where('city', '!=', '')
                ->distinct()->orderBy('city')
                ->pluck('city')
        );

        return view('admin.customers.index', compact('stats', 'nationalities', 'cities'));
    }

    public function data(Request $request)
    {
        // Select only the columns we actually render. Avoids loading TEXT columns
        // (notes) and unused image paths for the table — huge gain at 1M rows.
        $cols = [
            'id', 'code', 'full_name', 'full_name_en', 'national_id',
            'passport_number', 'passport_expiry_date',
            'phone', 'whatsapp', 'email', 'photo',
            'gender', 'type', 'status', 'created_by', 'created_at',
        ];

        $query = Customer::query()
            ->select($cols)
            ->with('creator:id,name');

        // ── Quick chips (status / type / passport-expiring) ──────────────
        if ($request->filled('status_filter')) {
            $query->where('status', $request->status_filter);
        }
        if ($request->filled('type_filter')) {
            $query->where('type', $request->type_filter);
        }

        // ── Fast search using FULLTEXT index when available ──────────────
        if ($request->filled('q')) {
            $term = trim((string) $request->q);
            $this->applySearch($query, $term);
        }

        if ($request->filled('nationality')) {
            $query->where('nationality', $request->nationality);
        }
        if ($request->filled('city')) {
            $query->where('city', $request->city);
        }
        if ($request->filled('gender')) {
            $query->where('gender', $request->gender);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $passportFilter = $request->passport_filter;
        if ($passportFilter === 'expiring') {
            $query->whereNotNull('passport_expiry_date')
                  ->whereBetween('passport_expiry_date', [now(), now()->addMonths(6)]);
        } elseif ($passportFilter === 'valid') {
            $query->whereNotNull('passport_expiry_date')
                  ->where('passport_expiry_date', '>', now()->addMonths(6));
        } elseif ($passportFilter === 'expired') {
            $query->whereNotNull('passport_expiry_date')
                  ->where('passport_expiry_date', '<', now());
        } elseif ($passportFilter === 'missing') {
            $query->whereNull('passport_number');
        }

        return DataTables::eloquent($query)
            ->addColumn('customer_info', function (Customer $c) {
                $sub = $c->full_name_en ? '<div class="cust-sub" dir="ltr">' . e($c->full_name_en) . '</div>' : '';
                return '<div class="cust-cell">'
                    . '<img src="' . $c->photo_url . '" class="cust-avatar" loading="lazy" onerror="this.onerror=null;this.src=\'data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%2240%22 height=%2240%22><rect width=%22100%25%22 height=%22100%25%22 fill=%22%23eef0f5%22/></svg>\';">'
                    . '<div class="cust-body">'
                    . '<div class="cust-name">' . e($c->full_name) . '</div>'
                    . '<div class="cust-code"><i class="bi bi-hash"></i> ' . e($c->code) . '</div>'
                    . $sub
                    . '</div></div>';
            })
            ->addColumn('contact_info', function (Customer $c) {
                $html  = '<div class="contact-cell">';
                $html .= '<div><i class="bi bi-telephone text-success"></i> <span dir="ltr">' . e($c->phone) . '</span></div>';
                if ($c->whatsapp) {
                    $html .= '<div><i class="bi bi-whatsapp text-success"></i> <span dir="ltr">' . e($c->whatsapp) . '</span></div>';
                }
                if ($c->email) {
                    $html .= '<div class="text-muted small"><i class="bi bi-envelope"></i> <span dir="ltr">' . e($c->email) . '</span></div>';
                }
                $html .= '</div>';
                return $html;
            })
            ->addColumn('passport_info', function (Customer $c) {
                if (!$c->passport_number) {
                    return '<span class="text-muted small">—</span>';
                }
                $exp = $c->passport_expiry_date;
                $badge = '';
                if ($exp) {
                    $daysLeft = now()->diffInDays($exp, false);
                    if ($daysLeft < 0) {
                        $badge = '<span class="badge bg-danger-soft"><i class="bi bi-exclamation-triangle"></i> منتهي</span>';
                    } elseif ($daysLeft <= 180) {
                        $badge = '<span class="badge bg-warning-soft"><i class="bi bi-clock"></i> ' . (int)$daysLeft . ' يوم</span>';
                    } else {
                        $badge = '<span class="badge bg-success-soft"><i class="bi bi-check-circle"></i> سارٍ</span>';
                    }
                }
                return '<div class="pass-cell">'
                    . '<div><code class="small">' . e($c->passport_number) . '</code></div>'
                    . ($exp ? '<div class="small text-muted">' . $exp->format('Y-m-d') . '</div>' : '')
                    . $badge
                    . '</div>';
            })
            ->editColumn('type', fn (Customer $c) =>
                '<span class="badge type-' . $c->type . '">' . $c->type_label . '</span>'
            )
            ->editColumn('status', fn (Customer $c) =>
                '<span class="badge bg-' . $c->status_badge . '-soft">' . $c->status_label . '</span>'
            )
            ->editColumn('created_at', fn (Customer $c) =>
                '<div class="small">' . $c->created_at?->format('Y-m-d') . '</div>'
                . '<div class="text-muted" style="font-size:.7rem;">' . $c->created_at?->diffForHumans() . '</div>'
            )
            ->addColumn('actions', function (Customer $c) {
                $user = auth()->user();
                $buttons = '<a href="' . route('admin.customers.show', $c) . '" class="btn btn-icon btn-sm btn-light-primary" title="عرض"><i class="bi bi-eye"></i></a> ';
                if ($user && $user->can('customers.update')) {
                    $buttons .= '<a href="' . route('admin.customers.edit', $c) . '" class="btn btn-icon btn-sm btn-light-info" title="تعديل"><i class="bi bi-pencil"></i></a> ';
                }
                if ($c->whatsapp || $c->phone) {
                    $wa = preg_replace('/[^0-9]/', '', $c->whatsapp ?: $c->phone);
                    $buttons .= '<a href="https://wa.me/' . $wa . '" target="_blank" rel="noopener" class="btn btn-icon btn-sm btn-light-success" title="واتساب"><i class="bi bi-whatsapp"></i></a> ';
                }
                if ($user && $user->can('customers.delete')) {
                    $buttons .= '<button data-url="' . route('admin.customers.destroy', $c) . '" class="btn btn-icon btn-sm btn-light-danger btn-delete" title="حذف"><i class="bi bi-trash"></i></button>';
                }
                return '<div class="table-actions">' . $buttons . '</div>';
            })
            ->rawColumns(['customer_info', 'contact_info', 'passport_info', 'type', 'status', 'created_at', 'actions'])
            ->make(true);
    }

    /**
     * Apply search to the query.
     * Uses FULLTEXT MATCH when on MySQL for sub-second search on 1M+ rows.
     * Falls back to LIKE for short terms or other DB drivers.
     */
    private function applySearch($query, string $term): void
    {
        $isMySql   = DB::connection()->getDriverName() === 'mysql';
        $isLongEnough = mb_strlen($term) >= 3;
        // FULLTEXT requires escaped operators and minimum word length (4 by default in InnoDB).
        // For shorter terms or non-alphanumerics we fall back to LIKE on indexed columns.
        $isAlnum = preg_match('/^[\p{L}\p{N}\s\-_@.]+$/u', $term);

        if ($isMySql && $isLongEnough && $isAlnum) {
            $boolean = '+' . str_replace([' ', '+', '-', '@', '(', ')'], ' ', $term) . '*';
            $query->whereRaw(
                "MATCH(full_name, full_name_en, email, phone, national_id, passport_number) AGAINST (? IN BOOLEAN MODE)",
                [$boolean]
            );
            return;
        }

        // Fallback: leading-anchored LIKE on the most-likely columns.
        // We avoid `%term%` because it can't use any index on 1M+ rows.
        $query->where(function ($w) use ($term) {
            $w->where('code',            'like', "{$term}%")
              ->orWhere('phone',         'like', "{$term}%")
              ->orWhere('mobile',        'like', "{$term}%")
              ->orWhere('whatsapp',      'like', "{$term}%")
              ->orWhere('national_id',   'like', "{$term}%")
              ->orWhere('passport_number','like', "{$term}%")
              ->orWhere('email',         'like', "{$term}%")
              ->orWhere('full_name',     'like', "{$term}%")
              ->orWhere('full_name_en',  'like', "{$term}%");
        });
    }

    public function create()
    {
        return view('admin.customers.create');
    }

    public function store(CustomerRequest $request)
    {
        DB::transaction(function () use ($request) {
            $data = $request->validated();

            foreach (['photo', 'passport_image', 'national_id_image'] as $field) {
                if ($request->hasFile($field)) {
                    $data[$field] = $this->uploadImage($request->file($field), 'customers/' . $field);
                }
            }

            Customer::create($data);
        });

        $this->flushStatsCache();

        return redirect()->route('admin.customers.index')->with('success', __('messages.created_successfully'));
    }

    public function show(Customer $customer)
    {
        $customer->load('creator');
        return view('admin.customers.show', compact('customer'));
    }

    public function edit(Customer $customer)
    {
        return view('admin.customers.edit', compact('customer'));
    }

    public function update(CustomerRequest $request, Customer $customer)
    {
        DB::transaction(function () use ($request, $customer) {
            $data = $request->validated();

            foreach (['photo', 'passport_image', 'national_id_image'] as $field) {
                if ($request->hasFile($field)) {
                    $data[$field] = $this->uploadImage($request->file($field), 'customers/' . $field, $customer->$field);
                }
            }

            $customer->update($data);
        });

        $this->flushStatsCache();

        return redirect()->route('admin.customers.index')->with('success', __('messages.updated_successfully'));
    }

    public function destroy(Customer $customer)
    {
        foreach (['photo', 'passport_image', 'national_id_image'] as $field) {
            $this->deleteImage($customer->$field);
        }
        $customer->delete();

        $this->flushStatsCache();

        return response()->json(['message' => __('messages.deleted_successfully')]);
    }

    private function flushStatsCache(): void
    {
        Cache::forget(self::STATS_CACHE_KEY);
        Cache::forget('customers.nationalities');
        Cache::forget('customers.cities');
    }
}
