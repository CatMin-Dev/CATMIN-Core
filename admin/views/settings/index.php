<?php

declare(strict_types=1);

use Core\security\CsrfManager;

$settings = isset($settings) && is_array($settings) ? $settings : [];
$section = (string) ($section ?? 'general');
$message = trim((string) ($message ?? ''));
$messageType = trim((string) ($messageType ?? 'success'));
$activeSettingsNav = (string) ($activeSettingsNav ?? 'general');
$sectionLabels = [
    'general' => 'Général',
    'mail' => 'Mail',
    'security' => 'Sécurité',
];
$sectionTitle = $sectionLabels[$section] ?? 'Général';

$pageTitle = 'Paramètres · ' . $sectionTitle;
$pageDescription = '';
$activeNav = $activeSettingsNav;
$breadcrumbs = [
    ['label' => 'Admin', 'href' => $adminBase . '/'],
    ['label' => 'Paramètres', 'href' => $adminBase . '/settings/general'],
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
                <h3 class="h6 mb-0">Sécurité</h3>
            </div>
            <div class="card-body pt-2">
                <div class="row g-3">
                    <div class="col-6 col-lg-3">
                        <label class="form-label">Session (min)</label>
                        <input class="form-control" type="number" min="15" name="session_minutes" value="<?= (int) ($security['session_minutes'] ?? 120) ?>">
                    </div>
                    <div class="col-6 col-lg-3">
                        <label class="form-label">Max tentatives</label>
                        <input class="form-control" type="number" min="3" name="max_attempts" value="<?= (int) ($security['max_attempts'] ?? 5) ?>">
                    </div>
                    <div class="col-6 col-lg-3">
                        <label class="form-label">Password min</label>
                        <input class="form-control" type="number" min="8" name="password_min" value="<?= (int) ($security['password_min'] ?? 12) ?>">
                    </div>
                    <div class="col-6 col-lg-3">
                        <label class="form-label d-block">2FA</label>
                        <label class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" name="enforce_2fa" value="1" <?= ((bool) ($security['enforce_2fa'] ?? false)) ? 'checked' : '' ?>>
                            <span class="form-check-label">Obligatoire</span>
                        </label>
                    </div>
                </div>
            </div>
        </section>
    <?php elseif ($section === 'mail'): ?>
        <section class="card">
            <div class="card-header bg-transparent border-0 pt-3">
                <h3 class="h6 mb-0">Email</h3>
            </div>
            <div class="card-body pt-2">
                <div class="row g-3">
                    <div class="col-12 col-lg-2">
                        <label class="form-label d-block">Activation</label>
                        <label class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" name="email_enabled" value="1" <?= ((bool) ($email['enabled'] ?? false)) ? 'checked' : '' ?>>
                            <span class="form-check-label">ON</span>
                        </label>
                    </div>
                    <div class="col-6 col-lg-2">
                        <label class="form-label">Driver</label>
                        <select class="form-select" name="email_driver">
                            <?php foreach (['smtp', 'sendmail', 'mailgun'] as $driver): ?>
                                <option value="<?= $driver ?>" <?= ((string) ($email['driver'] ?? 'smtp') === $driver) ? 'selected' : '' ?>><?= strtoupper($driver) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6 col-lg-3">
                        <label class="form-label">From name</label>
                        <input class="form-control" type="text" name="email_from_name" value="<?= htmlspecialchars((string) ($email['from_name'] ?? 'CATMIN'), ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="col-12 col-lg-5">
                        <label class="form-label">From email</label>
                        <input class="form-control" type="email" name="email_from_email" value="<?= htmlspecialchars((string) ($email['from_email'] ?? 'noreply@example.com'), ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="col-12 col-lg-5">
                        <label class="form-label">Host</label>
                        <input class="form-control" type="text" name="email_host" value="<?= htmlspecialchars((string) ($email['host'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="col-4 col-lg-2">
                        <label class="form-label">Port</label>
                        <input class="form-control" type="number" min="1" name="email_port" value="<?= (int) ($email['port'] ?? 587) ?>">
                    </div>
                    <div class="col-4 col-lg-2">
                        <label class="form-label">Crypto</label>
                        <select class="form-select" name="email_encryption">
                            <?php foreach (['tls', 'ssl', 'none'] as $enc): ?>
                                <option value="<?= $enc ?>" <?= ((string) ($email['encryption'] ?? 'tls') === $enc) ? 'selected' : '' ?>><?= strtoupper($enc) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-4 col-lg-3">
                        <label class="form-label">Username</label>
                        <input class="form-control" type="text" name="email_username" value="<?= htmlspecialchars((string) ($email['username'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                </div>
            </div>
        </section>
    <?php else: ?>
        <section class="card">
            <div class="card-header bg-transparent border-0 pt-3">
                <h3 class="h6 mb-0">Paramètres généraux</h3>
            </div>
            <div class="card-body pt-2">
                <div class="row g-3">
                    <div class="col-12 col-lg-4">
                        <label class="form-label">App name</label>
                        <input class="form-control" type="text" name="app_name" value="<?= htmlspecialchars((string) ($general['app_name'] ?? 'CATMIN'), ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="col-6 col-lg-4">
                        <label class="form-label">Environnement</label>
                        <select class="form-select" name="app_env">
                            <?php foreach (['production', 'staging', 'development'] as $env): ?>
                                <option value="<?= $env ?>" <?= ((string) ($general['app_env'] ?? 'production') === $env) ? 'selected' : '' ?>><?= ucfirst($env) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6 col-lg-4">
                        <label class="form-label">Timezone</label>
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
                        <label class="form-label">Route admin</label>
                        <input class="form-control" type="text" name="admin_path" value="<?= htmlspecialchars((string) ($general['admin_path'] ?? 'admin'), ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                </div>
            </div>
        </section>

        <section class="card">
            <div class="card-header bg-transparent border-0 pt-3">
                <h3 class="h6 mb-0">Interface + Maintenance</h3>
            </div>
            <div class="card-body pt-2">
                <div class="row g-3">
                    <div class="col-6 col-lg-3">
                        <label class="form-label">Thème défaut</label>
                        <select class="form-select" name="theme_default">
                            <?php foreach (['light', 'dark', 'corporate'] as $theme): ?>
                                <option value="<?= $theme ?>" <?= ((string) ($interface['theme_default'] ?? 'corporate') === $theme) ? 'selected' : '' ?>><?= ucfirst($theme) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6 col-lg-3">
                        <label class="form-label">Densité table</label>
                        <select class="form-select" name="table_density">
                            <?php foreach (['compact', 'comfortable', 'spacious'] as $density): ?>
                                <option value="<?= $density ?>" <?= ((string) ($interface['table_density'] ?? 'comfortable') === $density) ? 'selected' : '' ?>><?= ucfirst($density) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6 col-lg-3">
                        <label class="form-label d-block">Sidebar compacte</label>
                        <label class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" name="compact_sidebar" value="1" <?= ((bool) ($interface['compact_sidebar'] ?? true)) ? 'checked' : '' ?>>
                            <span class="form-check-label">Active</span>
                        </label>
                    </div>
                    <div class="col-6 col-lg-3">
                        <label class="form-label d-block">Debug mapping</label>
                        <label class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" name="show_debug" value="1" <?= ((bool) ($interface['show_debug'] ?? false)) ? 'checked' : '' ?>>
                            <span class="form-check-label">Visible</span>
                        </label>
                    </div>
                    <div class="col-6 col-lg-3">
                        <label class="form-label d-block">Mode maintenance</label>
                        <label class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" name="maintenance_enabled" value="1" <?= ((bool) ($maintenance['enabled'] ?? false)) ? 'checked' : '' ?>>
                            <span class="form-check-label">Active</span>
                        </label>
                    </div>
                    <div class="col-6 col-lg-3">
                        <label class="form-label d-block">Admin bypass</label>
                        <label class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" name="maintenance_allow_admin" value="1" <?= ((bool) ($maintenance['allow_admin'] ?? true)) ? 'checked' : '' ?>>
                            <span class="form-check-label">Autorise</span>
                        </label>
                    </div>
                    <div class="col-12 col-lg-6">
                        <label class="form-label">Message maintenance</label>
                        <input class="form-control" type="text" name="maintenance_message" value="<?= htmlspecialchars((string) ($maintenance['message'] ?? 'Maintenance en cours'), ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <div class="d-flex gap-2">
        <button class="btn btn-primary" type="submit">Enregistrer</button>
        <a class="btn btn-outline-secondary" href="<?= htmlspecialchars($adminBase . '/settings/' . $section, ENT_QUOTES, 'UTF-8') ?>">Recharger</a>
    </div>
</form>
<?php
$content = (string) ob_get_clean();
require CATMIN_ADMIN . '/views/layouts/admin.php';
