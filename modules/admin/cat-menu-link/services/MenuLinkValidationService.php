<?php

declare(strict_types=1);

namespace Modules\CatMenuLink\services;

final class MenuLinkValidationService
{
    public function normalizeMenuKey(string $menuKey): string
    {
        $menuKey = strtolower(trim($menuKey));
        $menuKey = preg_replace('/[^a-z0-9_\-]+/', '_', $menuKey) ?: '';
        return trim($menuKey, '_') ?: 'main_nav';
    }

    public function normalizeLinkType(string $type): string
    {
        $type = strtolower(trim($type));
        return in_array($type, ['entity_link', 'custom_url'], true) ? $type : 'entity_link';
    }

    public function sanitizeLabel(?string $value): ?string
    {
        $value = trim((string) $value);
        return $value === '' ? null : mb_substr($value, 0, 180);
    }
}
