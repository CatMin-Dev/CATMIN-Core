<!doctype html>
<html lang="fr">
<head>
    @include('admin.partials.head')
    <title>CATMIN - @yield('page_title', 'Administration')</title>
</head>
<body>
    <div class="catmin-shell">
        @include('admin.partials.navigation')

        <div class="catmin-shell-main">
            @include('admin.partials.topbar')

            <main class="catmin-main" role="main">
                <div class="catmin-page px-3 px-lg-4 py-4">
                    @yield('content')
                </div>
            </main>

            @include('admin.partials.footer')
        </div>
    </div>

    @stack('scripts')
</body>
</html>
