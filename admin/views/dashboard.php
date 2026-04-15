<?php

declare(strict_types=1);

use Core\versioning\Version;

$odinCssFile = dirname(__DIR__, 2) . '/public/odin-color.css';
$bootstrapMappingSnippet = '';
if (is_file($odinCssFile)) {
    $odinCss = (string) file_get_contents($odinCssFile);
    if (preg_match('/\/\*\s*Bootstrap mapping\s*\*\/(.*?)caret-color:\s*var\(--bs-primary\);/s', $odinCss, $matches)) {
        $bootstrapMappingSnippet = "/* Bootstrap mapping */\n" . trim((string) $matches[1]) . "\n\n    caret-color: var(--bs-primary);";
    }
}

$pageTitle = 'Dashboard';
$pageDescription = 'Vue d ensemble administration CATMIN.';
$activeNav = 'dashboard';
$breadcrumbs = [
    ['label' => 'Admin', 'href' => $adminBase . '/'],
    ['label' => 'Dashboard'],
];
$pageActions = [
    ['label' => 'Rapport', 'href' => '#', 'class' => 'btn btn-outline-secondary btn-sm'],
    ['label' => 'Quick Actions', 'href' => '#catQuickActions', 'class' => 'btn btn-primary btn-sm'],
];

ob_start();
?>
<section class="row g-3">
    <div class="col-12 col-xl-4">
        <article class="card h-100 border-2 cat-theme-card cat-theme-card-light">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h3 class="h6 mb-0">LIGHT</h3>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-theme-set="light">Appliquer</button>
                </div>
                <p class="small text-body-secondary mb-2">Mode clair base rose + stone.</p>
                <div class="d-flex flex-wrap gap-2">
                    <span class="badge rounded-pill cat-theme-chip cat-theme-chip-rose-500">rose-500</span>
                    <span class="badge rounded-pill cat-theme-chip cat-theme-chip-rose-600">rose-600</span>
                    <span class="badge rounded-pill border cat-theme-chip cat-theme-chip-stone-50">stone-50</span>
                    <span class="badge rounded-pill cat-theme-chip cat-theme-chip-stone-500">stone-500</span>
                </div>
            </div>
        </article>
    </div>
    <div class="col-12 col-xl-4">
        <article class="card h-100 border-2 cat-theme-card cat-theme-card-dark">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h3 class="h6 mb-0">DARK</h3>
                    <button type="button" class="btn btn-sm btn-outline-light" data-theme-set="dark">Appliquer</button>
                </div>
                <p class="small mb-2 cat-theme-card-dark-text">Mode sombre stone profond + accents rose.</p>
                <div class="d-flex flex-wrap gap-2">
                    <span class="badge rounded-pill cat-theme-chip cat-theme-chip-rose-500">rose-500</span>
                    <span class="badge rounded-pill cat-theme-chip cat-theme-chip-rose-700">rose-700</span>
                    <span class="badge rounded-pill cat-theme-chip cat-theme-chip-stone-700">stone-700</span>
                    <span class="badge rounded-pill cat-theme-chip cat-theme-chip-stone-900">stone-900</span>
                </div>
            </div>
        </article>
    </div>
    <div class="col-12 col-xl-4">
        <article class="card h-100 border-2 cat-theme-card cat-theme-card-corporate">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h3 class="h6 mb-0">CORPORATE</h3>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-theme-set="corporate">Appliquer</button>
                </div>
                <p class="small text-body-secondary mb-2">Mode pro equilibre pour production.</p>
                <div class="d-flex flex-wrap gap-2">
                    <span class="badge rounded-pill cat-theme-chip cat-theme-chip-rose-500">rose-500</span>
                    <span class="badge rounded-pill cat-theme-chip cat-theme-chip-rose-800">rose-800</span>
                    <span class="badge rounded-pill border cat-theme-chip cat-theme-chip-stone-100">stone-100</span>
                    <span class="badge rounded-pill cat-theme-chip cat-theme-chip-stone-600">stone-600</span>
                </div>
            </div>
        </article>
    </div>
</section>

