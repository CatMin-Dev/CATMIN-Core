<?php

declare(strict_types=1);

final class CoreModuleMandatoryDependencies
{
    /**
     * Mandatory dependencies enforced by core policy, even if a future module manifest forgets to declare them.
     */
    public static function forSlug(string $slug): array
    {
        $slug = strtolower(trim($slug));

        return [];
    }

    public static function mergedWithManifest(array $manifest): array
    {
        $slug = strtolower(trim((string) ($manifest['slug'] ?? '')));
        $requires = [];

        $deps = $manifest['dependencies'] ?? [];
        if (is_array($deps)) {
            if (array_is_list($deps)) {
                foreach ($deps as $dep) {
                    $d = strtolower(trim((string) $dep));
                    if ($d !== '') {
                        $requires[] = $d;
                    }
                }
            } else {
                foreach ((array) ($deps['requires'] ?? []) as $dep) {
                    $d = strtolower(trim((string) $dep));
                    if ($d !== '') {
                        $requires[] = $d;
                    }
                }
            }
        }

        $requires = array_merge($requires, self::forSlug($slug));

        return array_values(array_unique(array_filter($requires, static fn (string $d): bool => $d !== '')));
    }
}
