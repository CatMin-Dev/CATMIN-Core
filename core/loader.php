<?php

declare(strict_types=1);

final class CoreLoader
{
    /**
     * @return array<int, array{name:string,type:string,path:string,enabled:bool}>
     */
    public static function loadModules(): array
    {
        require_once CATMIN_CORE . '/module-runtime-snapshot.php';

        $snapshot = new CoreModuleRuntimeSnapshot();
        $loaded = [];

        foreach ($snapshot->modules() as $module) {
            if (!((bool) ($module['valid'] ?? false)) || !((bool) ($module['compatible'] ?? false)) || !((bool) ($module['enabled'] ?? false))) {
                continue;
            }

            $manifest = (array) ($module['manifest'] ?? []);
            $moduleDir = (string) ($module['path'] ?? '');
            $loaded[] = [
                'name' => (string) ($manifest['name'] ?? basename($moduleDir !== '' ? $moduleDir : 'module')),
                'type' => (string) ($manifest['type'] ?? 'module'),
                'path' => $moduleDir,
                'enabled' => true,
            ];
        }

        return $loaded;
    }
}
