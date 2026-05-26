<?php

namespace App\Http\Requests\Admin\Concerns;

/**
 * Trait للتفويض الموحد في FormRequests.
 *
 * يفترض أن الـ routes محمية بـ permission middleware من Spatie،
 * لكنه يضيف طبقة دفاع داخل FormRequest نفسه (defense in depth).
 *
 * الـ FormRequest يحدد قائمة الصلاحيات المسموحة عبر `permissions()`،
 * والـ trait يعود true إذا كان للمستخدم أي منها (لأن نفس الـ Request
 * عادةً يستخدم لكل من create و update، وراوت كل واحد منهم محمي
 * بصلاحيته المناسبة).
 */
trait AuthorizesByPermissions
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyPermission($this->permissions()) ?? false;
    }

    /**
     * قائمة الصلاحيات المسموح لأصحابها بإرسال هذا الطلب.
     * يجب أن تُعرَّف في الـ FormRequest المستخدم للـ trait.
     *
     * @return array<int,string>
     */
    abstract protected function permissions(): array;
}
