<?php

namespace App\Http\Requests\Admin\Suppliers;

use App\Http\Requests\Admin\Concerns\AuthorizesByPermissions;
use Illuminate\Foundation\Http\FormRequest;

class SupplierInvoiceRequest extends FormRequest
{
    use AuthorizesByPermissions;

    protected function permissions(): array
    {
        return ['supplier_invoices.create'];
    }

    public function rules(): array
    {
        return [
            'supplier_id'        => ['required', 'ulid', 'exists:suppliers,id'],
            'expense_account_id' => ['required', 'ulid', 'exists:accounts,id'],
            'invoice_date'       => ['required', 'date'],
            'due_date'           => ['nullable', 'date', 'after_or_equal:invoice_date'],
            'supplier_reference' => ['nullable', 'string', 'max:120'],
            'description'        => ['required', 'string', 'max:500'],
            'currency'           => ['required', 'in:EGP,SAR,USD'],
            'amount'             => ['required', 'numeric', 'min:0.01', 'max:99999999.99'],
            'tax_amount'         => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'exchange_rate'      => ['nullable', 'numeric', 'min:0', 'max:9999.9999'],
        ];
    }

    public function attributes(): array
    {
        return [
            'supplier_id'        => 'المورد',
            'expense_account_id' => 'حساب المصروف',
            'invoice_date'       => 'تاريخ الفاتورة',
            'due_date'           => 'تاريخ الاستحقاق',
            'amount'             => 'القيمة قبل الضريبة',
            'tax_amount'         => 'قيمة الضريبة',
            'description'        => 'البيان',
        ];
    }
}
