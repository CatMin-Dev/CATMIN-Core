<?php

declare(strict_types=1);

use Core\security\CsrfManager;

$pageTitle = __('market.title');
$pageDescription = '';
$activeNav = 'module-market';
$breadcrumbs = [
    ['label' => __('common.admin'), 'href' => $adminBase . '/'],
    ['label' => __('nav.modules'), 'href' => $adminBase . '/modules'],
    ['label' => __('market.title')],
];

$catalog = is_array($catalog ?? null) ? $catalog : [];
$items = is_array($catalog['items'] ?? null) ? $catalog['items'] : [];
$stats = is_array($catalog['stats'] ?? null) ? $catalog['stats'] : [];
$policy = is_array($catalog['policy'] ?? null) ? $catalog['policy'] : [];
$error = trim((string) ($catalog['error'] ?? ''));
$search = strtolower(trim((string) ($filters['q'] ?? '')));
$status = strtolower(trim((string) ($filters['status'] ?? 'all')));
$scope = strtolower(trim((string) ($filters['scope'] ?? 'all')));
$trustFilter = strtolower(trim((string) ($filters['trust'] ?? (((bool) ($policy['show_community_by_default'] ?? false)) ? 'all' : 'non-community'))));

$scopes = [];
foreach ($items as $item) {
    $scopes[] = strtolower(trim((string) ($item['scope'] ?? 'unknown')));
}
$scopes = array_values(array_unique(array_filter($scopes, static fn (string $v): bool => $v !== '')));
sort($scopes);

$items = array_values(array_filter($items, static function (array $item) use ($search, $status, $scope, $trustFilter): bool {
    $label = strtolower(trim((string) (($item['name'] ?? '') . ' ' . ($item['slug'] ?? '') . ' ' . ($item['description'] ?? ''))));
    if ($search !== '' && !str_contains($label, $search)) {
        return false;
    }
    if ($scope !== 'all' && strtolower((string) ($item['scope'] ?? '')) !== $scope) {
        return false;
    }
    $repoTrust = strtolower((string) ($item['repo_trust_level'] ?? 'community'));
    if ($trustFilter === 'non-community' && $repoTrust === 'community') {
        return false;
    }
    if (in_array($trustFilter, ['official', 'trusted', 'community', 'blocked'], true) && $repoTrust !== $trustFilter) {
        return false;
    }

    $installed = (bool) ($item['installed'] ?? false);
    $compatible = (bool) ($item['compatible'] ?? true);
    $hasUpdate = (bool) ($item['has_update'] ?? false);

    return match ($status) {
        'installed' => $installed,
        'not-installed' => !$installed,
        'updates' => $hasUpdate,
        'incompatible' => !$compatible,
        default => true,
    };
}));

$csrf = htmlspecialchars((new CsrfManager())->token(), ENT_QUOTES, 'UTF-8');

ob_start();
?>
<section class="row g-3 mb-3">
    <div class="col-6 col-xl-3"><div class="card h-100"><div class="card-body"><small class="text-body-secondary"><?= htmlspecialchars(__('market.stats.total'), ENT_QUOTES, 'UTF-8') ?></small><p class="h4 mb-0"><?= (int) ($stats['total'] ?? 0) ?></p></div></div></div>
    <div class="col-6 col-xl-3"><div class="card h-100"><div class="card-body"><small class="text-body-secondary"><?= htmlspecialchars(__('market.stats.installed'), ENT_QUOTES, 'UTF-8') ?></small><p class="h4 mb-0 text-success"><?= (int) ($stats['installed'] ?? 0) ?></p></div></div></div>
    <div class="col-6 col-xl-3"><div class="card h-100"><div class="card-body"><small class="text-body-secondary"><?= htmlspecialchars(__('market.stats.updates'), ENT_QUOTES, 'UTF-8') ?></small><p class="h4 mb-0 text-warning"><?= (int) ($stats['updates'] ?? 0) ?></p></div></div></div>
    <div class="col-6 col-xl-3"><div class="card h-100"><div class="card-body"><small class="text-body-secondary"><?= htmlspecialchars(__('market.stats.incompatible'), ENT_QUOTES, 'UTF-8') ?></small><p class="h4 mb-0 text-danger"><?= (int) ($stats['incompatible'] ?? 0) ?></p></div></div></div>
