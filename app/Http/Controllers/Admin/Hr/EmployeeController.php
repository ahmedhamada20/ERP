<?php

namespace App\Http\Controllers\Admin\Hr;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EmployeeRequest;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\Facades\DataTables;

class EmployeeController extends Controller
{
    private const STATS_CACHE_KEY = 'employees.kpi_stats';
    private const STATS_TTL       = 600;

    public function index()
    {
        $stats = Cache::remember(self::STATS_CACHE_KEY, self::STATS_TTL, function () {
            return [
                'total'      => Employee::count(),
                'active'     => Employee::where('status', 'active')->count(),
                'on_leave'   => Employee::where('status', 'on_leave')->count(),
                'terminated' => Employee::where('status', 'terminated')->count(),
                'suspended'  => Employee::where('status', 'suspended')->count(),
            ];
        });

        $branches    = Branch::active()->orderBy('name')->get(['id', 'name']);
        $departments = Department::active()->orderBy('name')->get(['id', 'name']);

        return view('admin.hr.employees.index', compact('stats', 'branches', 'departments'));
    }

    public function data(Request $request)
    {
        $canSeeSalary = auth()->user()?->can('employees.view_salary');

        $cols = ['id', 'code', 'full_name', 'phone', 'email', 'photo',
                 'branch_id', 'department_id', 'position_id',
                 'hire_date', 'employment_type', 'status', 'created_at'];

        if ($canSeeSalary) {
            $cols = array_merge($cols, ['basic_salary', 'housing_allowance',
                                        'transport_allowance', 'other_allowances']);
        }

        $query = Employee::query()->select($cols)
            ->with([
                'branch:id,name',
                'department:id,name',
                'position:id,title,default_basic_salary,default_housing_allowance,default_transport_allowance,default_other_allowances',
            ]);

        if ($request->filled('status_filter')) {
            $query->where('status', $request->status_filter);
        }
        if ($request->filled('branch_filter')) {
            $query->where('branch_id', $request->branch_filter);
        }
        if ($request->filled('department_filter')) {
            $query->where('department_id', $request->department_filter);
        }
        if ($request->filled('q')) {
            $term = trim((string) $request->q);
            $query->where(function ($q) use ($term) {
                $q->where('full_name', 'like', "%{$term}%")
                  ->orWhere('code', 'like', "%{$term}%")
                  ->orWhere('national_id', 'like', "%{$term}%")
                  ->orWhere('phone', 'like', "%{$term}%")
                  ->orWhere('email', 'like', "%{$term}%");
            });
        }

        return DataTables::eloquent($query)
            ->addColumn('emp_info', function (Employee $e) {
                $photo = $e->photo
                    ? asset('storage/' . $e->photo)
                    : asset('admin/img/user-placeholder.png');
                return '<div class="d-flex align-items-center gap-2">'
                    . '<img src="' . e($photo) . '" class="avatar-sm rounded-circle" alt="" style="width:38px;height:38px;object-fit:cover;border:2px solid #e2e8f0;">'
                    . '<div>'
                    . '<div><strong>' . e($e->full_name) . '</strong></div>'
                    . '<div class="text-muted small"><i class="bi bi-hash"></i>' . e($e->code) . '</div>'
                    . '</div></div>';
            })
            ->addColumn('contact', function (Employee $e) {
                $html = '<div class="small">';
                if ($e->phone) {
                    $html .= '<div><i class="bi bi-telephone text-primary"></i> <span dir="ltr">' . e($e->phone) . '</span></div>';
                }
                if ($e->email) {
                    $html .= '<div class="text-muted x-small"><i class="bi bi-envelope"></i> ' . e($e->email) . '</div>';
                }
                $html .= '</div>';
                return $html;
            })
            ->addColumn('org', function (Employee $e) {
                $html = '<div class="small">';
                if ($e->position) {
                    $html .= '<div><strong>' . e($e->position->title) . '</strong></div>';
                }
                if ($e->department) {
                    $html .= '<div class="text-muted"><i class="bi bi-diagram-3"></i> ' . e($e->department->name) . '</div>';
                }
                if ($e->branch) {
                    $html .= '<div class="text-muted x-small"><i class="bi bi-buildings"></i> ' . e($e->branch->name) . '</div>';
                }
                $html .= '</div>';
                return $html ?: '<span class="text-muted">—</span>';
            })
            ->addColumn('salary', function (Employee $e) use ($canSeeSalary) {
                if (! $canSeeSalary) {
                    return '<span class="text-muted x-small"><i class="bi bi-lock"></i> محجوب</span>';
                }
                $gross = $e->effectiveGrossSalary();
                if ($gross <= 0) {
                    return '<span class="text-muted small">—</span>';
                }
                return '<div class="small"><strong class="text-success">'
                    . number_format($gross, 2) . '</strong> <span class="text-muted x-small">ج.م</span></div>';
            })
            ->editColumn('status', function (Employee $e) {
                $badge = $e->status_badge;
                $label = $e->status_label;
                return '<span class="badge bg-' . $badge . '-soft">' . e($label) . '</span>';
            })
            ->editColumn('hire_date', fn (Employee $e) =>
                $e->hire_date
                    ? '<div class="small">' . $e->hire_date->format('Y-m-d')
                        . '<div class="text-muted x-small">' . number_format($e->years_of_service, 1) . ' سنة</div></div>'
                    : '—'
            )
            ->addColumn('actions', function (Employee $e) {
                $user = auth()->user();
                $buttons = '<a href="' . route('admin.hr.employees.show', $e) . '" class="btn btn-icon btn-sm btn-light-primary" title="عرض"><i class="bi bi-eye"></i></a> ';
                if ($user && $user->can('employees.update')) {
                    $buttons .= '<a href="' . route('admin.hr.employees.edit', $e) . '" class="btn btn-icon btn-sm btn-light-info" title="تعديل"><i class="bi bi-pencil"></i></a> ';
                }
                if ($user && $user->can('employees.delete')) {
                    $buttons .= '<button data-url="' . route('admin.hr.employees.destroy', $e) . '" class="btn btn-icon btn-sm btn-light-danger btn-delete" title="حذف"><i class="bi bi-trash"></i></button>';
                }
                return '<div class="table-actions">' . $buttons . '</div>';
            })
            ->rawColumns(['emp_info', 'contact', 'org', 'salary', 'status', 'hire_date', 'actions'])
            ->make(true);
    }

