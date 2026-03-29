@extends('admin.layouts.catmin')

@section('page_title', 'Queue - Detail job')

@section('content')
<x-admin.crud.page-header
    title="Détail job queue"
    subtitle="Inspection opérationnelle d'un job pending/failed."
/>

<div class="catmin-page-body">
    <x-admin.crud.flash-messages />

    <div class="mb-3 d-flex justify-content-between align-items-center">
        <a href="{{ route('admin.queue.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Retour queue
        </a>

        @if(($job['source'] ?? '') === 'failed' && catmin_can('module.queue.config'))
            <div class="d-flex gap-2">
                <form method="POST" action="{{ route('admin.queue.failed.retry', $job['id']) }}" onsubmit="return confirm('Relancer ce job en échec ?');">
                    @csrf
                    <button class="btn btn-sm btn-outline-primary" type="submit">
                        <i class="bi bi-arrow-repeat me-1"></i>Retry job
                    </button>
                </form>
                <form method="POST" action="{{ route('admin.queue.failed.delete', $job['id']) }}" onsubmit="return confirm('Supprimer ce job en échec ?');">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-sm btn-outline-danger" type="submit">
                        <i class="bi bi-trash3 me-1"></i>Delete job
                    </button>
                </form>
            </div>
        @endif
    </div>

    <div class="row g-3">
        <div class="col-12 col-xl-4">
            <div class="card h-100">
                <div class="card-header bg-white">
                    <h2 class="h6 mb-0">Métadonnées</h2>
                </div>
                <div class="card-body small">
                    <div class="d-flex justify-content-between py-2 border-bottom"><span>ID</span><strong>#{{ $job['id'] }}</strong></div>
                    <div class="d-flex justify-content-between py-2 border-bottom"><span>Source</span><strong>{{ $job['source'] }}</strong></div>
                    <div class="d-flex justify-content-between py-2 border-bottom"><span>Statut</span><strong>{{ $job['status'] }}</strong></div>
                    <div class="d-flex justify-content-between py-2 border-bottom"><span>Queue</span><strong>{{ $job['queue'] }}</strong></div>
                    <div class="d-flex justify-content-between py-2 border-bottom"><span>Connexion</span><strong>{{ $job['connection'] ?: '—' }}</strong></div>
                    <div class="d-flex justify-content-between py-2 border-bottom"><span>Attempts</span><strong>{{ $job['attempts'] }}</strong></div>
                    <div class="d-flex justify-content-between py-2 border-bottom"><span>Créé le</span><strong>{{ $job['created_at'] ?: '—' }}</strong></div>
                    <div class="d-flex justify-content-between py-2 border-bottom"><span>Disponible le</span><strong>{{ $job['available_at'] ?: '—' }}</strong></div>
                    <div class="d-flex justify-content-between py-2 border-bottom"><span>Réservé le</span><strong>{{ $job['reserved_at'] ?: '—' }}</strong></div>
                    <div class="d-flex justify-content-between py-2"><span>Failed at</span><strong>{{ $job['failed_at'] ?: '—' }}</strong></div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-8">
            <div class="card mb-3">
                <div class="card-header bg-white">
                    <h2 class="h6 mb-0">Type / class</h2>
                </div>
                <div class="card-body">
                    <code>{{ $job['class'] }}</code>
                </div>
            </div>

            @if(!empty($job['exception_excerpt']))
                <div class="card mb-3 border-danger-subtle">
                    <div class="card-header bg-white">
                        <h2 class="h6 mb-0 text-danger">Erreur résumée</h2>
                    </div>
                    <div class="card-body">
                        <p class="mb-0 small text-danger">{{ $job['exception_excerpt'] }}</p>
                    </div>
                </div>
            @endif

            @if(!empty($job['exception']) && catmin_can('module.queue.config'))
                <div class="card mb-3">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h2 class="h6 mb-0">Exception complète / stack trace</h2>
                        <span class="badge text-bg-secondary">Accès config</span>
                    </div>
                    <div class="card-body">
                        <pre class="small mb-0" style="max-height: 340px; overflow: auto; white-space: pre-wrap;">{{ $job['exception'] }}</pre>
                    </div>
                </div>
            @endif

            <div class="card">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h2 class="h6 mb-0">Payload utile (sanitisé)</h2>
                    <span class="badge text-bg-light">Données sensibles masquées</span>
                </div>
                <div class="card-body">
                    <pre class="small mb-0" style="max-height: 420px; overflow: auto; white-space: pre-wrap;">{{ json_encode($job['payload'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
