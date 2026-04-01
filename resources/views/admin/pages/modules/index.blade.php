@extends('admin.layouts.catmin')

@section('page_title', 'Modules')

@section('content')
<header class="catmin-page-header">
    <h1 class="h3 mb-1">Modules</h1>
    <p class="text-muted mb-0">Catalogue des modules declares.</p>
</header>

<div class="catmin-page-body">
    @php
        $canManageModules = catmin_can('module.core.config');
        $canManageAddons = catmin_can('addon.enable') || catmin_can('addon.disable');
    @endphp

    @if(!empty($stateIssues ?? []))
        <div class="alert alert-warning" role="alert">
            <strong>Etat modules a surveiller :</strong>
            <ul class="mb-0 mt-2">
                @foreach(($stateIssues ?? []) as $issue)
                    <li>{{ $issue['message'] }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h2 class="h6 mb-0">Etat des modules</h2>
            <div class="d-flex align-items-center gap-2">
                <span class="badge text-bg-light">{{ $modules->count() }}</span>
                @if(($migratableEnabledCount ?? 0) > 0 && $canManageModules)
                    <form method="POST" action="{{ route('admin.modules.migrate-enabled') }}" onsubmit="return confirm('Lancer les migrations sur tous les modules actifs qui possèdent des migrations ?');" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-primary">
                            <i class="bi bi-database-gear me-1"></i>Migrer tout ({{ $migratableEnabledCount }})
                        </button>
                    </form>
                @endif
            </div>
        </div>
        <div class="table-responsive catmin-table-scroll">
            <table class="table table-striped table-hover align-middle mb-0">
                <thead><tr><th>Nom</th><th>Slug</th><th>Version</th><th>Type</th><th>Etat</th><th>Routes</th><th>Dependances</th><th>Actions</th></tr></thead>
                <tbody>
                    @forelse($modules as $module)
                        @php
                            $isSystemModule = in_array($module->slug, ['core']);
                            $moduleRouteInfo = ($routesInfo ?? [])[$module->slug] ?? null;
                            $hasRoutes = (bool) ($moduleRouteInfo['has_routes'] ?? false);
                            $mi = ($migrationInfo ?? [])[$module->slug] ?? [];
                            $hasMigrations = (bool) ($mi['has_migrations'] ?? false);
                            $hasUpgrade    = (bool) ($mi['has_upgrade'] ?? false);
                            $neverMigrated = (bool) ($mi['never_migrated'] ?? false);
                            $installedVersion = (string) ($mi['installed_version'] ?? '');
                            $hasConfig = (bool) (($configInfo ?? [])[$module->slug]['has_config'] ?? false);
                        @endphp
                        <tr>
                            <td>{{ $module->name }}</td>
                            <td>{{ $module->slug }}</td>
                            <td>
                                @php $versionValue = (string) ($module->version ?? 'n/a'); @endphp
                                <span class="badge {{ str_contains($versionValue, '-') ? 'text-bg-primary' : 'text-bg-light text-dark' }}">{{ $versionValue }}</span>
                                @if($hasUpgrade)
                                    <span class="badge text-bg-warning ms-1" title="Installé: {{ $installedVersion }}">⬆ Update</span>
                                @elseif($neverMigrated && $module->enabled)
                                    <span class="badge text-bg-info ms-1">DB ?</span>
                                @endif
                            </td>
                            <td>
                                @if($isSystemModule)
                                    <span class="badge text-bg-dark">Système</span>
                                @else
                                    <span class="badge text-bg-light text-dark">Optionnel</span>
                                @endif
                            </td>
                            <td><span class="badge {{ $module->enabled ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $module->enabled ? 'Actif' : 'Desactive' }}</span></td>
                            <td>
                                <span class="badge {{ $hasRoutes ? 'text-bg-info' : 'text-bg-light text-dark' }}">{{ $hasRoutes ? 'Chargeables' : 'Aucune' }}</span>
                            </td>
                            <td>{{ collect($module->depends ?? [])->join(', ') ?: 'Aucune' }}</td>
                            <td>
                                @if(!$isSystemModule && $canManageModules)
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Êtes-vous sûr?');">
                                        @csrf
                                        @if($module->enabled)
                                            <button type="submit" formaction="{{ route('admin.modules.disable', $module->slug) }}" class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-power"></i> Desactiver
                                            </button>
                                        @else
                                            <button type="submit" formaction="{{ route('admin.modules.enable', $module->slug) }}" class="btn btn-sm btn-outline-success">
                                                <i class="bi bi-check-circle"></i> Activer
                                            </button>
                                        @endif
                                    </form>
                                    @if($hasMigrations && $module->enabled)
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Exécuter les migrations de {{ addslashes($module->name) }} ?');">
                                            @csrf
                                            <button type="submit" formaction="{{ route('admin.modules.migrate', $module->slug) }}"
                                                class="btn btn-sm {{ $hasUpgrade ? 'btn-warning' : 'btn-outline-secondary' }} ms-1"
                                                title="{{ $hasUpgrade ? 'Upgrade disponible (v'.$installedVersion.' → v'.($module->version ?? '?').')' : 'Ré-exécuter les migrations (idempotent)' }}">
                                                <i class="bi bi-database-up"></i> {{ $hasUpgrade ? 'Upgrade DB' : 'Migrer' }}
                                            </button>
                                        </form>
                                    @endif
                                    @if($hasConfig)
                                        <a href="{{ route('admin.modules.config', $module->slug) }}" class="btn btn-sm btn-outline-primary ms-1">
                                            <i class="bi bi-sliders"></i> Config
                                        </a>
                                    @endif
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted py-4">Aucun module.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h2 class="h6 mb-0">Etat des addons</h2>
            <div class="d-flex align-items-center gap-2">
                <span class="badge text-bg-light">{{ collect($addons ?? [])->count() }}</span>
                @if(catmin_can('addon.registry.view'))
                    <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.addons.marketplace.index') }}">
                        <i class="bi bi-bag me-1"></i>Marketplace
                    </a>
                @endif
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle mb-0">
                <thead><tr><th>Nom</th><th>Slug</th><th>Version</th><th>Etat</th><th>Dependances</th><th>Actions</th></tr></thead>
                <tbody>
                @forelse(($addons ?? []) as $addon)
                    <tr>
                        <td>{{ $addon->name ?? ucfirst($addon->slug) }}</td>
                        <td>{{ $addon->slug }}</td>
                        <td><span class="badge text-bg-light text-dark">{{ $addon->version ?? 'n/a' }}</span></td>
                        <td>
                            <span class="badge {{ !empty($addon->enabled) ? 'text-bg-success' : 'text-bg-secondary' }}">
                                {{ !empty($addon->enabled) ? 'Actif' : 'Desactive' }}
                            </span>
                        </td>
                        <td>{{ collect(($addon->required_modules ?? $addon->depends_modules ?? []))->join(', ') ?: 'Aucune' }}</td>
                        <td>
                            @if($canManageAddons)
                                @if(!empty($addon->enabled) && catmin_can('addon.disable'))
                                    <form method="post" action="{{ route('admin.addons.marketplace.disable') }}" class="d-inline" onsubmit="return confirm('Desactiver cet addon ?');">
                                        @csrf
                                        <input type="hidden" name="slug" value="{{ $addon->slug }}">
                                        <button type="submit" class="btn btn-sm btn-outline-secondary">
                                            <i class="bi bi-power me-1"></i>Desactiver
                                        </button>
                                    </form>
                                @elseif(empty($addon->enabled) && catmin_can('addon.enable'))
                                    <form method="post" action="{{ route('admin.addons.marketplace.enable') }}" class="d-inline" onsubmit="return confirm('Activer cet addon ?');">
                                        @csrf
                                        <input type="hidden" name="slug" value="{{ $addon->slug }}">
                                        <button type="submit" class="btn btn-sm btn-outline-success">
                                            <i class="bi bi-check-circle me-1"></i>Activer
                                        </button>
                                    </form>
                                @endif
                            @else
                                <span class="text-muted small">Permission manquante</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">Aucun addon detecte.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger mt-3" role="alert">
            <strong>Erreur:</strong>
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif
</div>

@if(session('success'))
    <div class="mt-3">
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const alert = document.createElement('div');
                alert.className = 'alert alert-success alert-dismissible fade show';
                alert.setAttribute('role', 'alert');
                alert.innerHTML = `
                    {!! session('success') !!}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                `;
                document.querySelector('.catmin-page-body').insertAdjacentElement('beforebegin', alert);
            });
        </script>
    </div>
@endif

@if(session('error'))
    <div class="mt-3">
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const alert = document.createElement('div');
                alert.className = 'alert alert-danger alert-dismissible fade show';
                alert.setAttribute('role', 'alert');
                alert.innerHTML = `
                    {!! session('error') !!}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                `;
                document.querySelector('.catmin-page-body').insertAdjacentElement('beforebegin', alert);
            });
        </script>
    </div>
@endif
@endsection
