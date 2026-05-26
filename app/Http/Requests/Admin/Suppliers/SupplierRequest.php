<?php

namespace App\Http\Requests\Admin\Suppliers;

use App\Http\Requests\Admin\Concerns\AuthorizesByPermissions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SupplierRequest extends FormRequest
{
    use AuthorizesByPermissions;

    protected function permissions(): array
    {
        return ['suppliers.create', 'suppliers.update'];
    }

    public function rules(): array
    {
        $id = $this->route('supplier')?->id;

        return [
            'name'                 => ['required', 'string', 'max:200'],
            'name_en'              => ['nullable', 'string', 'max:200'],
            'type'                 => ['required', 'in:hotel,airline,transport,visa,other'],

            'contact_person'       => ['nullable', 'string', 'max:120'],
            'phone'                => ['nullable', 'string', 'max:30'],
            'mobile'               => ['nullable', 'string', 'max:30'],
            'email'                => ['nullable', 'email', 'max:120',
                                       Rule::unique('suppliers', 'email')->ignore($id)->whereNull('deleted_at')],
            'address'              => ['nullable', 'string', 'max:300'],
            'city'                 => ['nullable', 'string', 'max:80'],
            'country'              => ['nullable', 'string', 'max:80'],

            'tax_number'           => ['nullable', 'string', 'max:30',
                                       Rule::unique('suppliers', 'tax_number')->ignore($id)->whereNull('deleted_at')],
            'commercial_register'  => ['nullable', 'string', 'max:30'],

            'currency'             => ['required', 'in:EGP,SAR,USD'],
            'opening_balance'      => ['nullable', 'numeric', 'min:-99999999999.99', 'max:99999999999.99'],
            'opening_balance_date' => ['nullable', 'date'],
            'payment_terms_days'   => ['nullable', 'integer', 'min:0', 'max:365'],

            'is_active'            => ['nullable', 'boolean'],
            'notes'                => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name'                 => 'اسم المورد',
            'type'                 => 'تصنيف المورد',
            'phone'                => 'الهاتف',
            'mobile'               => 'الجوال',
            'email'                => 'البريد الإلكتروني',
            'tax_number'           => 'الرقم الضريبي',
            'commercial_register'  => 'السجل التجاري',
            'currency'             => 'العملة',
            'opening_balance'      => 'الرصيد الافتتاحي',
            'payment_terms_days'   => 'مهلة السداد (أيام)',
        ];
    }
}
