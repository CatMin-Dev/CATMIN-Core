<?php if ($breadcrumbs !== []): ?>
    <nav aria-label="breadcrumb" class="cat-breadcrumb-wrap">
        <ol class="breadcrumb mb-0">
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
