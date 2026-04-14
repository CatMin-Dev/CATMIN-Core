<?php

declare(strict_types=1);

namespace Core\front;

final class FrontBridgeRegistry
{
    /** @return array<int, array<string,mixed>> */
    public function register(array $modules): array
    {
        $endpoints = [];

        foreach ($modules as $module) {
            $manifest = is_array($module['manifest'] ?? null) ? $module['manifest'] : [];
            $entrypoints = is_array($manifest['entrypoints'] ?? null) ? $manifest['entrypoints'] : [];
            $bridgeFile = trim((string) ($entrypoints['front_bridge'] ?? ''));
            if ($bridgeFile === '') {
                continue;
            }

            $modulePath = (string) ($module['path'] ?? '');
            if ($modulePath === '') {
                continue;
            }

            $candidate = str_starts_with($bridgeFile, '/') ? $bridgeFile : ($modulePath . '/' . ltrim($bridgeFile, '/'));
            $real = realpath($candidate);
            if (!is_string($real) || $real === '' || !is_file($real) || !str_starts_with($real, CATMIN_MODULES . '/')) {
                continue;
            }

            $payload = require $real;
            if (!is_array($payload) || !((bool) ($payload['enabled'] ?? false))) {
                continue;
            }

            foreach ((array) ($payload['endpoints'] ?? []) as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $endpoint = trim((string) ($row['endpoint'] ?? ''));
                if ($endpoint === '') {
                    continue;
                }
                $endpoints[] = [
                    'module' => strtolower(trim((string) ($manifest['slug'] ?? ''))),
                    'endpoint' => $endpoint,
                    'method' => strtoupper(trim((string) ($row['method'] ?? 'GET'))),
                    'visibility' => strtolower(trim((string) ($row['visibility'] ?? 'public'))),
                    'permission_required' => $row['permission_required'] ?? null,
                ];
            }
        }

        return $endpoints;
    }
}
