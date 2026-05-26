<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SyncSequencesCommand extends Command
{
    protected $signature = 'sequences:sync {--dry-run : عرض التغييرات بدون تطبيقها}';

    protected $description = 'مزامنة جدول sequences مع الأرقام الفعلية في كل الموديلات (يمنع أخطاء Duplicate entry بعد restore أو truncate)';

    /**
     * كل موديل يستخدم Sequence::next — table, column, parser regex, sequence-key formula.
     *
     * - regex: capture groups must produce [match, ...components] used by `key_parts`
     * - key_parts: ordered list of indexes from regex matches that build the sequence key suffix
     * - key_prefix: prefix in sequences.key (e.g. 'religious_booking')
     * - num_index: index of regex match that contains the numeric serial
     */
    private array $mappings = [
        // ── Tourism: bookings & payments ─────────────────────────
        'religious_booking' => [
            'table' => 'religious_bookings', 'column' => 'booking_number',
            'regex' => '/^(UM|HJ)-(\d{4})-(\d+)$/',
            'key_parts' => [1, 2], 'num_index' => 3, // religious_booking:UM:2026
        ],
        'domestic_booking' => [
            'table' => 'domestic_bookings', 'column' => 'booking_number',
            'regex' => '/^DM-(\d{4})-(\d+)$/',
            'key_parts' => [1], 'num_index' => 2, // domestic_booking:2026
        ],
        'booking_payment' => [
            'table' => 'booking_payments', 'column' => 'receipt_number',
            'regex' => '/^RCP-(\d{4})-(\d+)$/',
            'key_parts' => [1], 'num_index' => 2,
        ],
        'domestic_booking_payment' => [
            'table' => 'domestic_booking_payments', 'column' => 'receipt_number',
            'regex' => '/^DRCP-(\d{4})-(\d+)$/',
            'key_parts' => [1], 'num_index' => 2,
        ],
        'domestic_program' => [
            'table' => 'domestic_programs', 'column' => 'code',
            'regex' => '/^DOM-(\d{4})-(\d+)$/',
            'key_parts' => [1], 'num_index' => 2,
        ],

        // ── Customers, leads, opportunities (CRM) ─────────────────
        'customer' => [
            'table' => 'customers', 'column' => 'code',
            'regex' => '/^CUS-(\d{4})-(\d+)$/',
            'key_parts' => [1], 'num_index' => 2,
        ],
        'lead' => [
            'table' => 'leads', 'column' => 'code',
            'regex' => '/^LEAD-(\d{4})-(\d+)$/',
            'key_parts' => [1], 'num_index' => 2,
        ],
        'opportunity' => [
            'table' => 'opportunities', 'column' => 'code',
            'regex' => '/^OPP-(\d{4})-(\d+)$/',
            'key_parts' => [1], 'num_index' => 2,
        ],

        // ── HR ──────────────────────────────────────────────────
        'employee' => [
            'table' => 'employees', 'column' => 'code',
            'regex' => '/^EMP-(\d{4})-(\d+)$/',
            'key_parts' => [1], 'num_index' => 2,
        ],
        'employee_loan' => [
            'table' => 'employee_loans', 'column' => 'loan_code',
            'regex' => '/^LOAN-(\d{4})-(\d+)$/',
            'key_parts' => [1], 'num_index' => 2,
        ],
        'payroll_run' => [
            'table' => 'payroll_runs', 'column' => 'run_code',
            'regex' => '/^PAY-(\d{4})-(\d{2})-(\d+)$/',
            'key_parts' => [1, 2], 'num_index' => 3, // payroll_run:2026:01
        ],

        // ── Suppliers ──────────────────────────────────────────────
        'supplier' => [
            'table' => 'suppliers', 'column' => 'code',
            'regex' => '/^SUP-(\d{4})-(\d+)$/',
            'key_parts' => [1], 'num_index' => 2,
        ],
        'supplier_invoice' => [
            'table' => 'supplier_invoices', 'column' => 'number',
            'regex' => '/^SI-(\d{4})-(\d+)$/',
            'key_parts' => [1], 'num_index' => 2,
        ],

        // ── Vendors masters ────────────────────────────────────────
        'hotel' => [
            'table' => 'hotels', 'column' => 'code',
            'regex' => '/^HTL-(\d{4})-(\d+)$/',
            'key_parts' => [1], 'num_index' => 2,
        ],
        'airline' => [
            'table' => 'airlines', 'column' => 'code',
            'regex' => '/^AIR-(\d{4})-(\d+)$/',
            'key_parts' => [1], 'num_index' => 2,
        ],
        'transport_provider' => [
            'table' => 'transport_providers', 'column' => 'code',
            'regex' => '/^TRP-(\d{4})-(\d+)$/',
            'key_parts' => [1], 'num_index' => 2,
        ],

        // ── Accounting ─────────────────────────────────────────────
        'journal_entry' => [
            'table' => 'journal_entries', 'column' => 'number',
            'regex' => '/^JE-(\d{4})-(\d+)$/',
            'key_parts' => [1], 'num_index' => 2,
        ],
        'voucher' => [
            'table' => 'vouchers', 'column' => 'number',
            'regex' => '/^(VR|VP)-(\d{4})-(\d+)$/',
            'key_parts' => [1, 2], 'num_index' => 3, // voucher:VR:2026
        ],
    ];

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $this->info('🔄 مزامنة sequences مع البيانات الفعلية' . ($dryRun ? ' (DRY-RUN)' : ''));
        $this->newLine();

        $rows = [];
        $totalUpdated = 0;
        $totalSkipped = 0;

        foreach ($this->mappings as $prefix => $cfg) {
            if (!Schema::hasTable($cfg['table'])) {
                $rows[] = [$prefix, $cfg['table'], '—', '—', '—', 'جدول غير موجود'];
                continue;
            }

            // pick up max per derived key in a single query — group by year/subtype
            $maxPerKey = [];
            $records = DB::table($cfg['table'])
                ->select($cfg['column'])
                ->whereNotNull($cfg['column'])
                ->get();

            foreach ($records as $r) {
                $val = $r->{$cfg['column']};
                if (!preg_match($cfg['regex'], $val, $m)) {
                    continue;
                }
                $keyParts = array_map(fn ($i) => $m[$i], $cfg['key_parts']);
                $key = $prefix . ':' . implode(':', $keyParts);
                $num = (int) $m[$cfg['num_index']];
                if (!isset($maxPerKey[$key]) || $maxPerKey[$key] < $num) {
                    $maxPerKey[$key] = $num;
                }
            }

            if (empty($maxPerKey)) {
                $rows[] = [$prefix, $cfg['table'], '—', '—', '—', 'لا توجد بيانات'];
                continue;
            }

            foreach ($maxPerKey as $key => $maxNum) {
                $current = DB::table('sequences')->where('key', $key)->value('last_number');
                $current = $current === null ? null : (int) $current;

                if ($current !== null && $current >= $maxNum) {
                    $rows[] = [$prefix, $cfg['table'], $key, (string) $current, (string) $maxNum, 'مزامن ✔'];
                    $totalSkipped++;
                    continue;
                }

                if (!$dryRun) {
                    DB::table('sequences')->updateOrInsert(
                        ['key' => $key],
                        ['last_number' => $maxNum, 'updated_at' => now()]
                    );
                }

                $rows[] = [
                    $prefix, $cfg['table'], $key,
                    $current === null ? 'null' : (string) $current,
                    (string) $maxNum,
                    $dryRun ? '⚠ سيُحدث' : '✅ تم التحديث',
                ];
                $totalUpdated++;
            }
        }

        $this->table(
            ['Prefix', 'Table', 'Sequence Key', 'Old', 'Max in Data', 'Status'],
            $rows
        );

        $this->newLine();
        $this->info("📊 الملخص:");
        $this->line("   • " . ($dryRun ? 'محتاج تحديث' : 'تم تحديث') . ": <fg=yellow>{$totalUpdated}</> sequence");
        $this->line("   • متزامن مسبقاً: <fg=green>{$totalSkipped}</> sequence");

        if ($dryRun && $totalUpdated > 0) {
            $this->newLine();
            $this->warn('💡 شغّل الأمر بدون --dry-run لتطبيق التغييرات: php artisan sequences:sync');
        }

        return self::SUCCESS;
    }
}
