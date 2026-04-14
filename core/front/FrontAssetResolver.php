<?php

declare(strict_types=1);

namespace Core\front;

final class FrontAssetResolver
{
    /** @return array{css:array<int,string>,js:array<int,string>} */
    public function resolve(array $modules): array
    {
        $css = [];
        $js = [];

        foreach ($modules as $module) {
            $manifest = is_array($module['manifest'] ?? null) ? $module['manifest'] : [];
            $assets = is_array($manifest['assets'] ?? null) ? $manifest['assets'] : [];
            $front = is_array($assets['front'] ?? null) ? $assets['front'] : [];

            foreach ((array) ($front['css'] ?? []) as $asset) {
                $assetPath = trim((string) $asset);
                if ($assetPath !== '') {
                    $css[] = $assetPath;
                }
            }
            foreach ((array) ($front['js'] ?? []) as $asset) {
                $assetPath = trim((string) $asset);
                if ($assetPath !== '') {
                    $js[] = $assetPath;
                }
            }
        }

        return [
            'css' => array_values(array_unique($css)),
            'js' => array_values(array_unique($js)),
        ];
    }
}
