<?php

namespace App\Providers;

use App\Services\ModuleViewLoader;
use App\Services\ModuleAssetLoader;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

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
    }
}
