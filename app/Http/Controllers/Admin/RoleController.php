<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RoleRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Yajra\DataTables\Facades\DataTables;

class RoleController extends Controller
{
    public function index()
    {
        return view('admin.roles.index');
    }

    public function data(Request $request)
    {
        $query = Role::query()
            ->withCount(['permissions', 'users'])
            ->where('guard_name', 'web');

        return DataTables::eloquent($query)
            ->addColumn('actions', function (Role $role) {
                $editBtn = $deleteBtn = '';
                if (auth()->user()->can('roles.update')) {
                    $editBtn = '<a href="' . route('admin.roles.edit', $role) . '" class="btn btn-icon btn-sm btn-info text-white" title="تعديل"><i class="bi bi-pencil"></i></a>';
                }
                if (auth()->user()->can('roles.delete') && $role->name !== 'super-admin') {
                    $deleteBtn = '<button type="button" data-url="' . route('admin.roles.destroy', $role) . '" class="btn btn-icon btn-sm btn-danger btn-delete" title="حذف"><i class="bi bi-trash"></i></button>';
                }
                return '<div class="table-actions">' . $editBtn . ' ' . $deleteBtn . '</div>';
            })
            ->addColumn('permissions_badge', fn (Role $r) =>
                '<span class="badge bg-info">' . $r->permissions_count . ' إذن</span>'
            )
            ->addColumn('users_badge', fn (Role $r) =>
                '<span class="badge bg-secondary">' . $r->users_count . ' مستخدم</span>'
            )
            ->editColumn('created_at', fn (Role $r) => $r->created_at?->format('Y-m-d'))
            ->rawColumns(['actions', 'permissions_badge', 'users_badge'])
            ->make(true);
    }

    public function create()
    {
        $permissions = Permission::where('guard_name', 'web')->get()->groupBy(function ($p) {
            return explode('.', $p->name)[0];
        });
        return view('admin.roles.create', compact('permissions'));
    }

    public function store(RoleRequest $request)
    {
        DB::transaction(function () use ($request) {
            $role = Role::create([
                'name'       => $request->name,
                'guard_name' => 'web',
            ]);
            $role->syncPermissions($request->permissions ?? []);
        });

        return redirect()->route('admin.roles.index')->with('success', __('messages.created_successfully'));
    }

    public function edit(Role $role)
    {
        abort_if($role->name === 'super-admin', 403, 'لا يمكن تعديل صلاحية المدير العام');

        $permissions    = Permission::where('guard_name', 'web')->get()->groupBy(function ($p) {
            return explode('.', $p->name)[0];
        });
        $rolePermissions = $role->permissions->pluck('name')->toArray();

        return view('admin.roles.edit', compact('role', 'permissions', 'rolePermissions'));
    }

    public function update(RoleRequest $request, Role $role)
    {
        abort_if($role->name === 'super-admin', 403);

        DB::transaction(function () use ($request, $role) {
            $role->update(['name' => $request->name]);
            $role->syncPermissions($request->permissions ?? []);
        });

        return redirect()->route('admin.roles.index')->with('success', __('messages.updated_successfully'));
    }

    public function destroy(Role $role)
    {
        if ($role->name === 'super-admin') {
            return response()->json(['message' => 'لا يمكن حذف صلاحية المدير العام'], 403);
        }
        if ($role->users()->exists()) {
            return response()->json(['message' => 'لا يمكن حذف هذه الصلاحية لأن هناك مستخدمين مرتبطين بها'], 422);
        }

        $role->delete();
        return response()->json(['message' => __('messages.deleted_successfully')]);
    }
}
