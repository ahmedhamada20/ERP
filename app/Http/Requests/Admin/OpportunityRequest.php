<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Admin\Concerns\AuthorizesByPermissions;
use Illuminate\Foundation\Http\FormRequest;

class OpportunityRequest extends FormRequest
{
    use AuthorizesByPermissions;

    protected function permissions(): array
    {
        return ['opportunities.create', 'opportunities.update'];
    }

    public function rules(): array
    {
        $bookingType = $this->input('booking_type');

        return [
            'title'               => ['required', 'string', 'max:200'],
            'lead_id'             => ['nullable', 'exists:leads,id'],
            'customer_id'         => ['nullable', 'exists:customers,id'],

            'booking_type'        => ['required', 'in:religious,domestic'],
            'sub_type'            => array_merge(
                ['nullable', 'string', 'max:40'],
                $bookingType === 'religious' ? [
                    'in:hajj,umrah',
                ] : ($bookingType === 'domestic' ? [
                    'in:hotel_only,package,day_trip,cruise,camp,event',
                ] : []),
            ),
            'destination'         => ['nullable', 'string', 'max:200'],
            'expected_trip_date'  => ['nullable', 'date'],

            'pax_count'           => ['required', 'integer', 'min:1', 'max:1000'],
            'estimated_value'     => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'probability'         => ['nullable', 'integer', 'min:0', 'max:100'],

            'stage'               => ['nullable', 'in:prospecting,qualification,proposal,negotiation,closed_won,closed_lost'],
            'expected_close_date' => ['nullable', 'date'],
            'lost_reason'         => ['nullable', 'string', 'max:200'],

            'assigned_to'         => ['nullable', 'exists:users,id'],
            'notes'               => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'title'               => 'عنوان الصفقة',
            'lead_id'             => 'العميل المحتمل',
            'customer_id'         => 'العميل',
            'booking_type'        => 'نوع الحجز',
            'sub_type'            => 'النوع الفرعي',
            'destination'         => 'الوجهة',
            'expected_trip_date'  => 'تاريخ السفر المتوقع',
            'pax_count'           => 'عدد الأشخاص',
            'estimated_value'     => 'القيمة المتوقعة',
            'probability'         => 'نسبة الفوز',
            'stage'               => 'المرحلة',
            'expected_close_date' => 'تاريخ الإغلاق المتوقع',
            'lost_reason'         => 'سبب الخسارة',
            'assigned_to'         => 'الموظف المسؤول',
        ];
    }
}
