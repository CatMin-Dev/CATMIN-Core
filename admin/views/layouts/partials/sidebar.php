<?php
$navGroups = [
    [
        'key' => 'dashboard',
        'label' => __('nav.dashboard'),
        'icon' => 'house-door',
        'order' => 10,
        'href' => $adminBase . '/',
        'children' => [],
    ],
    [
        'key' => 'organization',
        'label' => __('nav.organization'),
        'icon' => 'diagram-3',
        'order' => 40,
        'children' => [
            ['key' => 'staff', 'label' => __('nav.staff_admins'), 'href' => $adminBase . '/staff', 'permissions' => ['admin.users.manage']],
            ['key' => 'roles', 'label' => __('nav.roles_permissions'), 'href' => $adminBase . '/roles', 'permissions' => ['admin.roles.manage']],
        ],
    ],
    [
        'key' => 'system',
        'label' => __('nav.system'),
        'icon' => 'speedometer2',
        'order' => 60,
        'children' => [
            ['key' => 'monitoring', 'label' => __('nav.monitoring'), 'href' => $adminBase . '/system/monitoring', 'permissions' => ['core.system.view']],
            ['key' => 'health', 'label' => __('nav.health_check'), 'href' => $adminBase . '/system/health', 'permissions' => ['core.system.view']],
            ['key' => 'core-update', 'label' => __('nav.core_update'), 'href' => $adminBase . '/system/updates', 'permissions' => ['core.system.manage']],
            ['key' => 'queue', 'label' => __('nav.queue'), 'href' => $adminBase . '/system/queue', 'permissions' => ['core.system.manage']],
            ['key' => 'logs', 'label' => __('nav.logs'), 'href' => $adminBase . '/logs', 'permissions' => ['core.logs.view']],
            ['key' => 'notifications', 'label' => __('nav.notifications'), 'href' => $adminBase . '/notifications', 'permissions' => ['core.notifications.view']],
            ['key' => 'cron', 'label' => __('nav.cron'), 'href' => $adminBase . '/cron', 'permissions' => ['core.cron.manage']],
            ['key' => 'maintenance', 'label' => __('nav.maintenance'), 'href' => $adminBase . '/maintenance', 'permissions' => ['core.maintenance.manage']],
        ],
    ],
    [
        'key' => 'modules',
        'label' => __('nav.modules'),
        'icon' => 'puzzle',
        'order' => 70,
        'children' => [
            ['key' => 'module-manager', 'label' => __('nav.module_manager'), 'href' => $adminBase . '/modules', 'permissions' => ['core.modules.manage']],
            ['key' => 'module-status', 'label' => __('nav.module_status'), 'href' => $adminBase . '/modules/status', 'permissions' => ['core.modules.view']],
            ['key' => 'module-market', 'label' => __('nav.module_market'), 'href' => $adminBase . '/modules/market', 'permissions' => ['core.modules.manage']],
            ['key' => 'trust-center', 'label' => __('nav.trust_center'), 'href' => $adminBase . '/system/trust-center', 'permissions' => ['core.trust.manage']],
        ],
    ],
    [
        'key' => 'settings',
        'label' => __('nav.settings'),
        'icon' => 'gear',
        'order' => 80,
        'permissions' => ['core.settings.manage'],
        'href' => $adminBase . '/settings/general',
        'children' => [],
    ],
];

$groupMeta = [
    'content' => ['label' => __('nav.content'), 'icon' => 'file-earmark-text', 'order' => 20],
    'media' => ['label' => __('nav.media'), 'icon' => 'images', 'order' => 30],
    'marketing' => ['label' => __('nav.marketing'), 'icon' => 'megaphone', 'order' => 50],
];

if (!class_exists('CoreSettingsEngine')) {
    require_once CATMIN_CORE . '/settings-engine.php';
}
if (!class_exists('Core\\database\\ConnectionManager')) {
    require_once CATMIN_CORE . '/database/ConnectionManager.php';
}
if (!class_exists('Admin\\controllers\\AuthController')) {
    require_once CATMIN_ADMIN . '/controllers/AuthController.php';
}

