<?php

namespace App\Http\Requests\Admin;

use App\Models\PayrollRun;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PayrollRunRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('payroll.process') ?? false;
    }

    public function rules(): array
    {
        return [
            'branch_id'    => ['required', 'ulid', 'exists:branches,id'],
            'period_year'  => ['required', 'integer', 'min:2020', 'max:2099'],
            'period_month' => ['required', 'integer', 'min:1', 'max:12'],
            'payment_date' => ['nullable', 'date'],
            'notes'        => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'branch_id.required'    => 'يجب اختيار الفرع.',
            'branch_id.exists'      => 'الفرع المختار غير موجود.',
            'period_year.required'  => 'يجب تحديد سنة الدورة.',
            'period_month.required' => 'يجب تحديد شهر الدورة.',
            'period_month.between'  => 'الشهر يجب أن يكون بين 1 و 12.',
        ];
    }

    /**
     * Custom rule: prevent creating a second non-cancelled run for the same
     * (branch, year, month) combination. Enforced here (not in DB) because
     * MySQL/SQLite can't partial-index on status.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            if ($v->errors()->any()) return;

            $exists = PayrollRun::query()
                ->where('branch_id',    $this->branch_id)
                ->where('period_year',  $this->period_year)
                ->where('period_month', $this->period_month)
                ->where('status', '!=', PayrollRun::STATUS_CANCELLED)
                ->exists();

            if ($exists) {
                $v->errors()->add('period_month',
                    'يوجد بالفعل دورة رواتب لهذا الفرع لنفس الشهر. ألغِ الدورة الحالية أولاً.');
            }
        });
    }
}
