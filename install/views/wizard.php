<?php

declare(strict_types=1);

use Core\security\CsrfManager;

$contextData = is_object($context) && method_exists($context, 'data') ? $context->data($step) : [];
$contextData = is_array($contextData) ? $contextData : [];
$csrf = (new CsrfManager())->token();
$customModulesValue = '';
if (isset($contextData['custom_modules'])) {
    if (is_array($contextData['custom_modules'])) {
        $customModulesValue = implode(', ', array_map('strval', $contextData['custom_modules']));
    } else {
        $customModulesValue = (string) $contextData['custom_modules'];
    }
}
?><!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>CATMIN Installer - <?= htmlspecialchars((string) ($definition['title'] ?? $step), ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="/assets/vendor/bootstrap/5.3.8/css/bootstrap.min.css">
    <link rel="stylesheet" href="/odin-color.css">
</head>
<body class="container py-4">
    <h1 class="mb-3">CATMIN Installer</h1>
    <p class="mb-3">Step: <strong><?= htmlspecialchars((string) $step, ENT_QUOTES, 'UTF-8') ?></strong></p>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger" role="alert"><?= htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="mb-3 small text-muted">
        <?= htmlspecialchars(implode(' -> ', array_map('strval', $steps ?? [])), ENT_QUOTES, 'UTF-8') ?>
    </div>

    <form method="post" action="/install/step">
        <input type="hidden" name="_step" value="<?= htmlspecialchars((string) $step, ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">

        <?php if ($step === 'legal'): ?>
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" value="1" id="accept_legal" name="accept_legal">
                <label class="form-check-label" for="accept_legal">I accept legal documents.</label>
            </div>
        <?php elseif ($step === 'profile'): ?>
            <label class="form-label" for="profile">Install profile</label>
            <select class="form-select mb-3" id="profile" name="profile">
                <?php foreach (['core-only', 'recommended', 'full', 'custom'] as $profile): ?>
                    <option value="<?= htmlspecialchars($profile, ENT_QUOTES, 'UTF-8') ?>" <?= (($contextData['profile'] ?? 'recommended') === $profile) ? 'selected' : '' ?>><?= htmlspecialchars($profile, ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
            <label class="form-label" for="custom_modules">Custom modules (comma separated)</label>
            <input class="form-control mb-3" id="custom_modules" name="custom_modules" value="<?= htmlspecialchars($customModulesValue, ENT_QUOTES, 'UTF-8') ?>">
        <?php elseif ($step === 'database'): ?>
            <label class="form-label" for="driver">Driver</label>
            <select class="form-select mb-3" id="driver" name="driver">
                <?php foreach (['sqlite', 'mysql', 'mariadb', 'pgsql', 'sqlsrv'] as $driver): ?>
                    <option value="<?= htmlspecialchars($driver, ENT_QUOTES, 'UTF-8') ?>" <?= (($contextData['driver'] ?? 'sqlite') === $driver) ? 'selected' : '' ?>><?= htmlspecialchars($driver, ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
            <input class="form-control mb-2" name="sqlite_path" placeholder="SQLite path" value="<?= htmlspecialchars((string) ($contextData['sqlite_path'] ?? base_path('storage/database.sqlite')), ENT_QUOTES, 'UTF-8') ?>">
            <input class="form-control mb-2" name="host" placeholder="Host" value="<?= htmlspecialchars((string) ($contextData['host'] ?? '127.0.0.1'), ENT_QUOTES, 'UTF-8') ?>">
            <input class="form-control mb-2" name="port" placeholder="Port" value="<?= htmlspecialchars((string) ($contextData['port'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            <input class="form-control mb-2" name="database" placeholder="Database" value="<?= htmlspecialchars((string) ($contextData['database'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            <input class="form-control mb-2" name="username" placeholder="Username" value="<?= htmlspecialchars((string) ($contextData['username'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            <input class="form-control mb-3" type="password" name="password" placeholder="Password">
        <?php elseif ($step === 'identity'): ?>
            <input class="form-control mb-2" name="app_name" placeholder="App name" value="<?= htmlspecialchars((string) ($contextData['app_name'] ?? 'CATMIN'), ENT_QUOTES, 'UTF-8') ?>">
            <input class="form-control mb-2" name="app_url" placeholder="App URL" value="<?= htmlspecialchars((string) ($contextData['app_url'] ?? '/'), ENT_QUOTES, 'UTF-8') ?>">
            <input class="form-control mb-3" name="operator_name" placeholder="Operator" value="<?= htmlspecialchars((string) ($contextData['operator_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        <?php elseif ($step === 'superadmin'): ?>
            <input class="form-control mb-2" name="username" placeholder="Username" value="<?= htmlspecialchars((string) ($contextData['username'] ?? 'superadmin'), ENT_QUOTES, 'UTF-8') ?>">
            <input class="form-control mb-2" type="email" name="email" placeholder="Email" value="<?= htmlspecialchars((string) ($contextData['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            <input class="form-control mb-3" type="password" name="password" placeholder="Password">
        <?php elseif ($step === 'security'): ?>
            <input class="form-control mb-2" name="admin_path" placeholder="Admin path" value="<?= htmlspecialchars((string) ($contextData['admin_path'] ?? 'admin'), ENT_QUOTES, 'UTF-8') ?>">
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" value="1" id="ip_whitelist_enabled" name="ip_whitelist_enabled" <?= !empty($contextData['ip_whitelist_enabled']) ? 'checked' : '' ?>>
                <label class="form-check-label" for="ip_whitelist_enabled">Enable IP whitelist</label>
            </div>
        <?php elseif ($step === 'system'): ?>
            <input class="form-control mb-2" name="timezone" placeholder="Timezone" value="<?= htmlspecialchars((string) ($contextData['timezone'] ?? 'UTC'), ENT_QUOTES, 'UTF-8') ?>">
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" value="1" id="consent_tracking" name="consent_tracking" <?= !empty($contextData['consent_tracking']) ? 'checked' : '' ?>>
                <label class="form-check-label" for="consent_tracking">Consent to minimal tracking</label>
            </div>
        <?php elseif ($step === 'execution'): ?>
            <p>Ready to execute installation backend (DB test, migrations, seeders, superadmin, config write).</p>
        <?php elseif ($step === 'recovery_codes'): ?>
            <?php $codes = $contextData['codes'] ?? []; ?>
            <?php if (is_array($codes) && $codes !== []): ?>
                <div class="alert alert-warning">Save these recovery codes now:</div>
                <ul>
                    <?php foreach ($codes as $code): ?>
                        <li><code><?= htmlspecialchars((string) $code, ENT_QUOTES, 'UTF-8') ?></code></li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p>Recovery codes will be generated.</p>
            <?php endif; ?>
        <?php elseif ($step === 'report'): ?>
            <p>Installation report will be generated. Continue to lock.</p>
        <?php elseif ($step === 'lock'): ?>
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" value="1" id="confirm_lock" name="confirm_lock">
                <label class="form-check-label" for="confirm_lock">Confirm final lock and installer neutralization.</label>
            </div>
        <?php else: ?>
            <p>Step input not required.</p>
        <?php endif; ?>

        <button class="btn btn-primary" type="submit">Continue</button>
        <a class="btn btn-outline-secondary" href="/install/report">Report</a>
        <a class="btn btn-outline-secondary" href="<?= htmlspecialchars((string) $adminPath . '/login', ENT_QUOTES, 'UTF-8') ?>">Admin Login</a>
    </form>
</body>
</html>