$resolveUser = static function () use ($user): ?array {
    if (is_array($user) && $user !== []) {
        return $user;
    }
    try {
        $controller = new Admin\controllers\AuthController();
        return $controller->currentUser();
    } catch (Throwable $exception) {
        return null;
    }
};

$currentUser = $resolveUser();
$isSuperAdmin = is_array($currentUser) && (string) ($currentUser['role_slug'] ?? '') === 'super-admin';
$rolePermissions = [];

if (!$isSuperAdmin && is_array($currentUser) && !empty($currentUser['role_id'])) {
    try {
        $pdo = (new Core\database\ConnectionManager())->connection();
        $adminPrefix = (string) config('database.prefixes.admin', 'admin_');
        $permissionsTable = $adminPrefix . 'permissions';
        $rolePermissionsTable = $adminPrefix . 'role_permissions';
        $stmt = $pdo->prepare(
            'SELECT p.slug FROM ' . $permissionsTable . ' p '
            . 'INNER JOIN ' . $rolePermissionsTable . ' rp ON rp.permission_id = p.id '
            . 'WHERE rp.role_id = :role_id'
        );
        $stmt->execute(['role_id' => (int) $currentUser['role_id']]);
        $rolePermissions = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
    } catch (Throwable $exception) {
        $rolePermissions = [];
    }
}

$hasAnyPermission = static function (array $required) use ($isSuperAdmin, $rolePermissions): bool {
    if ($required === []) {
        return true;
    }
    if ($isSuperAdmin) {
        return true;
    }
    if ($rolePermissions === []) {
        return false;
    }
    foreach ($required as $perm) {
        if (in_array($perm, $rolePermissions, true)) {
            return true;
        }
    }
    return false;
};

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
            $allowedGroups = ['content', 'media', 'organization', 'marketing', 'system', 'modules', 'settings'];
            if (!in_array($group, $allowedGroups, true)) {
                $group = 'modules';
            }
            $href = trim((string) ($item['href'] ?? ''));
            if ($href === '') {
                $href = '#';
            } elseif ($href[0] !== '/') {
                $href = rtrim($adminBase, '/') . '/' . ltrim($href, '/');
            }
            $permissions = [];
            if (isset($item['permission'])) {
                $permissions = [trim((string) $item['permission'])];
            } elseif (isset($item['permissions']) && is_array($item['permissions'])) {
                $permissions = array_values(array_filter(array_map('trim', $item['permissions']), static fn (string $value): bool => $value !== ''));
            }

            $moduleNavEntries[] = [
                'group' => $group !== '' ? $group : 'modules',
                'key' => strtolower(trim((string) ($item['key'] ?? ($slug . '-' . md5($label))))),
                'label' => $label,
                'href' => $href,
                'permissions' => $permissions,
                'order' => (int) ($item['order'] ?? 100),
            ];
        }
    }
}

if ($moduleNavEntries !== []) {
    usort($moduleNavEntries, static fn (array $a, array $b): int => ($a['order'] <=> $b['order']) ?: strcmp((string) $a['label'], (string) $b['label']));

    foreach ($moduleNavEntries as $entry) {
        if (!$hasAnyPermission((array) ($entry['permissions'] ?? []))) {
            continue;
        }
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
            $meta = $groupMeta[$groupKey] ?? null;
            $navGroups[] = [
                'key' => $groupKey,
                'label' => $meta !== null ? (string) ($meta['label'] ?? ucfirst($groupKey)) : ucfirst($groupKey),
                'icon' => $meta !== null ? (string) ($meta['icon'] ?? 'chat') : 'chat',
                'order' => (int) ($meta['order'] ?? 35),
                'permissions' => [],
                'children' => [[
                    'key' => (string) $entry['key'],
                    'label' => (string) $entry['label'],
                    'href' => (string) $entry['href'],
                ]],
            ];
        }
    }
}

if (!class_exists('CoreSettingsEngine')) {
    require_once CATMIN_CORE . '/settings-engine.php';
}
$sidebarOrderRaw = '';
try {
    $settingsEngine = new CoreSettingsEngine();
    $sidebarOrderRaw = (string) $settingsEngine->get('ui.sidebar_order', '');
} catch (Throwable $exception) {
    $sidebarOrderRaw = '';
}
$sidebarOrder = array_values(array_filter(array_map('trim', explode(',', $sidebarOrderRaw)), static fn (string $value): bool => $value !== ''));

