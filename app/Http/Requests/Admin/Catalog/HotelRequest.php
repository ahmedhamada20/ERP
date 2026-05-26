<?php

namespace App\Http\Requests\Admin\Catalog;

use App\Http\Requests\Admin\Concerns\AuthorizesByPermissions;
use Illuminate\Foundation\Http\FormRequest;

class HotelRequest extends FormRequest
{
    use AuthorizesByPermissions;

    protected function permissions(): array
    {
        return ['catalog.hotels.manage'];
    }

    public function rules(): array
    {
        return [
            'name'                 => ['required', 'string', 'max:120'],
            'name_en'              => ['nullable', 'string', 'max:120'],
            'city'                 => ['required', 'in:mecca,medina,jeddah,cairo,dubai,istanbul,kuala_lumpur,other'],
            'grade'                => ['required', 'in:economy,3_stars,4_stars,5_stars,luxury'],
            'distance_meters'      => ['nullable', 'integer', 'min:0', 'max:50000'],
            'address'              => ['nullable', 'string', 'max:255'],
            'contact_phone'        => ['nullable', 'string', 'max:30'],
            'contact_email'        => ['nullable', 'email', 'max:120'],
            'website'              => ['nullable', 'url', 'max:200'],
            'base_price_per_night' => ['required', 'numeric', 'min:0', 'max:9999999.99'],
            'currency'             => ['required', 'in:EGP,SAR,USD,AED,TRY'],
            'room_types'           => ['nullable', 'array'],
            'room_types.*'         => ['string', 'in:single,double,triple,quad,quintuple,sextuple,suite'],
            'max_occupancy'        => ['required', 'integer', 'min:1', 'max:12'],
            'amenities'            => ['nullable', 'array'],
            'amenities.*'          => ['string', 'max:60'],
            'cover_image'          => ['nullable', 'image', 'max:4096'],
            'notes'                => ['nullable', 'string', 'max:2000'],
            'is_active'            => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name'                 => 'اسم الفندق',
            'city'                 => 'المدينة',
            'grade'                => 'الدرجة',
            'base_price_per_night' => 'سعر الليلة',
            'currency'             => 'العملة',
            'max_occupancy'        => 'سعة الإشغال',
        ];
    }
}
