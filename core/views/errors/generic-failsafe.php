<?php
declare(strict_types=1);
$status = $status ?? 500;
$title = $title ?? 'Failsafe';
$message = $message ?? 'Une erreur inattendue est survenue.';
require __DIR__ . '/_layout.php';

