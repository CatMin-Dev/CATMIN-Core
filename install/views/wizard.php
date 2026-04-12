<?php

declare(strict_types=1);

use Core\security\CsrfManager;
use Install\InstallerPrecheck;

if (!function_exists('install_icon')) {
    function install_icon(string $name, string $class = 'step-icon'): string
    {
        $paths = match ($name) {
            'precheck' => '<path d="M12 3l7 4v5c0 5-3.5 8-7 9-3.5-1-7-4-7-9V7l7-4z"/><path d="M9 12l2 2 4-4"/>',
            'legal' => '<path d="M6 3h9l3 3v15H6z"/><path d="M9 13h6M9 9h3M9 17h6"/>',
            'profile' => '<path d="M4 7h16M4 12h16M4 17h16"/><circle cx="8" cy="7" r="1.5"/><circle cx="14" cy="12" r="1.5"/><circle cx="10" cy="17" r="1.5"/>',
            'database' => '<ellipse cx="12" cy="6" rx="7" ry="3"/><path d="M5 6v8c0 1.7 3.1 3 7 3s7-1.3 7-3V6"/>',
            'identity' => '<path d="M4 19V7h16v12"/><path d="M9 19V12h6v7"/>',
            'superadmin' => '<circle cx="12" cy="8" r="3"/><path d="M5 20c1.5-3 4-4 7-4s5.5 1 7 4"/>',
            'security' => '<rect x="6" y="10" width="12" height="9" rx="2"/><path d="M9 10V8a3 3 0 116 0v2"/>',
            'system' => '<rect x="5" y="5" width="14" height="14" rx="2"/><path d="M9 9h6v6H9z"/>',
            'execution' => '<path d="M5 12h8"/><path d="M12 5l7 7-7 7"/>',
            'recovery_codes' => '<path d="M8 13a4 4 0 115.7 3.6L12 19l-1.7-2.4A4 4 0 018 13z"/>',
            'report' => '<path d="M6 3h9l3 3v15H6z"/><path d="M9 10h6M9 14h6M9 18h4"/>',
            'lock' => '<rect x="6" y="10" width="12" height="9" rx="2"/><path d="M9 10V8a3 3 0 116 0v2"/><circle cx="12" cy="14" r="1"/>',
            default => '<circle cx="12" cy="12" r="8"/>',
        };

        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . '">' . $paths . '</svg>';
    }
}

if (!function_exists('install_markdown_to_html')) {
    function install_markdown_to_html(string $markdown): string
    {
        $escaped = htmlspecialchars($markdown, ENT_QUOTES, 'UTF-8');
        $lines = preg_split('/\R/', $escaped) ?: [];
        $html = [];
        $inList = false;

        foreach ($lines as $line) {
            $trim = trim($line);
            if ($trim === '') {
                if ($inList) {
                    $html[] = '</ul>';
                    $inList = false;
                }
                continue;
            }

            if (str_starts_with($trim, '## ')) {
                if ($inList) {
                    $html[] = '</ul>';
                    $inList = false;
                }
                $html[] = '<h6 class="md-h2 mb-2 mt-3">' . substr($trim, 3) . '</h6>';
                continue;
            }

            if (str_starts_with($trim, '# ')) {
                if ($inList) {
                    $html[] = '</ul>';
                    $inList = false;
                }
                $html[] = '<h5 class="md-h1 mb-2">' . substr($trim, 2) . '</h5>';
                continue;
            }

            if (str_starts_with($trim, '- ')) {
                if (!$inList) {
                    $html[] = '<ul class="md-list mb-2">';
                    $inList = true;
                }
                $html[] = '<li>' . substr($trim, 2) . '</li>';
                continue;
            }

            if ($inList) {
                $html[] = '</ul>';
                $inList = false;
            }
            $html[] = '<p class="md-p mb-2">' . $trim . '</p>';
        }

        if ($inList) {
            $html[] = '</ul>';
        }

        return implode("\n", $html);
    }
}

