<?php

declare(strict_types=1);

use Core\security\CsrfManager;

$profile = isset($profile) && is_array($profile) ? $profile : [];
$users = isset($users) && is_array($users) ? $users : [];
$message = isset($message) ? trim((string) $message) : '';
$messageType = isset($messageType) ? trim((string) $messageType) : 'info';
$tr = isset($tr) && is_array($tr) ? $tr : [];
$adminBase = isset($adminBase) ? (string) $adminBase : '/admin';

$pageTitle = (string) ($tr['edit_profile'] ?? 'Modifier auteur');
$pageDescription = (string) ($tr['description'] ?? 'Gestion des auteurs');
$activeNav = 'author-bridge';
$breadcrumbs = [
    ['label' => __('common.admin'), 'href' => $adminBase . '/'],
    ['label' => 'Organisation'],
    ['label' => (string) ($tr['title'] ?? 'Auteurs'), 'href' => $adminBase . '/modules/author-bridge'],
    ['label' => $pageTitle],
];

ob_start();
?>

<?php if ($message !== ''): ?>
<div class="alert alert-<?= htmlspecialchars($messageType, ENT_QUOTES, 'UTF-8') ?> mb-3">
  <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
</div>
<?php endif; ?>

<section class="card mb-3">
  <div class="cat-staff-manage-bar">
    <span class="small cat-staff-manage-bar-label"><?= htmlspecialchars((string) ($tr['edit_profile'] ?? 'Modifier auteur'), ENT_QUOTES, 'UTF-8') ?></span>
    <a class="btn btn-outline-secondary cat-staff-manage-cta" href="<?= htmlspecialchars($adminBase . '/modules/author-bridge', ENT_QUOTES, 'UTF-8') ?>">
      <?= htmlspecialchars((string) ($tr['btn_back_list'] ?? 'Retour liste'), ENT_QUOTES, 'UTF-8') ?>
    </a>
  </div>
</section>

<?php
$csrf = (new CsrfManager())->token();
$action = $adminBase . '/modules/author-bridge/profile/update';
$submitLabel = (string) ($tr['btn_save'] ?? 'Enregistrer');
$mode = 'edit';
$cancelHref = $adminBase . '/modules/author-bridge';
require __DIR__ . '/partials/form.php';
?>

<script src="<?= htmlspecialchars($adminBase . '/modules/author-bridge/assets/admin.js?v=1', ENT_QUOTES, 'UTF-8') ?>"></script>

<?php
$content = (string) ob_get_clean();
require CATMIN_ADMIN . '/views/layouts/admin.php';
