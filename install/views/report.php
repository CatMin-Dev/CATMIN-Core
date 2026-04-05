<?php

declare(strict_types=1);

$completed = is_object($context) && method_exists($context, 'completed') ? $context->completed() : [];
$metaReport = is_object($context) && method_exists($context, 'meta') ? $context->meta('report_path', '') : '';
?><!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>CATMIN Installer Report</title>
    <link rel="stylesheet" href="/assets/vendor/bootstrap/5.3.8/css/bootstrap.min.css">
    <link rel="stylesheet" href="/odin-color.css">
</head>
<body class="container py-4">
    <h1 class="mb-3">Installer Report</h1>
    <p>Completed steps: <strong><?= htmlspecialchars(implode(', ', array_map('strval', is_array($completed) ? $completed : [])), ENT_QUOTES, 'UTF-8') ?></strong></p>
    <?php if (is_string($metaReport) && $metaReport !== ''): ?>
        <p>Latest report: <code><?= htmlspecialchars($metaReport, ENT_QUOTES, 'UTF-8') ?></code></p>
    <?php endif; ?>
    <a class="btn btn-primary" href="/install/step?s=lock">Go to lock step</a>
    <a class="btn btn-outline-secondary" href="/install/step?s=report">Back to wizard</a>
</body>
</html>
