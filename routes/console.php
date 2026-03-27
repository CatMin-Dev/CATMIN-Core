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

Schedule::call(function (): void {
    CronService::runTask('cache.clear');
})->daily()->name('cron.cache-clear')->withoutOverlapping();

Schedule::call(function (): void {
    CronService::runTask('queue.prune');
})->weekly()->name('cron.queue-prune')->withoutOverlapping();
