<?php

declare(strict_types=1);

if (!function_exists('install_icon')) {
    function install_icon(string $name, string $class = 'step-icon'): string
    {
        $paths = match ($name) {
            'report' => '<path d="M6 3h9l3 3v15H6z"/><path d="M9 10h6M9 14h6M9 18h4"/>',
            'lock' => '<rect x="6" y="10" width="12" height="9" rx="2"/><path d="M9 10V8a3 3 0 116 0v2"/>',
            'superadmin' => '<circle cx="12" cy="8" r="3"/><path d="M5 20c1.5-3 4-4 7-4s5.5 1 7 4"/>',
            default => '<circle cx="12" cy="12" r="8"/>',
        };

        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . '">' . $paths . '</svg>';
    }
}

$completed = is_object($context) && method_exists($context, 'completed') ? $context->completed() : [];
$completed = is_array($completed) ? $completed : [];
$metaReport = is_object($context) && method_exists($context, 'meta') ? $context->meta('report_path', '') : '';
?><!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>CATMIN Installer Report</title>
    <link rel="icon" type="image/png" href="/favicon.png">
    <link rel="stylesheet" href="/assets/vendor/bootstrap/5.3.8/css/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/css/odin-color.css?v=14">
    <link rel="stylesheet" href="/assets/css/install-pro.css">
    <script src="/assets/js/odin-color.js?v=1"></script>
</head>
<body class="install-pro">
<div class="container py-5 install-shell">
    <div class="install-card card mx-auto" style="max-width: 960px;">
        <div class="card-body p-4 p-lg-5">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
                <div class="d-flex align-items-center gap-3">
                    <img src="/assets/logo-color.png" alt="CATMIN" class="logo-mark">
                    <div>
                        <h1 class="h3 mb-1">Résumé de déploiement CATMIN</h1>
                        <p class="text-secondary mb-0">Vérifie les étapes finalisées avant le lock final.</p>
                    </div>
                </div>
                <span class="badge text-bg-success fs-6"><?= count($completed) ?> étapes</span>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-6"><div class="kv"><span class="key">Étapes complétées</span><span class="val"><?= count($completed) ?></span></div></div>
                <div class="col-md-6"><div class="kv"><span class="key">Admin Login</span><span class="val"><?= htmlspecialchars((string) $adminPath . '/login', ENT_QUOTES, 'UTF-8') ?></span></div></div>
            </div>

            <div class="install-card card mb-4">
                <div class="card-header"><strong>Étapes exécutées</strong></div>
                <div class="card-body">
                    <?php if ($completed === []): ?>
                        <p class="text-secondary mb-0">Aucune étape finalisée pour le moment.</p>
                    <?php else: ?>
                        <div class="d-flex flex-wrap gap-2">
                            <?php foreach ($completed as $item): ?>
                                <span class="badge rounded-pill text-bg-light border"><?= htmlspecialchars((string) $item, ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (is_string($metaReport) && $metaReport !== ''): ?>
                <div class="alert alert-info mb-4">Dernier rapport généré: <code><?= htmlspecialchars($metaReport, ENT_QUOTES, 'UTF-8') ?></code></div>
            <?php endif; ?>

            <div class="d-flex flex-wrap gap-2 install-actions">
                <a class="btn btn-catmin" href="/install/step/lock"><?= install_icon('lock', 'inline-icon') ?>Passer au Lock Final</a>
                <a class="btn btn-outline-secondary" href="/install/step/report"><?= install_icon('report', 'inline-icon') ?>Retour Wizard</a>
                <a class="btn btn-outline-dark" href="<?= htmlspecialchars((string) $adminPath . '/login', ENT_QUOTES, 'UTF-8') ?>"><?= install_icon('superadmin', 'inline-icon') ?>Login Admin</a>
            </div>
        </div>
    </div>
</div>
<script src="/assets/vendor/bootstrap/5.3.8/js/bootstrap.bundle.min.js"></script>
</body>
</html>
