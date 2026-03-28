<?php

namespace App\Services;

use Illuminate\Support\Facades\View;

class ModuleViewLoader
{
    /**
     * @var array<string, bool>
     */
    protected static array $registered = [];

    public static function registerNamespaces(): void
    {
        foreach (ModuleManager::all() as $module) {
            $slug = strtolower((string) ($module->slug ?? ''));
            $directory = (string) ($module->directory ?? '');

            if ($slug === '' || $directory === '') {
                continue;
            }

            $viewsPath = base_path('modules/' . $directory . '/Views');
            if (!is_dir($viewsPath)) {
                continue;
            }

            $namespace = self::namespaceForSlug($slug);
            if (isset(self::$registered[$namespace])) {
                continue;
            }

            View::addNamespace($namespace, $viewsPath);
            self::$registered[$namespace] = true;
        }
    }

    public static function namespaceForSlug(string $slug): string
    {
        return 'module_' . str_replace('-', '_', strtolower($slug));
    }
}
