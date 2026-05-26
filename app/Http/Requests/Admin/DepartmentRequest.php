<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Admin\Concerns\AuthorizesByPermissions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DepartmentRequest extends FormRequest
{
    use AuthorizesByPermissions;

    protected function permissions(): array
    {
        return ['departments.create', 'departments.update'];
    }

    public function rules(): array
    {
        $deptId = $this->route('department')?->id;

        return [
            'name'    => ['required', 'string', 'max:200'],
            'name_en' => ['nullable', 'string', 'max:200'],

            'branch_id' => ['nullable', 'string', Rule::exists('branches', 'id')],

            'manager_employee_id' => ['nullable', 'string', Rule::exists('employees', 'id')],

            'description' => ['nullable', 'string', 'max:2000'],

            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    public function attributes(): array
    {
        return [
            'name'                => 'اسم القسم',
            'name_en'              => 'الاسم بالإنجليزية',
            'branch_id'           => 'الفرع',
            'manager_employee_id' => 'مدير القسم',
            'description'         => 'الوصف',
            'is_active'           => 'نشط',
        ];
    }
}
