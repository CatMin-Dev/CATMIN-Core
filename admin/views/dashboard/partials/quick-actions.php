<?php

declare(strict_types=1);

$actions = [
    ['label' => __('dashboard.quick_actions.add_admin'), 'href' => ($adminBase ?? '/admin') . '/staff/create', 'class' => 'btn-outline-secondary'],
    ['label' => __('dashboard.quick_actions.roles_permissions'), 'href' => ($adminBase ?? '/admin') . '/roles', 'class' => 'btn-outline-secondary'],
    ['label' => __('dashboard.quick_actions.view_logs'), 'href' => '#', 'class' => 'btn-outline-secondary'],
    ['label' => __('dashboard.quick_actions.run_backup'), 'href' => '#', 'class' => 'btn-outline-secondary'],
];
?>
<section class="card h-100">
    <div class="card-header bg-transparent border-0 pt-3">
        <h3 class="h6 mb-0"><?= htmlspecialchars(__('dashboard.quick_actions.title'), ENT_QUOTES, 'UTF-8') ?></h3>
    </div>
    <div class="card-body pt-2">
        <?php if ($actions === []): ?>
            <?php
            $title = __('dashboard.quick_actions.empty_title');
            $description = __('dashboard.quick_actions.empty_description');
            require CATMIN_ADMIN . '/views/components/empty-states/basic.php';
            ?>
        <?php else: ?>
            <div class="d-grid gap-2">
                <?php foreach ($actions as $action): ?>
                    <a href="<?= htmlspecialchars((string) $action['href'], ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm text-start <?= htmlspecialchars((string) $action['class'], ENT_QUOTES, 'UTF-8') ?>">
                        <?= htmlspecialchars((string) $action['label'], ENT_QUOTES, 'UTF-8') ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
