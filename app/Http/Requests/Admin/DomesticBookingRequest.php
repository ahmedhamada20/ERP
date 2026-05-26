<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Admin\Concerns\AuthorizesByPermissions;
use Illuminate\Foundation\Http\FormRequest;

class DomesticBookingRequest extends FormRequest
{
    use AuthorizesByPermissions;

    protected function permissions(): array
    {
        return ['domestic_bookings.create', 'domestic_bookings.update'];
    }

    public function rules(): array
    {
        return [
            'customer_id'             => ['required', 'exists:customers,id'],
            'program_id'              => ['nullable', 'exists:domestic_programs,id'],
            'hotel_id'                => ['nullable', 'exists:hotels,id'],
            'type'                    => ['required', 'in:hotel_only,package,day_trip,cruise,camp,event'],

            'destination_city'        => ['required', 'string', 'max:120'],
            'destination_area'        => ['nullable', 'string', 'max:120'],

            'contract_number'         => ['nullable', 'string', 'max:80'],
            'receipt_number'          => ['nullable', 'string', 'max:80'],

            'booking_date'            => ['required', 'date'],
            'trip_date'               => ['required', 'date', 'after_or_equal:booking_date'],
            'return_date'             => ['nullable', 'date', 'after_or_equal:trip_date'],
            'duration_days'           => ['required', 'integer', 'min:1', 'max:90'],
            'duration_nights'         => ['nullable', 'integer', 'min:0', 'max:89'],

            'adults_count'            => ['required', 'integer', 'min:1', 'max:500'],
            'children_count'          => ['nullable', 'integer', 'min:0', 'max:200'],
            'infants_count'           => ['nullable', 'integer', 'min:0', 'max:50'],
            'children_data'           => ['nullable', 'array'],
            'children_data.*.name'    => ['nullable', 'string', 'max:120'],
            'children_data.*.age'     => ['nullable', 'integer', 'min:0', 'max:17'],

            'accommodation_type'      => ['required', 'in:single,double,triple,quad,family_room,suite'],
            'rooms_count'             => ['nullable', 'integer', 'min:1', 'max:500'],
            'accommodation_grade'     => ['required', 'in:economy,3_stars,4_stars,5_stars,resort'],
            'meal_plan'               => ['required', 'in:ro,bb,hb,fb,ai'],
            'transport_type'          => ['required', 'in:none,bus,minivan,private_car,train,flight'],

            'responsible_manager_id'  => ['nullable', 'exists:users,id'],
            'responsible_employee_id' => ['nullable', 'exists:users,id'],

            'selling_price'           => ['required', 'numeric', 'min:0', 'max:99999999.99'],

            'status'                  => ['nullable', 'in:pending,confirmed,in_progress,completed,cancelled'],
            'workflow_stage'          => ['nullable', 'in:sales,manager_review,operations,finance,closed'],

            'notes'                   => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function prepareForValidation(): void
    {
        $children = collect($this->input('children_data', []))
            ->filter(fn ($row) => !empty($row['name']) || isset($row['age']))
            ->values()
            ->all();

        $this->merge([
            'children_data'  => $children,
            'children_count' => count($children),
            'rooms_count'    => $this->input('rooms_count') ?: 1,
        ]);
    }

    public function attributes(): array
    {
        return [
            'customer_id'             => 'العميل',
            'program_id'              => 'البرنامج',
            'hotel_id'                => 'الفندق',
            'type'                    => 'نوع الرحلة',
            'destination_city'        => 'المدينة',
            'booking_date'            => 'تاريخ الحجز',
            'trip_date'               => 'تاريخ السفر',
            'return_date'             => 'تاريخ العودة',
            'duration_days'           => 'مدة الرحلة',
            'duration_nights'         => 'عدد الليالي',
            'adults_count'            => 'عدد البالغين',
            'accommodation_type'      => 'نوع الغرفة',
            'rooms_count'             => 'عدد الغرف',
            'accommodation_grade'     => 'مستوى السكن',
            'meal_plan'               => 'نظام الإقامة',
            'transport_type'          => 'وسيلة النقل',
            'responsible_manager_id'  => 'المدير المسؤول',
            'responsible_employee_id' => 'الموظف المسؤول',
            'selling_price'           => 'سعر البيع',
        ];
    }
}
