<?php

declare(strict_types=1);
?>
<section class="card">
    <div class="card-header bg-transparent border-0 pt-3">
        <h3 class="h6 mb-0">Version / Environnement</h3>
    </div>
    <div class="card-body pt-2">
        <dl class="row mb-3 small">
            <dt class="col-6 col-lg-3">CATMIN</dt>
            <dd class="col-6 col-lg-3"><small class="cat-version-value"><?= htmlspecialchars((string) ($versionInfo['version'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></small></dd>
            <dt class="col-6 col-lg-3">CATMIN UI/UX</dt>
            <dd class="col-6 col-lg-3"><small class="cat-version-value">Core admin shell V1</small></dd>

            <dt class="col-6 col-lg-3">PHP</dt>
            <dd class="col-6 col-lg-3"><small class="cat-version-value"><?= htmlspecialchars((string) ($versionInfo['php'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></small></dd>
            <dt class="col-6 col-lg-3">DB</dt>
            <dd class="col-6 col-lg-3"><small class="cat-version-value"><?= htmlspecialchars((string) (($versionInfo['db_driver'] ?? '-') . ' ' . ($versionInfo['db_version'] ?? '-')), ENT_QUOTES, 'UTF-8') ?></small></dd>

            <dt class="col-6 col-lg-3">Cron status</dt>
            <dd class="col-6 col-lg-3"><small class="cat-version-value"><?= htmlspecialchars((string) ($versionInfo['cron_status'] ?? 'Inconnu'), ENT_QUOTES, 'UTF-8') ?></small></dd>
            <dt class="col-6 col-lg-3">Maintenance</dt>
            <dd class="col-6 col-lg-3"><small class="cat-version-value"><?= htmlspecialchars((string) ($versionInfo['maintenance'] ?? 'Inconnue'), ENT_QUOTES, 'UTF-8') ?></small></dd>

            <dt class="col-6 col-lg-3">Environnement</dt>
            <dd class="col-6 col-lg-3"><small class="cat-version-value"><?= htmlspecialchars((string) ($versionInfo['app_env'] ?? 'production'), ENT_QUOTES, 'UTF-8') ?></small></dd>
            <dt class="col-6 col-lg-3">Route admin</dt>
            <dd class="col-6 col-lg-3"><small class="cat-version-value"><?= htmlspecialchars((string) ($versionInfo['admin_path'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></small></dd>

            <dt class="col-6 col-lg-3">GIT</dt>
            <dd class="col-6 col-lg-3"><small class="cat-version-value"><?= htmlspecialchars((string) ($versionInfo['git_branch'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></small></dd>
            <dt class="col-6 col-lg-3">Commit</dt>
            <dd class="col-6 col-lg-3"><small class="cat-version-value"><?= htmlspecialchars((string) ($versionInfo['git_commit'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></small></dd>
        </dl>

        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-outline-secondary btn-sm" href="/storage/logs/catmin.log" target="_blank" rel="noopener">Logs applicatifs</a>
            <a class="btn btn-outline-secondary btn-sm" href="/admin/locked">Etat verrouillage</a>
        </div>
    </div>
</section>
