<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Admin\Concerns\AuthorizesByPermissions;
use Illuminate\Foundation\Http\FormRequest;

class DomesticProgramRequest extends FormRequest
{
    use AuthorizesByPermissions;

    protected function permissions(): array
    {
        return ['domestic_programs.create', 'domestic_programs.update'];
    }

    public function rules(): array
    {
        return [
            'name'                        => ['required', 'string', 'max:200'],
            'name_en'                     => ['nullable', 'string', 'max:200'],
            'type'                        => ['required', 'in:hotel_only,package,day_trip,cruise,camp,event'],
            'season'                      => ['nullable', 'string', 'max:80'],

            'destination_country'         => ['required', 'string', 'max:80'],
            'destination_city'            => ['required', 'string', 'max:120'],
            'destination_area'            => ['nullable', 'string', 'max:120'],

            'start_date'                  => ['nullable', 'date'],
            'end_date'                    => ['nullable', 'date', 'after_or_equal:start_date'],
            'duration_days'               => ['required', 'integer', 'min:1', 'max:90'],
            'duration_nights'             => ['nullable', 'integer', 'min:0', 'max:89'],

            'default_accommodation_grade' => ['required', 'in:economy,3_stars,4_stars,5_stars,resort'],
            'default_transport_type'      => ['required', 'in:none,bus,minivan,private_car,train,flight'],
            'default_meal_plan'           => ['required', 'in:ro,bb,hb,fb,ai'],

            'base_price_per_person'       => ['required', 'numeric', 'min:0', 'max:9999999.99'],
            'min_guests'                  => ['required', 'integer', 'min:1', 'max:1000'],
            'max_guests'                  => ['required', 'integer', 'min:1', 'max:5000', 'gte:min_guests'],

            'inclusions'                  => ['nullable', 'string', 'max:5000'],
            'exclusions'                  => ['nullable', 'string', 'max:5000'],
            'description'                 => ['nullable', 'string', 'max:5000'],
            'cover_image'                 => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:4096'],

            'is_active'                   => ['nullable', 'boolean'],
            'is_published'                => ['nullable', 'boolean'],
        ];
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'is_active'    => $this->boolean('is_active'),
            'is_published' => $this->boolean('is_published'),
            'destination_country' => $this->input('destination_country') ?: 'Egypt',
        ]);
    }

    public function attributes(): array
    {
        return [
            'name'                        => 'اسم البرنامج',
            'name_en'                     => 'الاسم بالإنجليزية',
            'type'                        => 'نوع البرنامج',
            'season'                      => 'الموسم',
            'destination_country'         => 'الدولة',
            'destination_city'            => 'المدينة',
            'destination_area'            => 'المنطقة',
            'start_date'                  => 'تاريخ البداية',
            'end_date'                    => 'تاريخ النهاية',
            'duration_days'               => 'مدة البرنامج',
            'duration_nights'             => 'عدد الليالي',
            'default_accommodation_grade' => 'مستوى السكن',
            'default_transport_type'      => 'وسيلة النقل',
            'default_meal_plan'           => 'نظام الإقامة',
            'base_price_per_person'       => 'السعر الأساسي للفرد',
            'min_guests'                  => 'الحد الأدنى للضيوف',
            'max_guests'                  => 'الحد الأقصى للضيوف',
            'cover_image'                 => 'صورة البرنامج',
        ];
    }
}
