<?php

namespace App\Services;

use Illuminate\Support\Facades\Lang;

/**
 * AddonLangLoader — registers translation namespaces for all installed addons.
 *
 * Convention: addons/{AddonDir}/lang/{locale}.php or addons/{AddonDir}/lang/{locale}/{file}.php
 * Access in code: __('addon_cat_blog::blog.title')
 *
 * Called from AppServiceProvider::boot() alongside AddonViewLoader.
 */
class AddonLangLoader
{
    /** @var array<string, bool> */
    protected static array $registered = [];

    public static function registerNamespaces(): void
    {
        foreach (AddonManager::all() as $addon) {
            $slug      = strtolower((string) ($addon->slug ?? ''));
            $directory = (string) ($addon->directory ?? '');

            if ($slug === '' || $directory === '') {
                continue;
            }

            $langPath = base_path('addons/' . $directory . '/lang');
            if (!is_dir($langPath)) {
                continue;
            }

            $namespace = self::namespaceForSlug($slug);
            if (isset(self::$registered[$namespace])) {
                continue;
            }

            Lang::addNamespace($namespace, $langPath);
            self::$registered[$namespace] = true;
        }
    }

    public static function namespaceForSlug(string $slug): string
    {
        // Support both cat-blog and cat_blog style slugs
        return 'addon_' . str_replace('-', '_', strtolower($slug));
    }
}
