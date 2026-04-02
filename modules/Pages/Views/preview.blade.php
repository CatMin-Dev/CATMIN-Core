<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Preview page - {{ $title }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-body-tertiary">
    <main class="container py-4 py-lg-5">
        <div class="row justify-content-center">
            <div class="col-12 col-xl-10">
                <article class="card shadow-sm">
                    <div class="card-body p-4 p-lg-5">
                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                            <div>
                                <div class="text-uppercase small text-muted">Preview admin</div>
                                <h1 class="h3 mb-1">{{ $title }}</h1>
                                @if(!empty($slug))
                                    <div class="text-muted small">Slug: {{ $slug }}</div>
                                @endif
                            </div>
                            <div class="d-flex gap-2">
                                <span class="badge {{ $status === 'published' ? 'text-bg-success' : ($status === 'scheduled' ? 'text-bg-warning' : 'text-bg-secondary') }}">
                                    {{ $status === 'published' ? 'Publie' : ($status === 'scheduled' ? 'Programme' : 'Brouillon') }}
                                </span>
                                <span class="badge text-bg-light border">{{ $publishedAt ?: 'sans date' }}</span>
                            </div>
                        </div>

                        @if(!empty($excerpt))
                            <p class="lead mb-3">{{ $excerpt }}</p>
                        @endif

                        <hr>

                        <div class="mt-3">
                            {!! (string) $renderedContent !!}
                        </div>
                    </div>
                </article>
            </div>
        </div>
    </main>
</body>
</html>
