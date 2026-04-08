<?php

declare(strict_types=1);

$pageTitle = 'Roles & Permissions';
$pageDescription = 'Gestion des roles, criticite et securisation explicite des droits.';
$activeNav = 'roles';
$breadcrumbs = [
    ['label' => 'Admin', 'href' => $adminBase . '/'],
    ['label' => 'Roles & Permissions'],
];
$pageActions = [
    ['label' => 'Creer un role', 'href' => $adminBase . '/roles/create', 'class' => 'btn btn-primary btn-sm'],
];

ob_start();
?>
<?php if (!empty($message)): ?>
    <?php
    $messageText = (string) $message;
    $message = $messageText;
    $type = (string) ($messageType ?? 'success');
    $dismissible = true;
    require CATMIN_ADMIN . '/views/components/alerts/inline.php';
    ?>
<?php endif; ?>
<?php require __DIR__ . '/partials/table.php'; ?>
<script src="/assets/js/catmin-roles.js?v=2"></script>
<?php
$content = (string) ob_get_clean();
require CATMIN_ADMIN . '/views/layouts/admin.php';
