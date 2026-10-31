<?php

namespace App\Services\Backup;

use App\Models\Setting;
use App\Models\Tenant;
use App\Support\Tenancy;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Backups to Google Drive for the super admin (tenant null: everything) and for each shop
 * (its own data only). "Backup now" / "Restore" are queued by the pages and run by the
 * scheduler, because a web request could time out.
 *
 * Settings per owner: backup_keep, backup_status (last run), backup_task ({action, file_id, name, by, at, state}).
 */
class BackupManager
{
    public const KEEP_OPTIONS = [7, 14, 30, 60];
    public const LOCAL_KEEP = 2;   // copies also kept on the server

    public static function keep(?int $tenantId = null): int
    {
        return (int) (Setting::getValue('backup_keep', 14, $tenantId) ?: 14);
    }

    public static function status(?int $tenantId = null): array
    {
        return json_decode((string) Setting::getValue('backup_status', '{}', $tenantId), true) ?: [];
    }

    public static function task(?int $tenantId = null): ?array
    {
        return json_decode((string) Setting::getValue('backup_task', '', $tenantId), true) ?: null;
    }

    public static function queue(string $action, int $by, ?string $fileId = null, ?string $name = null, ?int $tenantId = null): void
    {
        self::put('backup_task', ['action' => $action, 'file_id' => $fileId, 'name' => $name, 'by' => $by, 'at' => now()->toIso8601String(), 'state' => 'waiting'], $tenantId);
    }

    public static function clearTask(?int $tenantId = null): void
    {
        Setting::where('tenant_id', $tenantId)->where('key', 'backup_task')->delete();
    }

    /** Run every queued "Backup now" / "Restore" (super admin and shops). Called every minute. */
    public static function runQueued(): array
    {
        $done = [];
        $owners = Setting::where('key', 'backup_task')->pluck('tenant_id')->all();
        foreach ($owners as $tenantId) {
            $task = self::task($tenantId);
            if (! $task || ($task['state'] ?? '') !== 'waiting') {
                continue;
            }
            self::put('backup_task', ['state' => 'running'] + $task, $tenantId);
            try {
                $done[] = $task['action'] === 'restore'
                    ? self::restoreFromDrive($task['file_id'], $task['name'] ?? $task['file_id'], $tenantId)
                    : self::backup('manual', $tenantId);
                self::clearTask($tenantId);
            } catch (Throwable $e) {
                self::put('backup_task', ['state' => 'failed', 'error' => $e->getMessage()] + $task, $tenantId);
                $done[] = 'FAILED ' . ($tenantId ? 'shop ' . $tenantId : 'super admin') . ': ' . $e->getMessage();
            }
        }

        return $done;
    }

    /** Daily run for every shop that connected its own Drive. */
    public static function backupShops(): array
    {
        $done = [];
        $ids = Setting::where('key', 'backup_google_token')->whereNotNull('tenant_id')->pluck('tenant_id');
        foreach (Tenant::whereIn('id', $ids)->where('status', 'active')->get() as $tenant) {
            try {
                $done[] = $tenant->name . ': ' . self::backup('daily', $tenant->id);
            } catch (Throwable $e) {
                $done[] = $tenant->name . ' FAILED: ' . $e->getMessage();
            }
        }

        return $done;
    }

