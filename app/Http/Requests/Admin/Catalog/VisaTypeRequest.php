<?php

namespace App\Http\Requests\Admin\Catalog;

use App\Http\Requests\Admin\Concerns\AuthorizesByPermissions;
use Illuminate\Foundation\Http\FormRequest;

class VisaTypeRequest extends FormRequest
{
    use AuthorizesByPermissions;

    protected function permissions(): array
    {
        return ['catalog.visas.manage'];
    }

    public function rules(): array
    {
        return [
            'name'             => ['required', 'string', 'max:120'],
            'country'          => ['required', 'string', 'max:60'],
            'type'             => ['required', 'in:tourist,business,transit,work,religious,student,medical,family_visit,other'],
            'duration_days'    => ['required', 'integer', 'min:1', 'max:365'],
            'multiple_entry'   => ['nullable', 'boolean'],
            'processing_days'  => ['required', 'integer', 'min:1', 'max:90'],
            'validity_months'  => ['required', 'integer', 'min:1', 'max:60'],
            'base_fee'         => ['required', 'numeric', 'min:0', 'max:9999999.99'],
            'service_fee'      => ['nullable', 'numeric', 'min:0', 'max:9999999.99'],
            'currency'         => ['required', 'in:EGP,SAR,USD'],
            'supplier_name'    => ['nullable', 'string', 'max:120'],
            'supplier_contact' => ['nullable', 'string', 'max:120'],
            'requirements'     => ['nullable', 'array'],
            'requirements.*'   => ['string', 'max:200'],
            'notes'            => ['nullable', 'string', 'max:2000'],
            'is_active'        => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name'            => 'اسم التأشيرة',
            'country'         => 'الدولة',
            'type'            => 'النوع',
            'duration_days'   => 'مدة الإقامة',
            'processing_days' => 'مدة الإصدار',
            'base_fee'        => 'الرسوم الأساسية',
            'currency'        => 'العملة',
        ];
    }
}
