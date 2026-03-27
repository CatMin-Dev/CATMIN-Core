<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $page->title }} - {{ $siteName }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-body-tertiary">
    <main class="container py-5">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-9">
                <article class="card shadow-sm">
                    <div class="card-body p-4 p-lg-5">
                        <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                            <div>
                                <h1 class="h3 mb-1">{{ $page->title }}</h1>
                                <p class="text-muted mb-0">Slug: {{ $page->slug }}</p>
                            </div>
                            <a href="{{ $siteUrl }}" class="btn btn-outline-secondary btn-sm">Retour accueil</a>
                        </div>

                        <hr>

                        <div class="mt-3">
                            {!! nl2br(e((string) $page->content)) !!}
                        </div>
                    </div>
                </article>
            </div>
        </div>
    </main>
</body>
</html>
