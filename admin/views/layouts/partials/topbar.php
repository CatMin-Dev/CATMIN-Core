<?php
$userLabel = (string) ($user['username'] ?? $user['email'] ?? 'admin');
$topIconSvg = static function (string $name): string {
    return match ($name) {
        'menu' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16"/></svg>',
        'search' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>',
        'bell' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 17H5l2-2v-4a5 5 0 1 1 10 0v4l2 2h-4"/><path d="M9 17a3 3 0 0 0 6 0"/></svg>',
        'apps' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="4" width="6" height="6"/><rect x="14" y="4" width="6" height="6"/><rect x="4" y="14" width="6" height="6"/><rect x="14" y="14" width="6" height="6"/></svg>',
        'settings' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 0 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 0 1-4 0v-.2a1.7 1.7 0 0 0-1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 0 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 0 1 0-4h.2a1.7 1.7 0 0 0 1.5-1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 0 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3h.1a1.7 1.7 0 0 0 1-1.5V3a2 2 0 0 1 4 0v.2a1.7 1.7 0 0 0 1 1.5h.1a1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 0 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8v.1a1.7 1.7 0 0 0 1.5 1H21a2 2 0 0 1 0 4h-.2a1.7 1.7 0 0 0-1.5 1z"/></svg>',
        'moon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12.8A9 9 0 1 1 11.2 3 7 7 0 0 0 21 12.8z"/></svg>',
        'fullscreen' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 3H3v5M16 3h5v5M8 21H3v-5M21 21h-5v-5"/></svg>',
        'user' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>',
        default => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="8"/></svg>',
    };
};
?>
<header class="cat-topbar border-bottom">
    <div class="cat-topbar-left">
        <button type="button" class="cat-icon-btn" data-cat-sidebar-toggle aria-label="Menu">
            <?= $topIconSvg('menu') ?>
        </button>

        <form class="cat-search-form" role="search" data-cat-search-form>
            <span class="cat-search-icon"><?= $topIconSvg('search') ?></span>
            <input type="search" class="form-control cat-topbar-search" placeholder="Search...">
            <button type="button" class="btn btn-primary btn-sm cat-search-submit">Search</button>
        </form>
    </div>

    <div class="cat-topbar-right">
        <div class="dropdown">
            <button class="cat-topbar-link dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <span aria-hidden="true">🇺🇸</span> English
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="#">Francais</a></li>
                <li><a class="dropdown-item" href="#">English</a></li>
            </ul>
        </div>

        <button type="button" class="cat-icon-btn has-dot" aria-label="Notifications">
            <?= $topIconSvg('bell') ?>
        </button>
        <button type="button" class="cat-icon-btn" aria-label="Apps">
            <?= $topIconSvg('apps') ?>
        </button>
        <button type="button" class="cat-icon-btn" aria-label="Settings">
            <?= $topIconSvg('settings') ?>
        </button>
        <button type="button" class="cat-icon-btn" data-cat-theme-toggle aria-label="Theme">
            <?= $topIconSvg('moon') ?>
        </button>
        <button type="button" class="cat-icon-btn" aria-label="Fullscreen">
            <?= $topIconSvg('fullscreen') ?>
        </button>

        <div class="cat-user-card">
            <span class="cat-user-avatar"><?= $topIconSvg('user') ?></span>
            <span class="cat-user-meta">
                <strong><?= htmlspecialchars($userLabel, ENT_QUOTES, 'UTF-8') ?></strong>
                <small>Founder</small>
            </span>
        </div>
    </div>
</header>
