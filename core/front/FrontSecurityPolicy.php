<?php

declare(strict_types=1);

namespace Core\front;

final class FrontSecurityPolicy
{
    public function allowsModule(array $module): bool
    {
        if (!((bool) ($module['valid'] ?? false)) || !((bool) ($module['compatible'] ?? false)) || !((bool) ($module['enabled'] ?? false))) {
            return false;
        }

        $manifest = is_array($module['manifest'] ?? null) ? $module['manifest'] : [];
        $lifecycle = strtolower(trim((string) ($manifest['lifecycle_status'] ?? 'active')));
        if (in_array($lifecycle, ['abandoned', 'archived'], true)) {
            return false;
        }

        return true;
    }

    public function allowsRegion(array $region): bool
    {
        $key = trim((string) ($region['key'] ?? ''));
        if ($key === '' || preg_match('/^[a-z0-9][a-z0-9._-]*$/', $key) !== 1) {
            return false;
        }

        $visibility = strtolower(trim((string) ($region['visibility'] ?? 'public')));
        return in_array($visibility, ['public', 'authenticated'], true);
    }
}