if (!function_exists('install_git_head_meta')) {
    function install_git_head_meta(string $root): array
    {
        $gitDir = rtrim($root, '/') . '/.git';
        $headFile = $gitDir . '/HEAD';
        if (!is_file($headFile)) {
            return ['branch' => '-', 'commit' => '-'];
        }

        $headRaw = trim((string) file_get_contents($headFile));
        if ($headRaw === '') {
            return ['branch' => '-', 'commit' => '-'];
        }

        $branch = 'detached';
        $commit = '';
        if (str_starts_with($headRaw, 'ref: ')) {
            $ref = trim(substr($headRaw, 5));
            $branch = basename($ref);
            $refFile = $gitDir . '/' . $ref;
            if (is_file($refFile)) {
                $commit = trim((string) file_get_contents($refFile));
            } elseif (is_file($gitDir . '/packed-refs')) {
                $lines = (array) file($gitDir . '/packed-refs', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                foreach ($lines as $line) {
                    $line = trim((string) $line);
                    if ($line === '' || str_starts_with($line, '#') || str_starts_with($line, '^')) {
                        continue;
                    }
                    $parts = preg_split('/\s+/', $line);
                    if (is_array($parts) && count($parts) >= 2 && $parts[1] === $ref) {
                        $commit = (string) $parts[0];
                        break;
                    }
                }
            }
        } else {
            $commit = $headRaw;
        }

        return [
            'branch' => $branch !== '' ? $branch : '-',
            'commit' => $commit !== '' ? substr($commit, 0, 12) : '-',
        ];
    }
}

$contextData = is_object($context) && method_exists($context, 'data') ? $context->data($step) : [];
$contextData = is_array($contextData) ? $contextData : [];
$installBackup = is_object($context) && method_exists($context, 'meta') ? $context->meta('install_backup', []) : [];
$installBackup = is_array($installBackup) ? $installBackup : [];
$completedSteps = is_object($context) && method_exists($context, 'completed') ? $context->completed() : [];
$completedSteps = is_array($completedSteps) ? $completedSteps : [];
$csrf = (new CsrfManager())->token();
$customModulesValue = '';
if (isset($contextData['custom_modules'])) {
    $customModulesValue = is_array($contextData['custom_modules'])
        ? implode(', ', array_map('strval', $contextData['custom_modules']))
        : (string) $contextData['custom_modules'];
}

$stepTitles = [];
foreach (($steps ?? []) as $knownStep) {
    $file = CATMIN_INSTALL . '/steps/' . $knownStep . '.php';
    if (is_file($file)) {
        $def = require $file;
        $stepTitles[(string) $knownStep] = (string) ($def['title'] ?? $knownStep);
    }
}

$versionPayload = [];
if (is_file(CATMIN_ROOT . '/version.json')) {
    $decoded = json_decode((string) file_get_contents(CATMIN_ROOT . '/version.json'), true);
    if (is_array($decoded)) {
        $versionPayload = $decoded;
    }
}
$gitMeta = install_git_head_meta(CATMIN_ROOT);
$installerVersion = (string) ($versionPayload['version'] ?? 'unknown');
$installerDbSchema = (string) ($versionPayload['db_schema'] ?? '-');
$buildMeta = is_array($versionPayload['build'] ?? null) ? $versionPayload['build'] : [];
$publicCommit = (string) ($buildMeta['public_commit'] ?? '-');
$embeddedCommit = (string) ($buildMeta['commit'] ?? '-');
$activeCommit = (string) (($gitMeta['commit'] ?? '-') !== '-' ? $gitMeta['commit'] : ($publicCommit !== '-' ? $publicCommit : $embeddedCommit));
$activeBranch = (string) ($gitMeta['branch'] ?? '-');
$commitScope = ($gitMeta['commit'] ?? '-') !== '-' ? 'DEV' : 'PUBLIC';
$githubPublic = (string) ($versionPayload['links']['github_public'] ?? 'https://github.com/CatMin-Dev/CATMIN-Core');
$githubDev = (string) ($versionPayload['links']['github_dev'] ?? 'https://github.com/CatMin-Dev/core');

$precheckLive = null;
$precheckSummary = ['required_failed' => 0, 'failed' => 0, 'passed' => 0, 'total' => 0];
$precheckCategoryModals = [];
$legalDocs = [];
$templateModuleOptions = [];
$timezoneOptions = [];
if ($step === 'precheck') {
    $precheckLive = (new InstallerPrecheck())->run();
    $precheckSummary = is_array($precheckLive['summary'] ?? null) ? $precheckLive['summary'] : $precheckSummary;
    if (!isset($contextData['checks']) || !is_array($contextData['checks'])) {
        $contextData = $precheckLive;
    }
} elseif ($step === 'legal') {
    $files = glob(CATMIN_INSTALL . '/legal/*.md');
    $files = is_array($files) ? $files : [];
    sort($files);
    foreach ($files as $file) {
        $raw = is_file($file) ? (string) file_get_contents($file) : '';
        $title = basename($file, '.md');
        if (preg_match('/^#\s+(.+)$/m', $raw, $m) === 1) {
            $title = trim($m[1]);
        }

        $legalDocs[] = [
            'id' => preg_replace('/[^a-z0-9_]/i', '_', basename($file)),
            'title' => $title,
            'html' => install_markdown_to_html($raw),
        ];
    }
} elseif ($step === 'profile') {
    $detected = [];
    $moduleDirs = glob(CATMIN_MODULES . '/*', GLOB_ONLYDIR);
    if (is_array($moduleDirs)) {
        foreach ($moduleDirs as $dir) {
            $name = strtolower(trim(basename($dir)));
            if ($name !== '' && $name !== 'core') {
                $detected[] = $name;
            }
        }
    }

    $templateModuleOptions = array_values(array_unique(array_filter($detected)));
} elseif ($step === 'system') {
    $timezoneOptions = \DateTimeZone::listIdentifiers();
}
?><!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>CATMIN Installer - <?= htmlspecialchars((string) ($stepTitles[$step] ?? $step), ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="icon" type="image/png" href="/favicon.png">
    <link rel="stylesheet" href="/assets/vendor/bootstrap/5.3.8/css/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/css/odin-color.css?v=14">
    <link rel="stylesheet" href="/assets/css/install-pro.css">
    <script src="/assets/js/odin-color.js?v=1"></script>
</head>
<body class="100vh d-flex justify-content-center align-items-center">
<div class="container-fluid py-4 py-lg-5 install-shell">
    <div class="install-frame">
    <header class="install-header w-100 p-3">
        <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap">
            <div class="d-flex align-items-center gap-3">
                <img src="/assets/logo-color.png" alt="CATMIN" class="logo-mark">
                <div>
                    <h1 class="install-title h3 mb-1">CATMIN Installer</h1>
                    <p class="text-secondary mb-0">Bienvenue sur l'installateur CATMIN.</p>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge text-bg-dark"><?= htmlspecialchars((string) ($stepTitles[$step] ?? $step), ENT_QUOTES, 'UTF-8') ?></span>
            </div>
        </div>
        <div class="install-meta mt-2">
            <span>Version <?= htmlspecialchars($installerVersion, ENT_QUOTES, 'UTF-8') ?> · DB <?= htmlspecialchars($installerDbSchema, ENT_QUOTES, 'UTF-8') ?></span>
            <span>Commit <?= htmlspecialchars($commitScope, ENT_QUOTES, 'UTF-8') ?>: <code><?= htmlspecialchars($activeCommit, ENT_QUOTES, 'UTF-8') ?></code><?= $activeBranch !== '-' ? ' · ' . htmlspecialchars($activeBranch, ENT_QUOTES, 'UTF-8') : '' ?></span>
        </div>
    </header>

    <section class="install-card p-3">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <strong>Étapes d'installation</strong>
            <span class="small text-secondary"><?= count($completedSteps) ?>/<?= count($steps ?? []) ?></span>
        </div>
        <div>
            <div class="progress-scroll">
                <ol class="install-progress" aria-label="Progression de l'installation">
                    <?php foreach (($steps ?? []) as $knownStep): ?>
                        <?php
                        $isCurrent = $knownStep === $step;
                        $isDone = in_array((string) $knownStep, $completedSteps, true);
                        $classes = 'progress-step';
                        if ($isCurrent) {
                            $classes .= ' current';
                        } elseif ($isDone) {
                            $classes .= ' done';
                        }
                        ?>
                        <li class="<?= htmlspecialchars($classes, ENT_QUOTES, 'UTF-8') ?>">
                            <?php if ($isDone || $isCurrent): ?>
                                <a class="text-decoration-none text-reset d-inline-flex flex-column align-items-center" href="/install/step/<?= htmlspecialchars((string) $knownStep, ENT_QUOTES, 'UTF-8') ?>">
                                    <span class="progress-node"><?= install_icon((string) $knownStep) ?></span>
                                    <span class="progress-label"><?= htmlspecialchars((string) ($stepTitles[$knownStep] ?? $knownStep), ENT_QUOTES, 'UTF-8') ?></span>
                                </a>
                            <?php else: ?>
                                <span class="progress-node"><?= install_icon((string) $knownStep) ?></span>
                                <span class="progress-label"><?= htmlspecialchars((string) ($stepTitles[$knownStep] ?? $knownStep), ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ol>
            </div>
        </div>
    </section>

    <main class="install-card p-3">
        <div class="d-flex align-items-center gap-2 mb-2">
            <?= install_icon((string) $step, 'step-icon') ?>
            <strong><?= htmlspecialchars((string) ($stepTitles[$step] ?? $step), ENT_QUOTES, 'UTF-8') ?></strong>
        </div>
        <div>
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger" role="alert"><?= htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <form method="post" action="/install/step" class="row g-3 js-install-submit" id="<?= htmlspecialchars((string) ($step . '-form'), ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="_step" value="<?= htmlspecialchars((string) $step, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">

                <?php if ($step === 'legal'): ?>
                    <div class="col-12">
                        <div class="accordion legal-accordion" id="legalAccordion">
                            <?php foreach ($legalDocs as $idx => $doc): ?>
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button <?= $idx === 0 ? '' : 'collapsed' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#legalCollapse-<?= htmlspecialchars((string) $doc['id'], ENT_QUOTES, 'UTF-8') ?>">
                                            <?= htmlspecialchars((string) $doc['title'], ENT_QUOTES, 'UTF-8') ?>
                                        </button>
                                    </h2>
                                    <div id="legalCollapse-<?= htmlspecialchars((string) $doc['id'], ENT_QUOTES, 'UTF-8') ?>" class="accordion-collapse collapse <?= $idx === 0 ? 'show' : '' ?>" data-bs-parent="#legalAccordion">
                                        <div class="accordion-body legal-doc-body">
                                            <?= $doc['html'] ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-check mt-1">
                            <input class="form-check-input" type="checkbox" value="1" id="accept_legal" name="accept_legal" required>
                            <label class="form-check-label" for="accept_legal">J'accepte les documents légaux CATMIN.</label>
                        </div>
                    </div>
                <?php elseif ($step === 'precheck'): ?>
                    <?php
                    $checks = is_array($contextData['checks'] ?? null) ? $contextData['checks'] : [];
                    $categories = is_array($contextData['categories'] ?? null) ? $contextData['categories'] : [];
                    $summary = is_array($contextData['summary'] ?? null) ? $contextData['summary'] : $precheckSummary;
                    $precheckCategoryModals = [];
                    ?>
                    <div class="col-12">
                        <?php if (((int) ($summary['required_failed'] ?? 0)) > 0): ?>
                            <div class="alert alert-danger mb-0">Précheck bloquant: corrige les prérequis critiques avant de continuer.</div>
                        <?php elseif (((int) ($summary['failed'] ?? 0)) > 0): ?>
                            <div class="alert alert-warning mb-0">Précheck validé avec recommandations non bloquantes.</div>
                        <?php else: ?>
                            <div class="alert alert-success mb-0">Précheck validé: tous les prérequis sont conformes.</div>
                        <?php endif; ?>
                    </div>
                    <div class="col-12">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <p class="precheck-hint mb-0">Référentiel principal: PHP 8.3.0</p>
                            <a href="/install/step/precheck" class="btn btn-outline-secondary btn-sm">Refresh</a>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="accordion precheck-accordion" id="precheckAccordion">
                            <?php foreach ($categories as $idx => $category): ?>
                                <?php
                                $categoryKey = (string) ($category['key'] ?? ('category_' . $idx));
                                $categoryId = preg_replace('/[^a-z0-9_]/i', '_', $categoryKey);
                                $categoryChecks = is_array($category['checks'] ?? null) ? $category['checks'] : [];
                                $categoryTotal = count($categoryChecks);
                                $categoryPassed = 0;
                                $categoryFailed = 0;
                                $categoryBlocking = 0;
                                foreach ($categoryChecks as $itemCheck) {
                                    $ok = !empty($itemCheck['ok']);
                                    $required = !empty($itemCheck['required']);
                                    if ($ok) {
                                        $categoryPassed++;
                                    } else {
                                        $categoryFailed++;
                                        if ($required) {
                                            $categoryBlocking++;
                                        }
                                    }
                                }
                                $categoryFailedChecks = array_values(array_filter(
                                    $categoryChecks,
                                    static fn (array $item): bool => !empty($item['required']) && empty($item['ok'])
                                ));
                                $open = $idx === 0 ? 'show' : '';
                                $collapsed = $idx === 0 ? '' : 'collapsed';
                                $precheckCategoryModals[] = [
                                    'id' => $categoryId,
                                    'title' => (string) ($category['title'] ?? $categoryKey),
                                    'requisites' => (string) ($category['requisites'] ?? ''),
                                    'prerequisites' => (string) ($category['prerequisites'] ?? ''),
                                    'fails' => $categoryFailedChecks,
                                ];
                                ?>
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button <?= $collapsed ?>" type="button" data-bs-toggle="collapse" data-bs-target="#precheckCollapse-<?= htmlspecialchars($categoryId, ENT_QUOTES, 'UTF-8') ?>" aria-expanded="<?= $idx === 0 ? 'true' : 'false' ?>">
                                            <span><?= htmlspecialchars((string) ($category['title'] ?? $categoryKey), ENT_QUOTES, 'UTF-8') ?></span>
                                            <span class="precheck-acc-count ms-auto">
                                                <?= $categoryPassed ?>/<?= $categoryTotal ?>
                                                <?php if ($categoryFailed > 0): ?>
                                                    <span class="text-danger"> (<?= $categoryFailed ?> échec<?= $categoryFailed > 1 ? 's' : '' ?><?= $categoryBlocking > 0 ? ', ' . $categoryBlocking . ' bloquant' . ($categoryBlocking > 1 ? 's' : '') : '' ?>)</span>
                                                <?php endif; ?>
                                            </span>
                                        </button>
                                    </h2>
                                    <div id="precheckCollapse-<?= htmlspecialchars($categoryId, ENT_QUOTES, 'UTF-8') ?>" class="accordion-collapse collapse <?= $open ?>" data-bs-parent="#precheckAccordion">
                                        <div class="accordion-body">
                                            <div class="precheck-meta">
                                                <div><strong>Requis:</strong> <?= htmlspecialchars((string) ($category['requisites'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                                <div><strong>Pré-requis:</strong> <?= htmlspecialchars((string) ($category['prerequisites'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                            </div>
                                            <div class="precheck-list">
                                                <?php foreach ($categoryChecks as $check): ?>
                                                    <?php
                                                    $badgeClass = (string) ($check['status_class'] ?? 'text-bg-warning');
                                                    $badgeText = (string) ($check['status_text'] ?? 'O');
                                                    ?>
                                                    <div class="precheck-item d-flex align-items-center justify-content-between gap-2">
                                                        <span class="precheck-label"><?= htmlspecialchars((string) ($check['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                                        <span class="badge <?= htmlspecialchars($badgeClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($badgeText, ENT_QUOTES, 'UTF-8') ?></span>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                            <div class="pt-2">
                                                <button type="button" class="btn btn-light btn-sm precheck-detail-main" data-bs-toggle="modal" data-bs-target="#precheckGuide-<?= htmlspecialchars($categoryId, ENT_QUOTES, 'UTF-8') ?>">
                                                    <span class="precheck-info-icon bg-info">i</span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php elseif ($step === 'profile'): ?>
                    <?php $selectedTemplate = (string) ($contextData['profile'] ?? 'recommended'); ?>
                    <?php $selectedCustomModules = is_array($contextData['custom_modules'] ?? null) ? array_map('strval', $contextData['custom_modules']) : []; ?>
                    <?php if (!in_array('core', $selectedCustomModules, true)) { $selectedCustomModules[] = 'core'; } ?>
                    <?php $savedPhase = (string) ($contextData['profile_phase'] ?? 'select'); ?>
                    <?php $queryPhase = isset($_GET['profile_phase']) ? (string) $_GET['profile_phase'] : ''; ?>
                    <?php if (!in_array($queryPhase, ['select', 'modules'], true)) { $queryPhase = ''; } ?>
                    <?php if ($queryPhase !== '') { $savedPhase = $queryPhase; } ?>
                    <?php $initialModulesStage = $savedPhase === 'modules' ? 'modules' : 'select'; ?>
                    <?php if ($selectedTemplate !== 'custom') { $initialModulesStage = 'select'; } ?>
                    <input type="hidden" name="profile_phase" value="<?= htmlspecialchars($initialModulesStage, ENT_QUOTES, 'UTF-8') ?>" class="js-profile-phase">
                    <div class="col-12 js-profile-select-panel <?= $initialModulesStage === 'modules' ? 'd-none' : '' ?>">
                        <div class="row g-3 template-grid">
                            <div class="col-12 col-lg-6">
                                <input class="btn-check" type="radio" name="profile" id="tpl_core_only" value="core-only" <?= $selectedTemplate === 'core-only' ? 'checked' : '' ?>>
                                <label class="template-card" for="tpl_core_only">
                                    <span class="template-title">Core Only</span>
                                    <span class="template-desc">Installation minimale pour démarrage rapide.</span>
                                    <span class="template-meta">Inclus: core</span>
                                    <span class="template-selected-badge">Sélectionné</span>
                                </label>
                            </div>
                            <div class="col-12 col-lg-6">
                                <input class="btn-check" type="radio" name="profile" id="tpl_recommended" value="recommended" <?= $selectedTemplate === 'recommended' ? 'checked' : '' ?>>
                                <label class="template-card recommended" for="tpl_recommended">
                                    <span class="template-title-row">
                                        <span class="template-title">Template recommandé</span>
                                        <span class="template-chip">Recommandé</span>
                                    </span>
                                    <span class="template-desc">Équilibré pour la majorité des projets.</span>
                                    <span class="template-meta">Inclus: core, security, backup, legal</span>
                                    <span class="template-selected-badge">Sélectionné</span>
                                </label>
                            </div>
                            <div class="col-12 col-lg-6">
                                <input class="btn-check" type="radio" name="profile" id="tpl_full" value="full" <?= $selectedTemplate === 'full' ? 'checked' : '' ?>>
                                <label class="template-card" for="tpl_full">
                                    <span class="template-title">Template complet</span>
                                    <span class="template-desc">Pack étendu prêt pour usage riche.</span>
                                    <span class="template-meta">Inclus: core, security, backup, legal, notifications, analytics</span>
                                    <span class="template-selected-badge">Sélectionné</span>
                                </label>
                            </div>
                            <div class="col-12 col-lg-6">
                                <input class="btn-check" type="radio" name="profile" id="tpl_custom" value="custom" <?= $selectedTemplate === 'custom' ? 'checked' : '' ?>>
                                <label class="template-card" for="tpl_custom">
                                    <span class="template-title">Template personnalisé</span>
                                    <span class="template-desc">Crée ton template sur mesure avec modules/addons.</span>
                                    <span class="template-meta">Après Continuer: choix des modules personnalisés (core obligatoire).</span>
                                    <span class="template-selected-badge">Sélectionné</span>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 js-profile-custom-panel <?= $initialModulesStage === 'modules' ? '' : 'd-none' ?>">
                        <div class="profile-modules-panel">
                            <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                                <strong>Template personnalisé: modules</strong>
                                <a href="/install/step/profile?profile_phase=select" class="btn btn-outline-secondary btn-sm js-profile-back">Retour template</a>
                            </div>
                            <p class="small text-secondary mb-2">Choisis les modules à installer. <strong>core</strong> est obligatoire.</p>
                            <div class="row g-2 module-pick-grid">
                                <div class="col-12 col-lg-6">
                                    <label class="module-pick-card module-pick-card-fixed">
                                        <input class="form-check-input module-pick-input" type="checkbox" checked disabled>
                                        <input type="hidden" name="custom_modules[]" value="core">
                                        <span class="module-pick-text">
                                            <span class="module-pick-name">core</span>
                                            <span class="module-pick-meta">Module noyau obligatoire</span>
                                        </span>
                                        <span class="badge text-bg-dark">Obligatoire</span>
                                    </label>
                                </div>
                                <?php if ($templateModuleOptions === []): ?>
                                    <div class="col-12">
                                        <div class="alert alert-secondary mb-0 small">Aucun autre module détecté. Le template personnalisé installera uniquement <strong>core</strong>.</div>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($templateModuleOptions as $moduleName): ?>
                                        <?php $checked = in_array((string) $moduleName, $selectedCustomModules, true); ?>
                                        <div class="col-12 col-lg-6">
                                            <label class="module-pick-card">
                                                <input class="form-check-input module-pick-input" type="checkbox" name="custom_modules[]" value="<?= htmlspecialchars((string) $moduleName, ENT_QUOTES, 'UTF-8') ?>" <?= $checked ? 'checked' : '' ?>>
                                                <span class="module-pick-text">
                                                    <span class="module-pick-name"><?= htmlspecialchars((string) $moduleName, ENT_QUOTES, 'UTF-8') ?></span>
                                                    <span class="module-pick-meta">Addon/module optionnel</span>
                                                </span>
                                                <span class="badge text-bg-light border">Optionnel</span>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php elseif ($step === 'database'): ?>
                    <?php $dbDriver = (string) ($contextData['driver'] ?? 'sqlite'); ?>
                    <?php
                    $defaultPorts = [
                        'sqlite' => '',
                        'mysql' => '3306',
                        'mariadb' => '3306',
                        'pgsql' => '5432',
                        'sqlsrv' => '1433',
                    ];
                    $portValue = (string) ($contextData['port'] ?? ($defaultPorts[$dbDriver] ?? ''));
                    $databaseValue = (string) ($contextData['database'] ?? 'catmin');
                    ?>
                    <?php
                    $sqlitePathValue = (string) ($contextData['sqlite_path'] ?? 'db/database.sqlite');
                    $rootPrefix = rtrim((string) CATMIN_ROOT, '/') . '/';
                    if (str_starts_with($sqlitePathValue, $rootPrefix)) {
                        $sqlitePathValue = substr($sqlitePathValue, strlen($rootPrefix));
                    }
                    ?>
                    <div class="col-12">
                        <div class="db-driver-band">
                            <?php foreach (['sqlite', 'mysql', 'mariadb', 'pgsql', 'sqlsrv'] as $driver): ?>
                                <input class="btn-check js-db-driver-radio" type="radio" name="driver" id="db_driver_<?= htmlspecialchars($driver, ENT_QUOTES, 'UTF-8') ?>" value="<?= htmlspecialchars($driver, ENT_QUOTES, 'UTF-8') ?>" <?= $dbDriver === $driver ? 'checked' : '' ?>>
                                <label class="btn btn-outline-dark btn-sm" for="db_driver_<?= htmlspecialchars($driver, ENT_QUOTES, 'UTF-8') ?>"><?= strtoupper(htmlspecialchars($driver, ENT_QUOTES, 'UTF-8')) ?></label>
                            <?php endforeach; ?>
                            <button type="button" class="btn btn-outline-primary btn-sm ms-auto js-db-test-btn">Tester la connexion</button>
                        </div>
                    </div>
                    <div class="col-12 d-none js-db-test-result" role="status"></div>
                    <div class="col-12 js-db-sqlite-fields">
                        <label class="form-label">SQLite Path</label>
                        <input class="form-control" name="sqlite_path" value="<?= htmlspecialchars($sqlitePathValue, ENT_QUOTES, 'UTF-8') ?>" placeholder="db/database.sqlite">
                        <div class="form-text">Chemin relatif au projet CATMIN (ex: <code>db/database.sqlite</code>).</div>
                    </div>
                    <div class="col-md-4 js-db-server-fields">
                        <label class="form-label">Host</label>
                        <input class="form-control" name="host" value="<?= htmlspecialchars((string) ($contextData['host'] ?? '127.0.0.1'), ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="col-md-2 js-db-server-fields">
                        <label class="form-label">Port</label>
                        <input class="form-control" name="port" value="<?= htmlspecialchars($portValue, ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="col-md-6 js-db-server-fields">
                        <label class="form-label">Database</label>
                        <input class="form-control" name="database" value="<?= htmlspecialchars($databaseValue, ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="col-md-6 js-db-server-fields">
                        <label class="form-label">Username</label>
                        <input class="form-control" name="username" value="<?= htmlspecialchars((string) ($contextData['username'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="col-md-6 js-db-server-fields">
                        <label class="form-label">Password</label>
                        <input class="form-control" type="password" name="password" placeholder="********">
                    </div>
                <?php elseif ($step === 'identity'): ?>
                    <div class="col-md-6"><label class="form-label">Nom application</label><input class="form-control" name="app_name" value="<?= htmlspecialchars((string) ($contextData['app_name'] ?? 'CATMIN'), ENT_QUOTES, 'UTF-8') ?>"></div>
                    <div class="col-md-6"><label class="form-label">URL application</label><input class="form-control" name="app_url" value="<?= htmlspecialchars((string) ($contextData['app_url'] ?? '/'), ENT_QUOTES, 'UTF-8') ?>"></div>
                    <div class="col-12">
                        <label class="form-label">Exploitant</label>
                        <div class="input-group">
                            <?php $operatorType = (string) ($contextData['operator_type'] ?? 'particulier'); ?>
                            <select class="form-select flex-grow-0 w-auto" name="operator_type">
                                <?php foreach (['particulier' => 'Particulier', 'entreprise' => 'Entreprise', 'asbl' => 'ASBL', 'association' => 'Association', 'collectivite' => 'Collectivité', 'administration' => 'Administration', 'autre' => 'Autre'] as $typeKey => $typeLabel): ?>
                                    <option value="<?= htmlspecialchars($typeKey, ENT_QUOTES, 'UTF-8') ?>" <?= $operatorType === $typeKey ? 'selected' : '' ?>><?= htmlspecialchars($typeLabel, ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                            </select>
                            <input class="form-control js-operator-name" name="operator_name" placeholder="Nom nominatif / raison sociale" value="<?= htmlspecialchars((string) ($contextData['operator_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                    </div>
                <?php elseif ($step === 'superadmin'): ?>
                    <div class="col-md-4"><label class="form-label">Username</label><input class="form-control" name="username" value="<?= htmlspecialchars((string) ($contextData['username'] ?? 'superadmin'), ENT_QUOTES, 'UTF-8') ?>"></div>
                    <div class="col-md-4"><label class="form-label">Email</label><input class="form-control" type="email" name="email" value="<?= htmlspecialchars((string) ($contextData['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
                    <div class="col-md-4"><label class="form-label">Password</label><input class="form-control" type="password" name="password" minlength="12" autocomplete="new-password" required></div>
                <?php elseif ($step === 'security'): ?>
                    <?php
                    $adminPathMode = (string) ($contextData['admin_path_mode'] ?? 'manual');
                    if (!in_array($adminPathMode, ['auto', 'manual'], true)) {
                        $adminPathMode = 'manual';
                    }
                    $adminPathValue = (string) ($contextData['admin_path'] ?? 'admin');
                    $detectedInstallerIp = '';
                    $xff = (string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? '');
                    if ($xff !== '') {
                        $parts = preg_split('/\s*,\s*/', $xff) ?: [];
                        foreach ($parts as $candidate) {
                            if (filter_var($candidate, FILTER_VALIDATE_IP) !== false) {
                                $detectedInstallerIp = $candidate;
                                break;
                            }
                        }
                    }
                    if ($detectedInstallerIp === '') {
                        $remote = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
                        if (filter_var($remote, FILTER_VALIDATE_IP) !== false) {
                            $detectedInstallerIp = $remote;
                        }
                    }
                    $savedWhitelist = is_array($contextData['ip_whitelist'] ?? null) ? array_map('strval', $contextData['ip_whitelist']) : [];
                    $whitelistValue = implode("\n", $savedWhitelist);
                    ?>
                    <div class="col-12">
                        <div class="db-driver-band">
                            <input class="btn-check js-admin-mode-radio" type="radio" name="admin_path_mode" id="admin_mode_auto" value="auto" <?= $adminPathMode === 'auto' ? 'checked' : '' ?>>
                            <label class="btn btn-outline-dark btn-sm" for="admin_mode_auto">Auto</label>
                            <input class="btn-check js-admin-mode-radio" type="radio" name="admin_path_mode" id="admin_mode_manual" value="manual" <?= $adminPathMode === 'manual' ? 'checked' : '' ?>>
                            <label class="btn btn-outline-dark btn-sm" for="admin_mode_manual">Manuel</label>
                        </div>
                    </div>
                    <div class="col-md-6 js-admin-manual-field">
                        <label class="form-label">Route Admin (manuel)</label>
                        <input class="form-control" name="admin_path" value="<?= htmlspecialchars($adminPathValue, ENT_QUOTES, 'UTF-8') ?>" placeholder="admin">
                    </div>
                    <div class="col-md-6 js-admin-auto-field">
                        <label class="form-label">Route Admin (auto)</label>
                        <div class="input-group">
                            <input class="form-control js-admin-auto-input" name="admin_path_auto" value="<?= htmlspecialchars($adminPathMode === 'auto' ? $adminPathValue : ('admin-' . substr(bin2hex(random_bytes(4)), 0, 8)), ENT_QUOTES, 'UTF-8') ?>" readonly>
                            <button class="btn btn-outline-secondary js-admin-regenerate" type="button">Regénérer</button>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-check">
                            <input class="form-check-input js-whitelist-toggle" type="checkbox" value="1" id="ip_whitelist_enabled" name="ip_whitelist_enabled" <?= !empty($contextData['ip_whitelist_enabled']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="ip_whitelist_enabled">Activer whitelist IP (inclure automatiquement mon IP d'installation)</label>
                        </div>
                        <input type="hidden" class="js-whitelist-installer-ip" value="<?= htmlspecialchars($detectedInstallerIp, ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="col-12 js-whitelist-wrap <?= !empty($contextData['ip_whitelist_enabled']) ? '' : 'd-none' ?>">
                        <label class="form-label">IPs autorisées</label>
                        <textarea class="form-control js-whitelist-text" name="ip_whitelist" rows="4" placeholder="Une IP par ligne ou séparées par virgules"><?= htmlspecialchars($whitelistValue, ENT_QUOTES, 'UTF-8') ?></textarea>
                        <div class="form-text">Local: <code>127.0.0.1</code>, <code>::1</code>. Hébergement: IP publique de connexion détectée automatiquement.</div>
                    </div>
                <?php elseif ($step === 'system'): ?>
                    <?php $selectedTimezone = (string) ($contextData['timezone'] ?? 'UTC'); ?>
                    <div class="col-md-7">
                        <label class="form-label">Timezone</label>
                        <select class="form-select" name="timezone">
                            <?php foreach ($timezoneOptions as $tz): ?>
                                <option value="<?= htmlspecialchars((string) $tz, ENT_QUOTES, 'UTF-8') ?>" <?= $selectedTimezone === (string) $tz ? 'selected' : '' ?>><?= htmlspecialchars((string) $tz, ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">Consentement tracking minimal</label>
                        <div class="input-group">
                            <div class="input-group-text">
                                <input class="form-check-input mt-0" type="checkbox" value="1" id="consent_tracking" name="consent_tracking" <?= !empty($contextData['consent_tracking']) ? 'checked' : '' ?>>
                            </div>
                            <span class="form-control">Activer le consentement tracking minimal</span>
                            <button class="btn btn-outline-primary" type="button" data-bs-toggle="modal" data-bs-target="#consentTrackingInfoModal">Info</button>
                        </div>
                    </div>
                <?php elseif ($step === 'execution'): ?>
                    <div class="col-12"><div class="alert alert-info mb-0">Exécution backend: DB test, migrations, seeders, création SuperAdmin, écriture config.</div></div>
                <?php elseif ($step === 'recovery_codes'): ?>
                    <?php $codes = $contextData['codes'] ?? []; ?>
                    <div class="col-12"><?php if (is_array($codes) && $codes !== []): ?><div class="alert alert-warning">Enregistre ces recovery codes immédiatement.</div><div class="row g-2"><?php foreach ($codes as $code): ?><div class="col-sm-6 col-lg-4"><code class="d-block p-2 bg-light border rounded"><?= htmlspecialchars((string) $code, ENT_QUOTES, 'UTF-8') ?></code></div><?php endforeach; ?></div><?php else: ?><div class="alert alert-secondary mb-0">Les recovery codes seront générés à cette étape.</div><?php endif; ?></div>
                <?php elseif ($step === 'report'): ?>
                    <div class="col-12"><div class="alert alert-success mb-0">Le rapport d'installation est prêt. Passe ensuite au lock final.</div></div>
                    <?php if (!empty($installBackup['ok'])): ?>
                        <?php
                        $downloadUrl = '/install/backup/download?t=' . rawurlencode((string) ($installBackup['token'] ?? ''));
                        $expiresAt = (int) ($installBackup['expires_at'] ?? 0);
                        ?>
                        <div class="col-12">
                            <div class="alert alert-info mb-0">
                                <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between">
                                    <div>
                                        <strong>Backup initial DB créé:</strong>
                                        <?= htmlspecialchars((string) ($installBackup['name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>
                                        (<?= number_format(((int) ($installBackup['size'] ?? 0)) / 1024, 1, '.', ' ') ?> KB)
                                        <?php if ($expiresAt > 0): ?>
                                            <div class="small">Lien temporaire valable jusqu'à <?= htmlspecialchars(date('Y-m-d H:i:s', $expiresAt), ENT_QUOTES, 'UTF-8') ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <a class="btn btn-outline-primary btn-sm" href="<?= htmlspecialchars($downloadUrl, ENT_QUOTES, 'UTF-8') ?>">Télécharger le backup initial</a>
                                </div>
                                <div class="small mt-2">Télécharge ce backup immédiatement et conserve-le hors serveur. Le lien sera invalide après lock final.</div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="col-12">
                            <div class="alert alert-warning mb-0">
                                Backup initial non disponible: <?= htmlspecialchars((string) ($installBackup['error'] ?? 'échec génération backup'), ENT_QUOTES, 'UTF-8') ?>.
                                L'installation peut continuer, mais configure une sauvegarde DB immédiatement après.
                            </div>
                        </div>
                    <?php endif; ?>
                <?php elseif ($step === 'lock'): ?>
                    <div class="col-12"><div class="alert alert-danger mb-0">Action irréversible: verrouillage final et neutralisation de l'installateur.</div></div>
                    <div class="col-12"><div class="form-check mt-2"><input class="form-check-input" type="checkbox" value="1" id="confirm_lock" name="confirm_lock" required><label class="form-check-label" for="confirm_lock">Je confirme le lock final.</label></div></div>
                <?php else: ?>
                    <div class="col-12"><p class="mb-0">Aucune donnée requise pour cette étape.</p></div>
                <?php endif; ?>

                <div class="col-12 d-flex flex-wrap gap-2 pt-2 install-actions">
                    <button class="btn btn-catmin js-continue-btn" type="submit"><?= install_icon('execution', 'inline-icon') ?><span class="btn-label">Continuer</span></button>
                    <a class="btn btn-outline-secondary" href="/install/report"><?= install_icon('report', 'inline-icon') ?>Rapport</a>
                    <button class="btn btn-outline-danger" type="submit" form="install-reset-form">Recommencer</button>
                </div>
            </form>
        </div>
    </main>
    <footer class="install-footer">
        <small>© <?= date('Y') ?> CATMIN. Tous droits réservés.</small>
        <div class="install-footer-links">
            <a href="<?= htmlspecialchars($githubPublic, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer">GitHub Public</a>
            <a href="<?= htmlspecialchars($githubDev, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer">GitHub DEV</a>
        </div>
    </footer>
    </div>
</div>
<form method="post" action="/install/reset" id="install-reset-form" class="d-none">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
</form>
<?php if ($step === 'precheck' && $precheckCategoryModals !== []): ?>
    <?php foreach ($precheckCategoryModals as $modal): ?>
        <div class="modal fade" id="precheckGuide-<?= htmlspecialchars((string) $modal['id'], ENT_QUOTES, 'UTF-8') ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Détail: <?= htmlspecialchars((string) ($modal['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-2"><strong>Requis:</strong> <?= htmlspecialchars((string) ($modal['requisites'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                        <p class="mb-2"><strong>Pré-requis:</strong> <?= htmlspecialchars((string) ($modal['prerequisites'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                        <p class="mb-2"><strong>Actions à faire:</strong></p>
                        <?php if (($modal['fails'] ?? []) !== []): ?>
                            <ul class="mb-0">
                                <?php foreach (($modal['fails'] ?? []) as $failed): ?>
                                    <li>
                                        <strong><?= htmlspecialchars((string) ($failed['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?>:</strong>
                                        <?= htmlspecialchars((string) ($failed['details'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p class="mb-0">Aucun échec bloquant dans cette catégorie.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
<?php if ($step === 'system'): ?>
    <div class="modal fade" id="consentTrackingInfoModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Consentement tracking minimal</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2">Ce mode active uniquement le suivi strictement nécessaire au fonctionnement et à la sécurité du CMS.</p>
                    <p class="mb-2"><strong>Pourquoi c'est important:</strong></p>
                    <ul class="mb-0">
                        <li>Conformité RGPD/ePrivacy dès l'installation.</li>
                        <li>Réduction de la collecte de données non essentielles.</li>
                        <li>Base propre pour activer plus tard des modules analytics selon votre politique.</li>
                        <li>Limitation du risque juridique en phase de mise en production.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>
<script src="/assets/vendor/bootstrap/5.3.8/js/bootstrap.bundle.min.js"></script>
<script src="/assets/js/catmin-components.js?v=8"></script>
<script src="/assets/js/install-wizard.js?v=6"></script>
</body>
</html>
