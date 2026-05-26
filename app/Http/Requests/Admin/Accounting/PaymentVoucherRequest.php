<?php

namespace App\Http\Requests\Admin\Accounting;

use App\Http\Requests\Admin\Concerns\AuthorizesByPermissions;
use Illuminate\Foundation\Http\FormRequest;

class PaymentVoucherRequest extends FormRequest
{
    use AuthorizesByPermissions;

    protected function permissions(): array
    {
        return ['accounting.vouchers.create'];
    }

    public function rules(): array
    {
        return [
            'date'                 => ['required', 'date'],
            'cash_account_id'      => ['required', 'ulid', 'exists:accounts,id'],
            'counter_account_id'   => ['required', 'ulid', 'exists:accounts,id', 'different:cash_account_id'],
            'party_type'           => ['nullable', 'in:customer,supplier,employee,other'],
            'party_id'             => ['nullable', 'ulid'],
            'party_name'           => ['required', 'string', 'max:200'],
            'supplier_id'          => ['nullable', 'ulid', 'exists:suppliers,id'],
            'supplier_invoice_id'  => ['nullable', 'ulid', 'exists:supplier_invoices,id'],
            'currency'             => ['required', 'in:EGP,SAR,USD'],
            'amount'               => ['required', 'numeric', 'min:0.01', 'max:99999999.99'],
            'exchange_rate'        => ['nullable', 'numeric', 'min:0', 'max:9999.9999'],
            'description'          => ['required', 'string', 'max:500'],
            'reference'            => ['nullable', 'string', 'max:120'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            if ($v->errors()->isNotEmpty()) return;

            $invoiceId  = $this->input('supplier_invoice_id');
            $supplierId = $this->input('supplier_id');

            // If an invoice is linked, it MUST belong to the chosen supplier
            if ($invoiceId && $supplierId) {
                $invoice = \App\Models\SupplierInvoice::find($invoiceId);
                if ($invoice && $invoice->supplier_id !== $supplierId) {
                    $v->errors()->add('supplier_invoice_id', 'الفاتورة المختارة لا تخص هذا المورد');
                }
            }

            // Invoice linkage requires supplier
            if ($invoiceId && ! $supplierId) {
                $v->errors()->add('supplier_id', 'يجب اختيار المورد قبل ربط الفاتورة');
            }
        });
    }

    public function attributes(): array
    {
        return [
            'date'               => 'التاريخ',
            'cash_account_id'    => 'الخزينة / البنك',
            'counter_account_id' => 'الحساب المقابل',
            'party_name'         => 'اسم المستفيد',
            'amount'             => 'القيمة',
            'currency'           => 'العملة',
            'description'        => 'البيان',
            'reference'          => 'المرجع',
        ];
    }
}
