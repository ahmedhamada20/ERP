<?php

namespace App\Http\Requests\Admin\Accounting;

use App\Http\Requests\Admin\Concerns\AuthorizesByPermissions;
use Illuminate\Foundation\Http\FormRequest;

class ReceiptVoucherRequest extends FormRequest
{
    use AuthorizesByPermissions;

    protected function permissions(): array
    {
        return ['accounting.vouchers.create'];
    }

    public function rules(): array
    {
        return [
            'date'                => ['required', 'date'],
            'cash_account_id'     => ['required', 'ulid', 'exists:accounts,id'],
            'counter_account_id'  => ['required', 'ulid', 'exists:accounts,id', 'different:cash_account_id'],
            'party_type'          => ['nullable', 'in:customer,supplier,employee,other'],
            'party_id'            => ['nullable', 'ulid'],
            'party_name'          => ['required', 'string', 'max:200'],
            'currency'            => ['required', 'in:EGP,SAR,USD'],
            'amount'              => ['required', 'numeric', 'min:0.01', 'max:99999999.99'],
            'exchange_rate'       => ['nullable', 'numeric', 'min:0', 'max:9999.9999'],
            'description'         => ['required', 'string', 'max:500'],
            'reference'           => ['nullable', 'string', 'max:120'],
        ];
    }

    public function attributes(): array
    {
        return [
            'date'               => 'التاريخ',
            'cash_account_id'    => 'الخزينة / البنك',
            'counter_account_id' => 'الحساب المقابل',
            'party_name'         => 'اسم المستلم',
            'amount'             => 'القيمة',
            'currency'           => 'العملة',
            'description'        => 'البيان',
            'reference'          => 'المرجع',
        ];
    }
}