$filteredGroups = [];
foreach ($navGroups as $group) {
    $required = is_array($group['permissions'] ?? null) ? $group['permissions'] : [];
    $children = is_array($group['children'] ?? null) ? $group['children'] : [];
    if ($children !== []) {
        $children = array_values(array_filter($children, static function (array $child) use ($hasAnyPermission): bool {
            $requiredPerms = is_array($child['permissions'] ?? null) ? $child['permissions'] : [];
            return $hasAnyPermission($requiredPerms);
        }));
        $group['children'] = $children;
    }
    if ($children === [] && empty($group['href']) && !$hasAnyPermission($required)) {
        continue;
    }
    if ($children === [] && !empty($group['href']) && !$hasAnyPermission($required)) {
        continue;
    }
    $filteredGroups[] = $group;
}
$navGroups = $filteredGroups;

usort($navGroups, static fn (array $a, array $b): int => ((int) ($a['order'] ?? 999)) <=> ((int) ($b['order'] ?? 999)));

if ($sidebarOrder !== []) {
    $orderIndex = [];
    foreach ($sidebarOrder as $i => $key) {
        $orderIndex[(string) $key] = $i;
    }
    usort($navGroups, static function (array $a, array $b) use ($orderIndex): int {
        $aKey = (string) ($a['key'] ?? '');
        $bKey = (string) ($b['key'] ?? '');
        $aHas = array_key_exists($aKey, $orderIndex);
        $bHas = array_key_exists($bKey, $orderIndex);
        if ($aHas && $bHas) {
            return $orderIndex[$aKey] <=> $orderIndex[$bKey];
        }
        if ($aHas) {
            return -1;
        }
        if ($bHas) {
            return 1;
        }
        return ((int) ($a['order'] ?? 999)) <=> ((int) ($b['order'] ?? 999));
    });
}

$emptyLabel = __('nav.empty_group');
foreach ($navGroups as &$group) {
    $children = is_array($group['children'] ?? null) ? $group['children'] : [];
    if (!empty($group['href'])) {
        continue;
    }
    if ($children === []) {
        $group['children'][] = [
            'key' => $group['key'] . '-empty',
            'label' => $emptyLabel,
            'href' => '#',
        ];
    }
}
unset($group);

$sidebarIconSvg = static function (string $name): string {
    return match ($name) {
        'house-door', 'home' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V21h14V9.5"/></svg>',
        'calendar3', 'calendar' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="17" rx="2"/><path d="M8 2v4M16 2v4M3 10h18"/></svg>',
        'chat-left-text', 'chat' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M7 17 3 21V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H7Z"/></svg>',
        'file-earmark-text' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 3h9l3 3v15H6z"/><path d="M9 10h6M9 14h6M9 18h4"/></svg>',
        'images' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 15 4-4 4 4 5-6 5 6"/></svg>',
        'diagram-3' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="6" height="6" rx="1"/><rect x="15" y="3" width="6" height="6" rx="1"/><rect x="9" y="15" width="6" height="6" rx="1"/><path d="M6 9v3a3 3 0 0 0 3 3h3"/><path d="M18 9v3a3 3 0 0 1-3 3h-3"/></svg>',
        'megaphone' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 11v2a2 2 0 0 0 2 2h1l2 4h3l-1.5-4H14l6-4V7l-6-4H8L5 5H3a2 2 0 0 0-2 2v2"/><path d="M14 5v14"/></svg>',
        'puzzle' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M7 3h4a2 2 0 0 1 2 2v1h1a2 2 0 1 0 0-4h2a2 2 0 0 1 2 2v4h-1a2 2 0 1 0 4 0v2a2 2 0 0 1-2 2h-4v-1a2 2 0 1 0 0 4v1a2 2 0 0 1-2 2h-4v-1a2 2 0 1 0-4 0H5a2 2 0 0 1-2-2v-4h1a2 2 0 1 0 0-4H3V5a2 2 0 0 1 2-2h2z"/></svg>',
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
