<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('logs:cleanup')->daily();
Schedule::command('orders:pull-new')->everyMinute()->withoutOverlapping();
Schedule::command('orders:distribute')->everyMinute()->withoutOverlapping();
Schedule::command('sheets:import')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('couriers:sync')->everyThirtyMinutes()->withoutOverlapping();

// Full backup to the super admin's Google Drive; queued "Backup now" / "Restore" run within a minute.
Schedule::command('backup:run')->dailyAt('02:30')->withoutOverlapping(180);
Schedule::command('backup:run --queued')->everyMinute()->withoutOverlapping(180);
