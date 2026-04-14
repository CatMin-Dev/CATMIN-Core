<?php

declare(strict_types=1);

namespace Core\front;

final class FrontRouteResolver
{
    /** @return array<int, array{slug:string,path:string}> */
    public function resolve(array $modules): array
    {
        $resolved = [];

        foreach ($modules as $module) {
            $manifest = is_array($module['manifest'] ?? null) ? $module['manifest'] : [];
            $slug = strtolower(trim((string) ($manifest['slug'] ?? '')));
            if ($slug === '') {
                continue;
            }

            $entrypoints = is_array($manifest['entrypoints'] ?? null) ? $manifest['entrypoints'] : [];
            $frontRoutes = trim((string) ($entrypoints['front_routes'] ?? ''));
            if ($frontRoutes === '') {
                continue;
            }

            $modulePath = (string) ($module['path'] ?? '');
            if ($modulePath === '') {
                continue;
            }

            $candidate = str_starts_with($frontRoutes, '/') ? $frontRoutes : ($modulePath . '/' . ltrim($frontRoutes, '/'));
            $real = realpath($candidate);
            if (!is_string($real) || $real === '' || !is_file($real) || !str_starts_with($real, CATMIN_MODULES . '/')) {
                continue;
            }

            $resolved[] = ['slug' => $slug, 'path' => $real];
        }

        return $resolved;
    }
}
