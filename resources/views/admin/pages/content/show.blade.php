@extends('admin.layouts.catmin')

@section('page_title', $module->name)

@section('content')
<header class="catmin-page-header d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-3">
    <div>
        <h1 class="h3 mb-1">{{ $module->name }}</h1>
        <p class="text-muted mb-0">Section d'administration du module {{ $module->slug }}.</p>
    </div>
    <div class="d-flex gap-2">
        <a class="btn btn-outline-primary" href="{{ admin_route('modules.index') }}">Tous les modules</a>
        <a class="btn btn-primary" href="{{ admin_route('index') }}">Tableau de bord</a>
    </div>
</header>

<div class="catmin-page-body">
    <div class="row g-4">
        <div class="col-12 col-lg-8">
            <div class="card">
                <div class="card-header bg-white"><h2 class="h6 mb-0">Informations</h2></div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-3">Slug</dt><dd class="col-sm-9">{{ $module->slug }}</dd>
                        <dt class="col-sm-3">Version</dt><dd class="col-sm-9">{{ $module->version ?? 'n/a' }}</dd>
                        <dt class="col-sm-3">Statut</dt><dd class="col-sm-9"><span class="badge {{ $module->enabled ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $module->enabled ? 'Actif' : 'Desactive' }}</span></dd>
                        <dt class="col-sm-3">Dependances</dt><dd class="col-sm-9">{{ collect($module->depends ?? [])->join(', ') ?: 'Aucune' }}</dd>
                    </dl>
                    <div class="alert alert-warning mt-4 mb-0" role="alert">Aucun CRUD specifique n'est encore branche pour ce module.</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="card">
                <div class="card-header bg-white"><h2 class="h6 mb-0">Actions</h2></div>
                <div class="card-body d-grid gap-2">
                    <a class="btn btn-outline-primary" href="{{ admin_route('modules.index') }}">Retour modules</a>
                    <a class="btn btn-outline-secondary" href="{{ admin_route('settings.index') }}">Parametres</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
