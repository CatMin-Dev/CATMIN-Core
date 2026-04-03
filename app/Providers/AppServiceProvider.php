<?php

namespace App\Providers;

use App\Models\AdminUser;
use App\Services\AddonViewLoader;
use App\Services\AdminRuntimeInfoService;
use App\Services\ModuleViewLoader;
use App\Services\ModuleAssetLoader;
use App\Services\ModuleLangLoader;
use App\Services\AddonLangLoader;
use App\Services\Performance\JobPerformanceState;
use App\Services\Performance\PerformanceBudgetService;
use App\Services\Performance\RequestPerformanceState;
use App\Services\SuperAdminGuardService;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Modules\Logger\Services\AlertingService;
use Modules\Logger\Services\SystemLogService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Blade::directive('catminHook', function (string $expression): string {
            return "<?php echo \\App\\Services\\CatminHookRegistry::render({$expression}); ?>";
        });

        Blade::directive('catminModuleCss', function (): string {
            return "<?php echo \\App\\Services\\ModuleAssetLoader::renderCss(); ?>";
        });

        Blade::directive('catminModuleJs', function (): string {
            return "<?php echo \\App\\Services\\ModuleAssetLoader::renderJs(); ?>";
        });

        View::composer('admin.partials.footer', function ($view): void {
            $view->with('adminRuntimeInfo', app(AdminRuntimeInfoService::class)->get());
        });

        ModuleViewLoader::registerNamespaces();
        AddonViewLoader::registerNamespaces();
        ModuleLangLoader::registerNamespaces();
        AddonLangLoader::registerNamespaces();

        AdminUser::updating(function (AdminUser $adminUser): void {
            /** @var SuperAdminGuardService $guard */
            $guard = app(SuperAdminGuardService::class);

            if ($adminUser->isDirty('is_super_admin')) {
                $result = $guard->canDemote($adminUser, (bool) $adminUser->is_super_admin);
                if (!$result['allowed']) {
                    try {
                        app(SystemLogService::class)->logAudit(
                            'super_admin.demote.blocked',
                            'Tentative de retrait statut super-admin bloquee',
                            ['admin_user_id' => $adminUser->id, 'reason' => $result['reason']],
                            'warning',
                            (string) session('catmin_admin_username', '')
                        );
                    } catch (\Throwable) {
                    }

                    throw new \RuntimeException($result['reason']);
                }
            }

            if ($adminUser->isDirty('is_active')) {
                $result = $guard->canDeactivate($adminUser, (bool) $adminUser->is_active);
                if (!$result['allowed']) {
                    try {
                        app(SystemLogService::class)->logAudit(
                            'super_admin.deactivate.blocked',
                            'Tentative de desactivation super-admin bloquee',
                            ['admin_user_id' => $adminUser->id, 'reason' => $result['reason']],
                            'warning',
                            (string) session('catmin_admin_username', '')
                        );
                    } catch (\Throwable) {
                    }

                    throw new \RuntimeException($result['reason']);
                }
            }
        });

        AdminUser::deleting(function (AdminUser $adminUser): void {
            /** @var SuperAdminGuardService $guard */
            $guard = app(SuperAdminGuardService::class);
            $result = $guard->canDelete($adminUser);

            if ($result['allowed']) {
                return;
            }

            try {
                app(SystemLogService::class)->logAudit(
                    'super_admin.delete.blocked',
                    'Tentative de suppression super-admin bloquee',
                    ['admin_user_id' => $adminUser->id, 'reason' => $result['reason']],
                    'warning',
                    (string) session('catmin_admin_username', '')
                );
            } catch (\Throwable) {
            }

            throw new \RuntimeException($result['reason']);
        });

        DB::listen(function ($query): void {
            $threshold = (int) config('catmin.performance.slow_query_ms', 250);
            $time = (float) ($query->time ?? 0);
            $connection = method_exists($query, 'connectionName')
                ? (string) $query->connectionName
                : ((string) ($query->connection?->getName() ?? 'default'));

            RequestPerformanceState::recordQuery((string) $query->sql, $time, $connection, $threshold);

            if ($time < $threshold) {
                return;
            }

            if (str_contains(strtolower((string) $query->sql), 'system_logs')) {
                return;
            }

            try {
                app(SystemLogService::class)->logPerformance(
                    'db.query.slow',
                    'Slow database query detected',
                    [
                        'sql' => (string) $query->sql,
                        'time_ms' => $time,
                        'threshold_ms' => $threshold,
                        'connection' => $connection,
                        'route_name' => app()->runningInConsole() ? '' : (string) optional(request()->route())->getName(),
                        'path' => app()->runningInConsole() ? '' : (string) request()->path(),
                        'budget' => app()->runningInConsole() ? null : app(PerformanceBudgetService::class)->budgetForRequest(request()),
                    ],
                    'warning'
                );
            } catch (\Throwable) {
                // Avoid breaking request flow if logging fails.
            }
        });

        Queue::before(function (JobProcessing $event): void {
            $key = method_exists($event->job, 'uuid') ? (string) $event->job->uuid() : spl_object_hash($event->job);
            JobPerformanceState::start($key);
        });

        Queue::after(function (JobProcessed $event): void {
            $key = method_exists($event->job, 'uuid') ? (string) $event->job->uuid() : spl_object_hash($event->job);
            $durationMs = JobPerformanceState::stop($key);

            if ($durationMs === null) {
                return;
            }

            $threshold = (int) config('catmin.performance.slow_job_ms', 1500);
            if ($durationMs < $threshold) {
                return;
            }

            try {
                $jobName = method_exists($event->job, 'resolveName')
                    ? (string) $event->job->resolveName()
                    : 'queue.job';

                app(SystemLogService::class)->logPerformance(
                    'queue.job.performance',
                    'Long queue job detected',
                    [
                        'job' => $jobName,
                        'duration_ms' => $durationMs,
                        'threshold_ms' => $threshold,
                        'connection' => (string) ($event->connectionName ?? ''),
                        'queue' => method_exists($event->job, 'getQueue') ? (string) $event->job->getQueue() : '',
                    ],
                    'warning'
                );
            } catch (\Throwable) {
                // Never break queue flow on perf logging failure.
            }
        });

        Queue::failing(function ($event): void {
            try {
                $jobName = method_exists($event->job, 'resolveName')
                    ? (string) $event->job->resolveName()
                    : 'queue.job';

                $errorMessage = $event->exception?->getMessage() ?: 'Job failed without message';

                app(AlertingService::class)->alertJobFailed($jobName, $errorMessage, [
                    'connection' => (string) ($event->connectionName ?? ''),
                    'queue' => method_exists($event->job, 'getQueue') ? (string) $event->job->getQueue() : '',
                ]);
            } catch (\Throwable) {
                // Never break queue flow on alerting failure.
            }
        });
    }
}
