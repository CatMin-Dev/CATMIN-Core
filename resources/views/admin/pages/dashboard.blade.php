@extends('admin.layouts.catmin')

@section('page_title', 'Tableau de bord')

@section('content')
<header class="catmin-page-header d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-3">
    <div>
        <h1 class="h3 mb-1">Tableau de bord</h1>
        <p class="text-muted mb-0">Vue generale de l'administration CATMIN.</p>
    </div>
</header>

<div class="catmin-page-body">
    <div class="card mb-4">
        <div class="card-body">
            <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 align-items-lg-center">
                <div>
                    <h2 class="h5 mb-1">Bienvenue {{ $welcome['admin_user'] }}</h2>
                    <p class="text-muted mb-0">Vous administrez <strong>{{ $welcome['site_name'] }}</strong> depuis le tableau de bord CATMIN.</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a class="btn btn-outline-primary btn-sm" href="{{ $welcome['site_url'] }}" target="_blank" rel="noreferrer noopener">Voir le site</a>
                    <a class="btn btn-primary btn-sm" href="{{ admin_route('settings.index') }}">Configurer</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xxl-3"><div class="card h-100"><div class="card-body"><p class="text-muted mb-1">Utilisateurs</p><p class="display-6 mb-0">{{ $stats['users'] }}</p></div></div></div>
        <div class="col-12 col-sm-6 col-xxl-3"><div class="card h-100"><div class="card-body"><p class="text-muted mb-1">Roles</p><p class="display-6 mb-0">{{ $stats['roles'] }}</p></div></div></div>
        <div class="col-12 col-sm-6 col-xxl-3"><div class="card h-100"><div class="card-body"><p class="text-muted mb-1">Parametres</p><p class="display-6 mb-0">{{ $stats['settings'] }}</p></div></div></div>
        <div class="col-12 col-sm-6 col-xxl-3"><div class="card h-100"><div class="card-body"><p class="text-muted mb-1">Modules actifs</p><p class="display-6 mb-0">{{ $stats['modules_enabled'] }}/{{ $stats['modules_total'] }}</p></div></div></div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12 col-lg-4">
            <div class="card h-100">
                <div class="card-header bg-white"><h2 class="h6 mb-0">Sante plateforme</h2></div>
                <div class="card-body">
                    <div class="d-flex justify-content-between py-2 border-bottom"><span>Erreurs (24h)</span><strong>{{ $health['recent_errors'] }}</strong></div>
                    <div class="d-flex justify-content-between py-2 border-bottom"><span>Jobs en echec</span><strong>{{ $health['failed_jobs'] }}</strong></div>
                    <div class="d-flex justify-content-between py-2 border-bottom"><span>Mails envoyes</span><strong>{{ $health['mailer_sent'] }}</strong></div>
                    <div class="d-flex justify-content-between py-2"><span>Mails en echec</span><strong>{{ $health['mailer_failed'] }}</strong></div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-8">
            <div class="card h-100">
                <div class="card-header bg-white"><h2 class="h6 mb-0">Activite des 7 derniers jours</h2></div>
                <div class="card-body">
                    @php
                        $maxUsers = max(1, max($activity['users']));
                        $maxContent = max(1, max($activity['content']));
                        $maxErrors = max(1, max($activity['errors']));
                    @endphp
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Jour</th>
                                    <th>Utilisateurs</th>
                                    <th>Contenu publie</th>
                                    <th>Erreurs</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($activity['labels'] as $i => $label)
                                    @php
                                        $usersWidth = (int) round(($activity['users'][$i] / $maxUsers) * 100);
                                        $contentWidth = (int) round(($activity['content'][$i] / $maxContent) * 100);
                                        $errorsWidth = (int) round(($activity['errors'][$i] / $maxErrors) * 100);
                                    @endphp
                                    <tr>
                                        <td class="fw-semibold">{{ $label }}</td>
                                        <td style="min-width: 180px;">
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="progress flex-grow-1" role="progressbar" aria-valuemin="0" aria-valuemax="{{ $maxUsers }}" aria-valuenow="{{ $activity['users'][$i] }}">
                                                    <div class="progress-bar js-progress-width" data-progress-width="{{ $usersWidth }}"></div>
                                                </div>
                                                <span class="small text-muted">{{ $activity['users'][$i] }}</span>
                                            </div>
                                        </td>
                                        <td style="min-width: 180px;">
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="progress flex-grow-1" role="progressbar" aria-valuemin="0" aria-valuemax="{{ $maxContent }}" aria-valuenow="{{ $activity['content'][$i] }}">
                                                    <div class="progress-bar bg-success js-progress-width" data-progress-width="{{ $contentWidth }}"></div>
                                                </div>
                                                <span class="small text-muted">{{ $activity['content'][$i] }}</span>
                                            </div>
                                        </td>
                                        <td style="min-width: 180px;">
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="progress flex-grow-1" role="progressbar" aria-valuemin="0" aria-valuemax="{{ $maxErrors }}" aria-valuenow="{{ $activity['errors'][$i] }}">
                                                    <div class="progress-bar bg-danger js-progress-width" data-progress-width="{{ $errorsWidth }}"></div>
                                                </div>
                                                <span class="small text-muted">{{ $activity['errors'][$i] }}</span>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-12 col-xl-8">
            <div class="card mb-4">
                <div class="card-header bg-white"><h2 class="h6 mb-0">Acces rapides</h2></div>
                <div class="card-body d-flex flex-wrap gap-2">
                    <a class="btn btn-primary" href="{{ admin_route('users.index') }}">Utilisateurs</a>
                    <a class="btn btn-outline-primary" href="{{ admin_route('roles.index') }}">Roles</a>
                    <a class="btn btn-outline-primary" href="{{ admin_route('settings.index') }}">Parametres</a>
                    <a class="btn btn-outline-primary" href="{{ admin_route('modules.index') }}">Modules</a>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header bg-white"><h2 class="h6 mb-0">Informations systeme</h2></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <div class="border rounded p-3 h-100">
                                <p class="text-muted mb-1">Version CATMIN</p>
                                <p class="h5 mb-0">{{ $systemInfo['catmin_version'] }}</p>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="border rounded p-3 h-100">
                                <p class="text-muted mb-1">Laravel / PHP</p>
                                <p class="h5 mb-0">{{ $systemInfo['laravel_version'] }} / {{ $systemInfo['php_version'] }}</p>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="border rounded p-3 h-100">
                                <p class="text-muted mb-1">Environnement</p>
                                <p class="h5 mb-0 text-capitalize">{{ $systemInfo['environment'] }}</p>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="border rounded p-3 h-100">
                                <p class="text-muted mb-1">Chemin admin</p>
                                <p class="h5 mb-0">/{{ $systemInfo['admin_path'] }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header bg-white"><h2 class="h6 mb-0">Modules de contenu</h2></div>
                <div class="card-body">
                    <div class="row g-3">
                        @forelse($contentModules as $contentModule)
                            <div class="col-12 col-md-6">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <h3 class="h6 mb-0">{{ $contentModule->name }}</h3>
                                            <span class="badge {{ $contentModule->enabled ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $contentModule->enabled ? 'Actif' : 'Desactive' }}</span>
                                        </div>
                                        <p class="small text-muted mb-3">Slug: {{ $contentModule->slug }} · Version {{ $contentModule->version ?? 'n/a' }}</p>
                                        <a class="btn btn-sm btn-outline-primary" href="{{ admin_route('content.show', ['module' => $contentModule->slug]) }}">Ouvrir</a>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="col-12"><div class="alert alert-secondary mb-0" role="alert">Aucun module de contenu actif.</div></div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header bg-white"><h2 class="h6 mb-0">Etat du contenu</h2></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-4">
                            <div class="border rounded p-3 h-100">
                                <p class="text-muted mb-2">Pages</p>
                                <div class="d-flex justify-content-between"><span>Publiees</span><strong>{{ $contentStatus['pages']['published'] }}</strong></div>
                                <div class="d-flex justify-content-between"><span>Brouillons/autres</span><strong>{{ $contentStatus['pages']['draft'] }}</strong></div>
                            </div>
                        </div>
                        <div class="col-12 col-md-4">
                            <div class="border rounded p-3 h-100">
                                <p class="text-muted mb-2">Articles</p>
                                <div class="d-flex justify-content-between"><span>Publies</span><strong>{{ $contentStatus['articles']['published'] }}</strong></div>
                                <div class="d-flex justify-content-between"><span>Brouillons/autres</span><strong>{{ $contentStatus['articles']['draft'] }}</strong></div>
                            </div>
                        </div>
                        <div class="col-12 col-md-4">
                            <div class="border rounded p-3 h-100">
                                <p class="text-muted mb-2">Produits shop</p>
                                <div class="d-flex justify-content-between"><span>Actifs</span><strong>{{ $contentStatus['products']['active'] }}</strong></div>
                                <div class="d-flex justify-content-between"><span>Inactifs/autres</span><strong>{{ $contentStatus['products']['inactive'] }}</strong></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-4">
            <div class="card mb-4">
                <div class="card-header bg-white"><h2 class="h6 mb-0">Modules actifs</h2></div>
                <div class="list-group list-group-flush">
                    @forelse($enabledModules as $module)
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <p class="mb-0 fw-semibold">{{ $module->name }}</p>
                                <p class="small text-muted mb-0">{{ $module->slug }}</p>
                            </div>
                            <span class="badge text-bg-success">{{ $module->version ?? 'n/a' }}</span>
                        </div>
                    @empty
                        <div class="list-group-item text-muted">Aucun module actif.</div>
                    @endforelse
                </div>
            </div>

            <div class="card">
                <div class="card-header bg-white"><h2 class="h6 mb-0">Utilisateurs recents</h2></div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead><tr><th>Nom</th><th>Email</th><th>Roles</th></tr></thead>
                        <tbody>
                            @forelse($recentUsers as $user)
                                <tr>
                                    <td>{{ $user->name }}</td>
                                    <td>{{ $user->email }}</td>
                                    <td>{{ $user->roles->pluck('display_name')->filter()->join(', ') ?: $user->roles->pluck('name')->join(', ') ?: 'Aucun role' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center text-muted py-4">Aucun utilisateur.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.js-progress-width').forEach(function (element) {
        var value = Number(element.getAttribute('data-progress-width') || 0);
        var clamped = Math.max(0, Math.min(100, value));
        element.style.width = clamped + '%';
    });
});
</script>
@endpush
