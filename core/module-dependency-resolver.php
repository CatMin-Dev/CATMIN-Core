<?php

declare(strict_types=1);

final class CoreModuleDependencyResolver
{
    public function resolve(array $modules): array
    {
        $errors = [];
        $graph = [];
        $inDegree = [];
        $index = [];

        foreach ($modules as $module) {
            $slug = strtolower(trim((string) ($module['manifest']['slug'] ?? '')));
            if ($slug === '') {
                continue;
            }
            $index[$slug] = $module;
            $graph[$slug] = [];
            $inDegree[$slug] = 0;
        }

        foreach ($index as $slug => $module) {
            $deps = $module['manifest']['dependencies'] ?? [];
            if (!is_array($deps)) {
                $deps = [];
            }

            foreach ($deps as $dep) {
                $depSlug = '';
                if (is_string($dep)) {
                    $depSlug = strtolower(trim($dep));
                } elseif (is_array($dep) && isset($dep['slug'])) {
                    $depSlug = strtolower(trim((string) $dep['slug']));
                }
                if ($depSlug === '') {
                    continue;
                }
                if (!isset($index[$depSlug])) {
                    $errors[] = 'Dépendance manquante: ' . $slug . ' -> ' . $depSlug;
                    continue;
                }

                $graph[$depSlug][] = $slug;
                $inDegree[$slug]++;
            }
        }

        $queue = [];
        foreach ($inDegree as $slug => $deg) {
            if ($deg === 0) {
                $queue[] = $slug;
            }
        }
        sort($queue);

        $sorted = [];
        while ($queue !== []) {
            $current = array_shift($queue);
            if (!is_string($current)) {
                continue;
            }
            $sorted[] = $current;
            foreach ($graph[$current] as $neighbor) {
                $inDegree[$neighbor]--;
                if ($inDegree[$neighbor] === 0) {
                    $queue[] = $neighbor;
                }
            }
            sort($queue);
        }

        if (count($sorted) !== count($index)) {
            $errors[] = 'Cycle de dépendances détecté';
        }

        return [
            'ok' => $errors === [],
            'errors' => $errors,
            'order' => $sorted,
        ];
    }
}

