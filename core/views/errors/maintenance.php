<?php
declare(strict_types=1);
$status = $status ?? 503;
$title = $title ?? 'Maintenance';
$message = $message ?? 'Le service est temporairement en maintenance.';
require __DIR__ . '/_layout.php';

