<?php
declare(strict_types=1);

$title = isset($title) ? (string) $title : 'Aucune donnee';
$description = isset($description) ? (string) $description : 'Aucun element a afficher.';
?>
<div class="cat-empty-state text-center p-4 border rounded-3">
    <p class="fw-semibold mb-1"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></p>
    <p class="small text-body-secondary mb-0"><?= htmlspecialchars($description, ENT_QUOTES, 'UTF-8') ?></p>
</div>
