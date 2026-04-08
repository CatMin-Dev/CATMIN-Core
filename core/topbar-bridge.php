<?php

declare(strict_types=1);

require_once CATMIN_CORE . '/topbar-registry.php';
require_once CATMIN_CORE . '/notifications-repository.php';
require_once CATMIN_CORE . '/apps-repository.php';

final class CoreTopbarBridge
{
    public function __construct(
        private readonly CoreTopbarRegistry $registry = new CoreTopbarRegistry(),
        private readonly CoreNotificationsRepository $notifications = new CoreNotificationsRepository(),
        private readonly CoreAppsRepository $apps = new CoreAppsRepository()
    ) {}

    public function payload(array $user = []): array
    {
        $adminPath = '/' . trim((string) config('security.admin_path', 'admin'), '/');
        $locale = function_exists('catmin_locale') ? catmin_locale() : 'fr';
        $activeTheme = trim((string) ($_SESSION['catmin_theme'] ?? 'corporate'));
        if (!in_array($activeTheme, ['light', 'dark', 'corporate'], true)) {
            $activeTheme = 'corporate';
        }

        return [
            'caps' => $this->registry->capabilities(),
            'search' => [
                'placeholder' => __('topbar.search.placeholder'),
                'button' => __('topbar.search.button'),
            ],
            'language' => [
                'active' => $locale,
                'options' => [
                    ['code' => 'fr', 'label' => 'Français', 'flag' => '🇫🇷'],
                    ['code' => 'en', 'label' => 'English', 'flag' => '🇺🇸'],
                ],
            ],
            'notifications' => [
                'unread' => $this->notifications->countUnread(),
                'recent' => $this->notifications->listRecent(8),
            ],
            'apps' => $this->apps->listEnabled(),
            'settings_url' => $adminPath . '/settings/general',
            'themes' => ['light', 'dark', 'corporate'],
            'active_theme' => $activeTheme,
            'profile' => [
                'username' => (string) ($user['username'] ?? 'admin'),
                'email' => (string) ($user['email'] ?? ''),
                'role' => (string) ($user['role_slug'] ?? 'admin'),
            ],
        ];
    }
}
