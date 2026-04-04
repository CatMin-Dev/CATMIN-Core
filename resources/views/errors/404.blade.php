@php
    $siteName = setting('site.name', config('app.name', 'CATMIN'));
    $seo = ['title' => 'Page introuvable – ' . $siteName, 'description' => '', 'robots' => 'noindex,nofollow', 'canonical' => url()->current(), 'og' => ['title' => '', 'description' => '', 'type' => 'website', 'url' => url()->current(), 'site_name' => $siteName]];
@endphp
<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $seo['title'] }}</title>
    <meta name="robots" content="noindex,nofollow">
    <link rel="stylesheet"
          href="{{ config('catmin_frontend.bootstrap_css', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css') }}"
          crossorigin="anonymous">
    <link rel="stylesheet" href="{{ asset('css/frontend.css') }}">
</head>
<body>

    @include('frontend.partials.nav', ['siteName' => $siteName, 'primaryMenu' => menu_tree('primary')])

    <main class="cf-main">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-md-8 col-lg-6 text-center py-5">
                    <p class="display-1 fw-bold text-muted">404</p>
                    <h1 class="h3 mb-3">Page introuvable</h1>
                    <p class="text-muted mb-4">
                        La page que vous recherchez n'existe pas ou a été déplacée.
                    </p>
                    <a href="{{ route('frontend.home') }}" class="btn btn-primary">
                        Retour à l'accueil
                    </a>
                </div>
            </div>
        </div>
    </main>

    @include('frontend.partials.footer', ['siteName' => $siteName])

    <script src="{{ config('catmin_frontend.bootstrap_js', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js') }}"
            crossorigin="anonymous"></script>
    <script src="{{ asset('js/frontend.js') }}" defer></script>
</body>
</html>