    /** Make a backup, upload it, drop old copies. Returns a short summary. */
    public static function backup(string $kind = 'daily', ?int $tenantId = null): string
    {
        $started = now();
        $drive = GoogleDrive::for($tenantId);
        try {
            if ($tenantId) {
                $tenant = Tenant::findOrFail($tenantId);
                if (! $drive->connected()) {
                    throw new \RuntimeException('Connect your Google Drive first.');
                }
                [$path, $manifest] = Tenancy::as($tenantId, fn () => TenantBackupArchive::create($tenant, self::dir($tenantId)));
                $csv = Tenancy::as($tenantId, fn () => TenantBackupArchive::ordersCsv($tenant, self::dir($tenantId)));
                $files = 0;
            } else {
                [$path, $manifest] = BackupArchive::create(self::dir(null));
                $csv = null;
                $files = $manifest['files'];
            }
            $size = filesize($path);
            $uploaded = false;

            if ($drive->connected()) {
                $drive->upload($path, basename($path));
                if ($csv) {
                    $drive->upload($csv, basename($csv), 'text/csv');
                }
                $drive->prune(self::keep($tenantId));
                $uploaded = true;
            }
            if ($csv) {
                File::delete($csv);
            }
            self::pruneLocal($tenantId);

            $rows = array_sum($manifest['tables']);
            self::put('backup_status', [
                'ok' => true, 'kind' => $kind, 'at' => now()->toIso8601String(), 'seconds' => $started->diffInSeconds(now()),
                'name' => basename($path), 'size' => $size, 'rows' => $rows, 'files' => $files,
                'orders' => $manifest['tables']['orders'] ?? null, 'drive' => $uploaded,
                'message' => $uploaded ? 'Saved to Google Drive.' : 'Saved on the server only — Google Drive is not connected.',
            ], $tenantId);

            return basename($path) . ' (' . number_format($size / 1048576, 1) . ' MB, ' . number_format($rows) . ' rows)';
        } catch (Throwable $e) {
            Log::error('backup.failed', ['tenant' => $tenantId, 'error' => $e->getMessage()]);
            self::put('backup_status', ['ok' => false, 'kind' => $kind, 'at' => now()->toIso8601String(), 'message' => $e->getMessage()] + self::status($tenantId), $tenantId);
            throw $e;
        }
    }

    /** Download a backup from Drive and put it back. A safety copy of the current data is made first. */
    public static function restoreFromDrive(string $fileId, string $name, ?int $tenantId = null): string
    {
        $dir = self::dir($tenantId);
        $tmp = $dir . DIRECTORY_SEPARATOR . 'restore-' . uniqid() . '.zip';
        try {
            GoogleDrive::for($tenantId)->download($fileId, $tmp);

            if ($tenantId) {
                $tenant = Tenant::findOrFail($tenantId);
                TenantBackupArchive::manifest($tmp);   // check before touching anything
                [$safety] = Tenancy::as($tenantId, fn () => TenantBackupArchive::create($tenant, $dir));
                $restored = Tenancy::as($tenantId, fn () => TenantBackupArchive::restore($tenant, $tmp));
            } else {
                BackupArchive::manifest($tmp);
                [$safety] = BackupArchive::create($dir);
                $restored = BackupArchive::restore($tmp);
            }
        } finally {
            File::delete($tmp);
        }

        $msg = 'Restored ' . $name . ': ' . number_format(array_sum($restored)) . ' rows'
            . (isset($restored['orders']) ? ' (' . number_format($restored['orders']) . ' orders)' : '')
            . '. The data from before the restore was kept as ' . basename($safety) . '.';
        self::put('backup_status', ['ok' => true, 'kind' => 'restore', 'at' => now()->toIso8601String(), 'message' => $msg] + self::status($tenantId), $tenantId);

        return $msg;
    }

    public static function localCopies(?int $tenantId = null): array
    {
        $dir = self::dir($tenantId);

        return collect(is_dir($dir) ? File::files($dir) : [])
            ->filter(fn ($f) => str_ends_with($f->getFilename(), '.zip') && ! str_starts_with($f->getFilename(), 'restore-'))
            ->sortByDesc(fn ($f) => $f->getMTime())
            ->values()
            ->all();
    }

    private static function dir(?int $tenantId): string
    {
        $dir = storage_path('app/backups' . ($tenantId ? '/shops/' . $tenantId : ''));
        File::ensureDirectoryExists($dir);

        return $dir;
    }

    private static function pruneLocal(?int $tenantId): void
    {
        foreach (array_slice(self::localCopies($tenantId), self::LOCAL_KEEP) as $old) {
            File::delete($old->getPathname());
        }
    }

    private static function put(string $key, array $value, ?int $tenantId): void
    {
        Setting::setValue($key, json_encode($value), 'backup', $tenantId);
    }
}
