<?php

declare(strict_types=1);

final class CoreLoader
{
    /**
     * @return array<int, array{name:string,type:string,path:string,enabled:bool}>
     */
    public static function loadModules(): array
    {
        $loaded = [];

        foreach (glob(CATMIN_MODULES . '/*', GLOB_ONLYDIR) ?: [] as $scopeDir) {
            $scope = strtolower(trim((string) basename($scopeDir)));

            foreach (glob($scopeDir . '/*', GLOB_ONLYDIR) ?: [] as $moduleDir) {
                $manifestFile = $moduleDir . '/manifest.json';
                if (!is_file($manifestFile)) {
                    continue;
                }

                $raw = file_get_contents($manifestFile);
                $decoded = is_string($raw) ? json_decode($raw, true) : null;
                if (!is_array($decoded)) {
                    continue;
                }

                $enabled = (bool) ($decoded['enabled'] ?? true);
                if (!$enabled) {
                    continue;
                }

                $loaded[] = [
                    'name' => (string) ($decoded['name'] ?? basename($moduleDir)),
                    'type' => (string) ($decoded['type'] ?? $scope),
                    'path' => $moduleDir,
                    'enabled' => true,
                ];
            }
        }

        return $loaded;
    }
}
