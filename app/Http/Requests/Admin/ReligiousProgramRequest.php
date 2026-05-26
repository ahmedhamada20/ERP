<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Admin\Concerns\AuthorizesByPermissions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReligiousProgramRequest extends FormRequest
{
    use AuthorizesByPermissions;

    protected function permissions(): array
    {
        return ['religious_programs.create', 'religious_programs.update'];
    }

    public function rules(): array
    {
        $programId = $this->route('program')?->id;

        return [
            'name'                       => ['required', 'string', 'max:200'],
            'name_en'                    => ['nullable', 'string', 'max:200'],
            'type'                       => ['required', 'in:hajj,umrah'],
            'season'                     => ['nullable', 'string', 'max:80'],
            'start_date'                 => ['nullable', 'date'],
            'end_date'                   => ['nullable', 'date', 'after_or_equal:start_date'],
            'duration_days'              => ['required', 'integer', 'min:1', 'max:90'],

            'default_visa_type'          => ['required', 'in:standard,haram,kaaba'],
            'default_accommodation_grade'=> ['required', 'in:economy,4_stars,5_stars'],
            'default_transport_type'     => ['required', 'in:bus,train,vip,flight'],
            'default_meal_plan'          => ['required', 'in:pp,hp'],
            'default_mutawif_grade'      => ['required', 'in:economy,land,5_stars'],

            'base_price_per_person'      => ['required', 'numeric', 'min:0', 'max:9999999.99'],
            'min_pilgrims'               => ['required', 'integer', 'min:1', 'max:1000'],
            'max_pilgrims'               => ['required', 'integer', 'min:1', 'max:5000', 'gte:min_pilgrims'],

            'inclusions'                 => ['nullable', 'string', 'max:5000'],
            'exclusions'                 => ['nullable', 'string', 'max:5000'],
            'description'                => ['nullable', 'string', 'max:5000'],
            'cover_image'                => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:4096'],

            'is_active'                  => ['nullable', 'boolean'],
            'is_published'               => ['nullable', 'boolean'],
        ];
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'is_active'    => $this->boolean('is_active'),
            'is_published' => $this->boolean('is_published'),
        ]);
    }

    public function attributes(): array
    {
        return [
            'name'                        => 'اسم البرنامج',
            'name_en'                     => 'الاسم بالإنجليزية',
            'type'                        => 'نوع البرنامج',
            'season'                      => 'الموسم',
            'start_date'                  => 'تاريخ البداية',
            'end_date'                    => 'تاريخ النهاية',
            'duration_days'               => 'مدة البرنامج',
            'default_visa_type'           => 'نوع التأشيرة',
            'default_accommodation_grade' => 'مستوى السكن',
            'default_transport_type'      => 'وسيلة النقل',
            'default_meal_plan'           => 'نظام الإقامة',
            'default_mutawif_grade'       => 'مستوى المطوف',
            'base_price_per_person'       => 'السعر الأساسي للفرد',
            'min_pilgrims'                => 'الحد الأدنى للمعتمرين',
            'max_pilgrims'                => 'الحد الأقصى للمعتمرين',
            'cover_image'                 => 'صورة البرنامج',
        ];
    }
}
