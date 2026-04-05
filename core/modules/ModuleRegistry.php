<?php

declare(strict_types=1);

namespace Core\modules;

use Core\support\PathManager;

final class ModuleRegistry
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function all(): array
    {
        $pathManager = new PathManager();
        $glob = $pathManager->root() . '/modules/*/module.json';

        $modules = [];
        foreach (glob($glob) ?: [] as $manifestPath) {
            $content = file_get_contents($manifestPath);
            if ($content === false) {
                continue;
            }

            $decoded = json_decode($content, true);
            if (!is_array($decoded)) {
                continue;
            }

            $decoded['manifest_path'] = $manifestPath;
            $modules[] = $decoded;
        }

        return $modules;
    }
}
