<?php
declare(strict_types=1);
?>
<section class="card h-100">
    <div class="card-header bg-transparent border-0 pt-3">
        <h3 class="h6 mb-0">Version / Environnement</h3>
    </div>
    <div class="card-body pt-2">
        <dl class="row mb-0 small">
            <dt class="col-5">CATMIN</dt>
            <dd class="col-7"><small class="cat-version-value"><?= htmlspecialchars((string) ($versionInfo['version'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></small></dd>
            <dt class="col-5">CATMIN UI/UX</dt>
            <dd class="col-7"><small class="cat-version-value">Core admin shell V1</small></dd>
            <dt class="col-5">PHP</dt>
            <dd class="col-7"><small class="cat-version-value"><?= htmlspecialchars((string) ($versionInfo['php'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></small></dd>
            <dt class="col-5">Admin path</dt>
            <dd class="col-7"><small class="cat-version-value"><?= htmlspecialchars((string) ($versionInfo['admin_path'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></small></dd>
            <dt class="col-5">Mode</dt>
            <dd class="col-7"><small class="cat-version-value"><?= htmlspecialchars((string) ($versionInfo['app_env'] ?? 'production'), ENT_QUOTES, 'UTF-8') ?></small></dd>
            <dt class="col-5">GIT</dt>
            <dd class="col-7"><small class="cat-version-value"><?= htmlspecialchars((string) ($versionInfo['git_branch'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></small></dd>
            <dt class="col-5">Commit</dt>
            <dd class="col-7"><small class="cat-version-value"><?= htmlspecialchars((string) ($versionInfo['git_commit'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></small></dd>
        </dl>
    </div>
</section>
