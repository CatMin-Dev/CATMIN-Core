<?php

declare(strict_types=1);

use Core\security\CsrfManager;

$settings = isset($settings) && is_array($settings) ? $settings : [];
$section = (string) ($section ?? 'general');
$message = trim((string) ($message ?? ''));
$messageType = trim((string) ($messageType ?? 'success'));
$activeSettingsNav = (string) ($activeSettingsNav ?? 'general');
$sectionLabels = [
    'general' => __('settings.section.general'),
    'mail' => __('settings.section.mail'),
    'security' => __('settings.section.security'),
];
$sectionTitle = $sectionLabels[$section] ?? __('settings.section.general');

$pageTitle = __('settings.title') . ' · ' . $sectionTitle;
$pageDescription = '';
$activeNav = $activeSettingsNav;
$breadcrumbs = [
    ['label' => __('common.admin'), 'href' => $adminBase . '/'],
    ['label' => __('nav.settings'), 'href' => $adminBase . '/settings/general'],
    ['label' => $sectionTitle],
];
$csrf = htmlspecialchars((new CsrfManager())->token(), ENT_QUOTES, 'UTF-8');

$general = (array) ($settings['general'] ?? []);
$security = (array) ($settings['security'] ?? []);
$email = (array) ($settings['mail'] ?? ($settings['email'] ?? []));
$interface = (array) ($settings['interface'] ?? []);
$maintenance = (array) ($settings['maintenance'] ?? []);
$timezones = \DateTimeZone::listIdentifiers();

