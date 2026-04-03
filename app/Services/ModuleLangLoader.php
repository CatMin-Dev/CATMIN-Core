<?php

namespace App\Services;

use Illuminate\Support\Facades\Lang;

/**
 * ModuleLangLoader — registers translation namespaces for all enabled modules.
 *
 * Convention: modules/{ModuleDir}/lang/{locale}.php or modules/{ModuleDir}/lang/{locale}/{file}.php
 * Access in code: __('module_notifications::notifications.title')
 *
 * Called from AppServiceProvider::boot() alongside ModuleViewLoader.
 */
class ModuleLangLoader
{
    /** @var array<string, bool> */
    protected static array $registered = [];

    public static function registerNamespaces(): void
    {
        foreach (ModuleManager::all() as $module) {
            $slug      = strtolower((string) ($module->slug ?? ''));
            $directory = (string) ($module->directory ?? '');

            if ($slug === '' || $directory === '') {
                continue;
            }

            $langPath = base_path('modules/' . $directory . '/lang');
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
        return 'module_' . str_replace('-', '_', strtolower($slug));
    }
}
