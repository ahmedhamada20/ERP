<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Admin\Concerns\AuthorizesByPermissions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CustomerRequest extends FormRequest
{
    use AuthorizesByPermissions;

    protected function permissions(): array
    {
        return ['customers.create', 'customers.update'];
    }

    public function rules(): array
    {
        $customerId = $this->route('customer')?->id;

        return [
            'full_name'            => ['required', 'string', 'max:200'],
            'full_name_en'         => ['nullable', 'string', 'max:200'],
            'national_id'          => ['nullable', 'string', 'max:20', 'regex:/^\d+$/',
                                        Rule::unique('customers', 'national_id')->ignore($customerId)->whereNull('deleted_at')],
            'passport_number'      => ['nullable', 'string', 'max:30', 'regex:/^[A-Za-z0-9]+$/'],
            'passport_issue_date'  => ['nullable', 'date', 'before_or_equal:today'],
            'passport_expiry_date' => ['nullable', 'date', 'after:passport_issue_date'],
            'passport_issue_place' => ['nullable', 'string', 'max:120'],
            'gender'               => ['required', 'in:male,female'],
            'birth_date'           => ['nullable', 'date', 'before:today', 'after:1900-01-01'],
            'nationality'          => ['nullable', 'string', 'max:80'],
            'religion'             => ['nullable', 'string', 'max:60'],
            'marital_status'       => ['nullable', 'string', 'max:30'],
            'phone'                => ['required', 'string', 'max:20', 'regex:/^[\+\d\s\-()]+$/'],
            'mobile'               => ['nullable', 'string', 'max:20', 'regex:/^[\+\d\s\-()]+$/'],
            'whatsapp'             => ['nullable', 'string', 'max:20', 'regex:/^[\+\d\s\-()]+$/'],
            'email'                => ['nullable', 'email:rfc', 'max:120'],
            'address'              => ['nullable', 'string', 'max:255'],
            'city'                 => ['nullable', 'string', 'max:80'],
            'governorate'          => ['nullable', 'string', 'max:80'],
            'country'              => ['nullable', 'string', 'max:80'],
            'type'                 => ['required', 'in:individual,agency,group'],
            'status'               => ['required', 'in:active,inactive,blacklisted'],
            'photo'                => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
            'passport_image'       => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
            'national_id_image'    => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
            'notes'                => ['nullable', 'string', 'max:1000'],
        ];
    }
}
