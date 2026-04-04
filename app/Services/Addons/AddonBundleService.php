<?php

namespace App\Services\Addons;

use App\Services\AddonManager;
use App\Services\ModuleManager;
use Illuminate\Support\Facades\File;

class AddonBundleService
{
    public function bundlesPath(): string
    {
        return base_path('bundles');
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function list(): array
    {
        if (!File::isDirectory($this->bundlesPath())) {
            return [];
        }

        $bundles = [];
        foreach (File::files($this->bundlesPath()) as $file) {
            if (!str_ends_with($file->getFilename(), '.bundle.json')) {
                continue;
            }

            $decoded = json_decode((string) File::get($file->getPathname()), true);
            if (!is_array($decoded)) {
                continue;
            }

            $bundles[] = $this->evaluate($decoded);
        }

        usort($bundles, fn ($a, $b) => strcmp((string) $a['name'], (string) $b['name']));

        return $bundles;
    }

    /**
     * @param array<string,mixed> $bundle
     * @return array<string,mixed>
     */
    public function evaluate(array $bundle): array
    {
        $addons = collect((array) ($bundle['addons_included'] ?? []))->map(fn ($s) => (string) $s)->values()->all();
        $requiredModules = collect((array) ($bundle['required_modules'] ?? []))->map(fn ($s) => (string) $s)->values()->all();
        $optionalAddons = collect((array) ($bundle['optional_addons'] ?? []))->map(fn ($s) => (string) $s)->values()->all();

        $missingAddons = array_values(array_filter($addons, fn ($slug) => !AddonManager::exists($slug)));
        $missingModules = array_values(array_filter($requiredModules, fn ($slug) => !ModuleManager::exists($slug)));

        $compatibility = empty($missingAddons) && empty($missingModules);

        return array_merge($bundle, [
            'addons_included' => $addons,
            'required_modules' => $requiredModules,
            'optional_addons' => $optionalAddons,
            'compatibility' => [
                'compatible' => $compatibility,
                'missing_addons' => $missingAddons,
                'missing_modules' => $missingModules,
            ],
        ]);
    }

    /**
     * @return array<string,mixed>|null
     */
    public function find(string $slug): ?array
    {
        foreach ($this->list() as $bundle) {
            if (($bundle['slug'] ?? null) === $slug) {
                return $bundle;
            }
        }

        return null;
    }
}
