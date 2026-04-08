<?php

declare(strict_types=1);

return [
    'title' => 'Template',
    'validate' => static function (array $input, \Install\InstallerContext|null $context = null): array {
        $profile = (string) ($input['profile'] ?? 'recommended');
        $phase = (string) ($input['profile_phase'] ?? 'select');
        $allowed = ['core-only', 'recommended', 'full', 'custom'];

        if (!in_array($profile, $allowed, true)) {
            return ['ok' => false, 'message' => 'Invalid profile.', 'data' => []];
        }

        if (!in_array($phase, ['select', 'modules'], true)) {
            $phase = 'select';
        }

        $custom = [];
        if ($profile === 'custom') {
            $raw = $input['custom_modules'] ?? [];
            if (!is_array($raw)) {
                $raw = [$raw];
            }

            $custom = array_values(array_unique(array_filter(array_map(
                static fn (mixed $value): string => strtolower(trim((string) $value)),
                $raw
            ))));

            if (!in_array('core', $custom, true)) {
                array_unshift($custom, 'core');
            }
        }

        return ['ok' => true, 'data' => ['profile' => $profile, 'profile_phase' => $phase, 'custom_modules' => $custom]];
    },
];
