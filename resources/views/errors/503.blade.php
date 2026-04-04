@php
    $siteName = setting('site.name', config('app.name', 'CATMIN'));
@endphp
<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Service indisponible – {{ $siteName }}</title>
    <meta name="robots" content="noindex,nofollow">
    <link rel="stylesheet"
          href="{{ config('catmin_frontend.bootstrap_css', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css') }}"
          crossorigin="anonymous">
    @vite('resources/css/frontend.css')
</head>
<body>
    <main class="cf-main d-flex align-items-center justify-content-center" style="min-height:100vh">
        <div class="text-center">
            <p class="display-1 fw-bold text-muted">503</p>
            <h1 class="h3 mb-3">{{ $siteName }} est temporairement indisponible</h1>
            <p class="text-muted mb-0">Une opération de maintenance est en cours. Merci de revenir dans quelques instants.</p>
        </div>
    </main>
</body>
</html>
