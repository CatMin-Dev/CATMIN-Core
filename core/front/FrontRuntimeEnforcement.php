<?php

declare(strict_types=1);

namespace Core\front;

final class FrontRuntimeEnforcement
{
    /** @param array<int,array<string,mixed>> $modules */
    public function enforce(array $modules): array
    {
        $safe = [];

        foreach ($modules as $module) {
            $path = (string) ($module['path'] ?? '');
            if ($path === '') {
                continue;
            }
            $real = realpath($path);
            if (!is_string($real) || $real === '' || !str_starts_with($real, CATMIN_MODULES . '/')) {
                continue;
            }
            $module['path'] = $real;
            $safe[] = $module;
        }

        return $safe;
    }
}
