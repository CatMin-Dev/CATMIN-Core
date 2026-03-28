<?php

namespace App\Services;

use Illuminate\Support\HtmlString;

class ModuleAssetLoader
{
    /**
     * @return array<int, string>
     */
    public static function css(): array
    {
        return self::collect('css');
    }

    /**
     * @return array<int, string>
     */
    public static function js(): array
    {
        return self::collect('js');
    }

    public static function renderCss(): HtmlString
    {
        $tags = array_map(
            fn (string $path) => '<link rel="stylesheet" href="' . e(asset($path)) . '">',
            self::css()
        );

        return new HtmlString(implode("\n", $tags));
    }

    public static function renderJs(): HtmlString
    {
        $tags = array_map(
            fn (string $path) => '<script src="' . e(asset($path)) . '" defer></script>',
            self::js()
        );

        return new HtmlString(implode("\n", $tags));
    }

    /**
     * @return array<int, string>
     */
    private static function collect(string $type): array
    {
        $assets = [];

        foreach (ModuleManager::enabled() as $module) {
            $declared = self::declaredAssets($module, $type);

            foreach ($declared as $path) {
                if (is_string($path) && trim($path) !== '') {
                    $assets[] = ltrim(trim($path), '/');
                }
            }
        }

        return array_values(array_unique($assets));
    }

    /**
     * @param object $module
     * @return array<int, string>
     */
    private static function declaredAssets(object $module, string $type): array
    {
        $assets = $module->assets ?? null;
        if ($assets === null) {
            return [];
        }

        if (is_array($assets)) {
            $group = $assets[$type] ?? [];
            return is_array($group) ? $group : [];
        }

        if (is_object($assets)) {
            $group = $assets->{$type} ?? [];
            return is_array($group) ? $group : [];
        }

        return [];
    }
}
