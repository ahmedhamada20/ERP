<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * تعبئة sequences للموديلات الإضافية التي اكتُشفت في Sprint 8:
 * Supplier, Employee, DomesticProgram, EmployeeLoan, Lead, Opportunity,
 * PayrollRun (لكل شهر).
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->backfill('suppliers',         'code',      'SUP',  'supplier');
        $this->backfill('employees',         'code',      'EMP',  'employee');
        $this->backfill('domestic_programs', 'code',      'DOM',  'domestic_program');
        $this->backfill('employee_loans',    'loan_code', 'LOAN', 'employee_loan');
        $this->backfill('leads',             'code',      'LEAD', 'lead');
        $this->backfill('opportunities',     'code',      'OPP',  'opportunity');
        $this->backfillPayrollRuns();
    }

    public function down(): void {}

    private function backfill(string $table, string $column, string $prefix, string $sequenceKeyBase): void
    {
        if (! DB::getSchemaBuilder()->hasTable($table)) {
            return;
        }

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
            DB::table('sequences')->updateOrInsert(
                ['key' => $sequenceKeyBase . ':' . $year],
                ['last_number' => $max, 'updated_at' => now()],
            );
        }
    }

    private function backfillPayrollRuns(): void
    {
        if (! DB::getSchemaBuilder()->hasTable('payroll_runs')) {
            return;
        }

        $rows = DB::table('payroll_runs')
            ->where('run_code', 'like', 'PAY-%')
            ->pluck('run_code');

        $maxByKey = [];
        foreach ($rows as $value) {
            if (preg_match('/^PAY-(\d{4})-(\d{2})-(\d+)$/', $value, $m)) {
                $key    = 'payroll_run:' . $m[1] . ':' . $m[2];
                $number = (int) $m[3];
                if (! isset($maxByKey[$key]) || $number > $maxByKey[$key]) {
                    $maxByKey[$key] = $number;
                }
            }
        }

        foreach ($maxByKey as $key => $max) {
            DB::table('sequences')->updateOrInsert(
                ['key' => $key],
                ['last_number' => $max, 'updated_at' => now()],
            );
        }
    }
};
