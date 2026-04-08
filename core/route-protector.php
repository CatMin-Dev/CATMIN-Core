<?php

declare(strict_types=1);

final class CoreRouteProtector
{
    public function isForbiddenPath(string $path): bool
    {
        $normalized = '/' . ltrim(trim($path), '/');
        foreach (['/core', '/modules', '/storage', '/config'] as $blocked) {
            if ($normalized === $blocked || str_starts_with($normalized, $blocked . '/')) {
                return true;
            }
        }

        return false;
    }
}

