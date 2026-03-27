@extends('admin.layouts.catmin')

@section('page_title', 'Cache')

@section('content')
<x-admin.crud.page-header
    title="Gestion du cache"
    subtitle="Vider les différentes couches de cache système."
/>

<div class="catmin-page-body">
    <x-admin.crud.flash-messages />

    <div class="row g-3 mb-4">
        <div class="col-12 col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <h2 class="h6 mb-1 text-muted">Driver</h2>
                    <p class="h4 mb-0 fw-bold">{{ strtoupper($info['driver']) }}</p>
                    @if($info['store'] !== 'unknown')
                        <small class="text-muted">{{ $info['store'] }}</small>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <h2 class="h6 mb-1 text-muted">Entrées en cache</h2>
                    <p class="h4 mb-0 fw-bold">
                        @if($entryCount >= 0)
                            {{ number_format($entryCount) }}
                        @else
                            n/a
                        @endif
                    </p>
                    <small class="text-muted">table cache (database driver)</small>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <h2 class="h6 mb-1 text-muted">Prefix</h2>
                    <p class="h4 mb-0 fw-bold font-monospace">{{ $info['prefix'] ?: '—' }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-white"><h2 class="h6 mb-0">Actions</h2></div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-12 col-md-6 col-lg-3">
                    <div class="card border-danger h-100">
                        <div class="card-body text-center">
                            <i class="bi bi-trash3 fs-2 text-danger mb-2"></i>
                            <h3 class="h6">Tout vider</h3>
                            <p class="small text-muted">Cache app + settings + vues + config</p>
                            <form method="POST" action="{{ route('admin.cache.clear') }}" onsubmit="return confirm('Vider tout le cache ?');">
                                @csrf
                                <button class="btn btn-danger btn-sm w-100" type="submit">
                                    <i class="bi bi-fire me-1"></i>Vider tout
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-3">
                    <div class="card border-warning h-100">
                        <div class="card-body text-center">
                            <i class="bi bi-sliders fs-2 text-warning mb-2"></i>
                            <h3 class="h6">Cache Settings</h3>
                            <p class="small text-muted">Recharge les paramètres depuis la BDD</p>
                            <form method="POST" action="{{ route('admin.cache.clear.settings') }}">
                                @csrf
                                <button class="btn btn-warning btn-sm w-100" type="submit">
                                    <i class="bi bi-arrow-clockwise me-1"></i>Vider settings
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-3">
                    <div class="card border-info h-100">
                        <div class="card-body text-center">
                            <i class="bi bi-file-earmark-code fs-2 text-info mb-2"></i>
                            <h3 class="h6">Cache Vues</h3>
                            <p class="small text-muted">Supprime les vues Blade compilées</p>
                            <form method="POST" action="{{ route('admin.cache.clear.views') }}">
                                @csrf
                                <button class="btn btn-info btn-sm w-100" type="submit">
                                    <i class="bi bi-code-slash me-1"></i>Vider vues
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
