<?php

namespace App\Providers;

use App\Models\AdminUser;
use App\Services\ModuleViewLoader;
use App\Services\ModuleAssetLoader;
use App\Services\SuperAdminGuardService;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
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

        ModuleViewLoader::registerNamespaces();

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
                        'connection' => method_exists($query, 'connectionName')
                            ? (string) $query->connectionName
                            : ((string) ($query->connection?->getName() ?? 'default')),
                    ],
                    'warning'
                );
            } catch (\Throwable) {
                // Avoid breaking request flow if logging fails.
            }
        });
    }
}
