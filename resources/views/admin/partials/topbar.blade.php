@php($adminUsername = session('catmin_admin_username', config('catmin.admin.username')))
@php($topbar = app(\App\Services\AdminUi\AdminTopbarService::class)->build())
@php($context = (array) ($topbar['context'] ?? []))

<header class="catmin-topbar">
    <div class="catmin-topbar__inner d-flex align-items-center justify-content-between px-3 px-lg-4 gap-3">
        <div class="d-flex align-items-center gap-3 min-w-0">
            <div class="d-none d-lg-block">
                @include('admin.components.topbar.breadcrumbs', ['breadcrumbs' => (array) ($context['breadcrumbs'] ?? [])])
            </div>

            <div>
                <div class="fw-semibold">{{ $context['title'] ?? 'Administration' }}</div>
                @if(!empty($context['subtitle']))<div class="small text-muted">{{ $context['subtitle'] }}</div>@endif
            </div>

            @include('admin.components.topbar.actions', ['actions' => (array) ($topbar['context_actions'] ?? [])])

            <div class="catmin-command-surface d-none d-xl-block">
                <input type="search" class="form-control form-control-sm" placeholder="Aller a..." data-topbar-command-input>
                <div class="catmin-command-surface__results" data-topbar-command-results>
                    @foreach(($topbar['command_items'] ?? []) as $cmd)
                        <a href="{{ $cmd['url'] }}" class="catmin-command-item" data-command-item>
                            <span>{{ $cmd['label'] }}</span>
                            <small>{{ $cmd['category'] }}</small>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="d-flex align-items-center gap-2">
            @foreach(($topbar['system_badges'] ?? []) as $badge)
                <span class="badge text-bg-{{ $badge['tone'] ?? 'secondary' }}">{{ $badge['label'] ?? '' }}</span>
            @endforeach

            <a class="btn btn-outline-primary btn-sm" href="{{ config('app.url') }}" target="_blank" rel="noreferrer noopener">Voir le site</a>

            @if(\App\Services\ModuleManager::isEnabled('notifications'))
                @include('module_notifications::partials.bell-dropdown')
            @else
                <div class="dropdown">
                    <button class="btn btn-outline-secondary btn-sm position-relative" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-bell"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end shadow-sm">
                        <div class="dropdown-item-text text-muted small">Module notifications inactif.</div>
                    </div>
                </div>
            @endif

            @if(!empty($topbar['global_actions']))
                <div class="dropdown">
                    <button class="btn btn-outline-secondary btn-sm" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-lightning-charge me-1"></i>Actions
                    </button>
                    <div class="dropdown-menu dropdown-menu-end shadow-sm">
                        @foreach($topbar['global_actions'] as $action)
                            <a class="dropdown-item" href="{{ $action['url'] ?? '#' }}">{{ $action['label'] ?? 'Action' }}</a>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="dropdown">
                <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    {{ $adminUsername }}
                </button>
                <div class="dropdown-menu dropdown-menu-end shadow-sm">
                    @if(catmin_can('module.core.list'))
                        <a class="dropdown-item" href="{{ admin_route('profile.show') }}">Profil</a>
                    @endif
                    @if(catmin_can('module.settings.list'))
                        <a class="dropdown-item" href="{{ admin_route('settings.index') }}">Parametres</a>
                    @endif
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
