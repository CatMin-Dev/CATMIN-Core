<?php

namespace App\Services;

use Illuminate\Support\Facades\View;

class AddonViewLoader
{
    /**
     * @var array<string, bool>
     */
    protected static array $registered = [];

    public static function registerNamespaces(): void
    {
        foreach (AddonManager::all() as $addon) {
            $slug = strtolower((string) ($addon->slug ?? ''));
            $directory = (string) ($addon->directory ?? '');

            if ($slug === '' || $directory === '') {
                continue;
            }

            $viewsPath = base_path('addons/' . $directory . '/Views');
            if (!is_dir($viewsPath)) {
                continue;
            }

            foreach (self::namespacesForSlug($slug) as $namespace) {
                if (isset(self::$registered[$namespace])) {
                    continue;
                }

                View::addNamespace($namespace, $viewsPath);
                self::$registered[$namespace] = true;
            }
        }
    }

    /**
     * @return array<int, string>
     */
    public static function namespacesForSlug(string $slug): array
    {
        $normalized = strtolower($slug);

        return array_values(array_unique([
            $normalized,
            'addon_' . str_replace('-', '_', $normalized),
        ]));
    }
}