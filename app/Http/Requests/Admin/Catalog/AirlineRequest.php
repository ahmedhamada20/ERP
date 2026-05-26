<?php

namespace App\Http\Requests\Admin\Catalog;

use App\Http\Requests\Admin\Concerns\AuthorizesByPermissions;
use Illuminate\Foundation\Http\FormRequest;

class AirlineRequest extends FormRequest
{
    use AuthorizesByPermissions;

    protected function permissions(): array
    {
        return ['catalog.airlines.manage'];
    }

    public function rules(): array
    {
        return [
            'airline_name'            => ['required', 'string', 'max:120'],
            'airline_code'            => ['nullable', 'string', 'max:10'],
            'route'                   => ['required', 'string', 'max:30'],
            'cabin_class'             => ['required', 'in:economy,business,first'],
            'aircraft_type'           => ['nullable', 'string', 'max:80'],
            'base_price_per_pax'      => ['required', 'numeric', 'min:0', 'max:9999999.99'],
            'currency'                => ['required', 'in:EGP,SAR,USD'],
            'departure_time'          => ['nullable', 'date_format:H:i'],
            'arrival_time'            => ['nullable', 'date_format:H:i'],
            'flight_duration_minutes' => ['nullable', 'integer', 'min:1', 'max:1440'],
            'capacity'                => ['nullable', 'integer', 'min:0', 'max:1000'],
            'available_seats'         => ['nullable', 'integer', 'min:0', 'max:1000'],
            'contact_phone'           => ['nullable', 'string', 'max:30'],
            'contact_email'           => ['nullable', 'email', 'max:120'],
            'notes'                   => ['nullable', 'string', 'max:2000'],
            'is_active'               => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'airline_name'       => 'اسم الشركة',
            'route'              => 'المسار',
            'cabin_class'        => 'درجة الكابينة',
            'base_price_per_pax' => 'السعر للراكب',
            'currency'           => 'العملة',
        ];
    }
}
