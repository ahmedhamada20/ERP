<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Admin\Concerns\AuthorizesByPermissions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RoleRequest extends FormRequest
{
    use AuthorizesByPermissions;

    protected function permissions(): array
    {
        return ['roles.create', 'roles.update'];
    }

    public function rules(): array
    {
        $roleId = $this->route('role')?->id;

        return [
            'name'          => ['required', 'string', 'max:120', Rule::unique('roles', 'name')->ignore($roleId)],
            'permissions'   => ['array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name'        => 'اسم الصلاحية',
            'permissions' => 'الأذونات',
        ];
    }
}
