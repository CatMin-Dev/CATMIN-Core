<?php

declare(strict_types=1);

namespace Install;

final class InstallerProfileResolver
{
    public function resolve(string $profile, array $customModules = []): array
    {
        $profile = strtolower(trim($profile));

        return match ($profile) {
            'core-only' => ['core'],
            'recommended' => ['core', 'security', 'backup', 'legal'],
            'full' => ['core', 'security', 'backup', 'legal', 'notifications', 'analytics'],
            'custom' => array_values(array_filter(array_map('strval', $customModules))),
            default => ['core', 'security', 'backup', 'legal'],
        };
    }
}
