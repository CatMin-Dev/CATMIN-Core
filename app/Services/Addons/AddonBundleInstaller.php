<?php

namespace App\Services\Addons;

use App\Services\AddonManager;
use Illuminate\Support\Facades\File;

class AddonBundleInstaller
{
    public function __construct(private readonly AddonBundleService $bundleService)
    {
    }

    /**
     * @return array<string,mixed>
     */
    public function install(string $bundleSlug): array
    {
        $bundle = $this->bundleService->find($bundleSlug);
        if ($bundle === null) {
            return ['ok' => false, 'message' => 'Bundle introuvable.'];
        }

        $compatibility = (array) ($bundle['compatibility'] ?? []);
        if (($compatibility['compatible'] ?? false) !== true) {
            return [
                'ok' => false,
                'message' => 'Bundle incompatible: dependances manquantes.',
                'compatibility' => $compatibility,
            ];
        }

        $order = $this->resolveInstallOrder($bundle);

        $installed = [];
        foreach ($order as $addonSlug) {
            if (!AddonManager::exists($addonSlug)) {
                return ['ok' => false, 'message' => 'Addon manquant: ' . $addonSlug, 'installed' => $installed];
            }

            $enabled = AddonManager::enable($addonSlug);
            if (!$enabled) {
                return ['ok' => false, 'message' => 'Echec activation addon: ' . $addonSlug, 'installed' => $installed];
            }

            $installed[] = $addonSlug;
        }

        $this->recordInstall($bundleSlug, $installed);

        return [
            'ok' => true,
            'message' => 'Bundle installe avec succes.',
            'installed' => $installed,
        ];
    }

    /**
     * @param array<string,mixed> $bundle
     * @return array<int,string>
     */
    public function resolveInstallOrder(array $bundle): array
    {
        return collect((array) ($bundle['install_order'] ?? $bundle['addons_included'] ?? []))
            ->map(fn ($s) => (string) $s)
            ->values()
            ->all();
    }

    /**
     * @return array<string,mixed>
     */
    public function state(): array
    {
        $path = storage_path('bundles-state.json');
        if (!File::exists($path)) {
            return ['bundles' => []];
        }

        $decoded = json_decode((string) File::get($path), true);

        return is_array($decoded) ? $decoded : ['bundles' => []];
    }

    private function recordInstall(string $bundleSlug, array $addons): void
    {
        $state = $this->state();
        $bundles = (array) ($state['bundles'] ?? []);

        $bundles[$bundleSlug] = [
            'installed_at' => now()->toIso8601String(),
            'addons' => array_values($addons),
        ];

        File::put(storage_path('bundles-state.json'), json_encode(['bundles' => $bundles], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}
