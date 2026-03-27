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

            @if(session('catmin_rbac_preview_active'))
                <div class="position-fixed bottom-0 start-50 translate-middle-x mb-3 px-3" style="z-index: 1080; width: min(960px, calc(100% - 1.5rem));">
                    <div class="alert alert-warning shadow-lg border-warning d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-2 mb-0">
                        <div>
                            <strong class="me-2"><i class="bi bi-eye-fill me-1"></i>Mode apercu role actif</strong>
                            <span>Vue actuelle: <strong>{{ session('catmin_rbac_preview_role_name', 'Role') }}</strong></span>
                            <span class="text-muted ms-2">(permissions temporaires)</span>
                        </div>
                        <form method="post" action="{{ admin_route('roles.preview.stop') }}" class="m-0">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-dark" type="submit">
                                <i class="bi bi-arrow-counterclockwise me-1"></i>Quitter l'apercu
                            </button>
                        </form>
                    </div>
                </div>
            @endif

            @include('admin.partials.footer')
        </div>
    </div>

    @stack('scripts')
</body>
</html>
