<!doctype html>
<html lang="fr">
<head>
    @php($seo = seo_meta_payload('pages', $page->id, [
        'title' => $page->title . ' - ' . $siteName,
        'description' => \Illuminate\Support\Str::limit(strip_tags((string) $page->content), 160),
        'og_type' => 'article',
    ]))
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
                <article class="card shadow-sm">
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
