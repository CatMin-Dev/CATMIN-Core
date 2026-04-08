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
            $deps = $this->extractRequires((array) ($module['manifest'] ?? []));
            foreach ($deps as $depSlug) {
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

    private function extractRequires(array $manifest): array
    {
        $deps = $manifest['dependencies'] ?? [];
        if (!is_array($deps)) {
            return [];
        }

        $requires = [];
        if (array_is_list($deps)) {
            foreach ($deps as $dep) {
                $slug = strtolower(trim((string) $dep));
                if ($slug !== '') {
                    $requires[] = $slug;
                }
            }
            return array_values(array_unique($requires));
        }

        foreach ((array) ($deps['requires'] ?? []) as $dep) {
            $slug = strtolower(trim((string) $dep));
            if ($slug !== '') {
                $requires[] = $slug;
            }
        }
        return array_values(array_unique($requires));
    }
}
