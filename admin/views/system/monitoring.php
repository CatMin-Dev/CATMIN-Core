<?php

declare(strict_types=1);

$pageTitle = __('system.monitoring.title');
$pageDescription = '';
$activeNav = 'monitoring';
$breadcrumbs = [
    ['label' => __('common.admin'), 'href' => $adminBase . '/'],
    ['label' => __('nav.system')],
    ['label' => __('system.monitoring.title')],
];

$snapshot = is_array($snapshot ?? null) ? $snapshot : [];
$widgets = (array) ($snapshot['widgets'] ?? []);
$health = (array) ($snapshot['health'] ?? []);
$healthSummary = (array) ($health['summary'] ?? []);

ob_start();
?>
<section class="mb-3">
    <div class="row g-3">
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card h-100"><div class="card-body">
                <h3 class="h6 mb-1"><?= htmlspecialchars(__('system.monitoring.critical_errors'), ENT_QUOTES, 'UTF-8') ?></h3>
                <div class="display-6 mb-2"><?= (int) (($widgets['critical_errors']['count'] ?? 0)) ?></div>
                <p class="small text-body-secondary mb-0"><?php
                    $lastError = (string) (($widgets['critical_errors']['last'] ?? '-') ?: '-');
                    $errorDisplay = '-';
                    if ($lastError !== '-') {
                        $translationKey = 'event.' . $lastError;
                        $translated = __($translationKey);
                        $errorDisplay = $translated !== $translationKey ? $translated : $lastError;
                    } else {
                        $errorDisplay = __('system.monitoring.no_data');
                    }
                    echo htmlspecialchars((string) $errorDisplay, ENT_QUOTES, 'UTF-8');
                ?></p>
            </div></div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card h-100"><div class="card-body">
                <h3 class="h6 mb-1"><?= htmlspecialchars(__('system.monitoring.security_alerts'), ENT_QUOTES, 'UTF-8') ?></h3>
                <div class="display-6 mb-2"><?= (int) (($widgets['security_alerts']['count'] ?? 0)) ?></div>
                <p class="small text-body-secondary mb-0"><?php
                    $lastAlert = (string) (($widgets['security_alerts']['last'] ?? '-') ?: '-');
                    $alertDisplay = '-';
                    if ($lastAlert !== '-') {
                        $translationKey = 'event.' . $lastAlert;
                        $translated = __($translationKey);
                        $alertDisplay = $translated !== $translationKey ? $translated : $lastAlert;
                    } else {
                        $alertDisplay = __('system.monitoring.no_data');
                    }
                    echo htmlspecialchars((string) $alertDisplay, ENT_QUOTES, 'UTF-8');
                ?></p>
            </div></div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card h-100"><div class="card-body">
                <h3 class="h6 mb-1"><?= htmlspecialchars(__('system.monitoring.maintenance'), ENT_QUOTES, 'UTF-8') ?></h3>
                <div class="display-6 mb-2"><?= !empty($widgets['maintenance']['active']) ? htmlspecialchars(__('system.monitoring.maintenance_on'), ENT_QUOTES, 'UTF-8') : htmlspecialchars(__('system.monitoring.maintenance_off'), ENT_QUOTES, 'UTF-8') ?></div>
                <p class="small text-body-secondary mb-0"><?= htmlspecialchars((string) (($widgets['maintenance']['meta'] ?? __('system.monitoring.no_data')) ?: __('system.monitoring.no_data')), ENT_QUOTES, 'UTF-8') ?></p>
            </div></div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card h-100"><div class="card-body">
                <h3 class="h6 mb-1"><?= htmlspecialchars(__('system.monitoring.modules_to_check'), ENT_QUOTES, 'UTF-8') ?></h3>
                <div class="display-6 mb-2"><?= (int) (($widgets['module_issues']['count'] ?? 0)) ?></div>
                <p class="small text-body-secondary mb-0"><?= htmlspecialchars(__('system.monitoring.modules_hint'), ENT_QUOTES, 'UTF-8') ?></p>
            </div></div>
        </div>
    </div>
</section>

<section class="card">
    <div class="card-header bg-transparent border-0 pt-3 d-flex justify-content-between">
        <h3 class="h6 mb-0"><?= htmlspecialchars(__('system.monitoring.health_title'), ENT_QUOTES, 'UTF-8') ?></h3>
        <a class="btn btn-sm btn-outline-primary" href="<?= htmlspecialchars($adminBase . '/system/health', ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(__('system.monitoring.view_health_check'), ENT_QUOTES, 'UTF-8') ?></a>
    </div>
    <div class="card-body pt-2">
        <div class="row g-2">
            <div class="col-6 col-lg-3"><div class="border rounded p-2 small"><?= htmlspecialchars(__('common.healthy'), ENT_QUOTES, 'UTF-8') ?>: <strong><?= (int) ($healthSummary['healthy'] ?? 0) ?></strong></div></div>
            <div class="col-6 col-lg-3"><div class="border rounded p-2 small"><?= htmlspecialchars(__('common.warning'), ENT_QUOTES, 'UTF-8') ?>: <strong><?= (int) ($healthSummary['warning'] ?? 0) ?></strong></div></div>
            <div class="col-6 col-lg-3"><div class="border rounded p-2 small"><?= htmlspecialchars(__('common.critical'), ENT_QUOTES, 'UTF-8') ?>: <strong><?= (int) ($healthSummary['critical'] ?? 0) ?></strong></div></div>
            <div class="col-6 col-lg-3"><div class="border rounded p-2 small"><?= htmlspecialchars(__('common.unknown'), ENT_QUOTES, 'UTF-8') ?>: <strong><?= (int) ($healthSummary['unknown'] ?? 0) ?></strong></div></div>
        </div>
    </div>
</section>
<?php
$content = (string) ob_get_clean();
require CATMIN_ADMIN . '/views/layouts/admin.php';
