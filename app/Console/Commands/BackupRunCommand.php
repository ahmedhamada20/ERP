<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Throwable;
use ZipArchive;

/**
 * Daily backup runner:
 *   1. Dumps MySQL DB via mysqldump into a temp file.
 *   2. Zips the SQL dump + storage/app/public (uploads/receipts/documents)
 *      into storage/app/backups/backup-YYYY-MM-DD-HHmmss.zip
 *   3. Deletes the temp SQL file.
 *
 * Cleanup is handled by `backup:clean` (separate command, separate schedule).
 */
class BackupRunCommand extends Command
{
    protected $signature = 'backup:run';

    protected $description = 'Create a compressed backup of the database and user uploads';

    public function handle(): int
    {
        $startedAt = now();
        $backupDir = storage_path('app/backups');
        File::ensureDirectoryExists($backupDir);

        $stamp     = $startedAt->format('Y-m-d-His');
        $sqlPath   = $backupDir . DIRECTORY_SEPARATOR . "db-{$stamp}.sql";
        $zipPath   = $backupDir . DIRECTORY_SEPARATOR . "backup-{$stamp}.zip";

        $this->info("[{$startedAt->toDateTimeString()}] Starting backup → {$zipPath}");

        try {
            $this->dumpDatabase($sqlPath);
            $sqlSize = File::size($sqlPath);
            $this->info(sprintf('DB dump complete (%s)', $this->humanBytes($sqlSize)));

            $this->createZip($zipPath, $sqlPath);
            $zipSize = File::size($zipPath);
            $this->info(sprintf('Zip created (%s)', $this->humanBytes($zipSize)));
        } catch (Throwable $e) {
            $this->error('Backup failed: ' . $e->getMessage());
            Log::channel('single')->error('backup:run failed', [
                'exception' => $e->getMessage(),
                'trace'     => $e->getTraceAsString(),
            ]);

            // Cleanup any half-written files
            File::delete([$sqlPath, $zipPath]);

            return self::FAILURE;
        } finally {
            File::delete($sqlPath);
        }

        $duration = $startedAt->diffInSeconds(now());

        Log::channel('single')->info('backup:run completed', [
            'file'        => basename($zipPath),
            'size_bytes'  => $zipSize ?? 0,
            'duration_s'  => $duration,
        ]);

        $this->info(sprintf('Done — %s (%s) in %ds', basename($zipPath), $this->humanBytes($zipSize), $duration));

        return self::SUCCESS;
    }

    private function dumpDatabase(string $sqlPath): void
    {
        $conn = Config::get('database.default');
        $db   = Config::get("database.connections.{$conn}");

        if (($db['driver'] ?? null) !== 'mysql') {
            throw new \RuntimeException("backup:run currently supports MySQL only (driver: {$db['driver']})");
        }

        $mysqldump = env('MYSQLDUMP_PATH', 'mysqldump');

        $command = [
            $mysqldump,
            '--host=' . ($db['host'] ?? '127.0.0.1'),
            '--port=' . ($db['port'] ?? 3306),
            '--user=' . ($db['username'] ?? 'root'),
            '--password=' . ($db['password'] ?? ''),
            '--single-transaction',
            '--quick',
            '--routines',
            '--triggers',
            '--default-character-set=' . ($db['charset'] ?? 'utf8mb4'),
            '--result-file=' . $sqlPath,
            $db['database'],
        ];

        $process = new Process($command);
        $process->setTimeout(600); // 10 minutes max
        $process->run();

        if (! $process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        if (! File::exists($sqlPath) || File::size($sqlPath) === 0) {
            throw new \RuntimeException('mysqldump produced an empty file — check credentials/database name');
        }
    }

    private function createZip(string $zipPath, string $sqlPath): void
    {
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException("Cannot create zip at {$zipPath}");
        }

        // 1) Add the SQL dump at the root of the archive
        $zip->addFile($sqlPath, 'database.sql');

        // 2) Add storage/app/public recursively (user uploads, receipts, passport scans)
        $publicPath = storage_path('app/public');
        if (File::isDirectory($publicPath)) {
            foreach (File::allFiles($publicPath) as $file) {
                $relative = 'uploads/' . ltrim(str_replace('\\', '/',
                    substr($file->getPathname(), strlen($publicPath))
                ), '/');
                $zip->addFile($file->getPathname(), $relative);
            }
        }

        $zip->close();
    }

    private function humanBytes(int $bytes): string
    {
        if ($bytes < 1024) return "{$bytes} B";
        if ($bytes < 1048576) return round($bytes / 1024, 1) . ' KB';
        if ($bytes < 1073741824) return round($bytes / 1048576, 1) . ' MB';
        return round($bytes / 1073741824, 2) . ' GB';
    }
}
