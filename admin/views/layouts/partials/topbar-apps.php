<?php
require_once CATMIN_CORE . '/apps-presenter.php';
$appsPresenter = new CoreAppsPresenter();
$apps = is_array($topbar['apps'] ?? null) ? $topbar['apps'] : [];
?>
<div class="dropdown">
    <button type="button" class="cat-icon-btn dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" aria-label="<?= htmlspecialchars(__('topbar.apps'), ENT_QUOTES, 'UTF-8') ?>">
        <i class="bi bi-grid-3x3-gap"></i>
    </button>
    <div class="dropdown-menu dropdown-menu-end cat-topbar-dropdown cat-topbar-dropdown-apps">
        <div class="cat-topbar-dropdown-head"><strong><?= htmlspecialchars(__('topbar.apps'), ENT_QUOTES, 'UTF-8') ?></strong></div>
        <div class="cat-apps-grid">
            <?php if ($apps === []): ?>
                <div class="small text-body-secondary px-2 py-2"><?= htmlspecialchars(__('topbar.apps_empty'), ENT_QUOTES, 'UTF-8') ?></div>
            <?php else: ?>
                <?php foreach ($apps as $app): ?>
                    <?php
                    $label = (string) ($app['label'] ?? 'App');
                    $url = (string) ($app['url'] ?? '#');
                    $target = $appsPresenter->normalizeTarget((string) ($app['target'] ?? '_blank'));
                    ?>
                    <a class="cat-apps-item" href="<?= htmlspecialchars($url, ENT_QUOTES, 'UTF-8') ?>" target="<?= htmlspecialchars($target, ENT_QUOTES, 'UTF-8') ?>" <?= $target === '_blank' ? 'rel="noopener noreferrer"' : '' ?>>
                        <span class="cat-apps-item-icon"><?= $appsPresenter->iconHtml($app) ?></span>
                        <span class="cat-apps-item-label"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></span>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <div class="cat-topbar-dropdown-foot">
            <a class="small text-decoration-none" href="<?= htmlspecialchars($adminBase . '/settings/advanced', ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(__('topbar.apps_manage'), ENT_QUOTES, 'UTF-8') ?></a>
        </div>
    </div>
</div>