<section class="card border-2 mt-3">
    <div class="card-body">
        <h2 class="h5 mb-3">Bootstrap Mapping Reel</h2>
        <h3 class="h6 mb-2">Mapping Declare (source CSS)</h3>
        <pre class="small p-3 rounded border bg-body-tertiary mb-3"><code><?= htmlspecialchars($bootstrapMappingSnippet !== '' ? $bootstrapMappingSnippet : 'Bloc "Bootstrap mapping" introuvable dans /public/odin-color.css', ENT_QUOTES, 'UTF-8') ?></code></pre>
        <p class="mb-3">Theme actif: <strong id="activeThemeLabel">corporate</strong></p>
        <div class="d-flex flex-wrap gap-2 mb-3">
            <span class="badge text-bg-primary">bg-primary</span>
            <span class="badge text-bg-secondary">bg-secondary</span>
            <span class="badge text-bg-success">bg-success</span>
            <span class="badge text-bg-warning">bg-warning</span>
            <span class="badge text-bg-danger">bg-danger</span>
            <span class="badge text-bg-info">bg-info</span>
            <span class="badge text-bg-light border">bg-light</span>
            <span class="badge text-bg-dark">bg-dark</span>
        </div>
        <div class="d-flex flex-wrap gap-2 mb-3">
            <span class="badge border border-primary text-primary">border-primary</span>
            <span class="badge border border-secondary text-secondary">border-secondary</span>
            <span class="badge border border-success text-success">border-success</span>
            <span class="badge border border-warning text-warning">border-warning</span>
            <span class="badge border border-danger text-danger">border-danger</span>
            <span class="badge border border-info text-info">border-info</span>
        </div>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead>
                <tr>
                    <th>Variable</th>
                    <th>Valeur brute</th>
                    <th>Valeur resolue</th>
                </tr>
                </thead>
                <tbody>
                <tr><td><code>--bs-primary</code></td><td><code id="v-bs-primary">-</code></td><td><code id="r-bs-primary">-</code></td></tr>
                <tr><td><code>--bs-secondary</code></td><td><code id="v-bs-secondary">-</code></td><td><code id="r-bs-secondary">-</code></td></tr>
                <tr><td><code>--bs-success</code></td><td><code id="v-bs-success">-</code></td><td><code id="r-bs-success">-</code></td></tr>
                <tr><td><code>--bs-warning</code></td><td><code id="v-bs-warning">-</code></td><td><code id="r-bs-warning">-</code></td></tr>
                <tr><td><code>--bs-danger</code></td><td><code id="v-bs-danger">-</code></td><td><code id="r-bs-danger">-</code></td></tr>
                <tr><td><code>--bs-info</code></td><td><code id="v-bs-info">-</code></td><td><code id="r-bs-info">-</code></td></tr>
                <tr><td><code>--bs-light</code></td><td><code id="v-bs-light">-</code></td><td><code id="r-bs-light">-</code></td></tr>
                <tr><td><code>--bs-dark</code></td><td><code id="v-bs-dark">-</code></td><td><code id="r-bs-dark">-</code></td></tr>
                <tr><td><code>--bs-body-bg</code></td><td><code id="v-bs-body-bg">-</code></td><td><code id="r-bs-body-bg">-</code></td></tr>
                <tr><td><code>--bs-body-color</code></td><td><code id="v-bs-body-color">-</code></td><td><code id="r-bs-body-color">-</code></td></tr>
                <tr><td><code>--bs-border-color</code></td><td><code id="v-bs-border-color">-</code></td><td><code id="r-bs-border-color">-</code></td></tr>
                </tbody>
            </table>
        </div>
    </div>
</section>

<section class="row g-3 mt-1">
    <div class="col-12 col-lg-4">
        <div class="card h-100">
            <div class="card-body">
                <h3 class="h6 mb-2">Utilisateur</h3>
                <p class="mb-1 text-body-secondary">Connecte en tant que:</p>
                <strong><?= htmlspecialchars((string) ($user['username'] ?? $user['email'] ?? 'admin'), ENT_QUOTES, 'UTF-8') ?></strong>
            </div>
        </div>
    </div>
    <div class="col-12 col-lg-4">
        <div class="card h-100">
            <div class="card-body">
                <h3 class="h6 mb-2">Etat auth</h3>
                <span class="badge text-bg-success">Auth admin native active</span>
            </div>
        </div>
    </div>
    <div class="col-12 col-lg-4">
        <div class="card h-100">
            <div class="card-body">
                <h3 class="h6 mb-2">Version</h3>
                <code><?= htmlspecialchars(Version::current(), ENT_QUOTES, 'UTF-8') ?></code>
            </div>
        </div>
    </div>
</section>

<script src="/assets/js/admin-theme-preview.js?v=3"></script>
<?php
$content = (string) ob_get_clean();

require __DIR__ . '/layouts/admin.php';
