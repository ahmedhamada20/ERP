<?php

namespace App\Http\Requests\Admin\Accounting;

use App\Http\Requests\Admin\Concerns\AuthorizesByPermissions;
use App\Models\Account;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class JournalEntryRequest extends FormRequest
{
    use AuthorizesByPermissions;

    protected function permissions(): array
    {
        return ['accounting.journal.create'];
    }

    public function rules(): array
    {
        return [
            'date'                => ['required', 'date'],
            'description'         => ['required', 'string', 'max:500'],
            'reference'           => ['nullable', 'string', 'max:120'],

            'lines'               => ['required', 'array', 'min:2', 'max:50'],
            'lines.*.account_id'  => ['required', 'ulid', 'exists:accounts,id'],
            'lines.*.debit'       => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'lines.*.credit'      => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'lines.*.description' => ['nullable', 'string', 'max:200'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            if ($v->errors()->isNotEmpty()) return;

            $lines = $this->input('lines', []);
            $totalDebit  = 0;
            $totalCredit = 0;

            // Pre-fetch all accounts in one query (avoid N+1)
            $accountIds = collect($lines)->pluck('account_id')->filter()->unique()->values()->all();
            $accounts   = Account::whereIn('id', $accountIds)->get()->keyBy('id');

            foreach ($lines as $i => $line) {
                $debit  = (float) ($line['debit']  ?? 0);
                $credit = (float) ($line['credit'] ?? 0);

                if ($debit > 0 && $credit > 0) {
                    $v->errors()->add("lines.{$i}.debit", "السطر " . ($i + 1) . ": لا يمكن إدخال مدين ودائن معاً");
                }
                if ($debit == 0 && $credit == 0) {
                    $v->errors()->add("lines.{$i}.debit", "السطر " . ($i + 1) . ": لازم تدخل مبلغ في المدين أو الدائن");
                }

                $account = $accounts->get($line['account_id'] ?? '');
                if ($account) {
                    if ($account->is_group) {
                        $v->errors()->add("lines.{$i}.account_id", "السطر " . ($i + 1) . ": لا يمكن الترحيل على حساب مجمّع ({$account->code})");
                    }
                    if (! $account->is_active) {
                        $v->errors()->add("lines.{$i}.account_id", "السطر " . ($i + 1) . ": الحساب متوقف ({$account->code})");
                    }
                }

                $totalDebit  += $debit;
                $totalCredit += $credit;
            }

            if (abs($totalDebit - $totalCredit) >= 0.01) {
                $v->errors()->add('lines', sprintf(
                    'القيد غير متوازن: إجمالي المدين = %s، إجمالي الدائن = %s، الفرق = %s ج.م',
                    number_format($totalDebit, 2),
                    number_format($totalCredit, 2),
                    number_format(abs($totalDebit - $totalCredit), 2),
                ));
            }

            if ($totalDebit < 0.01) {
                $v->errors()->add('lines', 'إجمالي القيد لا يمكن أن يكون صفر');
            }
        });
    }

    public function attributes(): array
    {
        return [
            'date'        => 'التاريخ',
            'description' => 'البيان',
            'reference'   => 'المرجع',
            'lines'       => 'سطور القيد',
        ];
    }
}
