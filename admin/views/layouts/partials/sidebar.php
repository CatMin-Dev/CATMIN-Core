<?php
$navGroups = [
    [
        'key' => 'dashboard',
        'label' => __('nav.dashboard'),
        'icon' => 'house-door',
        'href' => $adminBase . '/',
        'children' => [],
    ],
    [
        'key' => 'administration',
        'label' => __('nav.administration'),
        'icon' => 'calendar3',
        'children' => [
            ['key' => 'staff', 'label' => __('nav.staff_admins'), 'href' => $adminBase . '/staff'],
            ['key' => 'roles', 'label' => __('nav.roles_permissions'), 'href' => $adminBase . '/roles'],
        ],
    ],
    [
        'key' => 'modules',
        'label' => __('nav.modules'),
        'icon' => 'chat-left-text',
        'children' => [
            ['key' => 'module-manager', 'label' => __('nav.module_manager'), 'href' => $adminBase . '/modules'],
            ['key' => 'module-status', 'label' => __('nav.module_status'), 'href' => $adminBase . '/modules/status'],
            ['key' => 'module-market', 'label' => __('nav.module_market'), 'href' => $adminBase . '/modules/market'],
        ],
    ],
    [
        'key' => 'system',
        'label' => __('nav.system'),
        'icon' => 'speedometer2',
        'children' => [
            ['key' => 'monitoring', 'label' => __('nav.monitoring'), 'href' => $adminBase . '/system/monitoring'],
            ['key' => 'health', 'label' => __('nav.health_check'), 'href' => $adminBase . '/system/health'],
            ['key' => 'core-update', 'label' => __('nav.core_update'), 'href' => $adminBase . '/system/updates'],
            ['key' => 'logs', 'label' => __('nav.logs'), 'href' => $adminBase . '/logs'],
            ['key' => 'cron', 'label' => __('nav.cron'), 'href' => $adminBase . '/cron'],
            ['key' => 'maintenance', 'label' => __('nav.maintenance'), 'href' => $adminBase . '/maintenance'],
        ],
    ],
    [
        'key' => 'settings',
        'label' => __('nav.settings'),
        'icon' => 'gear',
        'children' => [
            ['key' => 'general', 'label' => __('nav.general'), 'href' => $adminBase . '/settings/general'],
            ['key' => 'mail', 'label' => __('nav.mail'), 'href' => $adminBase . '/settings/mail'],
            ['key' => 'security', 'label' => __('nav.security'), 'href' => $adminBase . '/settings/security'],
            ['key' => 'apps', 'label' => __('nav.apps'), 'href' => $adminBase . '/settings/apps'],
            ['key' => 'module-repositories', 'label' => __('nav.module_repositories'), 'href' => $adminBase . '/settings/module-repositories'],
        ],
    ],
];

$moduleNavEntries = [];
$adminModulesDir = defined('CATMIN_MODULES') ? CATMIN_MODULES . '/admin' : null;

if (is_string($adminModulesDir) && is_dir($adminModulesDir)) {
    foreach (glob($adminModulesDir . '/*', GLOB_ONLYDIR) ?: [] as $moduleDir) {
        $manifestFile = is_file($moduleDir . '/manifest.json') ? $moduleDir . '/manifest.json' : $moduleDir . '/module.json';
        if (!is_file($manifestFile)) {
            continue;
        }

        $raw = file_get_contents($manifestFile);
        $manifest = is_string($raw) ? (json_decode($raw, true) ?: []) : [];

        if (($manifest['enabled'] ?? true) !== true) {
            continue;
        }

        $items = $manifest['admin_sidebar'] ?? $manifest['sidebar'] ?? [];
        if (!is_array($items)) {
            continue;
        }

        $slug = strtolower(trim((string) ($manifest['slug'] ?? basename($moduleDir))));
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $label = trim((string) ($item['label'] ?? ''));
            if ($label === '') {
                continue;
            }

            $group = strtolower(trim((string) ($item['group'] ?? 'modules')));
            $href = trim((string) ($item['href'] ?? ''));
            if ($href === '') {
                $href = '#';
            } elseif ($href[0] !== '/') {
                $href = rtrim($adminBase, '/') . '/' . ltrim($href, '/');
            }

            $moduleNavEntries[] = [
                'group' => $group !== '' ? $group : 'modules',
                'key' => strtolower(trim((string) ($item['key'] ?? ($slug . '-' . md5($label))))),
                'label' => $label,
                'href' => $href,
                'order' => (int) ($item['order'] ?? 100),
            ];
        }
    }
}

