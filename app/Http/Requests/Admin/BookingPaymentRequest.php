<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Admin\Concerns\AuthorizesByPermissions;
use App\Models\Account;
use App\Models\ExchangeRate;
use App\Models\ReligiousBooking;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class BookingPaymentRequest extends FormRequest
{
    use AuthorizesByPermissions;

    protected function permissions(): array
    {
        return ['religious_bookings.manage_payments'];
    }

    public function rules(): array
    {
        $isRefund = $this->input('payment_type') === 'refund';

        return [
            'payment_date'          => ['required', 'date'],
            'payment_type'          => ['required', 'in:deposit,installment,final,refund'],
            'currency'              => ['required', 'in:EGP,SAR,USD'],
            'amount'                => ['required', 'numeric', 'min:0.01', 'max:99999999.99'],
            'exchange_rate'         => ['nullable', 'numeric', 'min:0', 'max:9999.9999'],
            'method'                => ['required', 'in:cash,bank_transfer,credit_card,cheque,instapay,vodafone_cash'],
            'cash_account_id'       => ['required', 'ulid', 'exists:accounts,id'],
            'bank_name'             => ['nullable', 'string', 'max:120'],
            'transaction_reference' => ['nullable', 'string', 'max:120'],
            'cheque_number'         => ['nullable', 'string', 'max:80'],
            'cheque_due_date'       => ['nullable', 'date'],
            'notes'                 => ['nullable', 'string', 'max:500'],
            'attachment'            => ['nullable', 'file', 'mimes:jpeg,jpg,png,webp,pdf', 'max:4096'],

            'refund_reason'         => [$isRefund ? 'required' : 'nullable', 'string', 'max:500'],
            'refunded_payment_id'   => ['nullable', 'ulid', 'exists:booking_payments,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            if ($v->errors()->isNotEmpty()) {
                return;
            }

            $booking = $this->route('booking');
            if (! $booking instanceof ReligiousBooking) {
                return;
            }

            // تحقق من أن cash_account_id حساب نشط من نوع cash أو bank فقط،
            // ومتوافق مع طريقة الدفع (cash → cash، باقي الطرق → bank).
            if ($cashAccountId = $this->input('cash_account_id')) {
                $account = Account::query()
                    ->where('id', $cashAccountId)
                    ->where('is_active', true)
                    ->where('is_group', false)
                    ->whereIn('sub_type', ['cash', 'bank'])
                    ->first();

                if (! $account) {
                    $v->errors()->add('cash_account_id',
                        'حساب الخزينة/البنك المختار غير نشط أو ليس من نوع خزينة/بنك.');
                } else {
                    $method = $this->input('method');
                    $expectedSubType = $method === 'cash' ? 'cash' : 'bank';
                    if ($account->sub_type !== $expectedSubType) {
                        $v->errors()->add('cash_account_id', sprintf(
                            'طريقة الدفع "%s" تستوجب حساب من نوع %s، الحساب المختار من نوع %s.',
                            $method,
                            $expectedSubType === 'cash' ? 'خزينة' : 'بنك',
                            $account->sub_type === 'cash' ? 'خزينة' : 'بنك',
                        ));
                    }
                }
            }

            $currency = $this->input('currency');
            $rate = (float) ($this->input('exchange_rate') ?: ($currency === 'EGP'
                ? 1
                : (ExchangeRate::rateFor($currency, 'EGP') ?: 1)));
            $newEgp = round((float) $this->input('amount') * $rate, 2);

            $excludeId = $this->route('payment')?->id;

            $base = $booking->payments();
            if ($excludeId) {
                $base->where('id', '!=', $excludeId);
            }

            $existingReceived = (float) (clone $base)
                ->where('payment_type', '!=', 'refund')
                ->sum('amount_egp');

            // Refunds that are reserving/consuming money: pending, approved, or paid.
            // Rejected refunds release their reservation.
            $existingRefundsReserved = (float) (clone $base)
                ->where('payment_type', 'refund')
                ->whereIn('refund_status', ['pending', 'approved', 'paid'])
                ->sum('amount_egp');

            $sellingPrice = (float) $booking->selling_price;
            $type         = $this->input('payment_type');

            if ($type === 'refund') {
                if ($refundedId = $this->input('refunded_payment_id')) {
                    $original = $booking->payments()->where('id', $refundedId)->first();
                    if (! $original) {
                        $v->errors()->add('refunded_payment_id', 'الدفعة الأصلية لا تنتمي لهذا الحجز');
                    } elseif ($original->payment_type === 'refund') {
                        $v->errors()->add('refunded_payment_id', 'لا يمكن استرداد من دفعة استرداد أخرى');
                    } else {
                        // فحص صارم لمنع الاسترداد المزدوج من نفس الدفعة:
                        // مجموع الاستردادات المحجوزة على الدفعة الأصلية + الجديد
                        // يجب ألا يتجاوز مبلغ الدفعة الأصلية.
                        $alreadyRefundedOnThis = (float) $booking->payments()
                            ->where('payment_type', 'refund')
                            ->where('refunded_payment_id', $refundedId)
                            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
                            ->whereIn('refund_status', ['pending', 'approved', 'paid'])
                            ->sum('amount_egp');

                        $perPaymentRemaining = (float) $original->amount_egp - $alreadyRefundedOnThis;
                        if ($newEgp > $perPaymentRemaining + 0.01) {
                            $v->errors()->add('amount', sprintf(
                                'تم استرداد %s ج.م من إيصال %s. الحد الأقصى المتبقي للاسترداد منها: %s ج.م',
                                number_format($alreadyRefundedOnThis, 2),
                                $original->receipt_number,
                                number_format(max(0, $perPaymentRemaining), 2),
                            ));
                        }
                    }
                }

                $availableForRefund = $existingReceived - $existingRefundsReserved;
                if ($newEgp > $availableForRefund + 0.01) {
                    $v->errors()->add('amount', sprintf(
                        'لا يمكن استرداد %s ج.م — الحد الأقصى المتاح هو %s ج.م (بعد خصم الاستردادات قيد التنفيذ)',
                        number_format($newEgp, 2),
                        number_format(max(0, $availableForRefund), 2),
                    ));
                }

                return;
            }

            $netReceivableAfter = ($existingReceived + $newEgp) - $existingRefundsReserved;
            if ($netReceivableAfter > $sellingPrice + 0.01) {
                $remaining = max(0, $sellingPrice - ($existingReceived - $existingRefundsReserved));
                $v->errors()->add('amount', sprintf(
                    'المبلغ يتجاوز الرصيد المتبقي — المتبقي للسداد: %s ج.م من سعر البيع %s ج.م',
                    number_format($remaining, 2),
                    number_format($sellingPrice, 2),
                ));
            }
        });
    }

    public function attributes(): array
    {
        return [
            'payment_date'          => 'تاريخ الدفع',
            'payment_type'          => 'نوع الدفعة',
            'currency'              => 'العملة',
            'amount'                => 'المبلغ',
            'exchange_rate'         => 'سعر الصرف',
            'method'                => 'طريقة الدفع',
            'cash_account_id'       => 'حساب الخزينة/البنك',
            'bank_name'             => 'اسم البنك',
            'transaction_reference' => 'رقم العملية',
            'cheque_number'         => 'رقم الشيك',
            'cheque_due_date'       => 'تاريخ استحقاق الشيك',
            'notes'                 => 'ملاحظات',
            'attachment'            => 'المرفق',
            'refund_reason'         => 'سبب الاسترداد',
            'refunded_payment_id'   => 'الدفعة الأصلية',
        ];
    }
}
