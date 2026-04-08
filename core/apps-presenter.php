<?php

declare(strict_types=1);

final class CoreAppsPresenter
{
    public function iconHtml(array $app): string
    {
        $icon = trim((string) ($app['icon'] ?? ''));
        $label = trim((string) ($app['label'] ?? 'App'));

        if ($icon === '') {
            return '<span class="cat-apps-fallback">' . htmlspecialchars(mb_strtoupper(mb_substr($label, 0, 1)), ENT_QUOTES, 'UTF-8') . '</span>';
        }

        if (preg_match('/\.(svg|png|jpg|jpeg|webp)$/i', $icon) === 1) {
            return '<img src="' . htmlspecialchars($icon, ENT_QUOTES, 'UTF-8') . '" alt="" class="cat-apps-icon-img">';
        }

        return '<i class="' . htmlspecialchars($icon, ENT_QUOTES, 'UTF-8') . '"></i>';
    }

    public function normalizeTarget(string $target): string
    {
        $target = strtolower(trim($target));
        return in_array($target, ['_self', '_blank'], true) ? $target : '_blank';
    }
}
