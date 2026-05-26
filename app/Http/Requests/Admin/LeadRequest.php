<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Admin\Concerns\AuthorizesByPermissions;
use Illuminate\Foundation\Http\FormRequest;

class LeadRequest extends FormRequest
{
    use AuthorizesByPermissions;

    protected function permissions(): array
    {
        return ['leads.create', 'leads.update'];
    }

    public function rules(): array
    {
        return [
            'full_name'           => ['required', 'string', 'max:200'],
            'phone'               => ['required', 'string', 'max:30'],
            'whatsapp'            => ['nullable', 'string', 'max:30'],
            'email'               => ['nullable', 'email', 'max:200'],
            'city'                => ['nullable', 'string', 'max:120'],

            'source'              => ['required', 'in:facebook,instagram,whatsapp,website,walk_in,referral,phone,tiktok,other'],
            'status'              => ['nullable', 'in:new,contacted,qualified,proposal,won,lost'],
            'interest_type'       => ['required', 'in:hajj,umrah,domestic,international,other'],

            'assigned_to'         => ['nullable', 'exists:users,id'],
            'estimated_value'     => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'expected_close_date' => ['nullable', 'date'],

            'lost_reason'         => ['nullable', 'string', 'max:200'],
            'notes'               => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'whatsapp' => $this->input('whatsapp') ?: $this->input('phone'),
        ]);
    }

    public function attributes(): array
    {
        return [
            'full_name'           => 'الاسم',
            'phone'               => 'رقم الهاتف',
            'whatsapp'            => 'رقم واتساب',
            'email'               => 'البريد الإلكتروني',
            'city'                => 'المدينة',
            'source'              => 'مصدر العميل',
            'status'              => 'الحالة',
            'interest_type'       => 'نوع الاهتمام',
            'assigned_to'         => 'الموظف المسؤول',
            'estimated_value'     => 'القيمة المتوقعة',
            'expected_close_date' => 'تاريخ الإغلاق المتوقع',
            'lost_reason'         => 'سبب الخسارة',
        ];
    }
}