</section>

<?php if ($error !== ''): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<form method="get" class="card mb-3">
    <div class="card-body py-3">
        <div class="row g-2 align-items-end">
            <div class="col-12 col-lg-5">
                <label class="form-label mb-1"><?= htmlspecialchars(__('common.search'), ENT_QUOTES, 'UTF-8') ?></label>
                <input class="form-control" type="text" name="q" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>" placeholder="<?= htmlspecialchars(__('market.filters.search_placeholder'), ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="col-6 col-lg-3">
                <label class="form-label mb-1"><?= htmlspecialchars(__('common.scope'), ENT_QUOTES, 'UTF-8') ?></label>
                <select class="form-select" name="scope">
                    <option value="all"><?= htmlspecialchars(__('common.all'), ENT_QUOTES, 'UTF-8') ?></option>
                    <?php foreach ($scopes as $scopeOption): ?>
                        <option value="<?= htmlspecialchars($scopeOption, ENT_QUOTES, 'UTF-8') ?>" <?= $scopeOption === $scope ? 'selected' : '' ?>><?= htmlspecialchars($scopeOption, ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-lg-2">
                <label class="form-label mb-1"><?= htmlspecialchars(__('common.status'), ENT_QUOTES, 'UTF-8') ?></label>
                <select class="form-select" name="status">
                    <?php
                    $statusOptions = [
                        'all' => __('common.all'),
                        'installed' => __('market.status.installed'),
                        'not-installed' => __('market.status.not_installed'),
                        'updates' => __('market.status.updates'),
                        'incompatible' => __('market.status.incompatible'),
                    ];
                    foreach ($statusOptions as $value => $label):
                        ?>
                        <option value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>" <?= $value === $status ? 'selected' : '' ?>><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-lg-2">
                <label class="form-label mb-1"><?= htmlspecialchars(__('settings.module_repositories.trust_level'), ENT_QUOTES, 'UTF-8') ?></label>
                <select class="form-select" name="trust">
                    <option value="all" <?= $trustFilter === 'all' ? 'selected' : '' ?>><?= htmlspecialchars(__('common.all'), ENT_QUOTES, 'UTF-8') ?></option>
                    <option value="non-community" <?= $trustFilter === 'non-community' ? 'selected' : '' ?>><?= htmlspecialchars(__('market.filter.non_community'), ENT_QUOTES, 'UTF-8') ?></option>
                    <option value="official" <?= $trustFilter === 'official' ? 'selected' : '' ?>><?= htmlspecialchars(__('common.official'), ENT_QUOTES, 'UTF-8') ?></option>
                    <option value="trusted" <?= $trustFilter === 'trusted' ? 'selected' : '' ?>><?= htmlspecialchars(__('common.trusted'), ENT_QUOTES, 'UTF-8') ?></option>
                    <option value="community" <?= $trustFilter === 'community' ? 'selected' : '' ?>><?= htmlspecialchars(__('common.community'), ENT_QUOTES, 'UTF-8') ?></option>
                </select>
            </div>
            <div class="col-12 col-lg-2">
                <div class="d-grid gap-2">
                    <button class="btn btn-primary" type="submit"><?= htmlspecialchars(__('common.filter'), ENT_QUOTES, 'UTF-8') ?></button>
                    <a class="btn btn-outline-secondary" href="<?= htmlspecialchars($adminBase . '/modules/market', ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(__('common.reset'), ENT_QUOTES, 'UTF-8') ?></a>
                </div>
            </div>
        </div>
    </div>
</form>

<section class="card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
            <tr>
                <th><?= htmlspecialchars(__('common.module'), ENT_QUOTES, 'UTF-8') ?></th>
                <th><?= htmlspecialchars(__('common.version'), ENT_QUOTES, 'UTF-8') ?></th>
                <th><?= htmlspecialchars(__('market.repository'), ENT_QUOTES, 'UTF-8') ?></th>
                <th><?= htmlspecialchars(__('market.channels'), ENT_QUOTES, 'UTF-8') ?></th>
                <th>Capabilities</th>
                <th><?= htmlspecialchars(__('market.compatibility'), ENT_QUOTES, 'UTF-8') ?></th>
                <th><?= htmlspecialchars(__('modules.table.integrity'), ENT_QUOTES, 'UTF-8') ?></th>
                <th><?= htmlspecialchars(__('modules.table.signature'), ENT_QUOTES, 'UTF-8') ?></th>
                <th><?= htmlspecialchars(__('market.trust_score'), ENT_QUOTES, 'UTF-8') ?></th>
                <th><?= htmlspecialchars(__('common.status'), ENT_QUOTES, 'UTF-8') ?></th>
                <th class="text-end"><?= htmlspecialchars(__('common.actions'), ENT_QUOTES, 'UTF-8') ?></th>
            </tr>
            </thead>
            <tbody>
            <?php if ($items === []): ?>
                <tr><td colspan="11" class="text-center py-5 text-body-secondary"><?= htmlspecialchars(__('market.empty'), ENT_QUOTES, 'UTF-8') ?></td></tr>
            <?php endif; ?>
            <?php foreach ($items as $item): ?>
                <?php
                $moduleScope = strtolower(trim((string) ($item['scope'] ?? 'unknown')));
                $slug = strtolower(trim((string) ($item['slug'] ?? 'unknown')));
                $installed = (bool) ($item['installed'] ?? false);
                $hasUpdate = (bool) ($item['has_update'] ?? false);
                $compatible = (bool) ($item['compatible'] ?? true);
                $integrityStatus = strtolower((string) ($item['integrity_status'] ?? 'n/a'));
                $signatureStatus = strtolower((string) ($item['signature_status'] ?? 'n/a'));
                $keyScope = strtolower((string) ($item['key_scope'] ?? 'unknown'));
                $keyStatus = strtolower((string) ($item['key_status'] ?? 'unknown'));
                ?>
                <tr>
                    <td>
                        <p class="mb-0 fw-semibold"><?= htmlspecialchars((string) ($item['name'] ?? $slug), ENT_QUOTES, 'UTF-8') ?></p>
                        <small class="text-body-secondary"><?= htmlspecialchars($moduleScope . '/' . $slug, ENT_QUOTES, 'UTF-8') ?></small>
                        <p class="small text-body-secondary mt-1 mb-0"><?= htmlspecialchars((string) ($item['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                    </td>
                    <td>
                        <span class="badge text-bg-light border"><?= htmlspecialchars((string) ($item['version'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></span>
                        <?php if ($installed): ?>
                            <small class="d-block text-body-secondary mt-1"><?= htmlspecialchars(__('market.installed_version'), ENT_QUOTES, 'UTF-8') ?>: <?= htmlspecialchars((string) ($item['installed_version'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php $repoTrust = strtolower((string) ($item['repo_trust_level'] ?? 'community')); ?>
                        <p class="mb-1 fw-semibold"><?= htmlspecialchars((string) ($item['repo_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
                        <span class="badge <?= $repoTrust === 'official' ? 'text-bg-success' : ($repoTrust === 'trusted' ? 'text-bg-info' : ($repoTrust === 'blocked' ? 'text-bg-danger' : 'text-bg-warning')) ?>">
                            <?= htmlspecialchars(__('common.' . $repoTrust), ENT_QUOTES, 'UTF-8') ?>
                        </span>
                        <?php foreach ((array) ($item['trust_warnings'] ?? []) as $trustWarning): ?>
                            <small class="d-block text-danger mt-1"><?= htmlspecialchars((string) $trustWarning, ENT_QUOTES, 'UTF-8') ?></small>
                        <?php endforeach; ?>
                    </td>
                    <td>
                        <?php $channel = strtolower((string) ($item['release_channel'] ?? 'stable')); ?>
                        <?php $lifecycle = strtolower((string) ($item['lifecycle_status'] ?? 'active')); ?>
                        <span class="badge text-bg-secondary"><?= htmlspecialchars($channel, ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="badge <?= in_array($lifecycle, ['deprecated', 'abandoned', 'archived'], true) ? 'text-bg-warning' : 'text-bg-success' ?>"><?= htmlspecialchars($lifecycle, ENT_QUOTES, 'UTF-8') ?></span>
                        <?php if ((string) ($item['replacement_slug'] ?? '') !== ''): ?>
                            <small class="d-block text-body-secondary mt-1"><?= htmlspecialchars(__('market.replaced_by'), ENT_QUOTES, 'UTF-8') ?>: <?= htmlspecialchars((string) ($item['replacement_slug'] ?? ''), ENT_QUOTES, 'UTF-8') ?></small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php
                        $capabilities = is_array($item['capabilities'] ?? null) ? $item['capabilities'] : [];
                        $risk = strtolower((string) ($item['capabilities_risk'] ?? 'low'));
                        $riskClass = match ($risk) {
                            'critical' => 'text-bg-danger',
                            'high' => 'text-bg-warning',
                            'medium' => 'text-bg-info',
                            default => 'text-bg-secondary',
                        };
                        ?>
                        <span class="badge <?= $riskClass ?>"><?= htmlspecialchars('risk:' . $risk, ENT_QUOTES, 'UTF-8') ?></span>
                        <?php if ($capabilities !== []): ?>
                            <div class="d-flex flex-wrap gap-1 mt-1">
                                <?php foreach (array_slice($capabilities, 0, 5) as $cap): ?>
                                    <span class="badge text-bg-light border"><?= htmlspecialchars((string) $cap, ENT_QUOTES, 'UTF-8') ?></span>
                                <?php endforeach; ?>
                                <?php if (count($capabilities) > 5): ?>
                                    <span class="badge text-bg-secondary">+<?= count($capabilities) - 5 ?></span>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <small class="text-body-secondary d-block mt-1"><?= htmlspecialchars(__('common.none_feminine'), ENT_QUOTES, 'UTF-8') ?></small>
                        <?php endif; ?>
                        <?php foreach ((array) ($item['capabilities_warnings'] ?? []) as $capWarn): ?>
                            <small class="d-block text-warning mt-1"><?= htmlspecialchars((string) $capWarn, ENT_QUOTES, 'UTF-8') ?></small>
                        <?php endforeach; ?>
                    </td>
                    <td>
                        <?php
                        $compatState = strtolower((string) ($item['compatibility_state'] ?? 'unknown'));
                        $compatClass = match ($compatState) {
                            'compatible' => 'text-bg-success',
                            'compatible_with_warning' => 'text-bg-warning',
                            'incompatible' => 'text-bg-danger',
                            default => 'text-bg-secondary',
                        };
                        ?>
                        <span class="badge <?= $compatClass ?>"><?= htmlspecialchars($compatState, ENT_QUOTES, 'UTF-8') ?></span>
                        <?php if (!$compatible): ?>
                            <ul class="mb-0 ps-3 small mt-1">
                                <?php foreach ((array) ($item['compat_errors'] ?? []) as $errorRow): ?>
                                    <li><?= htmlspecialchars((string) $errorRow, ENT_QUOTES, 'UTF-8') ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                        <?php foreach ((array) ($item['compat_warnings'] ?? []) as $warningRow): ?>
                            <small class="d-block text-warning mt-1"><?= htmlspecialchars((string) $warningRow, ENT_QUOTES, 'UTF-8') ?></small>
                        <?php endforeach; ?>
                        <small class="d-block text-body-secondary mt-1">
                            min: <?= htmlspecialchars((string) (($item['catmin_min'] ?? '') !== '' ? $item['catmin_min'] : '-'), ENT_QUOTES, 'UTF-8') ?>
                            · max: <?= htmlspecialchars((string) (($item['catmin_max'] ?? '') !== '' ? $item['catmin_max'] : '-'), ENT_QUOTES, 'UTF-8') ?>
                        </small>
                    </td>
                    <td>
                        <?php
                        $integrityBadge = match ($integrityStatus) {
                            'valid' => 'text-bg-success',
                            'tampered', 'invalid' => 'text-bg-danger',
                            'missing_checksums', 'unsupported_schema' => 'text-bg-warning',
                            default => 'text-bg-secondary',
                        };
                        ?>
                        <span class="badge <?= $integrityBadge ?>"><?= htmlspecialchars($integrityStatus, ENT_QUOTES, 'UTF-8') ?></span>
                    </td>
                    <td>
                        <?php
                        $signatureBadge = match ($signatureStatus) {
                            'signed_valid' => 'text-bg-success',
                            'unknown_key' => 'text-bg-warning',
                            'revoked_key' => 'text-bg-danger',
                            'unsigned', 'n/a' => 'text-bg-secondary',
                            default => 'text-bg-danger',
                        };
                        ?>
                        <span class="badge <?= $signatureBadge ?>"><?= htmlspecialchars($signatureStatus, ENT_QUOTES, 'UTF-8') ?></span>
                        <?php if ($signatureStatus === 'signed_valid'): ?>
                            <small class="d-block text-body-secondary mt-1"><?= htmlspecialchars('scope: ' . $keyScope . ' · status: ' . $keyStatus, ENT_QUOTES, 'UTF-8') ?></small>
                        <?php endif; ?>
                        <?php if ($keyScope === 'local_only'): ?>
                            <small class="d-block text-warning-emphasis mt-1"><?= htmlspecialchars(__('trust.keys.local_warning'), ENT_QUOTES, 'UTF-8') ?></small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php $trustScore = is_array($item['trust_score'] ?? null) ? $item['trust_score'] : []; ?>
                        <?php $score = (int) ($trustScore['score'] ?? 0); ?>
                        <span class="badge <?= $score >= 85 ? 'text-bg-success' : ($score >= 60 ? 'text-bg-warning' : 'text-bg-danger') ?>">
                            <?= $score ?>/100
                        </span>
                        <small class="d-block text-body-secondary mt-1"><?= htmlspecialchars((string) ($trustScore['explain'] ?? ''), ENT_QUOTES, 'UTF-8') ?></small>
                    </td>
                    <td>
                        <?php if (!$installed): ?>
                            <span class="badge text-bg-secondary"><?= htmlspecialchars(__('market.status.not_installed'), ENT_QUOTES, 'UTF-8') ?></span>
                        <?php elseif ($hasUpdate): ?>
                            <span class="badge text-bg-warning"><?= htmlspecialchars(__('market.status.updates'), ENT_QUOTES, 'UTF-8') ?></span>
                        <?php elseif ((bool) ($item['enabled'] ?? false)): ?>
                            <span class="badge text-bg-success"><?= htmlspecialchars(__('common.active'), ENT_QUOTES, 'UTF-8') ?></span>
                        <?php else: ?>
                            <span class="badge text-bg-warning"><?= htmlspecialchars(__('common.inactive'), ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end">
                        <form method="post" action="<?= htmlspecialchars($adminBase . '/modules/market/install', ENT_QUOTES, 'UTF-8') ?>" class="d-inline">
                            <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                            <input type="hidden" name="scope" value="<?= htmlspecialchars($moduleScope, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="slug" value="<?= htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="repository_slug" value="<?= htmlspecialchars((string) ($item['repo_slug'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                            <button type="submit" class="btn btn-sm <?= $hasUpdate ? 'btn-warning' : 'btn-primary' ?>" <?= (!$compatible || !((bool) ($item['install_allowed'] ?? true))) ? 'disabled' : '' ?>>
                                <?= htmlspecialchars($hasUpdate ? __('market.action.update') : __('market.action.install'), ENT_QUOTES, 'UTF-8') ?>
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
$content = (string) ob_get_clean();
require CATMIN_ADMIN . '/views/layouts/admin.php';
