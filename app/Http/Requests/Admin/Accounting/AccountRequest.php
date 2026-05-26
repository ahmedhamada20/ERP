<?php

namespace App\Http\Requests\Admin\Accounting;

use App\Http\Requests\Admin\Concerns\AuthorizesByPermissions;
use App\Models\Account;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class AccountRequest extends FormRequest
{
    use AuthorizesByPermissions;

    protected function permissions(): array
    {
        return ['accounting.chart.manage'];
    }

    public function rules(): array
    {
        $accountId = $this->route('account')?->id;

        return [
            'code'                 => ['required', 'string', 'max:20', 'regex:/^\d+$/',
                                       Rule::unique('accounts', 'code')->ignore($accountId)],
            'name'                 => ['required', 'string', 'max:200'],
            'name_en'              => ['nullable', 'string', 'max:200'],
            'type'                 => ['required', 'in:asset,liability,equity,revenue,expense'],
            'sub_type'             => ['nullable', 'in:current_asset,fixed_asset,other_asset,current_liability,long_term_liability,equity,operating_revenue,other_revenue,cost_of_services,operating_expense,other_expense,cash,bank'],
            'parent_id'            => ['nullable', 'ulid', 'exists:accounts,id'],
            'is_group'             => ['nullable', 'boolean'],
            'is_active'            => ['nullable', 'boolean'],
            'currency'             => ['required', 'in:EGP,SAR,USD'],
            'opening_balance'      => ['nullable', 'numeric', 'min:-99999999999.99', 'max:99999999999.99'],
            'opening_balance_date' => ['nullable', 'date'],
            'notes'                => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            if ($v->errors()->isNotEmpty()) return;

            $parentId = $this->input('parent_id');
            if (! $parentId) return;

            $currentId = $this->route('account')?->id;

            // Anti-cycle checks (only relevant when editing — on create there's no self yet)
            if ($currentId) {
                if ($parentId === $currentId) {
                    $v->errors()->add('parent_id', 'لا يمكن أن يكون الحساب أبًا لنفسه');
                    return;
                }
                if ($this->isDescendantOf($currentId, $parentId)) {
                    $v->errors()->add('parent_id', 'لا يمكن اختيار حساب فرعي كحساب أب — هذا سينشئ تبعية دائرية');
                    return;
                }
            }

            // Structural checks — run on create AND update
            $parent = Account::find($parentId);
            if (! $parent) return;

            if (! $parent->is_group) {
                $v->errors()->add('parent_id', 'الحساب الأب يجب أن يكون حساب رئيسي (مجمّع)');
            }
            if ($parent->type !== $this->input('type')) {
                $v->errors()->add('type', 'تصنيف الحساب يجب أن يطابق تصنيف الأب');
            }
        });

        $validator->after(function (Validator $v) {
            if ($v->errors()->isNotEmpty()) return;

            // If editing a group account, can't switch to leaf while it has children
            $current = $this->route('account');
            if ($current && $current->is_group && ! $this->boolean('is_group')) {
                if ($current->children()->exists()) {
                    $v->errors()->add('is_group', 'لا يمكن تحويل الحساب لحساب تفصيلي وله حسابات فرعية. احذف الفروع أولاً.');
                }
            }
        });
    }

    /** Recursively walk children to see if $candidate is under $ancestor. */
    private function isDescendantOf(string $ancestorId, string $candidateId): bool
    {
        $candidate = Account::find($candidateId);
        while ($candidate?->parent_id) {
            if ($candidate->parent_id === $ancestorId) return true;
            $candidate = $candidate->parent;
        }
        return false;
    }

    public function attributes(): array
    {
        return [
            'code'        => 'كود الحساب',
            'name'        => 'اسم الحساب',
            'name_en'     => 'الاسم بالإنجليزية',
            'type'        => 'تصنيف الحساب',
            'sub_type'    => 'التصنيف الفرعي',
            'parent_id'   => 'الحساب الأب',
            'is_group'    => 'حساب مجمّع',
            'is_active'   => 'مفعّل',
            'currency'    => 'العملة',
        ];
    }
}
