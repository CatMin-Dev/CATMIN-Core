<?php

namespace Tests\Concerns;

use App\Services\ModuleManager;
use App\Services\VersioningService;
use Illuminate\Support\Facades\File;

trait InteractsWithModules
{
    protected function module(string $slug): object
    {
        $module = ModuleManager::find($slug);
        $this->assertNotNull($module, "Module '{$slug}' introuvable.");

        return $module;
    }

    protected function modulePath(string $slug): string
    {
        $module = $this->module($slug);

        return base_path('modules/' . $module->directory);
    }

    protected function assertModuleVersionValid(string $slug): void
    {
        $module = $this->module($slug);
        $version = (string) ($module->version ?? '');

        $this->assertTrue(
            VersioningService::isValid($version),
            "Version invalide pour '{$slug}': {$version}"
        );
    }

    protected function assertModuleHasRoutesFile(string $slug): void
    {
        $path = $this->modulePath($slug) . '/routes.php';
        $this->assertTrue(File::exists($path), "routes.php manquant pour '{$slug}'.");
    }

    protected function assertModuleHasConfigFile(string $slug): void
    {
        $path = $this->modulePath($slug) . '/config.php';
        $this->assertTrue(File::exists($path), "config.php manquant pour '{$slug}'.");
    }

    protected function assertModuleDependsOn(string $slug, string $dependency): void
    {
        $module = $this->module($slug);
        $depends = collect((array) ($module->depends ?? []))
            ->map(fn ($value) => strtolower((string) $value))
            ->values()
            ->all();

        $this->assertContains(strtolower($dependency), $depends, "Le module '{$slug}' doit dependre de '{$dependency}'.");
    }
}
