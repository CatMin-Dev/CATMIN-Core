@extends('admin.layouts.catmin')

@section('page_title', 'Queue')

@section('content')
<x-admin.crud.page-header
    title="File de jobs (Queue)"
    subtitle="Surveillance des jobs en attente et en échec."
/>

<div class="catmin-page-body">
    <x-admin.crud.flash-messages />

    <div class="row g-3 mb-4">
        <div class="col-12 col-md-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="bi bi-stack fs-2 text-primary mb-2"></i>
                    <h2 class="h6 text-muted">Jobs en attente</h2>
                    <p class="h3 fw-bold mb-0">{{ number_format($pending) }}</p>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card h-100">
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
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="bi bi-plug fs-2 text-info mb-2"></i>
                    <h2 class="h6 text-muted">Connexion</h2>
                    <p class="h3 fw-bold mb-0 text-uppercase">{{ $connection }}</p>
                </div>
            </div>
        </div>
    </div>

    @if($failed > 0)
    <div class="card mb-4">
        <div class="card-header bg-white d-flex align-items-center justify-content-between">
            <h2 class="h6 mb-0">
                <i class="bi bi-x-octagon text-danger me-1"></i>Jobs en échec (20 derniers)
            </h2>
            @if(catmin_can('module.queue.config'))
            <div class="d-flex gap-2">
                <form method="POST" action="{{ route('admin.queue.failed.retry-all') }}"
                      onsubmit="return confirm('Relancer TOUS les jobs en échec ?');">
                    @csrf
                    <button class="btn btn-sm btn-outline-primary" type="submit">
                        <i class="bi bi-arrow-repeat me-1"></i>Tout relancer
                    </button>
                </form>
                <form method="POST" action="{{ route('admin.queue.failed.clear') }}"
                      onsubmit="return confirm('Supprimer TOUS les jobs en échec ?');">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-sm btn-danger" type="submit">
                        <i class="bi bi-trash3 me-1"></i>Tout supprimer
                    </button>
                </form>
            </div>
            @endif
        </div>
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Job class</th>
                        <th>Queue</th>
                        <th>Exception</th>
                        <th>Échoué le</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($failedJobs as $job)
                    <tr>
                        <td class="small text-muted">{{ $job->id }}</td>
                        <td>
                            @php
                                $payload = json_decode($job->payload, true);
                                $displayName = $payload['displayName'] ?? $payload['job'] ?? '—';
                            @endphp
                            <code class="small">{{ $displayName }}</code>
                        </td>
                        <td><span class="badge bg-secondary">{{ $job->queue }}</span></td>
                        <td class="small text-danger" style="max-width:300px;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;">
                            {{ Str::limit($job->exception, 120) }}
                        </td>
                        <td class="text-nowrap small text-muted">
                            {{ \Carbon\Carbon::parse($job->failed_at)->format('d/m/Y H:i') }}
                        </td>
                        <td>
                            <div class="d-flex gap-2">
                                <form method="POST" action="{{ route('admin.queue.failed.retry', $job->id) }}"
                                      onsubmit="return confirm('Relancer ce job ?');">
                                    @csrf
                                    <button class="btn btn-sm btn-outline-primary" type="submit" title="Relancer">
                                        <i class="bi bi-arrow-repeat"></i>
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('admin.queue.failed.delete', $job->id) }}"
                                      onsubmit="return confirm('Supprimer ce job ?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" type="submit" title="Supprimer">
                                        <i class="bi bi-trash3"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
@endsection
