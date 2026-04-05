<?php

declare(strict_types=1);

return [
    'title' => 'Profile',
    'validate' => static function (array $input, \Install\InstallerContext|null $context = null): array {
        $profile = (string) ($input['profile'] ?? 'recommended');
        $allowed = ['core-only', 'recommended', 'full', 'custom'];

        if (!in_array($profile, $allowed, true)) {
            return ['ok' => false, 'message' => 'Invalid profile.', 'data' => []];
        }

        $custom = [];
        if ($profile === 'custom') {
            $raw = (string) ($input['custom_modules'] ?? '');
            $custom = array_values(array_filter(array_map('trim', explode(',', $raw))));
        }

        return ['ok' => true, 'data' => ['profile' => $profile, 'custom_modules' => $custom]];
    },
];
