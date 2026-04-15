<?php

declare(strict_types=1);

$adminBase = isset($adminBase) ? (string) $adminBase : '/admin';
$query = trim((string) ($query ?? ''));
$items = isset($items) && is_array($items) ? $items : [];

$pageTitle = __('topbar.search.results_title');
$pageDescription = __('topbar.search.results_description');
$activeNav = 'search';
$breadcrumbs = [
    ['label' => __('common.admin'), 'href' => $adminBase . '/'],
    ['label' => __('topbar.search.results_title')],
];

ob_start();
?>
<section class="card mb-3">
    <div class="card-body">
        <form method="get" action="<?= htmlspecialchars($adminBase . '/search', ENT_QUOTES, 'UTF-8') ?>" class="row g-2 align-items-end" role="search">
            <div class="col-12 col-lg-9">
                <label class="form-label"><?= htmlspecialchars(__('topbar.search.query_label'), ENT_QUOTES, 'UTF-8') ?></label>
                <input class="form-control" type="search" name="q" value="<?= htmlspecialchars($query, ENT_QUOTES, 'UTF-8') ?>" placeholder="<?= htmlspecialchars(__('topbar.search.placeholder'), ENT_QUOTES, 'UTF-8') ?>" autofocus>
            </div>
            <div class="col-12 col-lg-3 d-grid">
                <button class="btn btn-primary" type="submit"><?= htmlspecialchars(__('topbar.search.button'), ENT_QUOTES, 'UTF-8') ?></button>
            </div>
        </form>
    </div>
</section>

<section class="card">
    <div class="card-body">
        <?php if ($query !== ''): ?>
            <p class="text-body-secondary mb-3">
                <?= htmlspecialchars(__('topbar.search.results_for'), ENT_QUOTES, 'UTF-8') ?>
                <strong><?= htmlspecialchars($query, ENT_QUOTES, 'UTF-8') ?></strong>
                · <?= count($items) ?>
            </p>
        <?php endif; ?>

        <?php if ($items === []): ?>
            <div class="alert alert-info mb-0"><?= htmlspecialchars(__('topbar.search.no_results'), ENT_QUOTES, 'UTF-8') ?></div>
        <?php else: ?>
            <div class="d-grid gap-3">
                <?php foreach ($items as $item): ?>
                    <?php
                    $label = trim((string) ($item['label'] ?? ''));
                    $url = trim((string) ($item['url'] ?? ''));
                    $description = trim((string) ($item['description'] ?? ''));
                    $answer = trim((string) ($item['answer'] ?? ''));
                    $type = trim((string) ($item['type'] ?? 'page'));
                    $inputs = array_values(array_filter((array) ($item['inputs'] ?? []), static fn ($v): bool => trim((string) $v) !== ''));
                    if ($label === '' || $url === '') {
                        continue;
                    }
                    ?>
                    <article class="border rounded p-3">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-1">
                            <a class="fw-semibold text-decoration-none" href="<?= htmlspecialchars($url, ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                            </a>
                            <span class="badge text-bg-light text-uppercase"><?= htmlspecialchars($type, ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                        <?php if ($description !== ''): ?>
                            <p class="small text-body-secondary mb-1"><?= htmlspecialchars($description, ENT_QUOTES, 'UTF-8') ?></p>
                        <?php endif; ?>
                        <?php if ($answer !== ''): ?>
                            <p class="mb-2"><?= htmlspecialchars($answer, ENT_QUOTES, 'UTF-8') ?></p>
                        <?php endif; ?>
                        <?php if ($inputs !== []): ?>
                            <div class="d-flex flex-wrap gap-1 mb-2">
                                <?php foreach ($inputs as $inputName): ?>
                                    <span class="badge rounded-pill text-bg-secondary"><?= htmlspecialchars((string) $inputName, ENT_QUOTES, 'UTF-8') ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <div><a class="small" href="<?= htmlspecialchars($url, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($url, ENT_QUOTES, 'UTF-8') ?></a></div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
<?php
$content = (string) ob_get_clean();
$scripts = '';
require CATMIN_ADMIN . '/views/layouts/admin.php';
