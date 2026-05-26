<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Admin\Concerns\AuthorizesByPermissions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EmployeeRequest extends FormRequest
{
    use AuthorizesByPermissions;

    protected function permissions(): array
    {
        return ['employees.create', 'employees.update'];
    }

    public function rules(): array
    {
        $empId = $this->route('employee')?->id;

        return [
            // Identity
            'full_name'      => ['required', 'string', 'max:200'],
            'full_name_en'   => ['nullable', 'string', 'max:200'],
            'national_id'    => ['nullable', 'string', 'max:20',
                                 Rule::unique('employees', 'national_id')->ignore($empId)],
            'passport_number'=> ['nullable', 'string', 'max:30'],
            'birth_date'     => ['nullable', 'date', 'before:today'],
            'gender'         => ['nullable', Rule::in(['male', 'female'])],
            'marital_status' => ['nullable', Rule::in(['single', 'married', 'divorced', 'widowed'])],
            'nationality'    => ['nullable', 'string', 'max:100'],
            'religion'       => ['nullable', 'string', 'max:100'],

            // Contact
            'phone'                   => ['required', 'string', 'max:30'],
            'whatsapp'                => ['nullable', 'string', 'max:30'],
            'email'                   => ['nullable', 'email', 'max:200'],
            'emergency_contact_name'  => ['nullable', 'string', 'max:200'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:30'],
            'address'                 => ['nullable', 'string', 'max:500'],
            'city'                    => ['nullable', 'string', 'max:100'],

            // Login link
            'user_id' => ['nullable', 'string',
                          Rule::exists('users', 'id'),
                          Rule::unique('employees', 'user_id')->ignore($empId)->whereNotNull('user_id')],

            // Organizational
            'branch_id'     => ['nullable', 'string', Rule::exists('branches', 'id')],
            'department_id' => ['nullable', 'string', Rule::exists('departments', 'id')],
            'position_id'   => ['nullable', 'string', Rule::exists('positions', 'id')],
            'reports_to'    => ['nullable', 'string',
                                Rule::exists('employees', 'id'),
                                $empId ? Rule::notIn([$empId]) : 'string'],

            // Employment
            'hire_date'        => ['required', 'date'],
            'termination_date' => ['nullable', 'date', 'after_or_equal:hire_date'],
            'employment_type'  => ['required', Rule::in(['full_time', 'part_time', 'contract', 'intern'])],
            'status'           => ['required', Rule::in(['active', 'on_leave', 'terminated', 'suspended'])],

            // Salary overrides (0 = inherit from position)
            'basic_salary'        => ['nullable', 'numeric', 'min:0', 'max:9999999.99'],
            'housing_allowance'   => ['nullable', 'numeric', 'min:0', 'max:9999999.99'],
            'transport_allowance' => ['nullable', 'numeric', 'min:0', 'max:9999999.99'],
            'other_allowances'    => ['nullable', 'numeric', 'min:0', 'max:9999999.99'],

            // Commission override (NULL = inherit)
            'commission_rate'  => ['nullable', 'numeric', 'min:0', 'max:100'],
            'commission_basis' => ['nullable', Rule::in(['selling_price', 'net_profit'])],

            // Payment
            'payment_method' => ['required', Rule::in(['cash', 'bank_transfer', 'cheque'])],
            'bank_name'      => ['nullable', 'string', 'max:200',
                                 Rule::requiredIf(fn () => $this->input('payment_method') === 'bank_transfer')],
            'bank_account'   => ['nullable', 'string', 'max:50',
                                 Rule::requiredIf(fn () => $this->input('payment_method') === 'bank_transfer')],
            'iban'           => ['nullable', 'string', 'max:50'],

            // Files
            'photo'    => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'id_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],

            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'basic_salary'        => $this->input('basic_salary') ?: 0,
            'housing_allowance'   => $this->input('housing_allowance') ?: 0,
            'transport_allowance' => $this->input('transport_allowance') ?: 0,
            'other_allowances'    => $this->input('other_allowances') ?: 0,
            // commission_rate stays nullable — empty means "inherit from position"
            'commission_rate'     => $this->filled('commission_rate') ? $this->input('commission_rate') : null,
            'commission_basis'    => $this->filled('commission_basis') ? $this->input('commission_basis') : null,
            'user_id'             => $this->filled('user_id') ? $this->input('user_id') : null,
            'reports_to'          => $this->filled('reports_to') ? $this->input('reports_to') : null,
        ]);
    }

    public function attributes(): array
    {
        return [
            'full_name'               => 'الاسم بالكامل',
            'full_name_en'            => 'الاسم بالإنجليزية',
            'national_id'             => 'الرقم القومي',
            'passport_number'         => 'رقم الجواز',
            'birth_date'              => 'تاريخ الميلاد',
            'gender'                  => 'النوع',
            'marital_status'          => 'الحالة الاجتماعية',
            'nationality'             => 'الجنسية',
            'religion'                => 'الديانة',
            'phone'                   => 'الهاتف',
            'whatsapp'                => 'واتساب',
            'email'                   => 'البريد الإلكتروني',
            'emergency_contact_name'  => 'اسم جهة الطوارئ',
            'emergency_contact_phone' => 'هاتف الطوارئ',
            'address'                 => 'العنوان',
            'city'                    => 'المدينة',
            'user_id'                 => 'حساب الدخول',
            'branch_id'               => 'الفرع',
            'department_id'           => 'القسم',
            'position_id'             => 'الوظيفة',
            'reports_to'              => 'المدير المباشر',
            'hire_date'               => 'تاريخ التعيين',
            'termination_date'        => 'تاريخ انتهاء الخدمة',
            'employment_type'         => 'نوع التعاقد',
            'status'                  => 'الحالة',
            'basic_salary'            => 'الراتب الأساسي',
            'housing_allowance'       => 'بدل السكن',
            'transport_allowance'     => 'بدل الانتقال',
            'other_allowances'        => 'بدلات أخرى',
            'commission_rate'         => 'نسبة العمولة',
            'commission_basis'        => 'أساس العمولة',
            'payment_method'          => 'طريقة الدفع',
            'bank_name'               => 'البنك',
            'bank_account'            => 'رقم الحساب',
            'iban'                    => 'IBAN',
            'photo'                   => 'صورة شخصية',
            'id_image'                => 'صورة البطاقة',
            'notes'                   => 'ملاحظات',
        ];
    }

    public function messages(): array
    {
        return [
            'reports_to.not_in' => 'الموظف لا يمكن أن يكون مديراً لنفسه.',
        ];
    }
}