if ($moduleNavEntries !== []) {
    usort($moduleNavEntries, static fn (array $a, array $b): int => ($a['order'] <=> $b['order']) ?: strcmp((string) $a['label'], (string) $b['label']));

    foreach ($moduleNavEntries as $entry) {
        $groupKey = (string) ($entry['group'] ?? 'modules');
        $inserted = false;

        foreach ($navGroups as &$group) {
            if ((string) ($group['key'] ?? '') !== $groupKey) {
                continue;
            }

            $group['children'][] = [
                'key' => (string) $entry['key'],
                'label' => (string) $entry['label'],
                'href' => (string) $entry['href'],
            ];
            $inserted = true;
            break;
        }
        unset($group);

        if (!$inserted) {
            $foundModulesGroup = false;
            foreach ($navGroups as &$group) {
                if ((string) ($group['key'] ?? '') !== 'modules') {
                    continue;
                }

                $group['children'][] = [
                    'key' => (string) $entry['key'],
                    'label' => (string) $entry['label'],
                    'href' => (string) $entry['href'],
                ];
                $foundModulesGroup = true;
                break;
            }
            unset($group);

            if (!$foundModulesGroup) {
                $navGroups[] = [
                    'key' => 'modules',
                    'label' => 'Modules',
                    'icon' => 'chat',
                    'children' => [[
                        'key' => (string) $entry['key'],
                        'label' => (string) $entry['label'],
                        'href' => (string) $entry['href'],
                    ]],
                ];
            }
        }
    }
}

$sidebarIconSvg = static function (string $name): string {
    return match ($name) {
        'house-door', 'home' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V21h14V9.5"/></svg>',
        'calendar3', 'calendar' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="17" rx="2"/><path d="M8 2v4M16 2v4M3 10h18"/></svg>',
        'chat-left-text', 'chat' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M7 17 3 21V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H7Z"/></svg>',
        'speedometer2', 'chart' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 14a8 8 0 1 1 16 0"/><path d="m12 14 4-4"/><path d="M6 18h12"/></svg>',
        'gear', 'cog' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 0 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 0 1-4 0v-.2a1.7 1.7 0 0 0-1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 0 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 0 1 0-4h.2a1.7 1.7 0 0 0 1.5-1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 0 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3h.1a1.7 1.7 0 0 0 1-1.5V3a2 2 0 0 1 4 0v.2a1.7 1.7 0 0 0 1 1.5h.1a1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 0 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8v.1a1.7 1.7 0 0 0 1.5 1H21a2 2 0 0 1 0 4h-.2a1.7 1.7 0 0 0-1.5 1z"/></svg>',
        default => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="8"/></svg>',
    };
};
?>
<aside class="cat-sidebar border-end">
    <div class="cat-sidebar-brand">
        <a href="<?= htmlspecialchars($adminBase . '/', ENT_QUOTES, 'UTF-8') ?>" class="cat-brand-link">
            <img src="/assets/logo-color.png" alt="CATMIN" class="cat-brand-logo cat-brand-logo-color">
            <img src="/assets/logo-white.png" alt="CATMIN" class="cat-brand-logo cat-brand-logo-white">
            <span class="cat-brand-text">CATMIN</span>
        </a>
    </div>

    <nav class="cat-sidebar-nav" aria-label="<?= htmlspecialchars(__('nav.main'), ENT_QUOTES, 'UTF-8') ?>">
        <?php foreach ($navGroups as $group): ?>
            <?php
            $children = is_array($group['children'] ?? null) ? $group['children'] : [];
            $isDirect = $children === [];
            $groupHasActive = ((string) ($group['key'] ?? '') === $activeNav);
            if (!$groupHasActive) {
                foreach ($children as $child) {
                    if (($child['key'] ?? '') === $activeNav) {
                        $groupHasActive = true;
                        break;
                    }
                }
            }
            ?>
            <?php if ($isDirect): ?>
                <a class="cat-nav-group-trigger <?= $groupHasActive ? 'is-direct-active' : '' ?>" href="<?= htmlspecialchars((string) ($group['href'] ?? '#'), ENT_QUOTES, 'UTF-8') ?>">
                    <span class="cat-nav-icon"><?= $sidebarIconSvg((string) $group['icon']) ?></span>
                    <span class="cat-nav-group-label"><?= htmlspecialchars((string) $group['label'], ENT_QUOTES, 'UTF-8') ?></span>
                </a>
            <?php else: ?>
                <div class="cat-nav-group <?= $groupHasActive ? 'is-open' : '' ?>" data-cat-nav-group>
                    <button type="button" class="cat-nav-group-trigger" data-cat-nav-trigger aria-expanded="<?= $groupHasActive ? 'true' : 'false' ?>">
                        <span class="cat-nav-icon"><?= $sidebarIconSvg((string) $group['icon']) ?></span>
                        <span class="cat-nav-group-label"><?= htmlspecialchars((string) $group['label'], ENT_QUOTES, 'UTF-8') ?></span>
                        <?php if (!empty($group['badge'])): ?>
                            <span class="cat-nav-group-badge"><?= htmlspecialchars((string) $group['badge'], ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endif; ?>
                    </button>

                    <div class="cat-subnav cat-nav-compact-panel" data-cat-subnav data-cat-group-title="<?= htmlspecialchars((string) $group['label'], ENT_QUOTES, 'UTF-8') ?>">
                        <div class="cat-compact-panel-title"><?= htmlspecialchars((string) $group['label'], ENT_QUOTES, 'UTF-8') ?></div>
                        <?php foreach ($children as $child): ?>
                            <a class="cat-subnav-link <?= (($child['key'] ?? '') === $activeNav) ? 'is-active' : '' ?>" href="<?= htmlspecialchars((string) ($child['href'] ?? '#'), ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars((string) ($child['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </nav>

</aside>