ob_start();
?>
<form method="post" action="<?= htmlspecialchars($adminBase . '/settings/' . $section, ENT_QUOTES, 'UTF-8') ?>" class="d-grid gap-3">
    <input type="hidden" name="_csrf" value="<?= $csrf ?>">

    <?php if ($section === 'security'): ?>
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
    <?php elseif ($section === 'mail'): ?>
        <section class="card">
            <div class="card-header bg-transparent border-0 pt-3">
                <h3 class="h6 mb-0"><?= htmlspecialchars(__('settings.mail.title'), ENT_QUOTES, 'UTF-8') ?></h3>
            </div>
            <div class="card-body pt-2">
                <div class="row g-3">
                    <div class="col-12 col-lg-2">
                        <label class="form-label d-block"><?= htmlspecialchars(__('settings.mail.activation'), ENT_QUOTES, 'UTF-8') ?></label>
                        <label class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" name="email_enabled" value="1" <?= ((bool) ($email['enabled'] ?? false)) ? 'checked' : '' ?>>
                            <span class="form-check-label"><?= htmlspecialchars(__('settings.mail.on'), ENT_QUOTES, 'UTF-8') ?></span>
                        </label>
                    </div>
                    <div class="col-6 col-lg-2">
                        <label class="form-label"><?= htmlspecialchars(__('settings.mail.driver'), ENT_QUOTES, 'UTF-8') ?></label>
                        <select class="form-select" name="email_driver">
                            <?php foreach (['smtp', 'sendmail', 'mailgun'] as $driver): ?>
                                <option value="<?= $driver ?>" <?= ((string) ($email['driver'] ?? 'smtp') === $driver) ? 'selected' : '' ?>><?= strtoupper($driver) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6 col-lg-3">
                        <label class="form-label"><?= htmlspecialchars(__('settings.mail.from_name'), ENT_QUOTES, 'UTF-8') ?></label>
                        <input class="form-control" type="text" name="email_from_name" value="<?= htmlspecialchars((string) ($email['from_name'] ?? 'CATMIN'), ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="col-12 col-lg-5">
                        <label class="form-label"><?= htmlspecialchars(__('settings.mail.from_email'), ENT_QUOTES, 'UTF-8') ?></label>
                        <input class="form-control" type="email" name="email_from_email" value="<?= htmlspecialchars((string) ($email['from_email'] ?? 'noreply@example.com'), ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="col-12 col-lg-5">
                        <label class="form-label"><?= htmlspecialchars(__('settings.mail.host'), ENT_QUOTES, 'UTF-8') ?></label>
                        <input class="form-control" type="text" name="email_host" value="<?= htmlspecialchars((string) ($email['host'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="col-4 col-lg-2">
                        <label class="form-label"><?= htmlspecialchars(__('settings.mail.port'), ENT_QUOTES, 'UTF-8') ?></label>
                        <input class="form-control" type="number" min="1" name="email_port" value="<?= (int) ($email['port'] ?? 587) ?>">
                    </div>
                    <div class="col-4 col-lg-2">
                        <label class="form-label"><?= htmlspecialchars(__('settings.mail.crypto'), ENT_QUOTES, 'UTF-8') ?></label>
                        <select class="form-select" name="email_encryption">
                            <?php foreach (['tls', 'ssl', 'none'] as $enc): ?>
                                <option value="<?= $enc ?>" <?= ((string) ($email['encryption'] ?? 'tls') === $enc) ? 'selected' : '' ?>><?= strtoupper($enc) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-4 col-lg-3">
                        <label class="form-label"><?= htmlspecialchars(__('settings.mail.username'), ENT_QUOTES, 'UTF-8') ?></label>
                        <input class="form-control" type="text" name="email_username" value="<?= htmlspecialchars((string) ($email['username'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                </div>
            </div>
        </section>
    <?php else: ?>
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

        <section class="card">
            <div class="card-header bg-transparent border-0 pt-3">
                <h3 class="h6 mb-0"><?= htmlspecialchars(__('settings.interface_maintenance.title'), ENT_QUOTES, 'UTF-8') ?></h3>
            </div>
            <div class="card-body pt-2">
                <div class="row g-3">
                    <div class="col-6 col-lg-3">
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
                                <option value="<?= $theme ?>" <?= ((string) ($interface['theme_default'] ?? 'corporate') === $theme) ? 'selected' : '' ?>><?= htmlspecialchars((string) ($themeLabels[$theme] ?? ucfirst($theme)), ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6 col-lg-3">
                        <label class="form-label"><?= htmlspecialchars(__('settings.interface_maintenance.table_density'), ENT_QUOTES, 'UTF-8') ?></label>
                        <select class="form-select" name="table_density">
                            <?php foreach (['compact', 'comfortable', 'spacious'] as $density): ?>
                                <option value="<?= $density ?>" <?= ((string) ($interface['table_density'] ?? 'comfortable') === $density) ? 'selected' : '' ?>><?= htmlspecialchars(__('settings.interface_maintenance.density.' . $density), ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6 col-lg-3">
                        <label class="form-label d-block"><?= htmlspecialchars(__('settings.interface_maintenance.compact_sidebar'), ENT_QUOTES, 'UTF-8') ?></label>
                        <label class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" name="compact_sidebar" value="1" <?= ((bool) ($interface['compact_sidebar'] ?? true)) ? 'checked' : '' ?>>
                            <span class="form-check-label"><?= htmlspecialchars(__('common.active'), ENT_QUOTES, 'UTF-8') ?></span>
                        </label>
                    </div>
                    <div class="col-6 col-lg-3">
                        <label class="form-label d-block"><?= htmlspecialchars(__('settings.interface_maintenance.debug_mapping'), ENT_QUOTES, 'UTF-8') ?></label>
                        <label class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" name="show_debug" value="1" <?= ((bool) ($interface['show_debug'] ?? false)) ? 'checked' : '' ?>>
                            <span class="form-check-label"><?= htmlspecialchars(__('settings.interface_maintenance.visible'), ENT_QUOTES, 'UTF-8') ?></span>
                        </label>
                    </div>
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
                </div>
            </div>
        </section>
    <?php endif; ?>

    <div class="d-flex gap-2">
        <button class="btn btn-primary" type="submit"><?= htmlspecialchars(__('common.save'), ENT_QUOTES, 'UTF-8') ?></button>
        <a class="btn btn-outline-secondary" href="<?= htmlspecialchars($adminBase . '/settings/' . $section, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(__('common.reload'), ENT_QUOTES, 'UTF-8') ?></a>
    </div>
</form>
<?php
$content = (string) ob_get_clean();
require CATMIN_ADMIN . '/views/layouts/admin.php';
