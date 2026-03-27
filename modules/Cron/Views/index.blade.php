@extends('admin.layouts.catmin')

@section('page_title', 'Planificateur')

@section('content')
<x-admin.crud.page-header
    title="Planificateur de tâches"
    subtitle="Scheduler Laravel — historique d'exécution et déclenchement manuel."
/>

<div class="catmin-page-body">
    <x-admin.crud.flash-messages />

    {{-- Manual task triggers --}}
    <div class="card mb-4">
        <div class="card-header bg-white d-flex align-items-center gap-2">
            <i class="bi bi-play-circle text-success"></i>
            <h2 class="h6 mb-0">Déclenchement manuel</h2>
        </div>
        <div class="card-body">
            <div class="row g-2">
                @foreach($tasks as $key => $label)
                <div class="col-12 col-sm-6 col-md-4">
                    <form method="POST" action="{{ route('admin.cron.run', $key) }}"
                          onsubmit="return confirm('Exécuter : {{ $label }} ?');">
                        @csrf
                        <button class="btn btn-outline-primary btn-sm w-100" type="submit">
                            <i class="bi bi-play me-1"></i>{{ $label }}
                        </button>
                    </form>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Scheduled tasks info --}}
    <div class="card mb-4">
        <div class="card-header bg-white d-flex align-items-center gap-2">
            <i class="bi bi-calendar-check"></i>
            <h2 class="h6 mb-0">Tâches planifiées (Scheduler)</h2>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Tâche</th>
                            <th>Fréquence</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>cron:cache-clear</code></td>
                            <td><span class="badge bg-secondary">Quotidien</span></td>
                            <td>Nettoyage du cache application</td>
                        </tr>
                        <tr>
                            <td><code>cron:queue-prune</code></td>
                            <td><span class="badge bg-secondary">Hebdomadaire</span></td>
                            <td>Suppression des jobs en échec de plus de 72 h</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p class="small text-muted mt-2 mb-0">
                <i class="bi bi-info-circle me-1"></i>
                Pour que le scheduler fonctionne, ajoutez à crontab :<br>
                <code>* * * * * cd /path/to/catmin && php artisan schedule:run >> /dev/null 2>&1</code>
            </p>
        </div>
    </div>

    {{-- Execution logs --}}
    <x-admin.crud.table-card title="Historique d'exécution (50 derniers)">
        <thead class="table-light">
            <tr>
                <th>Date</th>
                <th>Tâche</th>
                <th>Statut</th>
                <th>Sortie</th>
            </tr>
        </thead>
        <tbody>
            @forelse($logs as $log)
            <tr>
                <td class="text-nowrap small text-muted">
                    {{ \Carbon\Carbon::parse($log->created_at)->format('d/m/Y H:i:s') }}
                </td>
                <td><code>{{ $log->message }}</code></td>
                <td>
                    @if($log->level === 'error')
                        <span class="badge bg-danger">erreur</span>
                    @else
                        <span class="badge bg-success">ok</span>
                    @endif
                </td>
                <td>
                    @if($log->context)
                        @php $ctx = is_string($log->context) ? json_decode($log->context, true) : (array) $log->context; @endphp
                        <small class="text-muted font-monospace">{{ $ctx['output'] ?? '' }}</small>
                    @else
                        —
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="4" class="text-center text-muted py-4">Aucune exécution enregistrée.</td>
            </tr>
            @endforelse
        </tbody>
    </x-admin.crud.table-card>
</div>
@endsection
