<?php

declare(strict_types=1);

namespace Core\support;

final class PathManager
{
    public function root(): string
    {
        return CATMIN_ROOT;
    }

    public function areaPath(string $area): string
    {
        return match ($area) {
            'admin' => CATMIN_ADMIN,
            'install' => CATMIN_INSTALL,
            default => CATMIN_FRONT,
        };
    }

    public function routesFile(string $area): string
    {
        return $this->areaPath($area) . '/routes.php';
    }

    public function viewPath(string $area, string $template): string
    {
        $relative = str_replace('.', '/', $template) . '.php';
        return $this->areaPath($area) . '/views/' . ltrim($relative, '/');
    }

    public function storagePath(string $relative = ''): string
    {
        return CATMIN_STORAGE . ($relative !== '' ? '/' . ltrim($relative, '/') : '');
    }
}
