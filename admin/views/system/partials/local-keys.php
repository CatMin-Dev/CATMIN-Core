<?php

declare(strict_types=1);
?>
<section class="card mb-3">
    <div class="card-header bg-transparent border-0 pt-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
        <h3 class="h6 mb-0"><?= htmlspecialchars(__('trust.local_keys.title'), ENT_QUOTES, 'UTF-8') ?></h3>
        <form method="post" action="<?= htmlspecialchars($adminBase . '/system/trust-center/sync', ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string) ($_SESSION['csrf_token'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            <button type="submit" class="btn btn-sm btn-outline-primary" <?= !((bool) ($snapshot['sync_enabled'] ?? false)) ? 'disabled' : '' ?>>
                <?= htmlspecialchars(__('trust.actions.sync'), ENT_QUOTES, 'UTF-8') ?>
            </button>
        </form>
    </div>
    <div class="card-body pt-2">
        <p class="text-body-secondary small mb-3"><?= htmlspecialchars(__('trust.local_keys.help'), ENT_QUOTES, 'UTF-8') ?></p>

        <form method="post" action="<?= htmlspecialchars($adminBase . '/system/trust-center/local-keys/add', ENT_QUOTES, 'UTF-8') ?>" class="row g-2">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string) ($_SESSION['csrf_token'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            <div class="col-12 col-lg-3">
                <label class="form-label"><?= htmlspecialchars(__('trust.keys.key_id'), ENT_QUOTES, 'UTF-8') ?></label>
                <input type="text" class="form-control" name="key_id" placeholder="local-team-key-001" required>
            </div>
            <div class="col-12 col-lg-3">
                <label class="form-label"><?= htmlspecialchars(__('trust.keys.publisher'), ENT_QUOTES, 'UTF-8') ?></label>
                <input type="text" class="form-control" name="publisher" placeholder="team-local" required>
            </div>
            <div class="col-12 col-lg-6">
                <label class="form-label"><?= htmlspecialchars(__('trust.local_keys.public_key'), ENT_QUOTES, 'UTF-8') ?></label>
                <textarea class="form-control" name="public_key" rows="3" placeholder="-----BEGIN PUBLIC KEY-----" required></textarea>
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-primary"><?= htmlspecialchars(__('trust.actions.add_local'), ENT_QUOTES, 'UTF-8') ?></button>
            </div>
        </form>
    </div>
</section>
