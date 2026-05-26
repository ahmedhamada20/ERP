<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Admin\Concerns\AuthorizesByPermissions;
use Illuminate\Foundation\Http\FormRequest;

class BranchRequest extends FormRequest
{
    use AuthorizesByPermissions;

    protected function permissions(): array
    {
        return ['branches.create', 'branches.update'];
    }

    public function rules(): array
    {
        return [
            'name'         => ['required', 'string', 'max:200'],
            'name_en'      => ['nullable', 'string', 'max:200'],
            'phone'        => ['nullable', 'string', 'max:30'],
            'email'        => ['nullable', 'email', 'max:200'],
            'manager_name' => ['nullable', 'string', 'max:200'],

            'country'     => ['required', 'string', 'max:80'],
            'city'        => ['nullable', 'string', 'max:120'],
            'governorate' => ['nullable', 'string', 'max:120'],
            'address'     => ['nullable', 'string', 'max:500'],

            'is_main'   => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],

            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'is_main'   => $this->boolean('is_main'),
            'is_active' => $this->boolean('is_active'),
            'country'   => $this->input('country') ?: 'مصر',
        ]);
    }

    public function attributes(): array
    {
        return [
            'name'         => 'اسم الفرع',
            'name_en'      => 'الاسم بالإنجليزية',
            'phone'        => 'هاتف الفرع',
            'email'        => 'بريد الفرع',
            'manager_name' => 'مدير الفرع',
            'country'      => 'الدولة',
            'city'         => 'المدينة',
            'governorate'  => 'المحافظة',
            'address'      => 'العنوان',
            'is_main'      => 'فرع رئيسي',
            'is_active'    => 'نشط',
        ];
    }
}
