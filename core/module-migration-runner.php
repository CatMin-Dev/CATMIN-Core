<?php

declare(strict_types=1);

final class CoreModuleMigrationRunner
{
    public function run(string $modulePath): array
    {
        $dir = rtrim($modulePath, '/') . '/migrations';
        if (!is_dir($dir)) {
            return ['ok' => true, 'executed' => [], 'message' => 'Aucune migration module'];
        }

        $executed = [];
        foreach (glob($dir . '/*.php') ?: [] as $file) {
            $real = realpath($file);
            if (!is_string($real) || !str_starts_with($real, $dir . '/')) {
                continue;
            }
            $callable = require $real;
            if (is_callable($callable)) {
                $callable();
                $executed[] = basename($real);
            }
        }

        return ['ok' => true, 'executed' => $executed, 'message' => 'Migrations module executees'];
    }
}

