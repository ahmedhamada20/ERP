<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * عدّاد ذرّي — يستخدم لتوليد الأرقام التسلسلية بأمان تحت التزامن.
 *
 * المفتاح (key) هو السلسلة (مثل 'receipt:2026'). المُعامل الأساسي
 * `next()` يجب أن يُنفّذ داخل DB::transaction حتى تعمل lockForUpdate
 * بشكل صحيح ويتم منع طلبين متزامنين من قراءة نفس الرقم.
 *
 * مثال الاستخدام في موديل الإيصال:
 *
 *   public static function generateReceiptNumber(): string {
 *       $year   = date('Y');
 *       $key    = 'booking_payment:' . $year;
 *       $next   = Sequence::next($key);
 *       return 'RCP-' . $year . '-' . str_pad($next, 6, '0', STR_PAD_LEFT);
 *   }
 *
 * ولا تنسَ أن تستدعي generateReceiptNumber داخل DB::transaction (والذي
 * يحدث تلقائياً عند الحفظ في observer/boot creating كجزء من session).
 */
class Sequence extends Model
{
    protected $primaryKey   = 'key';
    public    $incrementing = false;
    protected $keyType      = 'string';
    public    $timestamps   = false;

    protected $fillable = ['key', 'last_number', 'updated_at'];

    protected $casts = [
        'last_number' => 'integer',
        'updated_at'  => 'datetime',
    ];

    /**
     * يُرجِع الرقم التالي ذرّياً (atomic) للسلسلة المحددة.
     *
     * يستخدم DB::transaction + lockForUpdate لضمان عدم حصول طلبين
     * متزامنين على نفس الرقم. إذا لم يكن المفتاح موجوداً يُنشأ ببداية 1.
     *
     * @return int الرقم التالي (يبدأ من 1)
     */
    public static function next(string $key): int
    {
        return DB::transaction(function () use ($key) {
            // إذا لم يكن السطر موجوداً، أنشئه بصمت ثم استرجع للقفل.
            // INSERT IGNORE يضمن عدم فشل race على creation نفسه.
            DB::table('sequences')->insertOrIgnore([
                'key'         => $key,
                'last_number' => 0,
                'updated_at'  => now(),
            ]);

            // قفل السطر الآن — أي معاملة أخرى لنفس المفتاح ستنتظر.
            $row = DB::table('sequences')
                ->where('key', $key)
                ->lockForUpdate()
                ->first();

            $next = ((int) $row->last_number) + 1;

            DB::table('sequences')
                ->where('key', $key)
                ->update([
                    'last_number' => $next,
                    'updated_at'  => now(),
                ]);

            return $next;
        });
    }
}
