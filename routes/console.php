<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduler (Cron module — 058)
|--------------------------------------------------------------------------
| These tasks run via: php artisan schedule:run
| Crontab: * * * * * cd /path/to/catmin && php artisan schedule:run >> /dev/null 2>&1
*/

use Illuminate\Support\Facades\Schedule;
use Modules\Cron\Services\CronService;
use Modules\Logger\Services\LogMaintenanceService;

Schedule::call(function (): void {
    CronService::runTask('cache.clear');
})->daily()->name('cron.cache-clear')->withoutOverlapping();

Schedule::call(function (): void {
    CronService::runTask('queue.prune');
})->weekly()->name('cron.queue-prune')->withoutOverlapping();

Schedule::call(function (): void {
    $retentionDays = (int) config('catmin.logs.retention_days', 14);
    $archiveRetentionDays = (int) config('catmin.logs.archive_retention_days', 90);

    app(LogMaintenanceService::class)->rotateDaily($retentionDays, $archiveRetentionDays);
})->dailyAt('02:30')->name('logger.rotate-daily')->withoutOverlapping();

Artisan::command('catmin:logs:rotate', function () {
    $retentionDays = (int) config('catmin.logs.retention_days', 14);
    $archiveRetentionDays = (int) config('catmin.logs.archive_retention_days', 90);

    $result = app(LogMaintenanceService::class)->rotateDaily($retentionDays, $archiveRetentionDays);

    $this->info('Rotation logs terminée.');
    $this->line('Archivé: ' . (int) ($result['archived'] ?? 0));
    $this->line('Purgé (archive): ' . (int) ($result['purged_archive'] ?? 0));
})->purpose('Rotate and archive system logs according to CATMIN retention policies');
