<?php

declare(strict_types=1);

require_once CATMIN_CORE . '/topbar-registry.php';
require_once CATMIN_CORE . '/notifications-repository.php';
require_once CATMIN_CORE . '/apps-repository.php';
require_once CATMIN_CORE . '/module-runtime-snapshot.php';

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
                'items' => $this->searchResults($adminPath, '', 12),
                'endpoint' => $adminPath . '/search/suggest',
                'results_url' => $adminPath . '/search',
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
     * @return array<int, array<string, mixed>>
     */
    public function searchResults(string $adminPath, string $query, int $limit = 12): array
    {
        $query = $this->normalizeText($query);
        $rows = $this->searchIndex($adminPath);
        $scored = [];

        foreach ($rows as $row) {
            $score = 0;
            if ($query !== '') {
                $label = $this->normalizeText((string) ($row['label'] ?? ''));
                $description = $this->normalizeText((string) ($row['description'] ?? ''));
                $keywords = $this->normalizeText((string) ($row['keywords'] ?? ''));
                $answer = $this->normalizeText((string) ($row['answer'] ?? ''));
                $url = $this->normalizeText((string) ($row['url'] ?? ''));
                $inputs = $this->normalizeText(implode(' ', array_map(static fn ($v): string => (string) $v, (array) ($row['inputs'] ?? []))));

                if ($label !== '' && str_starts_with($label, $query)) {
                    $score += 48;
                }
                if ($label !== '' && str_contains($label, $query)) {
                    $score += 30;
                }
                if ($keywords !== '' && str_contains($keywords, $query)) {
                    $score += 20;
                }
                if ($description !== '' && str_contains($description, $query)) {
                    $score += 16;
                }
                if ($inputs !== '' && str_contains($inputs, $query)) {
                    $score += 14;
                }
                if ($answer !== '' && str_contains($answer, $query)) {
                    $score += 12;
                }
                if ($url !== '' && str_contains($url, $query)) {
                    $score += 8;
                }

                if ($score <= 0) {
                    continue;
                }
            }

            $row['_score'] = $score;
            $scored[] = $row;
        }

        usort($scored, static function (array $a, array $b): int {
            $aScore = (int) ($a['_score'] ?? 0);
            $bScore = (int) ($b['_score'] ?? 0);
            if ($aScore !== $bScore) {
                return $bScore <=> $aScore;
            }
            return strcmp((string) ($a['label'] ?? ''), (string) ($b['label'] ?? ''));
        });

        $scored = array_slice($scored, 0, max(1, min(50, $limit)));
        foreach ($scored as &$row) {
            unset($row['_score']);
        }
        unset($row);

        return array_values($scored);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function searchIndex(string $adminPath): array
    {
        $rows = [
            [
                'label' => __('nav.dashboard'),
                'url' => $adminPath . '/',
                'description' => __('nav.dashboard'),
                'keywords' => 'home accueil dashboard',
                'type' => 'page',
                'inputs' => [],
                'answer' => 'Acceder au tableau de bord principal et aux raccourcis d administration.',
            ],
            [
                'label' => __('nav.monitoring'),
                'url' => $adminPath . '/system/monitoring',
                'description' => __('system.monitoring.title'),
                'keywords' => 'monitoring securite health alertes',
                'type' => 'page',
                'inputs' => ['q', 'scope', 'status'],
                'answer' => 'Consulte l etat du systeme, les checks et les alertes actives.',
            ],
            [
                'label' => __('nav.health_check'),
                'url' => $adminPath . '/system/health',
                'description' => __('system.health.title'),
                'keywords' => 'health check systeme',
                'type' => 'page',
                'inputs' => [],
                'answer' => 'Affiche les verifications de sante de la plateforme.',
            ],
            [
                'label' => __('nav.logs'),
                'url' => $adminPath . '/logs',
                'description' => __('logs.title'),
                'keywords' => 'logs securite events audit',
                'type' => 'page',
                'inputs' => ['q', 'stream', 'level'],
                'answer' => 'Recherche des evenements techniques et securite dans les journaux.',
            ],
            [
                'label' => __('nav.notifications'),
                'url' => $adminPath . '/notifications',
                'description' => __('nav.notifications'),
                'keywords' => 'notifications alertes messages',
                'type' => 'page',
                'inputs' => [],
                'answer' => 'Liste les notifications recentes et leur statut de lecture.',
            ],
            [
                'label' => __('nav.cron'),
                'url' => $adminPath . '/cron',
                'description' => __('nav.cron'),
                'keywords' => 'cron taches scheduler',
                'type' => 'page',
                'inputs' => ['q', 'is_active'],
                'answer' => 'Configure et pilote les taches cron de CATMIN.',
            ],
            [
                'label' => __('nav.maintenance'),
                'url' => $adminPath . '/maintenance',
                'description' => __('nav.maintenance'),
                'keywords' => 'maintenance backup restauration',
                'type' => 'page',
                'inputs' => ['mode', 'reason'],
                'answer' => 'Active la maintenance, sauvegardes et operations sensibles.',
            ],
            [
                'label' => __('nav.module_manager'),
                'url' => $adminPath . '/modules',
                'description' => __('nav.modules'),
                'keywords' => 'modules addons manager',
                'type' => 'page',
                'inputs' => ['q', 'scope', 'status', 'trust'],
                'answer' => 'Gere les modules installes, leur statut et leur cycle de vie.',
            ],
            [
                'label' => __('nav.module_market'),
                'url' => $adminPath . '/modules/market',
                'description' => __('nav.module_market'),
                'keywords' => 'market store repository modules',
                'type' => 'page',
                'inputs' => ['q', 'channel', 'category'],
                'answer' => 'Explore et installe les modules disponibles sur le market.',
            ],
            [
                'label' => __('nav.staff_admins'),
                'url' => $adminPath . '/staff',
                'description' => __('nav.organization'),
                'keywords' => 'staff admins utilisateurs users',
                'type' => 'page',
                'inputs' => ['q', 'role', 'status'],
                'answer' => 'Gere les comptes administrateurs et leur activation.',
            ],
            [
                'label' => __('nav.roles_permissions'),
                'url' => $adminPath . '/roles',
                'description' => __('nav.roles_permissions'),
                'keywords' => 'roles permissions rbac',
                'type' => 'page',
                'inputs' => ['role', 'permissions[]'],
                'answer' => 'Configure les roles et la matrice des permissions.',
            ],
            [
                'label' => __('nav.settings'),
                'url' => $adminPath . '/settings/general',
                'description' => __('nav.settings'),
                'keywords' => 'settings configuration parametres',
                'type' => 'page',
                'inputs' => ['app_name', 'app_env', 'timezone', 'admin_path'],
                'answer' => 'Parametres generaux de la plateforme et du panneau admin.',
            ],
            [
                'label' => __('settings.section.appearance'),
                'url' => $adminPath . '/settings/appearance',
                'description' => __('settings.section.appearance'),
                'keywords' => 'theme interface affichage ui',
                'type' => 'settings',
                'inputs' => ['theme_default', 'table_density', 'show_debug'],
                'answer' => 'Regle le theme, densite et options d affichage.',
            ],
            [
                'label' => __('settings.section.sidebar'),
                'url' => $adminPath . '/settings/sidebar',
                'description' => __('settings.section.sidebar'),
                'keywords' => 'sidebar menu navigation order',
                'type' => 'settings',
                'inputs' => ['compact_sidebar', 'sidebar_order', 'sidebar_item_order'],
                'answer' => 'Organise la navigation: ordre des menus et sous-menus.',
            ],
            [
                'label' => __('settings.section.mail'),
                'url' => $adminPath . '/settings/mail',
                'description' => __('settings.section.mail'),
                'keywords' => 'email smtp mail notifications',
                'type' => 'settings',
                'inputs' => ['email_driver', 'email_host', 'email_port', 'email_from_email'],
                'answer' => 'Configure l envoi d emails et les parametres SMTP.',
            ],
            [
                'label' => __('settings.section.security'),
                'url' => $adminPath . '/settings/security',
                'description' => __('settings.section.security'),
                'keywords' => 'security session password 2fa',
                'type' => 'settings',
                'inputs' => ['session_minutes', 'max_attempts', 'password_min', 'enforce_2fa'],
                'answer' => 'Definit les regles de securite, sessions et mots de passe.',
            ],
            [
                'label' => __('settings.section.performance'),
                'url' => $adminPath . '/settings/performance',
                'description' => __('settings.section.performance'),
                'keywords' => 'performance cache optimize',
                'type' => 'settings',
                'inputs' => ['cache_enabled', 'cache_ttl', 'compression'],
                'answer' => 'Ajuste cache et options de performance applicative.',
            ],
            [
                'label' => __('nav.core_update'),
                'url' => $adminPath . '/system/updates',
                'description' => __('nav.core_update'),
                'keywords' => 'update mise a jour core release',
                'type' => 'page',
                'inputs' => ['channel'],
                'answer' => 'Consulte les mises a jour core et les actions de deploiement.',
            ],
            [
                'label' => __('nav.queue'),
                'url' => $adminPath . '/system/queue',
                'description' => __('nav.queue'),
                'keywords' => 'queue file attente jobs',
                'type' => 'page',
                'inputs' => ['q', 'status'],
                'answer' => 'Surveille les jobs en file d attente et leur execution.',
            ],
            [
                'label' => __('nav.trust_center'),
                'url' => $adminPath . '/system/trust-center',
                'description' => __('nav.trust_center'),
                'keywords' => 'trust center signatures securite',
                'type' => 'page',
                'inputs' => ['q', 'scope', 'status'],
                'answer' => 'Verifie confiance, signatures et integrite des modules.',
            ],
        ];

        foreach ($this->moduleSearchEntries($adminPath) as $entry) {
            $rows[] = $entry;
        }

        return $rows;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function moduleSearchEntries(string $adminPath): array
    {
        $rows = [];
        try {
            $snapshot = new CoreModuleRuntimeSnapshot();
            foreach ($snapshot->modules() as $moduleRow) {
                if (!((bool) ($moduleRow['valid'] ?? false)) || !((bool) ($moduleRow['compatible'] ?? false)) || !((bool) ($moduleRow['enabled'] ?? false))) {
                    continue;
                }

                $manifest = is_array($moduleRow['manifest'] ?? null) ? $moduleRow['manifest'] : [];
                $moduleType = strtolower(trim((string) ($manifest['type'] ?? '')));
                if ($moduleType !== 'admin' && $moduleType !== 'core') {
                    continue;
                }

                $moduleLabel = trim((string) ($manifest['display_name'] ?? ($manifest['name'] ?? $manifest['slug'] ?? 'Module')));
                $items = $manifest['admin_sidebar'] ?? $manifest['sidebar_entries'] ?? [];
                if (!is_array($items)) {
                    continue;
                }

                foreach ($items as $item) {
                    if (!is_array($item)) {
                        continue;
                    }
                    $href = trim((string) ($item['href'] ?? ($item['route'] ?? '')));
                    if ($href === '') {
                        continue;
                    }
                    $url = str_starts_with($href, '/') ? $href : ($adminPath . '/' . ltrim($href, '/'));
                    $label = trim((string) ($item['label'] ?? ''));
                    if ($label === '') {
                        $label = $moduleLabel;
                    }

                    $rows[] = [
                        'label' => $label,
                        'url' => $url,
                        'description' => $moduleLabel,
                        'keywords' => strtolower($moduleLabel . ' module addon ' . $label),
                        'type' => 'module',
                        'inputs' => [],
                        'answer' => 'Ouvrir la page module pour administrer ' . $moduleLabel . '.',
                    ];
                }
            }
        } catch (\Throwable) {
            return [];
        }

        return $rows;
    }

    private function normalizeText(string $value): string
    {
        $value = trim(mb_strtolower($value, 'UTF-8'));
        if ($value === '') {
            return '';
        }

        $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        if (is_string($converted) && $converted !== '') {
            $value = $converted;
        }

        $value = preg_replace('/[^a-z0-9\s\-_.]/', ' ', $value) ?? $value;
        $value = preg_replace('/\s+/', ' ', $value) ?? $value;
        return trim($value);
    }
}
