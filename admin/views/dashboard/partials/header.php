<?php

declare(strict_types=1);

$userName = trim((string) (($user['username'] ?? $user['email'] ?? 'admin')));
$welcome = 'Bienvenue ' . ($userName !== '' ? $userName : 'admin') . ', voici le resume de ton instance CATMIN.';
?>
<section class="card">
    <div class="card-body d-flex flex-wrap justify-content-between align-items-start gap-3">
        <div>
            <h2 class="h5 mb-1">Dashboard</h2>
            <p class="text-body-secondary mb-0"><?= htmlspecialchars($welcome, ENT_QUOTES, 'UTF-8') ?></p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-primary btn-sm" href="#catQuickActions">Actions rapides</a>
            <a class="btn btn-outline-secondary btn-sm" href="#catNotificationsPanel">Notifications</a>
        </div>
    </div>
</section>
