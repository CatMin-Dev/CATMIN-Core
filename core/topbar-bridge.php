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
                'items' => $this->searchItems($adminPath),
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

    /**
     * @return array<int, array{label:string,url:string,description:string,keywords:string}>
     */
    private function searchItems(string $adminPath): array
    {
        return [
            [
                'label' => __('nav.dashboard'),
                'url' => $adminPath . '/',
                'description' => __('nav.dashboard'),
                'keywords' => 'home accueil dashboard',
            ],
            [
                'label' => __('nav.monitoring'),
                'url' => $adminPath . '/system/monitoring',
                'description' => __('system.monitoring.title'),
                'keywords' => 'monitoring securite health alertes',
            ],
            [
                'label' => __('nav.health_check'),
                'url' => $adminPath . '/system/health',
                'description' => __('system.health.title'),
                'keywords' => 'health check systeme',
            ],
            [
                'label' => __('nav.logs'),
                'url' => $adminPath . '/logs',
                'description' => __('logs.title'),
                'keywords' => 'logs securite events audit',
            ],
            [
                'label' => __('nav.notifications'),
                'url' => $adminPath . '/notifications',
                'description' => __('nav.notifications'),
                'keywords' => 'notifications alertes messages',
            ],
            [
                'label' => __('nav.cron'),
                'url' => $adminPath . '/cron',
                'description' => __('nav.cron'),
                'keywords' => 'cron taches scheduler',
            ],
            [
                'label' => __('nav.maintenance'),
                'url' => $adminPath . '/maintenance',
                'description' => __('nav.maintenance'),
                'keywords' => 'maintenance backup restauration',
            ],
            [
                'label' => __('nav.module_manager'),
                'url' => $adminPath . '/modules',
                'description' => __('nav.modules'),
                'keywords' => 'modules addons manager',
            ],
            [
                'label' => __('nav.module_market'),
                'url' => $adminPath . '/modules/market',
                'description' => __('nav.module_market'),
                'keywords' => 'market store repository modules',
            ],
            [
                'label' => __('nav.staff_admins'),
                'url' => $adminPath . '/staff',
                'description' => __('nav.organization'),
                'keywords' => 'staff admins utilisateurs users',
            ],
            [
                'label' => __('nav.roles_permissions'),
                'url' => $adminPath . '/roles',
                'description' => __('nav.roles_permissions'),
                'keywords' => 'roles permissions rbac',
            ],
            [
                'label' => __('nav.settings'),
                'url' => $adminPath . '/settings/general',
                'description' => __('nav.settings'),
                'keywords' => 'settings configuration parametres',
            ],
            [
                'label' => __('nav.core_update'),
                'url' => $adminPath . '/system/updates',
                'description' => __('nav.core_update'),
                'keywords' => 'update mise a jour core release',
            ],
            [
                'label' => __('nav.queue'),
                'url' => $adminPath . '/system/queue',
                'description' => __('nav.queue'),
                'keywords' => 'queue file attente jobs',
            ],
            [
                'label' => __('nav.trust_center'),
                'url' => $adminPath . '/system/trust-center',
                'description' => __('nav.trust_center'),
                'keywords' => 'trust center signatures securite',
            ],
        ];
    }
}
