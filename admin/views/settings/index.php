<?php

declare(strict_types=1);

use Core\security\CsrfManager;

$settings = isset($settings) && is_array($settings) ? $settings : [];
$apps = isset($apps) && is_array($apps) ? $apps : [];
$repositories = isset($repositories) && is_array($repositories) ? $repositories : [];
$policy = isset($policy) && is_array($policy) ? $policy : [];
$section = strtolower(trim((string) ($section ?? 'general')));
$message = trim((string) ($message ?? ''));
$messageType = trim((string) ($messageType ?? 'success'));

$sections = [
    'general' => __('settings.section.general'),
    'appearance' => __('settings.section.appearance'),
    'sidebar' => __('settings.section.sidebar'),
    'mail' => __('settings.section.mail'),
    'performance' => __('settings.section.performance'),
    'security' => __('settings.section.security'),
    'advanced' => __('settings.section.advanced'),
];

if (!array_key_exists($section, $sections)) {
    $section = 'general';
}

$pageTitle = __('settings.title') . ' · ' . ($sections[$section] ?? __('settings.section.general'));
$pageDescription = '';
$activeNav = 'settings';
$breadcrumbs = [
    ['label' => __('common.admin'), 'href' => $adminBase . '/'],
    ['label' => __('nav.settings'), 'href' => $adminBase . '/settings/general'],
    ['label' => $sections[$section] ?? __('settings.section.general')],
];
$csrf = htmlspecialchars((new CsrfManager())->token(), ENT_QUOTES, 'UTF-8');

$general = (array) ($settings['general'] ?? []);
$security = (array) ($settings['security'] ?? []);
$mail = (array) ($settings['mail'] ?? ($settings['email'] ?? []));
$ui = (array) ($settings['ui'] ?? []);
$maintenance = (array) ($settings['maintenance'] ?? []);
$backup = (array) ($settings['backup'] ?? []);
$system = (array) ($settings['system'] ?? []);
$sidebarGroups = isset($sidebarGroups) && is_array($sidebarGroups) ? $sidebarGroups : [];
$sidebarOrder = isset($sidebarOrder) && is_array($sidebarOrder) ? $sidebarOrder : [];
$timezones = \DateTimeZone::listIdentifiers();

