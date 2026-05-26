<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Admin\Concerns\AuthorizesByPermissions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserRequest extends FormRequest
{
    use AuthorizesByPermissions;

    protected function permissions(): array
    {
        return ['users.create', 'users.update'];
    }

    public function rules(): array
    {
        $userId   = $this->route('user')?->id;
        $isCreate = $this->isMethod('post');

        return [
            'name'      => ['required', 'string', 'max:120'],
            'email'     => ['required', 'email:rfc,dns', 'max:255',
                            Rule::unique('users', 'email')->ignore($userId)->whereNull('deleted_at')],
            'phone'     => ['nullable', 'string', 'max:20', 'regex:/^[\+\d\s\-()]+$/'],
            'password'  => [
                $isCreate ? 'required' : 'nullable',
                'confirmed',
                Password::min(10)->mixedCase()->letters()->numbers()->symbols()->uncompromised(),
            ],
            'is_active' => ['nullable', 'boolean'],
            'avatar'    => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
            'roles'     => ['nullable', 'array'],
            'roles.*'   => ['string', 'exists:roles,name'],
        ];
    }

    public function messages(): array
    {
        return [
            'password.confirmed' => 'تأكيد كلمة المرور غير مطابق',
            'phone.regex'        => 'رقم الهاتف يحتوي على أحرف غير مسموحة',
        ];
    }

    public function prepareForValidation(): void
    {
        $this->merge(['is_active' => $this->boolean('is_active', true)]);
    }
}
