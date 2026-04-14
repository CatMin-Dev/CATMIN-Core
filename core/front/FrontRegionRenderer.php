<?php

declare(strict_types=1);

namespace Core\front;

final class FrontRegionRenderer
{
    public function __construct(private readonly FrontSecurityPolicy $policy = new FrontSecurityPolicy()) {}

    /** @return array<int, array<string,mixed>> */
    public function declarations(array $modules): array
    {
        $regions = [];

        foreach ($modules as $module) {
            $manifest = is_array($module['manifest'] ?? null) ? $module['manifest'] : [];
            $entrypoints = is_array($manifest['entrypoints'] ?? null) ? $manifest['entrypoints'] : [];
            $regionFile = trim((string) ($entrypoints['front_regions'] ?? 'config/front.regions.php'));
            if ($regionFile === '') {
                continue;
            }

            $modulePath = (string) ($module['path'] ?? '');
            if ($modulePath === '') {
                continue;
            }

            $candidate = str_starts_with($regionFile, '/') ? $regionFile : ($modulePath . '/' . ltrim($regionFile, '/'));
            $real = realpath($candidate);
            if (!is_string($real) || $real === '' || !is_file($real) || !str_starts_with($real, CATMIN_MODULES . '/')) {
                continue;
            }

            $payload = require $real;
            foreach ((array) $payload as $entry) {
                if (!is_array($entry) || !$this->policy->allowsRegion($entry)) {
                    continue;
                }
                $entry['module'] = strtolower(trim((string) ($manifest['slug'] ?? '')));
                $entry['order'] = (int) ($entry['order'] ?? 100);
                $regions[] = $entry;
            }
        }

        usort($regions, static fn (array $a, array $b): int => ((int) ($a['order'] ?? 100) <=> (int) ($b['order'] ?? 100)));

        return $regions;
    }

    public function render(string $key, array $regions, array $context = []): string
    {
        $html = '';

        foreach ($regions as $entry) {
            if (trim((string) ($entry['key'] ?? '')) !== $key) {
                continue;
            }

            $renderer = trim((string) ($entry['renderer'] ?? ''));
            if ($renderer === '') {
                continue;
            }

            $output = $this->invokeRenderer($renderer, $entry, $context);
            if ($output !== '') {
                $html .= $output;
            }
        }

        return $html;
    }

    private function invokeRenderer(string $renderer, array $entry, array $context): string
    {
        if (str_contains($renderer, '@')) {
            [$class, $method] = array_map(static fn (string $v): string => trim($v), explode('@', $renderer, 2));
            if ($class !== '' && $method !== '' && class_exists($class)) {
                $instance = new $class();
                if (is_callable([$instance, $method])) {
                    $result = $instance->{$method}($context, $entry);
                    return is_string($result) ? $result : '';
                }
            }
            return '';
        }

        if (function_exists($renderer)) {
            $result = $renderer($context, $entry);
            return is_string($result) ? $result : '';
        }

        return '';
    }
}
