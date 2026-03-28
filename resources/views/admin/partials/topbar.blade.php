@php($adminUsername = session('catmin_admin_username', config('catmin.admin.username')))

<header class="catmin-topbar">
    <div class="catmin-topbar__inner d-flex align-items-center justify-content-end px-3 px-lg-4 gap-3">
        <div class="d-flex align-items-center gap-3">
            <a class="btn btn-outline-primary btn-sm" href="{{ config('app.url') }}" target="_blank" rel="noreferrer noopener">
                Voir le site
            </a>

            <div class="dropdown">
                <button class="btn btn-outline-secondary btn-sm position-relative" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-bell"></i>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill text-bg-primary">0</span>
                </button>
                <div class="dropdown-menu dropdown-menu-end shadow-sm">
                    <div class="dropdown-item-text text-muted small">Aucune notification.</div>
                </div>
            </div>

            <div class="dropdown">
                <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    {{ $adminUsername }}
                </button>
                <div class="dropdown-menu dropdown-menu-end shadow-sm">
                    <a class="dropdown-item" href="{{ admin_route('users.index') }}">Profil</a>
                    <a class="dropdown-item" href="{{ admin_route('settings.index') }}">Parametres</a>
                    <div class="dropdown-divider"></div>
                    <form action="{{ admin_route('logout') }}" method="POST" class="m-0">
                        @csrf
                        <button type="submit" class="dropdown-item text-danger">Se deconnecter</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>
