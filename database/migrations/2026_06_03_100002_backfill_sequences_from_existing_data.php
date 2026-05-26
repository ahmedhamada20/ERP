<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * تعبئة جدول sequences بآخر أرقام موجودة فعلاً في الجداول.
 *
 * بدون هذا، أول استدعاء لـ Sequence::next() سيرجع 1 لكل سلسلة، مما
 * قد يتسبب في تعارض مع أرقام موجودة (unique violation).
 *
 * يستخرج آخر رقم من كل جدول بناءً على البادئة + السنة، ثم يحفظه
 * في sequences. كل البادئات: RCP/DRCP/JE/VR/VP/SI/HJ/UM/DM.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->backfillFromColumn('booking_payments',          'receipt_number', 'RCP',  'booking_payment');
        $this->backfillFromColumn('domestic_booking_payments', 'receipt_number', 'DRCP', 'domestic_booking_payment');
        $this->backfillFromColumn('journal_entries',           'number',         'JE',   'journal_entry');
        $this->backfillFromColumn('supplier_invoices',         'number',         'SI',   'supplier_invoice');
        $this->backfillFromColumn('religious_bookings',        'booking_number', 'HJ',   'religious_booking:HJ');
        $this->backfillFromColumn('religious_bookings',        'booking_number', 'UM',   'religious_booking:UM');
        $this->backfillFromColumn('domestic_bookings',         'booking_number', 'DM',   'domestic_booking');
        $this->backfillFromColumn('vouchers',                  'number',         'VR',   'voucher:VR');
        $this->backfillFromColumn('vouchers',                  'number',         'VP',   'voucher:VP');
    }

    public function down(): void
    {
        // لا حاجة للتراجع — جدول sequences سيُحذف مع migration الأصلية.
    }

    /**
     * يستخرج آخر رقم لبادئة معينة في جدول/عمود، ويحفظه في sequences لكل سنة.
     */
    private function backfillFromColumn(string $table, string $column, string $prefix, string $sequenceKeyBase): void
    {
        if (! DB::getSchemaBuilder()->hasTable($table)) {
            return;
        }

        // اجمع كل الأرقام بالبادئة، استخرج السنة + الرقم، احسب max(number) لكل سنة
        $rows = DB::table($table)
            ->where($column, 'like', $prefix . '-%')
            ->pluck($column);

        $maxByYear = [];
        foreach ($rows as $value) {
            if (preg_match('/^' . preg_quote($prefix, '/') . '-(\d{4})-(\d+)$/', $value, $m)) {
                $year   = $m[1];
                $number = (int) $m[2];
                if (! isset($maxByYear[$year]) || $number > $maxByYear[$year]) {
                    $maxByYear[$year] = $number;
                }
            }
        }

        foreach ($maxByYear as $year => $max) {
            $key = $sequenceKeyBase . ':' . $year;
            DB::table('sequences')->updateOrInsert(
                ['key' => $key],
                ['last_number' => $max, 'updated_at' => now()],
            );
        }
    }
};
