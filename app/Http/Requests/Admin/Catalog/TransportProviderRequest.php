<?php

namespace App\Http\Requests\Admin\Catalog;

use App\Http\Requests\Admin\Concerns\AuthorizesByPermissions;
use Illuminate\Foundation\Http\FormRequest;

class TransportProviderRequest extends FormRequest
{
    use AuthorizesByPermissions;

    protected function permissions(): array
    {
        return ['catalog.transport.manage'];
    }

    public function rules(): array
    {
        return [
            'name'                   => ['required', 'string', 'max:120'],
            'type'                   => ['required', 'in:bus,train,vip,limousine,minivan'],
            'country'                => ['required', 'in:SA,EG,AE,TR,other'],
            'vehicle_count'          => ['required', 'integer', 'min:1', 'max:1000'],
            'capacity_per_vehicle'   => ['required', 'integer', 'min:1', 'max:100'],
            'base_price_per_pax'     => ['required', 'numeric', 'min:0', 'max:9999999.99'],
            'base_price_per_vehicle' => ['nullable', 'numeric', 'min:0', 'max:9999999.99'],
            'currency'               => ['required', 'in:EGP,SAR,USD'],
            'routes'                 => ['nullable', 'array'],
            'routes.*'               => ['string', 'max:30'],
            'contact_phone'          => ['nullable', 'string', 'max:30'],
            'contact_email'          => ['nullable', 'email', 'max:120'],
            'contact_person'         => ['nullable', 'string', 'max:120'],
            'notes'                  => ['nullable', 'string', 'max:2000'],
            'is_active'              => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name'                 => 'اسم الشركة',
            'type'                 => 'نوع النقل',
            'country'               => 'الدولة',
            'vehicle_count'        => 'عدد السيارات',
            'capacity_per_vehicle' => 'سعة السيارة',
            'base_price_per_pax'   => 'السعر للراكب',
            'currency'             => 'العملة',
        ];
    }
}
