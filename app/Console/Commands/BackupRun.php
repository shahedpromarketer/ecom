<?php

namespace App\Console\Commands;

use App\Services\Backup\BackupArchive;
use App\Services\Backup\BackupManager;
use Illuminate\Console\Command;

/**
 * backup:run            daily backup (scheduler) or by hand
 * backup:run --queued   runs a "Backup now" / "Restore" asked for on the Super Admin page
 * backup:run --restore=/path/to/file.zip   restore a downloaded backup from the command line
 */
class BackupRun extends Command
{
    protected $signature = 'backup:run {--queued : Run what the backup pages asked for} {--shops : Back up every shop that connected its own Drive} {--restore= : Restore this local backup zip}';
    protected $description = 'Back up all CRM data (database + uploaded files) to Google Drive, or restore it';

    public function handle(): int
    {
        try {
            if ($file = $this->option('restore')) {
                $m = BackupArchive::manifest($file);
                if (! $this->confirm('Replace ALL current data with the backup from ' . $m['created_at'] . '?')) {
                    return self::SUCCESS;
                }
                $rows = BackupArchive::restore($file);
                $this->info('Restored ' . number_format(array_sum($rows)) . ' rows.');

                return self::SUCCESS;
            }

            if ($this->option('queued')) {
                foreach (BackupManager::runQueued() as $msg) {
                    $this->line($msg);
                }

                return self::SUCCESS;
            }

            if ($this->option('shops')) {
                foreach (BackupManager::backupShops() as $msg) {
                    $this->line($msg);
                }

                return self::SUCCESS;
            }

            $this->info('Backup: ' . BackupManager::backup('daily'));

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}
