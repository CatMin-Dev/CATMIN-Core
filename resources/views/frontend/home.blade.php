<!doctype html>
<html lang="fr">
<head>
    @php($seo = seo_meta_payload(null, null, ['title' => $siteName]))
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $seo['title'] }}</title>
    <meta name="description" content="{{ $seo['description'] }}">
    <meta name="robots" content="{{ $seo['robots'] }}">
    <link rel="canonical" href="{{ $seo['canonical'] }}">
    <meta property="og:title" content="{{ $seo['og']['title'] }}">
    <meta property="og:description" content="{{ $seo['og']['description'] }}">
    <meta property="og:type" content="{{ $seo['og']['type'] }}">
    <meta property="og:url" content="{{ $seo['og']['url'] }}">
    <meta property="og:site_name" content="{{ $seo['og']['site_name'] }}">
    @if(!empty($seo['og']['image']))
        <meta property="og:image" content="{{ $seo['og']['image'] }}">
    @endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-body-tertiary">
    @php($primaryMenu = menu_tree('primary'))
    <main class="container py-5">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-9">
                <div class="card shadow-sm">
                    <div class="card-body p-4 p-lg-5">
                        @if($primaryMenu->isNotEmpty())
                            <nav class="mb-4">
                                <ul class="nav flex-column gap-2">
                                    @foreach($primaryMenu as $entry)
                                        <li class="nav-item">
                                            <a class="nav-link" href="{{ $entry['url'] }}">{{ $entry['label'] }}</a>
                                            @if(!empty($entry['children']))
                                                <ul class="nav ms-3 mt-1 flex-column">
                                                    @foreach($entry['children'] as $child)
                                                        <li class="nav-item">
                                                            <a class="nav-link small" href="{{ $child['url'] }}">{{ $child['label'] }}</a>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            </nav>
                        @endif

                        <h1 class="h3 mb-3">{{ $siteName }}</h1>
                        <p class="text-muted mb-4">Base frontend Catmin.</p>
                        <dl class="row mb-4">
                            <dt class="col-sm-3">URL</dt><dd class="col-sm-9">{{ $siteUrl }}</dd>
                            <dt class="col-sm-3">Chemin frontend</dt><dd class="col-sm-9">{{ $frontendConfig['path'] ?? 'site' }}</dd>
                            <dt class="col-sm-3">Frontend actif</dt><dd class="col-sm-9">{{ ($frontendConfig['enabled'] ?? false) ? 'Oui' : 'Non' }}</dd>
                        </dl>
                        <h2 class="h5 mb-3">Modules actifs</h2>
                        @if($enabledModules->isEmpty())
                            <div class="alert alert-secondary" role="alert">Aucun module actif.</div>
                        @else
                            <ul class="list-group mb-4">
                                @foreach($enabledModules as $module)
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span>{{ $module->name ?? ucfirst($module->slug) }}</span>
                                        <span class="badge text-bg-light">{{ $module->version ?? 'n/a' }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif

                        <h2 class="h5 mb-3">Contexte frontend utile</h2>
                        <div class="table-responsive mb-4">
                            <table class="table table-sm align-middle">
                                <tbody>
                                    <tr><th>Nom du site</th><td>{{ $frontendContext['site_name'] }}</td></tr>
                                    <tr><th>URL admin</th><td>{{ $frontendContext['admin_home_url'] }}</td></tr>
                                    <tr><th>Login admin</th><td>{{ $frontendContext['admin_login_url'] }}</td></tr>
                                    <tr><th>Frontend actif</th><td>{{ $frontendContext['frontend_enabled'] ? 'Oui' : 'Non' }}</td></tr>
                                    <tr><th>Page home publiee</th><td>{{ $homePage?->title ?? 'Aucune page slug=home' }}</td></tr>
                                    <tr><th>URL page home</th><td>{{ $homePage ? route('frontend.page', ['slug' => $homePage->slug]) : 'n/a' }}</td></tr>
                                </tbody>
                            </table>
                        </div>

                        <a href="{{ $frontendContext['admin_login_url'] }}" class="btn btn-primary">Acces administration</a>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
