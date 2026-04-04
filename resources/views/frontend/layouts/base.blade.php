<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- SEO meta injected per view --}}
    <title>@yield('meta_title', $seo['title'] ?? config('app.name', 'CATMIN'))</title>
    <meta name="description" content="@yield('meta_description', $seo['description'] ?? '')">
    <meta name="robots" content="{{ $seo['robots'] ?? 'index,follow' }}">
    <link rel="canonical" href="@yield('canonical', $seo['canonical'] ?? url()->current())">

    {{-- Open Graph --}}
    <meta property="og:title" content="{{ $seo['og']['title'] ?? '' }}">
    <meta property="og:description" content="{{ $seo['og']['description'] ?? '' }}">
    <meta property="og:type" content="{{ $seo['og']['type'] ?? 'website' }}">
    <meta property="og:url" content="{{ $seo['og']['url'] ?? url()->current() }}">
    <meta property="og:site_name" content="{{ $seo['og']['site_name'] ?? '' }}">
    @if(!empty($seo['og']['image']))
        <meta property="og:image" content="{{ $seo['og']['image'] }}">
    @endif

    {{-- External CSS — Bootstrap 5 from CDN. NO admin bundle loaded here. --}}
    <link rel="stylesheet"
          href="{{ config('catmin_frontend.bootstrap_css') }}"
          crossorigin="anonymous">

    {{-- Site-specific frontend CSS overrides (compiled by Vite, no admin classes) --}}
    @vite('resources/css/frontend.css')

    {{-- Per-view head stack (Leaflet CSS, etc.) --}}
    @stack('head_css')

    @yield('head_extra')
</head>
<body>

    {{-- ── Navigation ──────────────────────────────────────────────── --}}
    @include('frontend.partials.nav')

    {{-- ── Flash messages ──────────────────────────────────────────── --}}
    @if(session('success') || session('error') || session('info'))
        <div class="container mt-3">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible cf-flash" role="alert" data-autohide="6000">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible cf-flash" role="alert" data-autohide="8000">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
                </div>
            @endif
            @if(session('info'))
                <div class="alert alert-info alert-dismissible cf-flash" role="alert" data-autohide="6000">
                    {{ session('info') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
                </div>
            @endif
        </div>
    @endif

    {{-- ── Main content ─────────────────────────────────────────────── --}}
    <main class="cf-main" id="main-content">
        @yield('content')
    </main>

    {{-- ── Footer ───────────────────────────────────────────────────── --}}
    @include('frontend.partials.footer')

    {{-- Back-to-top button --}}
    <button id="cf-back-to-top"
            class="btn btn-secondary btn-sm d-none"
            style="position:fixed;bottom:1.5rem;right:1.5rem;z-index:999"
            aria-label="Retour en haut">↑</button>

    {{-- External JS — Bootstrap Bundle from CDN. NO admin JS. --}}
    <script src="{{ config('catmin_frontend.bootstrap_js') }}" crossorigin="anonymous"></script>

    {{-- Site-specific frontend JS (no admin code) --}}
    @vite('resources/js/frontend.js')

    {{-- Per-view scripts (Leaflet, etc.) --}}
    @stack('foot_js')

</body>
</html>