ob_start();
?>
<div class="row g-4">
    <div class="col-12 col-lg-3">
        <div class="list-group cat-settings-nav">
            <?php foreach ($sections as $key => $label): ?>
                <a class="list-group-item list-group-item-action <?= $section === $key ? 'active' : '' ?>" href="<?= htmlspecialchars($adminBase . '/settings/' . $key, ENT_QUOTES, 'UTF-8') ?>">
                    <?= htmlspecialchars((string) $label, ENT_QUOTES, 'UTF-8') ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="col-12 col-lg-9">
        <?php if ($message !== ''): ?>
            <div class="alert alert-<?= htmlspecialchars($messageType, ENT_QUOTES, 'UTF-8') ?>">
                <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <?php if ($section === 'general'): ?>
            <form method="post" action="<?= htmlspecialchars($adminBase . '/settings/general', ENT_QUOTES, 'UTF-8') ?>" class="d-grid gap-3">
                <input type="hidden" name="_csrf" value="<?= $csrf ?>">

                <section class="card">
                    <div class="card-header bg-transparent border-0 pt-3">
                        <h3 class="h6 mb-0"><?= htmlspecialchars(__('settings.general.title'), ENT_QUOTES, 'UTF-8') ?></h3>
                    </div>
                    <div class="card-body pt-2">
                        <div class="row g-3">
                            <div class="col-12 col-lg-4">
                                <label class="form-label"><?= htmlspecialchars(__('settings.general.app_name'), ENT_QUOTES, 'UTF-8') ?></label>
                                <input class="form-control" type="text" name="app_name" value="<?= htmlspecialchars((string) ($general['app_name'] ?? 'CATMIN'), ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                            <div class="col-6 col-lg-4">
                                <label class="form-label"><?= htmlspecialchars(__('settings.general.environment'), ENT_QUOTES, 'UTF-8') ?></label>
                                <select class="form-select" name="app_env">
                                    <?php foreach (['production', 'staging', 'development'] as $env): ?>
                                        <option value="<?= $env ?>" <?= ((string) ($general['app_env'] ?? 'production') === $env) ? 'selected' : '' ?>><?= ucfirst($env) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-6 col-lg-4">
                                <label class="form-label"><?= htmlspecialchars(__('settings.general.timezone'), ENT_QUOTES, 'UTF-8') ?></label>
                                <?php $selectedTimezone = (string) ($general['timezone'] ?? 'UTC'); ?>
                                <select class="form-select" name="timezone">
                                    <?php foreach ($timezones as $tz): ?>
                                        <option value="<?= htmlspecialchars((string) $tz, ENT_QUOTES, 'UTF-8') ?>" <?= $selectedTimezone === (string) $tz ? 'selected' : '' ?>>
                                            <?= htmlspecialchars((string) $tz, ENT_QUOTES, 'UTF-8') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12 col-lg-4">
                                <label class="form-label"><?= htmlspecialchars(__('settings.general.admin_route'), ENT_QUOTES, 'UTF-8') ?></label>
                                <input class="form-control" type="text" name="admin_path" value="<?= htmlspecialchars((string) ($general['admin_path'] ?? 'admin'), ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                        </div>
                    </div>
                </section>

                <div class="d-flex gap-2">
                    <button class="btn btn-primary" type="submit"><?= htmlspecialchars(__('common.save'), ENT_QUOTES, 'UTF-8') ?></button>
                    <a class="btn btn-outline-secondary" href="<?= htmlspecialchars($adminBase . '/settings/general', ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(__('common.reload'), ENT_QUOTES, 'UTF-8') ?></a>
                </div>
            </form>
        <?php elseif ($section === 'appearance'): ?>
            <form method="post" action="<?= htmlspecialchars($adminBase . '/settings/appearance', ENT_QUOTES, 'UTF-8') ?>" class="d-grid gap-3">
                <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                <section class="card">
                    <div class="card-header bg-transparent border-0 pt-3">
                        <h3 class="h6 mb-0"><?= htmlspecialchars(__('settings.appearance.title'), ENT_QUOTES, 'UTF-8') ?></h3>
                    </div>
                    <div class="card-body pt-2">
                        <div class="row g-3">
                            <div class="col-12 col-lg-4">
                                <label class="form-label"><?= htmlspecialchars(__('settings.interface_maintenance.default_theme'), ENT_QUOTES, 'UTF-8') ?></label>
                                <?php
                                $themeLabels = [
                                    'light' => __('topbar.theme.light'),
                                    'dark' => __('topbar.theme.dark'),
                                    'corporate' => __('topbar.theme.corporate'),
                                ];
                                ?>
                                <select class="form-select" name="theme_default">
                                    <?php foreach (['light', 'dark', 'corporate'] as $theme): ?>
                                        <option value="<?= $theme ?>" <?= ((string) ($ui['theme_default'] ?? 'corporate') === $theme) ? 'selected' : '' ?>><?= htmlspecialchars((string) ($themeLabels[$theme] ?? ucfirst($theme)), ENT_QUOTES, 'UTF-8') ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-6 col-lg-4">
                                <label class="form-label"><?= htmlspecialchars(__('settings.interface_maintenance.table_density'), ENT_QUOTES, 'UTF-8') ?></label>
                                <select class="form-select" name="table_density">
                                    <?php foreach (['compact', 'comfortable', 'spacious'] as $density): ?>
                                        <option value="<?= $density ?>" <?= ((string) ($ui['table_density'] ?? 'comfortable') === $density) ? 'selected' : '' ?>><?= htmlspecialchars(__('settings.interface_maintenance.density.' . $density), ENT_QUOTES, 'UTF-8') ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-6 col-lg-4">
                                <label class="form-label d-block"><?= htmlspecialchars(__('settings.interface_maintenance.debug_mapping'), ENT_QUOTES, 'UTF-8') ?></label>
                                <label class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" name="show_debug" value="1" <?= ((bool) ($ui['show_debug'] ?? false)) ? 'checked' : '' ?>>
                                    <span class="form-check-label"><?= htmlspecialchars(__('settings.interface_maintenance.visible'), ENT_QUOTES, 'UTF-8') ?></span>
                                </label>
                            </div>
                        </div>
                    </div>
                </section>
                <div class="d-flex gap-2">
                    <button class="btn btn-primary" type="submit"><?= htmlspecialchars(__('common.save'), ENT_QUOTES, 'UTF-8') ?></button>
                    <a class="btn btn-outline-secondary" href="<?= htmlspecialchars($adminBase . '/settings/appearance', ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(__('common.reload'), ENT_QUOTES, 'UTF-8') ?></a>
                </div>
            </form>
        <?php elseif ($section === 'sidebar'): ?>
            <form method="post" action="<?= htmlspecialchars($adminBase . '/settings/sidebar', ENT_QUOTES, 'UTF-8') ?>" class="d-grid gap-3">
                <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                <section class="card">
                    <div class="card-header bg-transparent border-0 pt-3">
                        <h3 class="h6 mb-0"><?= htmlspecialchars(__('settings.sidebar.title'), ENT_QUOTES, 'UTF-8') ?></h3>
                    </div>
                    <div class="card-body pt-2">
                        <div class="row g-3">
                            <div class="col-12 col-lg-4">
                                <label class="form-label d-block"><?= htmlspecialchars(__('settings.interface_maintenance.compact_sidebar'), ENT_QUOTES, 'UTF-8') ?></label>
                                <label class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" name="compact_sidebar" value="1" data-cat-sidebar-setting="1" <?= ((bool) ($ui['compact_sidebar'] ?? true)) ? 'checked' : '' ?>>
                                    <span class="form-check-label"><?= htmlspecialchars(__('common.active'), ENT_QUOTES, 'UTF-8') ?></span>
                                </label>
                            </div>
                            <div class="col-12">
                                <?php
                                $orderIndex = [];
                                foreach ($sidebarOrder as $i => $key) {
                                    $orderIndex[(string) $key] = $i;
                                }
                                usort($sidebarGroups, static function (array $a, array $b) use ($orderIndex): int {
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
                                    return ((int) ($a['order'] ?? 99)) <=> ((int) ($b['order'] ?? 99));
                                });
                                $sidebarOrderValue = implode(',', array_map(static fn (array $group): string => (string) ($group['key'] ?? ''), $sidebarGroups));
                                ?>
                                <input type="hidden" name="sidebar_order" value="<?= htmlspecialchars($sidebarOrderValue, ENT_QUOTES, 'UTF-8') ?>" data-cat-sidebar-order-input>
                                <div class="alert alert-light mb-2">
                                    <?= htmlspecialchars(__('settings.sidebar.order_placeholder'), ENT_QUOTES, 'UTF-8') ?>
                                </div>
                                <div class="list-group cat-sidebar-order" data-cat-sidebar-order>
                                    <?php foreach ($sidebarGroups as $group): ?>
                                        <div class="list-group-item d-flex align-items-center gap-2 cat-sidebar-order-item" draggable="true" data-cat-sidebar-item data-key="<?= htmlspecialchars((string) ($group['key'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                            <span class="cat-sidebar-order-handle">⋮⋮</span>
                                            <span class="text-body-secondary text-uppercase small"><?= htmlspecialchars((string) ($group['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                            <?php if (!empty($group['source']) && (string) $group['source'] === 'module'): ?>
                                                <span class="badge text-bg-light ms-auto">module</span>
                                            <?php else: ?>
                                                <span class="badge text-bg-dark ms-auto">core</span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
                <div class="d-flex gap-2">
                    <button class="btn btn-primary" type="submit"><?= htmlspecialchars(__('common.save'), ENT_QUOTES, 'UTF-8') ?></button>
                    <a class="btn btn-outline-secondary" href="<?= htmlspecialchars($adminBase . '/settings/sidebar', ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(__('common.reload'), ENT_QUOTES, 'UTF-8') ?></a>
                </div>
            </form>
        <?php elseif ($section === 'mail'): ?>
            <form method="post" action="<?= htmlspecialchars($adminBase . '/settings/mail', ENT_QUOTES, 'UTF-8') ?>" class="d-grid gap-3">
                <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                <section class="card">
                    <div class="card-header bg-transparent border-0 pt-3">
                        <h3 class="h6 mb-0"><?= htmlspecialchars(__('settings.mail.title'), ENT_QUOTES, 'UTF-8') ?></h3>
                    </div>
                    <div class="card-body pt-2">
                        <div class="row g-3">
                            <div class="col-12 col-lg-2">
                                <label class="form-label d-block"><?= htmlspecialchars(__('settings.mail.activation'), ENT_QUOTES, 'UTF-8') ?></label>
                                <label class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" name="email_enabled" value="1" <?= ((bool) ($mail['enabled'] ?? false)) ? 'checked' : '' ?>>
                                    <span class="form-check-label"><?= htmlspecialchars(__('settings.mail.on'), ENT_QUOTES, 'UTF-8') ?></span>
                                </label>
                            </div>
                            <div class="col-6 col-lg-2">
                                <label class="form-label"><?= htmlspecialchars(__('settings.mail.driver'), ENT_QUOTES, 'UTF-8') ?></label>
                                <select class="form-select" name="email_driver">
                                    <?php foreach (['smtp', 'sendmail', 'mailgun'] as $driver): ?>
                                        <option value="<?= $driver ?>" <?= ((string) ($mail['driver'] ?? 'smtp') === $driver) ? 'selected' : '' ?>><?= strtoupper($driver) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-6 col-lg-3">
                                <label class="form-label"><?= htmlspecialchars(__('settings.mail.from_name'), ENT_QUOTES, 'UTF-8') ?></label>
                                <input class="form-control" type="text" name="email_from_name" value="<?= htmlspecialchars((string) ($mail['from_name'] ?? 'CATMIN'), ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                            <div class="col-12 col-lg-5">
                                <label class="form-label"><?= htmlspecialchars(__('settings.mail.from_email'), ENT_QUOTES, 'UTF-8') ?></label>
                                <input class="form-control" type="email" name="email_from_email" value="<?= htmlspecialchars((string) ($mail['from_email'] ?? 'noreply@example.com'), ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                            <div class="col-12 col-lg-5">
                                <label class="form-label"><?= htmlspecialchars(__('settings.mail.host'), ENT_QUOTES, 'UTF-8') ?></label>
                                <input class="form-control" type="text" name="email_host" value="<?= htmlspecialchars((string) ($mail['host'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                            <div class="col-4 col-lg-2">
                                <label class="form-label"><?= htmlspecialchars(__('settings.mail.port'), ENT_QUOTES, 'UTF-8') ?></label>
                                <input class="form-control" type="number" min="1" name="email_port" value="<?= (int) ($mail['port'] ?? 587) ?>">
                            </div>
                            <div class="col-4 col-lg-2">
                                <label class="form-label"><?= htmlspecialchars(__('settings.mail.crypto'), ENT_QUOTES, 'UTF-8') ?></label>
                                <select class="form-select" name="email_encryption">
                                    <?php foreach (['tls', 'ssl', 'none'] as $enc): ?>
                                        <option value="<?= $enc ?>" <?= ((string) ($mail['encryption'] ?? 'tls') === $enc) ? 'selected' : '' ?>><?= strtoupper($enc) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-4 col-lg-3">
                                <label class="form-label"><?= htmlspecialchars(__('settings.mail.username'), ENT_QUOTES, 'UTF-8') ?></label>
                                <input class="form-control" type="text" name="email_username" value="<?= htmlspecialchars((string) ($mail['username'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                        </div>
                    </div>
                </section>
                <div class="d-flex gap-2">
                    <button class="btn btn-primary" type="submit"><?= htmlspecialchars(__('common.save'), ENT_QUOTES, 'UTF-8') ?></button>
                    <a class="btn btn-outline-secondary" href="<?= htmlspecialchars($adminBase . '/settings/mail', ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(__('common.reload'), ENT_QUOTES, 'UTF-8') ?></a>
                </div>
            </form>
        <?php elseif ($section === 'performance'): ?>
            <form method="post" action="<?= htmlspecialchars($adminBase . '/settings/performance', ENT_QUOTES, 'UTF-8') ?>" class="d-grid gap-3">
                <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                <section class="card">
                    <div class="card-header bg-transparent border-0 pt-3">
                        <h3 class="h6 mb-0"><?= htmlspecialchars(__('settings.performance.title'), ENT_QUOTES, 'UTF-8') ?></h3>
                    </div>
                    <div class="card-body pt-2">
                        <div class="row g-3">
                            <div class="col-6 col-lg-3">
                                <label class="form-label d-block"><?= htmlspecialchars(__('settings.interface_maintenance.maintenance_mode'), ENT_QUOTES, 'UTF-8') ?></label>
                                <label class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" name="maintenance_enabled" value="1" <?= ((bool) ($maintenance['enabled'] ?? false)) ? 'checked' : '' ?>>
                                    <span class="form-check-label"><?= htmlspecialchars(__('common.active'), ENT_QUOTES, 'UTF-8') ?></span>
                                </label>
                            </div>
                            <div class="col-6 col-lg-3">
                                <label class="form-label d-block"><?= htmlspecialchars(__('settings.interface_maintenance.admin_bypass'), ENT_QUOTES, 'UTF-8') ?></label>
                                <label class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" name="maintenance_allow_admin" value="1" <?= ((bool) ($maintenance['allow_admin'] ?? true)) ? 'checked' : '' ?>>
                                    <span class="form-check-label"><?= htmlspecialchars(__('settings.interface_maintenance.allowed'), ENT_QUOTES, 'UTF-8') ?></span>
                                </label>
                            </div>
                            <div class="col-12 col-lg-6">
                                <label class="form-label"><?= htmlspecialchars(__('settings.interface_maintenance.maintenance_message'), ENT_QUOTES, 'UTF-8') ?></label>
                                <input class="form-control" type="text" name="maintenance_message" value="<?= htmlspecialchars((string) ($maintenance['message'] ?? __('maintenance.placeholder.message')), ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                            <div class="col-12 col-lg-4">
                                <label class="form-label d-block"><?= htmlspecialchars(__('settings.performance.backup_local'), ENT_QUOTES, 'UTF-8') ?></label>
                                <label class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" name="backup_local_enabled" value="1" <?= ((bool) ($backup['local_enabled'] ?? true)) ? 'checked' : '' ?>>
                                    <span class="form-check-label"><?= htmlspecialchars(__('common.active'), ENT_QUOTES, 'UTF-8') ?></span>
                                </label>
                            </div>
                            <div class="col-12 col-lg-4">
                                <label class="form-label d-block"><?= htmlspecialchars(__('settings.performance.cron_enabled'), ENT_QUOTES, 'UTF-8') ?></label>
                                <label class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" name="cron_enabled" value="1" <?= ((bool) ($system['cron_enabled'] ?? true)) ? 'checked' : '' ?>>
                                    <span class="form-check-label"><?= htmlspecialchars(__('common.active'), ENT_QUOTES, 'UTF-8') ?></span>
                                </label>
                            </div>
                        </div>
                    </div>
                </section>
                <div class="d-flex gap-2">
                    <button class="btn btn-primary" type="submit"><?= htmlspecialchars(__('common.save'), ENT_QUOTES, 'UTF-8') ?></button>
                    <a class="btn btn-outline-secondary" href="<?= htmlspecialchars($adminBase . '/settings/performance', ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(__('common.reload'), ENT_QUOTES, 'UTF-8') ?></a>
                </div>
            </form>
        <?php elseif ($section === 'security'): ?>
            <form method="post" action="<?= htmlspecialchars($adminBase . '/settings/security', ENT_QUOTES, 'UTF-8') ?>" class="d-grid gap-3">
                <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                <section class="card">
                    <div class="card-header bg-transparent border-0 pt-3">
                        <h3 class="h6 mb-0"><?= htmlspecialchars(__('settings.security.title'), ENT_QUOTES, 'UTF-8') ?></h3>
                    </div>
                    <div class="card-body pt-2">
                        <div class="row g-3">
                            <div class="col-6 col-lg-3">
                                <label class="form-label"><?= htmlspecialchars(__('settings.security.session_minutes'), ENT_QUOTES, 'UTF-8') ?></label>
                                <input class="form-control" type="number" min="15" name="session_minutes" value="<?= (int) ($security['session_minutes'] ?? 120) ?>">
                            </div>
                            <div class="col-6 col-lg-3">
                                <label class="form-label"><?= htmlspecialchars(__('settings.security.max_attempts'), ENT_QUOTES, 'UTF-8') ?></label>
                                <input class="form-control" type="number" min="3" name="max_attempts" value="<?= (int) ($security['max_attempts'] ?? 5) ?>">
                            </div>
                            <div class="col-6 col-lg-3">
                                <label class="form-label"><?= htmlspecialchars(__('settings.security.password_min'), ENT_QUOTES, 'UTF-8') ?></label>
                                <input class="form-control" type="number" min="8" name="password_min" value="<?= (int) ($security['password_min'] ?? 12) ?>">
                            </div>
                            <div class="col-6 col-lg-3">
                                <label class="form-label d-block"><?= htmlspecialchars(__('settings.security.two_factor'), ENT_QUOTES, 'UTF-8') ?></label>
                                <label class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" name="enforce_2fa" value="1" <?= ((bool) ($security['enforce_2fa'] ?? false)) ? 'checked' : '' ?>>
                                    <span class="form-check-label"><?= htmlspecialchars(__('settings.security.required'), ENT_QUOTES, 'UTF-8') ?></span>
                                </label>
                            </div>
                        </div>
                    </div>
                </section>
                <div class="d-flex gap-2">
                    <button class="btn btn-primary" type="submit"><?= htmlspecialchars(__('common.save'), ENT_QUOTES, 'UTF-8') ?></button>
                    <a class="btn btn-outline-secondary" href="<?= htmlspecialchars($adminBase . '/settings/security', ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(__('common.reload'), ENT_QUOTES, 'UTF-8') ?></a>
                </div>
            </form>
        <?php elseif ($section === 'advanced'): ?>
            <div class="d-grid gap-3">
                <section class="card">
                    <div class="card-header bg-transparent border-0 pt-3">
                        <h3 class="h6 mb-0"><?= htmlspecialchars(__('settings.apps.launcher_topbar'), ENT_QUOTES, 'UTF-8') ?></h3>
                    </div>
                    <div class="card-body pt-2">
                        <form method="post" action="<?= htmlspecialchars($adminBase . '/settings/apps', ENT_QUOTES, 'UTF-8') ?>" class="row g-3 mb-4">
                            <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                            <input type="hidden" name="action" value="create">
                            <div class="col-12 col-lg-3">
                                <label class="form-label"><?= htmlspecialchars(__('settings.apps.label'), ENT_QUOTES, 'UTF-8') ?></label>
                                <input class="form-control" type="text" name="label" placeholder="GPT">
                            </div>
                            <div class="col-12 col-lg-3">
                                <label class="form-label"><?= htmlspecialchars(__('settings.apps.url'), ENT_QUOTES, 'UTF-8') ?></label>
                                <input class="form-control" type="text" name="url" placeholder="https://chat.openai.com">
                            </div>
                            <div class="col-6 col-lg-2">
                                <label class="form-label"><?= htmlspecialchars(__('settings.apps.type'), ENT_QUOTES, 'UTF-8') ?></label>
                                <select class="form-select" name="type">
                                    <option value="external"><?= htmlspecialchars(__('settings.apps.type.external'), ENT_QUOTES, 'UTF-8') ?></option>
                                    <option value="internal"><?= htmlspecialchars(__('settings.apps.type.internal'), ENT_QUOTES, 'UTF-8') ?></option>
                                </select>
                            </div>
                            <div class="col-6 col-lg-2">
                                <label class="form-label"><?= htmlspecialchars(__('settings.apps.target'), ENT_QUOTES, 'UTF-8') ?></label>
                                <select class="form-select" name="target">
                                    <option value="_blank"><?= htmlspecialchars(__('settings.apps.target.new_tab'), ENT_QUOTES, 'UTF-8') ?></option>
                                    <option value="_self"><?= htmlspecialchars(__('settings.apps.target.same_tab'), ENT_QUOTES, 'UTF-8') ?></option>
                                </select>
                            </div>
                            <div class="col-6 col-lg-1">
                                <label class="form-label"><?= htmlspecialchars(__('settings.apps.order'), ENT_QUOTES, 'UTF-8') ?></label>
                                <input class="form-control" type="number" min="1" name="sort_order" value="100">
                            </div>
                            <div class="col-6 col-lg-1">
                                <label class="form-label"><?= htmlspecialchars(__('settings.apps.on'), ENT_QUOTES, 'UTF-8') ?></label>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" name="is_enabled" value="1" checked>
                                </div>
                            </div>
                            <div class="col-12 col-lg-4">
                                <label class="form-label"><?= htmlspecialchars(__('settings.apps.icon'), ENT_QUOTES, 'UTF-8') ?></label>
                                <input class="form-control" type="text" name="icon" placeholder="bi bi-robot ou /assets/icons/app.svg">
                            </div>
                            <div class="col-12 col-lg-2 align-self-end">
                                <button class="btn btn-primary w-100" type="submit"><?= htmlspecialchars(__('common.add'), ENT_QUOTES, 'UTF-8') ?></button>
                            </div>
                        </form>

                        <div class="table-responsive">
                            <table class="table table-sm align-middle">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th><?= htmlspecialchars(__('settings.apps.label'), ENT_QUOTES, 'UTF-8') ?></th>
                                        <th><?= htmlspecialchars(__('settings.apps.url'), ENT_QUOTES, 'UTF-8') ?></th>
                                        <th><?= htmlspecialchars(__('settings.apps.type'), ENT_QUOTES, 'UTF-8') ?></th>
                                        <th><?= htmlspecialchars(__('settings.apps.target'), ENT_QUOTES, 'UTF-8') ?></th>
                                        <th><?= htmlspecialchars(__('settings.apps.order'), ENT_QUOTES, 'UTF-8') ?></th>
                                        <th><?= htmlspecialchars(__('common.status'), ENT_QUOTES, 'UTF-8') ?></th>
                                        <th class="text-end"><?= htmlspecialchars(__('common.actions'), ENT_QUOTES, 'UTF-8') ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($apps === []): ?>
                                        <tr><td colspan="8" class="text-body-secondary"><?= htmlspecialchars(__('settings.apps.empty'), ENT_QUOTES, 'UTF-8') ?></td></tr>
                                    <?php else: ?>
                                        <?php foreach ($apps as $app): ?>
                                            <?php $appId = (int) ($app['id'] ?? 0); ?>
                                            <tr>
                                                <td><?= $appId ?></td>
                                                <td><?= htmlspecialchars((string) ($app['label'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                                <td><small><?= htmlspecialchars((string) ($app['url'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></small></td>
                                                <td><?= htmlspecialchars(strtoupper((string) ($app['type'] ?? 'external')), ENT_QUOTES, 'UTF-8') ?></td>
                                                <td><?= htmlspecialchars((string) ($app['target'] ?? '_blank'), ENT_QUOTES, 'UTF-8') ?></td>
                                                <td><?= (int) ($app['sort_order'] ?? 100) ?></td>
                                                <td><?= !empty($app['is_enabled']) ? htmlspecialchars(__('settings.apps.on'), ENT_QUOTES, 'UTF-8') : htmlspecialchars(__('settings.apps.off'), ENT_QUOTES, 'UTF-8') ?></td>
                                                <td class="text-end">
                                                    <div class="d-inline-flex gap-1">
                                                        <form method="post" action="<?= htmlspecialchars($adminBase . '/settings/apps', ENT_QUOTES, 'UTF-8') ?>">
                                                            <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                                                            <input type="hidden" name="action" value="toggle">
                                                            <input type="hidden" name="app_id" value="<?= $appId ?>">
                                                            <button class="btn btn-outline-secondary btn-sm" type="submit"><?= htmlspecialchars(__('settings.apps.toggle'), ENT_QUOTES, 'UTF-8') ?></button>
                                                        </form>
                                                        <form method="post" action="<?= htmlspecialchars($adminBase . '/settings/apps', ENT_QUOTES, 'UTF-8') ?>" data-cat-confirm="<?= htmlspecialchars(__('settings.apps.confirm_delete'), ENT_QUOTES, 'UTF-8') ?>">
                                                            <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                                                            <input type="hidden" name="action" value="delete">
                                                            <input type="hidden" name="app_id" value="<?= $appId ?>">
                                                            <button class="btn btn-outline-danger btn-sm" type="submit"><?= htmlspecialchars(__('common.delete'), ENT_QUOTES, 'UTF-8') ?></button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

                <section class="card">
                    <div class="card-header bg-transparent border-0 pt-3">
                        <h3 class="h6 mb-0"><?= htmlspecialchars(__('settings.module_repositories.title'), ENT_QUOTES, 'UTF-8') ?></h3>
                    </div>
                    <div class="card-body pt-2">
                        <form method="post" action="<?= htmlspecialchars($adminBase . '/settings/module-repositories', ENT_QUOTES, 'UTF-8') ?>" class="row g-3">
                            <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                            <input type="hidden" name="action" value="save_policy">
                            <?php
                            $trustColumns = [
                                'official' => [
                                    'title' => __('settings.module_repositories.column.official.title'),
                                    'description' => __('settings.module_repositories.column.official.description'),
                                    'keys' => ['allow_official', 'require_checksums_official', 'require_signature_official'],
                                ],
                                'trusted' => [
                                    'title' => __('settings.module_repositories.column.trusted.title'),
                                    'description' => __('settings.module_repositories.column.trusted.description'),
                                    'keys' => ['allow_trusted', 'require_checksums_trusted', 'require_signature_trusted'],
                                ],
                                'community' => [
                                    'title' => __('settings.module_repositories.column.community.title'),
                                    'description' => __('settings.module_repositories.column.community.description'),
                                    'keys' => ['allow_community', 'require_checksums_community', 'require_signature_community'],
                                ],
                            ];
                            ?>
                            <?php foreach ($trustColumns as $column): ?>
                                <div class="col-12 col-lg-4">
                                    <section class="border rounded-3 h-100 p-3">
                                        <h4 class="h6 mb-1"><?= htmlspecialchars((string) ($column['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></h4>
                                        <p class="text-body-secondary small mb-3"><?= htmlspecialchars((string) ($column['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                                        <div class="d-grid gap-2">
                                            <?php foreach ((array) ($column['keys'] ?? []) as $key): ?>
                                                <label class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" name="<?= htmlspecialchars((string) $key, ENT_QUOTES, 'UTF-8') ?>" value="1" <?= !empty($policy[$key]) ? 'checked' : '' ?>>
                                                    <span class="form-check-label"><?= htmlspecialchars(__('settings.module_repositories.policy.' . $key), ENT_QUOTES, 'UTF-8') ?></span>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                    </section>
                                </div>
                            <?php endforeach; ?>

                            <div class="col-12">
                                <hr class="my-1">
                            </div>

                            <?php
                            $globalPolicyOptions = [
                                'require_checksums_all',
                                'require_signature_all',
                                'hide_unverified_modules',
                                'show_community_by_default',
                                'allow_channel_stable',
                                'allow_channel_beta',
                                'allow_channel_alpha',
                                'allow_channel_experimental',
                                'allow_install_deprecated',
                                'allow_install_abandoned',
                                'hide_archived_modules',
                            ];
                            ?>
                            <div class="col-12">
                                <h4 class="h6 mb-2"><?= htmlspecialchars(__('settings.module_repositories.global.title'), ENT_QUOTES, 'UTF-8') ?></h4>
                                <p class="text-body-secondary small mb-3"><?= htmlspecialchars(__('settings.module_repositories.global.description'), ENT_QUOTES, 'UTF-8') ?></p>
                            </div>
                            <?php foreach ($globalPolicyOptions as $key): ?>
                                <div class="col-12 col-lg-4">
                                    <label class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="<?= htmlspecialchars((string) $key, ENT_QUOTES, 'UTF-8') ?>" value="1" <?= !empty($policy[$key]) ? 'checked' : '' ?>>
                                        <span class="form-check-label"><?= htmlspecialchars(__('settings.module_repositories.policy.' . $key), ENT_QUOTES, 'UTF-8') ?></span>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary"><?= htmlspecialchars(__('common.save'), ENT_QUOTES, 'UTF-8') ?></button>
                            </div>
                        </form>
                    </div>
                </section>
            </div>
        <?php else: ?>
            <section class="card">
                <div class="card-body">
                    <p class="text-body-secondary mb-0"><?= htmlspecialchars(__('settings.section.placeholder'), ENT_QUOTES, 'UTF-8') ?></p>
                </div>
            </section>
        <?php endif; ?>
    </div>
</div>
<?php
$content = (string) ob_get_clean();
require CATMIN_ADMIN . '/views/layouts/admin.php';
