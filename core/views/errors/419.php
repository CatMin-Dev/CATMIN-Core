<?php
declare(strict_types=1);
$title = $title ?? 'Session expirée';
$message = $message ?? 'Le token CSRF est invalide ou expiré. Recharge la page et réessaie.';
require __DIR__ . '/_layout.php';

