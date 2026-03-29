@extends('admin.layouts.catmin')

@section('page_title', 'Queue')

@section('content')
<x-admin.crud.page-header
    title="Dashboard queue"
    subtitle="Pilotage des jobs pending/failed, retry contrôlé et purge sécurisée."
/>

<div class="catmin-page-body">
    <x-admin.crud.flash-messages />

    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.queue.index') }}" class="row g-2 align-items-end">
                <div class="col-12 col-lg-3">
                    <label class="form-label mb-1" for="queue-status-filter">Statut</label>
                    <select id="queue-status-filter" name="status" class="form-select">
                        <option value="all" @selected(($filters['status'] ?? 'all') === 'all')>Tous</option>
                        <option value="failed" @selected(($filters['status'] ?? '') === 'failed')>Failed seulement</option>
                        <option value="pending" @selected(($filters['status'] ?? '') === 'pending')>Pending seulement</option>
                    </select>
                </div>
                <div class="col-12 col-lg-3">
                    <label class="form-label mb-1" for="queue-name-filter">Queue</label>
                    <select id="queue-name-filter" name="queue" class="form-select">
                        <option value="">Toutes</option>
                        @foreach($queues as $queueName)
                            <option value="{{ $queueName }}" @selected(($filters['queue'] ?? '') === $queueName)>{{ $queueName }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-lg-4">
                    <label class="form-label mb-1" for="queue-search-filter">Recherche</label>
                    <input
                        id="queue-search-filter"
                        type="search"
                        name="q"
                        class="form-control"
                        value="{{ $filters['q'] ?? '' }}"
                        placeholder="ID job, classe, erreur..."
                    >
                </div>
                <div class="col-12 col-lg-2 d-grid">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-funnel me-1"></i>Filtrer
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12 col-md-4">
            <div class="card h-100 queue-stat-card">
                <div class="card-body text-center">
                    <i class="bi bi-stack fs-2 text-primary mb-2"></i>
                    <h2 class="h6 text-muted">Jobs en attente</h2>
                    <p class="h3 fw-bold mb-0">{{ number_format($pending) }}</p>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card h-100 queue-stat-card">
                <div class="card-body text-center">
                    <i class="bi bi-x-octagon fs-2 text-danger mb-2"></i>
                    <h2 class="h6 text-muted">Jobs en échec</h2>
                    <p class="h3 fw-bold mb-0 {{ $failed > 0 ? 'text-danger' : '' }}">
                        {{ number_format($failed) }}
                    </p>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card h-100 queue-stat-card">
                <div class="card-body text-center">
                    <i class="bi bi-plug fs-2 text-info mb-2"></i>
                    <h2 class="h6 text-muted">Connexion</h2>
                    <p class="h3 fw-bold mb-0 text-uppercase">{{ $connection }}</p>
                </div>
            </div>
        </div>
    </div>

    @if(($filters['status'] ?? 'all') !== 'failed')
        <div class="card mb-4">
            <div class="card-header bg-white d-flex align-items-center justify-content-between">
                <h2 class="h6 mb-0"><i class="bi bi-hourglass-split text-primary me-1"></i>Jobs pending</h2>
                <span class="badge text-bg-light">{{ $pendingJobs->total() }} au total</span>
            </div>
            @if($pendingJobs->count() > 0)
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Type / class</th>
                                <th>Queue</th>
                                <th>Statut</th>
                                <th>Attempts</th>
                                <th>Créé le</th>
                                <th>Disponible le</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pendingJobs as $job)
                                <tr>
                                    <td class="small text-muted">#{{ $job['id'] }}</td>
                                    <td>
                                        <div class="fw-semibold small">{{ $job['class'] }}</div>
                                        <div class="text-muted small">{{ $job['payload_preview'] }}</div>
                                    </td>
                                    <td><span class="badge bg-secondary">{{ $job['queue'] }}</span></td>
                                    <td>
                                        <span class="badge {{ $job['status'] === 'running' ? 'text-bg-warning' : 'text-bg-primary' }}">
                                            {{ $job['status'] === 'running' ? 'running' : 'pending' }}
                                        </span>
                                    </td>
                                    <td>{{ $job['attempts'] }}</td>
                                    <td class="small text-muted text-nowrap">{{ $job['created_at'] ?? '—' }}</td>
                                    <td class="small text-muted text-nowrap">{{ $job['available_at'] ?? '—' }}</td>
                                    <td>
                                        <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.queue.job.show', ['source' => 'pending', 'id' => $job['id']]) }}">
                                            Détail
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="card-footer bg-white">{{ $pendingJobs->links() }}</div>
            @else
                <div class="card-body">
                    <div class="alert alert-light border mb-0">Aucun job pending ne correspond à ce filtre.</div>
                </div>
            @endif
        </div>
    @endif

    @if(($filters['status'] ?? 'all') !== 'pending')
        <div class="card mb-4">
            <div class="card-header bg-white d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-2">
                <h2 class="h6 mb-0"><i class="bi bi-x-octagon text-danger me-1"></i>Jobs failed</h2>
                @if(catmin_can('module.queue.config'))
                    <div class="d-flex gap-2">
                        <form method="POST" action="{{ route('admin.queue.failed.retry-all') }}"
                              onsubmit="return confirm('Relancer TOUS les jobs en échec ?');">
                            @csrf
                            <button class="btn btn-sm btn-outline-primary" type="submit">
                                <i class="bi bi-arrow-repeat me-1"></i>Retry all
                            </button>
                        </form>
                        <form method="POST" action="{{ route('admin.queue.failed.clear') }}"
                              onsubmit="return confirm('Supprimer TOUS les jobs en échec ?');">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-danger" type="submit">
                                <i class="bi bi-trash3 me-1"></i>Clear all
                            </button>
                        </form>
                    </div>
                @endif
            </div>
            @if($failedJobs->count() > 0)
                <form method="POST" action="{{ route('admin.queue.failed.retry-selected') }}"
                      onsubmit="return confirm('Appliquer cette action sur les jobs sélectionnés ?');">
                    @csrf
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 2.5rem;">
                                        <input type="checkbox" class="form-check-input" data-queue-check-all>
                                    </th>
                                    <th>ID</th>
                                    <th>Type / class</th>
                                    <th>Queue</th>
                                    <th>Attempts</th>
                                    <th>Erreur résumée</th>
                                    <th>Failed at</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($failedJobs as $job)
                                    <tr>
                                        <td><input type="checkbox" name="ids[]" value="{{ $job['id'] }}" class="form-check-input" data-queue-check-row></td>
                                        <td class="small text-muted">#{{ $job['id'] }}</td>
                                        <td>
                                            <div class="fw-semibold small">{{ $job['class'] }}</div>
                                            <div class="text-muted small">{{ $job['payload_preview'] }}</div>
                                        </td>
                                        <td><span class="badge bg-secondary">{{ $job['queue'] }}</span></td>
                                        <td>{{ $job['attempts'] }}</td>
                                        <td class="small text-danger" style="max-width: 340px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                            {{ $job['error_excerpt'] }}
                                        </td>
                                        <td class="small text-muted text-nowrap">{{ $job['failed_at'] }}</td>
                                        <td>
                                            <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.queue.job.show', ['source' => 'failed', 'id' => $job['id']]) }}">Détail</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if(catmin_can('module.queue.config'))
                        <div class="card-footer bg-white d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-2">
                            <div class="small text-muted">Actions de masse sur la sélection actuelle.</div>
                            <div class="d-flex gap-2">
                                <button class="btn btn-sm btn-outline-primary" type="submit">
                                    <i class="bi bi-arrow-repeat me-1"></i>Retry selected
                                </button>
                                <button
                                    class="btn btn-sm btn-outline-danger"
                                    type="submit"
                                    formaction="{{ route('admin.queue.failed.clear-selected') }}"
                                >
                                    <i class="bi bi-trash3 me-1"></i>Clear selected
                                </button>
                            </div>
                        </div>
                    @endif
                </form>
                <div class="card-footer bg-white border-top">{{ $failedJobs->links() }}</div>
            @else
                <div class="card-body">
                    <div class="alert alert-light border mb-0">Aucun job failed ne correspond à ce filtre.</div>
                </div>
            @endif
        </div>
    @endif
</div>
@endsection
