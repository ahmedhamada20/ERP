<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserRequest;
use App\Models\User;
use App\Traits\HandlesImageUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Yajra\DataTables\Facades\DataTables;

class UserController extends Controller
{
    use HandlesImageUpload;

    public function index()
    {
        return view('admin.users.index');
    }

    public function data(Request $request)
    {
        $query = User::query()->with('roles')->latest();

        return DataTables::eloquent($query)
            ->addColumn('avatar', fn (User $u) =>
                '<img src="' . $u->avatar_url . '" alt="' . e($u->name) . '" class="rounded-circle" width="36" height="36" style="object-fit:cover;">'
            )
            ->addColumn('roles_list', function (User $u) {
                if ($u->roles->isEmpty()) return '<span class="badge bg-light text-dark">بدون</span>';
                return $u->roles->map(fn ($r) => '<span class="badge bg-info">' . $r->name . '</span>')->implode(' ');
            })
            ->editColumn('is_active', fn (User $u) =>
                $u->is_active
                    ? '<span class="badge bg-success"><i class="bi bi-check-circle"></i> نشط</span>'
                    : '<span class="badge bg-secondary"><i class="bi bi-x-circle"></i> موقوف</span>'
            )
            ->editColumn('created_at', fn (User $u) => $u->created_at?->format('Y-m-d H:i'))
            ->addColumn('actions', function (User $u) {
                $buttons = '';
                if (auth()->user()->can('users.update')) {
                    $buttons .= '<a href="' . route('admin.users.edit', $u) . '" class="btn btn-icon btn-sm btn-info text-white" title="تعديل"><i class="bi bi-pencil"></i></a> ';
                }
                if (auth()->user()->can('users.delete') && $u->id !== auth()->id() && !$u->hasRole('super-admin')) {
                    $buttons .= '<button data-url="' . route('admin.users.destroy', $u) . '" class="btn btn-icon btn-sm btn-danger btn-delete" title="حذف"><i class="bi bi-trash"></i></button>';
                }
                return '<div class="table-actions">' . $buttons . '</div>';
            })
            ->rawColumns(['avatar', 'roles_list', 'is_active', 'actions'])
            ->make(true);
    }

    public function create()
    {
        $roles = Role::where('guard_name', 'web')->pluck('name', 'name');
        return view('admin.users.create', compact('roles'));
    }

    public function store(UserRequest $request)
    {
        DB::transaction(function () use ($request) {
            $data = $request->validated();
            $data['password'] = Hash::make($data['password']);

            if ($request->hasFile('avatar')) {
                $data['avatar'] = $this->uploadImage($request->file('avatar'), 'users');
            }

            $user = User::create($data);
            if ($request->filled('roles')) {
                $user->syncRoles($request->roles);
            }
        });

        return redirect()->route('admin.users.index')->with('success', __('messages.created_successfully'));
    }

    public function edit(User $user)
    {
        $roles     = Role::where('guard_name', 'web')->pluck('name', 'name');
        $userRoles = $user->roles->pluck('name')->toArray();
        return view('admin.users.edit', compact('user', 'roles', 'userRoles'));
    }

    public function update(UserRequest $request, User $user)
    {
        DB::transaction(function () use ($request, $user) {
            $data = $request->validated();
            if (!empty($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            } else {
                unset($data['password']);
            }

            if ($request->hasFile('avatar')) {
                $data['avatar'] = $this->uploadImage($request->file('avatar'), 'users', $user->avatar);
            }

            $user->update($data);

            if (!$user->hasRole('super-admin') || auth()->user()->hasRole('super-admin')) {
                $user->syncRoles($request->roles ?? []);
            }
        });

        return redirect()->route('admin.users.index')->with('success', __('messages.updated_successfully'));
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return response()->json(['message' => 'لا يمكنك حذف حسابك الخاص'], 403);
        }
        if ($user->hasRole('super-admin')) {
            return response()->json(['message' => 'لا يمكن حذف المدير العام'], 403);
        }

        $this->deleteImage($user->avatar);
        $user->delete();

        return response()->json(['message' => __('messages.deleted_successfully')]);
    }
}
