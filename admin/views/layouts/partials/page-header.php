<section class="cat-page-header">
    <div>
        <h1 class="cat-page-title mb-0"><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></h1>
    </div>
    <?php if ($breadcrumbs !== []): ?>
        <nav aria-label="breadcrumb" class="cat-page-breadcrumb">
            <ol class="breadcrumb mb-0 justify-content-end">
                <?php foreach ($breadcrumbs as $index => $crumb): ?>
                    <?php
                    $label = (string) ($crumb['label'] ?? '');
                    $href = $crumb['href'] ?? null;
                    $isLast = $index === array_key_last($breadcrumbs);
                    ?>
                    <li class="breadcrumb-item <?= $isLast ? 'active' : '' ?>" <?= $isLast ? 'aria-current="page"' : '' ?>>
                        <?php if (!$isLast && is_string($href) && $href !== ''): ?>
                            <a href="<?= htmlspecialchars($href, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></a>
                        <?php else: ?>
                            <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ol>
        </nav>
    <?php endif; ?>
</section>
