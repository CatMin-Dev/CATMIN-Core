<?php
declare(strict_types=1);

$message = isset($message) ? (string) $message : '';
$type = isset($type) ? (string) $type : 'info';
$dismissible = !empty($dismissible);
$class = match ($type) {
    'success' => 'alert-success',
    'warning' => 'alert-warning',
    'danger' => 'alert-danger',
    default => 'alert-info',
};
?>
<?php if ($message !== ''): ?>
    <div class="alert <?= $class ?> <?= $dismissible ? 'alert-dismissible fade show' : '' ?>" role="alert">
        <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
        <?php if ($dismissible): ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        <?php endif; ?>
    </div>
<?php endif; ?>
