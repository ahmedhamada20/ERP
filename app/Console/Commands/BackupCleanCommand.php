<?php

namespace App\Console\Commands;

use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

/**
 * Tiered backup retention:
 *  - Keep the last N daily backups (default 7)
 *  - Keep N weekly backups (Sunday's backup) for the past N weeks (default 4)
 *  - Keep N monthly backups (1st-of-month's backup) for the past N months (default 6)
 *
 * Any backup file not protected by one of those rules is deleted.
 */
class BackupCleanCommand extends Command
{
    protected $signature = 'backup:clean';

    protected $description = 'Apply daily/weekly/monthly retention policy to existing backups';

    public function handle(): int
    {
        $backupDir = storage_path('app/backups');
        if (! File::isDirectory($backupDir)) {
            $this->info('No backup directory found, nothing to clean.');
            return self::SUCCESS;
        }

        $keepDaily   = (int) env('BACKUP_KEEP_DAILY', 7);
        $keepWeekly  = (int) env('BACKUP_KEEP_WEEKLY', 4);
        $keepMonthly = (int) env('BACKUP_KEEP_MONTHLY', 6);

        $backups = collect(File::files($backupDir))
            ->filter(fn ($f) => str_starts_with($f->getFilename(), 'backup-') && $f->getExtension() === 'zip')
            ->map(fn ($f) => [
                'file' => $f,
                'date' => $this->parseDate($f->getFilename()),
            ])
            ->filter(fn ($row) => $row['date'] !== null)
            ->sortByDesc(fn ($row) => $row['date']->getTimestamp())
            ->values();

        if ($backups->isEmpty()) {
            $this->info('No backups to clean.');
            return self::SUCCESS;
        }

        $keep = collect();

        // Daily: the most recent N backups (one per day, dedup by date)
        $byDay = $backups->groupBy(fn ($r) => $r['date']->toDateString());
        foreach ($byDay->take($keepDaily) as $day => $rows) {
            $keep->push($rows->first()['file']->getPathname());
        }

        // Weekly: one backup per week (Sunday), N weeks back
        $byWeek = $backups->groupBy(fn ($r) => $r['date']->startOfWeek()->toDateString());
        foreach ($byWeek->take($keepWeekly) as $week => $rows) {
            $keep->push($rows->first()['file']->getPathname());
        }

        // Monthly: one per month, N months back
        $byMonth = $backups->groupBy(fn ($r) => $r['date']->format('Y-m'));
        foreach ($byMonth->take($keepMonthly) as $month => $rows) {
            $keep->push($rows->first()['file']->getPathname());
        }

        $keepSet = $keep->unique()->all();
        $deleted = 0;
        $freedBytes = 0;

        foreach ($backups as $row) {
            $path = $row['file']->getPathname();
            if (in_array($path, $keepSet, true)) continue;

            $freedBytes += $row['file']->getSize();
            File::delete($path);
            $deleted++;
        }

        Log::channel('single')->info('backup:clean completed', [
            'kept'        => count($keepSet),
            'deleted'     => $deleted,
            'freed_bytes' => $freedBytes,
        ]);

        $this->info(sprintf(
            'Kept %d backup(s), deleted %d (freed %s)',
            count($keepSet),
            $deleted,
            $this->humanBytes($freedBytes),
        ));

        return self::SUCCESS;
    }

    private function parseDate(string $filename): ?CarbonImmutable
    {
        // Expected pattern: backup-YYYY-MM-DD-HHmmss.zip
        if (! preg_match('/^backup-(\d{4})-(\d{2})-(\d{2})-(\d{2})(\d{2})(\d{2})\.zip$/', $filename, $m)) {
            return null;
        }

        try {
            return CarbonImmutable::create(
                (int) $m[1], (int) $m[2], (int) $m[3],
                (int) $m[4], (int) $m[5], (int) $m[6],
            );
        } catch (\Throwable) {
            return null;
        }
    }

    private function humanBytes(int $bytes): string
    {
        if ($bytes < 1024) return "{$bytes} B";
        if ($bytes < 1048576) return round($bytes / 1024, 1) . ' KB';
        if ($bytes < 1073741824) return round($bytes / 1048576, 1) . ' MB';
        return round($bytes / 1073741824, 2) . ' GB';
    }
}
