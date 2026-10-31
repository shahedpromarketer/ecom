<?php

namespace App\Services\Backup;

use App\Models\Setting;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Daily backup to the super admin's Google Drive, plus "Backup now" and "Restore",
 * which the page queues and the scheduler runs (a web request could time out).
 *
 * Settings (tenant NULL): backup_keep (copies kept on Drive), backup_status (last run),
 * backup_task (queued job: {action, file_id, name, by, at}).
 */
class BackupManager
{
    public const KEEP_OPTIONS = [7, 14, 30, 60];
    public const LOCAL_KEEP = 2;   // copies also kept on the server

    public static function keep(): int
    {
        return (int) (Setting::getValue('backup_keep', 14, null) ?: 14);
    }

    public static function status(): array
    {
        return json_decode((string) Setting::getValue('backup_status', '{}', null), true) ?: [];
    }

    public static function task(): ?array
    {
        return json_decode((string) Setting::getValue('backup_task', '', null), true) ?: null;
    }

    public static function queue(string $action, int $by, ?string $fileId = null, ?string $name = null): void
    {
        Setting::setValue('backup_task', json_encode(['action' => $action, 'file_id' => $fileId, 'name' => $name, 'by' => $by, 'at' => now()->toIso8601String(), 'state' => 'waiting']), 'backup', null);
    }

    /** Run a queued "Backup now" / "Restore" (called every minute by the scheduler). */
    public static function runQueued(): ?string
    {
        $task = self::task();
        if (! $task || ($task['state'] ?? '') !== 'waiting') {
            return null;
        }
        Setting::setValue('backup_task', json_encode(['state' => 'running'] + $task), 'backup', null);

        try {
            $result = $task['action'] === 'restore'
                ? self::restoreFromDrive($task['file_id'], $task['name'] ?? $task['file_id'])
                : self::backup('manual');
            Setting::where('tenant_id', null)->where('key', 'backup_task')->delete();

            return $result;
        } catch (Throwable $e) {
            Setting::setValue('backup_task', json_encode(['state' => 'failed', 'error' => $e->getMessage()] + $task), 'backup', null);
            throw $e;
        }
    }

    /** Make a backup, upload it, drop old copies. Returns a short summary. */
    public static function backup(string $kind = 'daily'): string
    {
        $started = now();
        try {
            [$path, $manifest] = BackupArchive::create();
            $size = filesize($path);
            $driveId = null;

            if (GoogleDrive::connected()) {
                $driveId = GoogleDrive::upload($path, basename($path));
                foreach (array_slice(GoogleDrive::list(), self::keep()) as $old) {
                    GoogleDrive::delete($old['id']);
                }
            }
            self::pruneLocal();

            $rows = array_sum($manifest['tables']);
            self::saveStatus([
                'ok' => true, 'kind' => $kind, 'at' => now()->toIso8601String(), 'seconds' => $started->diffInSeconds(now()),
                'name' => basename($path), 'size' => $size, 'rows' => $rows, 'files' => $manifest['files'],
                'drive' => (bool) $driveId,
                'message' => $driveId ? 'Saved to Google Drive.' : 'Saved on the server only — Google Drive is not connected.',
            ]);

            return basename($path) . ' (' . number_format($size / 1048576, 1) . ' MB, ' . number_format($rows) . ' rows)';
        } catch (Throwable $e) {
            Log::error('backup.failed', ['error' => $e->getMessage()]);
            self::saveStatus(['ok' => false, 'kind' => $kind, 'at' => now()->toIso8601String(), 'message' => $e->getMessage()] + self::status());
            throw $e;
        }
    }

    /** Download a backup from Drive and put it back. A safety copy of the current data is made first. */
    public static function restoreFromDrive(string $fileId, string $name): string
    {
        $dir = storage_path('app/backups');
        File::ensureDirectoryExists($dir);

        [$safety] = BackupArchive::create($dir);   // in case this was the wrong date
        $tmp = $dir . DIRECTORY_SEPARATOR . 'restore-' . uniqid() . '.zip';
        try {
            GoogleDrive::download($fileId, $tmp);
            BackupArchive::manifest($tmp);   // check it is ours before touching anything
            $restored = BackupArchive::restore($tmp);
        } finally {
            File::delete($tmp);
        }

        $msg = 'Restored ' . $name . ': ' . number_format(array_sum($restored)) . ' rows. The data before the restore was saved as ' . basename($safety) . ' on the server.';
        self::saveStatus(['ok' => true, 'kind' => 'restore', 'at' => now()->toIso8601String(), 'message' => $msg] + self::status());

        return $msg;
    }

    public static function localCopies(): array
    {
        $dir = storage_path('app/backups');

        return collect(is_dir($dir) ? File::files($dir) : [])
            ->filter(fn ($f) => str_ends_with($f->getFilename(), '.zip') && str_starts_with($f->getFilename(), 'ecom-pilot-backup-'))
            ->sortByDesc(fn ($f) => $f->getMTime())
            ->values()
            ->all();
    }

    private static function pruneLocal(): void
    {
        foreach (array_slice(self::localCopies(), self::LOCAL_KEEP) as $old) {
            File::delete($old->getPathname());
        }
    }

    private static function saveStatus(array $s): void
    {
        Setting::setValue('backup_status', json_encode($s), 'backup', null);
    }
}
