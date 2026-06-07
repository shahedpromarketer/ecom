<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use Illuminate\Console\Command;

class DeleteOldActivityLogs extends Command
{
    protected $signature = 'logs:cleanup';
    protected $description = 'Delete activity logs older than 90 days';

    public function handle(): void
    {
        $deleted = ActivityLog::where('created_at', '<', now()->subDays(90))->delete();
        $this->info("Deleted {$deleted} old activity logs.");
    }
}
