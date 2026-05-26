<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Admin\Concerns\AuthorizesByPermissions;
use Illuminate\Foundation\Http\FormRequest;

class ReligiousBookingRequest extends FormRequest
{
    use AuthorizesByPermissions;

    protected function permissions(): array
    {
        return ['religious_bookings.create', 'religious_bookings.update'];
    }

    public function rules(): array
    {
        return [
            'customer_id'             => ['required', 'exists:customers,id'],
            'program_id'              => ['nullable', 'exists:religious_programs,id'],
            'type'                    => ['required', 'in:hajj,umrah'],

            'contract_number'         => ['nullable', 'string', 'max:80'],
            'receipt_number'          => ['nullable', 'string', 'max:80'],

            'booking_date'            => ['required', 'date'],
            'trip_date'               => ['required', 'date', 'after_or_equal:booking_date'],
            'return_date'             => ['nullable', 'date', 'after:trip_date'],
            'duration_days'           => ['required', 'integer', 'min:1', 'max:90'],

            'adults_count'            => ['required', 'integer', 'min:1', 'max:500'],
            'children_count'          => ['nullable', 'integer', 'min:0', 'max:200'],
            'infants_count'           => ['nullable', 'integer', 'min:0', 'max:50'],
            'children_data'           => ['nullable', 'array'],
            'children_data.*.name'    => ['nullable', 'string', 'max:120'],
            'children_data.*.age'    => ['nullable', 'integer', 'min:0', 'max:17'],

            'visa_type'               => ['required', 'in:standard,haram,kaaba'],
            'accommodation_type'      => ['required', 'in:single,double,triple,quad,quintuple,sextuple'],
            'meal_plan'               => ['required', 'in:pp,hp'],
            'transport_type'          => ['required', 'in:bus,train,vip,flight'],
            'mutawif_grade'           => ['required', 'in:economy,land,5_stars'],

            'responsible_manager_id'  => ['nullable', 'exists:users,id'],
            'responsible_employee_id' => ['nullable', 'exists:users,id'],

            'selling_price'           => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'exchange_rate_sar'       => ['nullable', 'numeric', 'min:0', 'max:9999.9999'],

            'status'                  => ['nullable', 'in:pending,confirmed,in_progress,completed,cancelled'],
            'workflow_stage'          => ['nullable', 'in:sales,manager_review,operations,finance,closed'],

            'notes'                   => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function prepareForValidation(): void
    {
        // Normalize children_data — drop rows that are completely blank
        $children = collect($this->input('children_data', []))
            ->filter(fn ($row) => !empty($row['name']) || isset($row['age']))
            ->values()
            ->all();

        $this->merge([
            'children_data'  => $children,
            'children_count' => count($children),
        ]);
    }

    public function attributes(): array
    {
        return [
            'customer_id'             => 'العميل',
            'program_id'              => 'البرنامج',
            'type'                    => 'نوع الرحلة',
            'booking_date'            => 'تاريخ الحجز',
            'trip_date'               => 'تاريخ السفر',
            'return_date'             => 'تاريخ العودة',
            'duration_days'           => 'مدة الرحلة',
            'adults_count'            => 'عدد البالغين',
            'visa_type'               => 'نوع التأشيرة',
            'accommodation_type'      => 'نوع التسكين',
            'meal_plan'               => 'نظام الإقامة',
            'transport_type'          => 'وسيلة النقل',
            'mutawif_grade'           => 'مستوى المطوف',
            'responsible_manager_id'  => 'المدير المسؤول',
            'responsible_employee_id' => 'الموظف المسؤول',
            'selling_price'           => 'سعر البيع',
            'exchange_rate_sar'       => 'سعر صرف الريال',
        ];
    }
}
