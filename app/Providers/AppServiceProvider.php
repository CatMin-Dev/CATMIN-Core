<?php

namespace App\Providers;

use App\Services\ModuleViewLoader;
use App\Services\ModuleAssetLoader;
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
