<?php

declare(strict_types=1);

final class CoreModuleMigrationRunner
{
    public function run(string $modulePath, string $direction = 'up', bool $strict = false): array
    {
        $direction = strtolower(trim($direction));
        if (!in_array($direction, ['up', 'down'], true)) {
            return ['ok' => false, 'executed' => [], 'message' => 'Direction de migration invalide'];
        }

        $baseDir = rtrim($modulePath, '/') . '/migrations';
        $dir = $direction === 'down' ? ($baseDir . '/down') : $baseDir;
        if (!is_dir($dir)) {
            if ($direction === 'down' && $strict) {
                return ['ok' => false, 'executed' => [], 'message' => 'Migrations DOWN absentes pour mode destructif'];
            }
            return ['ok' => true, 'executed' => [], 'message' => 'Aucune migration module'];
        }

        $files = glob($dir . '/*.php') ?: [];
        if ($direction === 'down') {
            rsort($files, SORT_STRING);
        } else {
            sort($files, SORT_STRING);
        }

        if ($files === []) {
            if ($direction === 'down' && $strict) {
                return ['ok' => false, 'executed' => [], 'message' => 'Migrations DOWN absentes pour mode destructif'];
            }
            return ['ok' => true, 'executed' => [], 'message' => 'Aucune migration module'];
        }

        $executed = [];
        foreach ($files as $file) {
            $real = realpath($file);
            if (!is_string($real) || !str_starts_with($real, $dir . '/')) {
                continue;
            }

            try {
                $callable = require $real;
                if (!is_callable($callable)) {
                    return ['ok' => false, 'executed' => $executed, 'message' => 'Migration module invalide: ' . basename($real)];
                }

                $rf = new ReflectionFunction(Closure::fromCallable($callable));
                if ($rf->getNumberOfParameters() >= 1) {
                    $callable($direction);
                } else {
                    $callable();
                }

                $executed[] = basename($real);
            } catch (Throwable $e) {
                return ['ok' => false, 'executed' => $executed, 'message' => 'Echec migration module ' . basename($real) . ': ' . $e->getMessage()];
            }
        }

        return ['ok' => true, 'executed' => $executed, 'message' => 'Migrations module executees (' . $direction . ')'];
    }
}