    public function create()
    {
        return view('admin.hr.employees.create', $this->formOptions());
    }

    public function store(EmployeeRequest $request)
    {
        $data = $this->prepareData($request);

        $employee = DB::transaction(function () use ($data, $request) {
            if ($request->hasFile('photo')) {
                $data['photo'] = $request->file('photo')->store('employees/photos', 'public');
            }
            if ($request->hasFile('id_image')) {
                $data['id_image'] = $request->file('id_image')->store('employees/ids', 'public');
            }
            return Employee::create($data);
        });

        Cache::forget(self::STATS_CACHE_KEY);

        return redirect()
            ->route('admin.hr.employees.show', $employee)
            ->with('success', 'تم إضافة الموظف بنجاح');
    }

    public function show(Employee $employee)
    {
        $employee->load([
            'branch', 'department', 'position',
            'manager:id,code,full_name,position_id',
            'manager.position:id,title',
            'subordinates:id,code,full_name,position_id,reports_to,photo,status',
            'subordinates.position:id,title',
            'user:id,name,email',
            'creator:id,name',
            'documents' => fn ($q) => $q->latest(),
        ]);

        $canSeeSalary = auth()->user()?->can('employees.view_salary');

        return view('admin.hr.employees.show', compact('employee', 'canSeeSalary'));
    }

    public function edit(Employee $employee)
    {
        return view('admin.hr.employees.edit',
            array_merge($this->formOptions(), compact('employee')));
    }

    public function update(EmployeeRequest $request, Employee $employee)
    {
        $data = $this->prepareData($request);

        DB::transaction(function () use ($data, $request, $employee) {
            if ($request->hasFile('photo')) {
                if ($employee->photo) {
                    Storage::disk('public')->delete($employee->photo);
                }
                $data['photo'] = $request->file('photo')->store('employees/photos', 'public');
            }
            if ($request->hasFile('id_image')) {
                if ($employee->id_image) {
                    Storage::disk('public')->delete($employee->id_image);
                }
                $data['id_image'] = $request->file('id_image')->store('employees/ids', 'public');
            }
            $employee->update($data);
        });

        Cache::forget(self::STATS_CACHE_KEY);

        return redirect()
            ->route('admin.hr.employees.show', $employee)
            ->with('success', 'تم تحديث بيانات الموظف');
    }

    public function destroy(Employee $employee)
    {
        if ($employee->subordinates()->exists()) {
            return response()->json([
                'message' => 'لا يمكن حذف الموظف — يوجد موظفون تابعون له. أعد إسناد المرؤوسين أولاً.',
            ], 422);
        }

        DB::transaction(function () use ($employee) {
            if ($employee->photo) {
                Storage::disk('public')->delete($employee->photo);
            }
            if ($employee->id_image) {
                Storage::disk('public')->delete($employee->id_image);
            }
            $employee->delete();
        });

        Cache::forget(self::STATS_CACHE_KEY);

        return response()->json(['message' => 'تم حذف الموظف']);
    }

    /** Form dropdown options shared by create/edit. */
    private function formOptions(): array
    {
        return [
            'branches'    => Branch::active()->orderBy('name')->get(['id', 'name']),
            'departments' => Department::active()->orderBy('name')->get(['id', 'name', 'branch_id']),
            'positions'   => Position::active()->orderBy('title')
                              ->get(['id', 'title', 'department_id',
                                     'default_basic_salary', 'default_housing_allowance',
                                     'default_transport_allowance', 'default_other_allowances',
                                     'commission_rate', 'commission_basis']),
            'managers'    => Employee::query()
                              ->where('status', 'active')
                              ->orderBy('full_name')
                              ->get(['id', 'code', 'full_name', 'position_id']),
            'users'       => User::query()
                              ->whereDoesntHave('employee')
                              ->orderBy('name')
                              ->get(['id', 'name', 'email']),
        ];
    }

    /**
     * Pull validated data and strip salary/commission fields if the caller
     * doesn't have permission to set them — prevents privilege bypass via
     * crafted form submissions.
     */
    private function prepareData(EmployeeRequest $request): array
    {
        $data = $request->validated();

        if (! auth()->user()?->can('employees.view_salary')) {
            unset(
                $data['basic_salary'],
                $data['housing_allowance'],
                $data['transport_allowance'],
                $data['other_allowances'],
                $data['commission_rate'],
                $data['commission_basis'],
                $data['payment_method'],
                $data['bank_name'],
                $data['bank_account'],
                $data['iban'],
            );
        }

        return $data;
    }
}
