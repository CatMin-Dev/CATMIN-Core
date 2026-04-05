<?php

declare(strict_types=1);

namespace Core\config;

final class EnvironmentDetector
{
    public function detect(EnvManager $env): string
    {
        $explicit = $env->get('APP_ENV') ?? $env->get('CATMIN_ENV');
        if (is_string($explicit) && $explicit !== '') {
            return strtolower($explicit);
        }

        if ($this->isDocker()) {
            return 'docker';
        }

        $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? php_uname('n')));
        if (str_contains($host, '.local') || str_contains($host, 'localhost') || str_ends_with($host, '.test')) {
            return 'local';
        }

        return 'production';
    }

    public function isDocker(): bool
    {
        return is_file('/.dockerenv') || is_file('/run/.containerenv');
    }
}
