<?php
declare(strict_types=1);

if ($toastMessage === '') {
    return;
}

$normalizedType = match (strtolower($toastType)) {
    'success' => 'success',
    'warning' => 'warning',
    'danger', 'error' => 'danger',
    default => 'info',
};

$toneLabel = match ($normalizedType) {
    'success' => 'Succès',
    'warning' => 'Attention',
    'danger' => 'Erreur',
    default => 'Info',
};

$toneIcon = match ($normalizedType) {
    'success' => 'bi-check-circle-fill',
    'warning' => 'bi-exclamation-triangle-fill',
    'danger' => 'bi-x-octagon-fill',
    default => 'bi-info-circle-fill',
};

$delayMs = 4200;
?>
<div class="toast-container position-fixed bottom-0 end-0 p-3 cat-toast-container">
    <div
        class="toast cat-toast cat-toast--<?= htmlspecialchars($normalizedType, ENT_QUOTES, 'UTF-8') ?> border-0"
        role="status"
        aria-live="polite"
        aria-atomic="true"
        data-bs-autohide="true"
        data-bs-delay="<?= $delayMs ?>"
        data-cat-toast
        data-cat-toast-delay="<?= $delayMs ?>"
    >
        <div class="toast-header">
            <span class="cat-toast-icon"><i class="bi <?= htmlspecialchars($toneIcon, ENT_QUOTES, 'UTF-8') ?>"></i></span>
            <strong class="me-auto"><?= htmlspecialchars($toneLabel, ENT_QUOTES, 'UTF-8') ?></strong>
            <button type="button" class="btn-close btn-close-white ms-2" data-bs-dismiss="toast" aria-label="Fermer"></button>
        </div>
        <div class="toast-body"><?= htmlspecialchars($toastMessage, ENT_QUOTES, 'UTF-8') ?></div>
        <div class="cat-toast-progress" data-cat-toast-progress></div>
    </div>
</div>
