<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Admin\Concerns\AuthorizesByPermissions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PositionRequest extends FormRequest
{
    use AuthorizesByPermissions;

    protected function permissions(): array
    {
        return ['positions.create', 'positions.update'];
    }

    public function rules(): array
    {
        return [
            'title'    => ['required', 'string', 'max:200'],
            'title_en' => ['nullable', 'string', 'max:200'],

            'department_id' => ['nullable', 'string', Rule::exists('departments', 'id')],

            'default_basic_salary'        => ['nullable', 'numeric', 'min:0', 'max:9999999.99'],
            'default_housing_allowance'   => ['nullable', 'numeric', 'min:0', 'max:9999999.99'],
            'default_transport_allowance' => ['nullable', 'numeric', 'min:0', 'max:9999999.99'],
            'default_other_allowances'    => ['nullable', 'numeric', 'min:0', 'max:9999999.99'],

            'commission_rate'  => ['nullable', 'numeric', 'min:0', 'max:100'],
            'commission_basis' => ['required', Rule::in(['selling_price', 'net_profit'])],

            'description' => ['nullable', 'string', 'max:2000'],

            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'is_active'                   => $this->boolean('is_active'),
            'default_basic_salary'        => $this->input('default_basic_salary') ?: 0,
            'default_housing_allowance'   => $this->input('default_housing_allowance') ?: 0,
            'default_transport_allowance' => $this->input('default_transport_allowance') ?: 0,
            'default_other_allowances'    => $this->input('default_other_allowances') ?: 0,
            'commission_rate'             => $this->input('commission_rate') ?: 0,
            'commission_basis'            => $this->input('commission_basis') ?: 'net_profit',
        ]);
    }

    public function attributes(): array
    {
        return [
            'title'                       => 'المسمى الوظيفي',
            'title_en'                    => 'بالإنجليزية',
            'department_id'               => 'القسم',
            'default_basic_salary'        => 'الراتب الأساسي الافتراضي',
            'default_housing_allowance'   => 'بدل السكن الافتراضي',
            'default_transport_allowance' => 'بدل الانتقال الافتراضي',
            'default_other_allowances'    => 'بدلات أخرى افتراضية',
            'commission_rate'             => 'نسبة العمولة',
            'commission_basis'            => 'أساس العمولة',
            'description'                 => 'الوصف',
            'is_active'                   => 'نشطة',
        ];
    }
}
