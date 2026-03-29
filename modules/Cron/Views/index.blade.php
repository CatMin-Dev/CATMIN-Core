@extends('admin.layouts.catmin')

@section('page_title', 'Planificateur')

@section('content')
<x-admin.crud.page-header
    title="Planificateur de taches"
    subtitle="Scheduler Laravel - historique, declenchement manuel et taches personnalisees."
/>

<div class="catmin-page-body">
    <x-admin.crud.flash-messages />

    <div class="card mb-4">
        <div class="card-header bg-white d-flex align-items-center gap-2">
            <i class="bi bi-play-circle text-success"></i>
            <h2 class="h6 mb-0">Declenchement manuel</h2>
        </div>
        <div class="card-body">
            <div class="row g-2">
                @if(catmin_can('module.cron.config'))
                    @foreach($tasks as $key => $label)
                        <div class="col-12 col-sm-6 col-md-4">
                            <form method="POST" action="{{ route('admin.cron.run', $key) }}"
                                  onsubmit="return confirm('Executer : {{ addslashes($label) }} ?');">
                                @csrf
                                <button class="btn btn-outline-primary btn-sm w-100" type="submit">
                                    <i class="bi bi-play me-1"></i>{{ $label }}
                                </button>
                            </form>
                        </div>
                    @endforeach
                @else
                    <div class="col-12">
                        <div class="alert alert-light border mb-0">Permission module.cron.config requise pour declencher des taches.</div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @if(catmin_can('module.cron.config'))
        <div class="card mb-4">
            <div class="card-header bg-white d-flex align-items-center gap-2">
                <i class="bi bi-plus-square"></i>
                <h2 class="h6 mb-0">Ajouter une tache cron personnalisee</h2>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.cron.custom.store') }}" class="row g-3">
                    @csrf
                    <div class="col-12 col-lg-4">
                        <label class="form-label" for="cron-task-label">Intitule</label>
                        <input id="cron-task-label" name="label" type="text" class="form-control" maxlength="120"
                               placeholder="Archivage hebdo logs" value="{{ old('label') }}" required>
                    </div>
                    <div class="col-12 col-lg-4">
                        <label class="form-label" for="cron-task-command">Commande artisan</label>
                        <input id="cron-task-command" name="command" type="text" class="form-control" maxlength="160"
                               placeholder="catmin:logs:rotate" value="{{ old('command', 'catmin:logs:rotate') }}" required>
                    </div>
                    <div class="col-12 col-lg-4">
                        <label class="form-label" for="cron-task-frequency">Frequence</label>
                        <select id="cron-task-frequency" name="frequency" class="form-select" required>
                            @foreach($frequencies as $value => $label)
                                <option value="{{ $value }}" @selected(old('frequency', 'weekly') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-lg-3">
                        <label class="form-label" for="cron-task-scope">Portee</label>
                        <select id="cron-task-scope" name="scope" class="form-select" required>
                            <option value="site" @selected(old('scope', 'site') === 'site')>Site</option>
                            <option value="module" @selected(old('scope') === 'module')>Module</option>
                        </select>
                    </div>
                    <div class="col-12 col-lg-3">
                        <label class="form-label" for="cron-task-module">Module cible (optionnel)</label>
                        <input id="cron-task-module" name="module" type="text" class="form-control" maxlength="80"
                               placeholder="logger" value="{{ old('module') }}">
                    </div>
                    <div class="col-12 col-lg-6">
                        <label class="form-label" for="cron-task-description">Description</label>
                        <input id="cron-task-description" name="description" type="text" class="form-control" maxlength="255"
                               placeholder="Archive et purge les logs selon retention" value="{{ old('description') }}">
                    </div>
                    <div class="col-12 d-flex justify-content-end">
                        <button class="btn btn-primary btn-sm" type="submit">
                            <i class="bi bi-plus-lg me-1"></i>Ajouter la tache
                        </button>
                    </div>
                </form>
                <p class="small text-muted mt-3 mb-0">
                    Exemple archivage: commande <code>catmin:logs:rotate</code> avec frequence <strong>Chaque semaine</strong>.
                </p>
            </div>
        </div>
    @endif

    <div class="card mb-4">
        <div class="card-header bg-white d-flex align-items-center gap-2">
            <i class="bi bi-list-check"></i>
            <h2 class="h6 mb-0">Taches personnalisees</h2>
        </div>
        <div class="card-body p-0">
            @if(!empty($customTasks))
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Intitule</th>
                                <th>Commande</th>
                                <th>Frequence</th>
                                <th>Portee</th>
                                <th>Description</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($customTasks as $task)
                                <tr>
                                    <td class="fw-semibold">{{ $task['label'] ?? '-' }}</td>
                                    <td><code>{{ $task['command'] ?? '-' }}</code></td>
                                    <td>{{ $frequencies[$task['frequency'] ?? ''] ?? ($task['frequency'] ?? '-') }}</td>
                                    <td>
                                        <span class="badge text-bg-light">
                                            {{ ($task['scope'] ?? 'site') === 'module' ? 'Module: ' . ($task['module'] ?? '?') : 'Site' }}
                                        </span>
                                    </td>
                                    <td class="small text-muted">{{ $task['description'] ?? '' }}</td>
                                    <td class="text-end">
                                        @if(catmin_can('module.cron.config'))
                                            <div class="d-inline-flex gap-1">
                                                <form method="POST" action="{{ route('admin.cron.run', $task['id']) }}"
                                                                                                            onsubmit="return confirm('Executer cette tache personnalisee ?');">
                                                    @csrf
                                                    <button class="btn btn-outline-primary btn-sm" type="submit" title="Executer">
                                                        <i class="bi bi-play"></i>
                                                    </button>
                                                </form>
                                                <form method="POST" action="{{ route('admin.cron.custom.delete', $task['id']) }}"
                                                      onsubmit="return confirm('Supprimer cette tache personnalisee ?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="btn btn-outline-danger btn-sm" type="submit" title="Supprimer">
                                                        <i class="bi bi-trash3"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        @else
                                            <span class="small text-muted">Lecture seule</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="p-3">
                    <div class="alert alert-light border mb-0">Aucune tache personnalisee. Creez une tache pour un module/site (ex: archivage).</div>
                </div>
            @endif
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header bg-white d-flex align-items-center gap-2">
            <i class="bi bi-calendar-check"></i>
            <h2 class="h6 mb-0">Scheduler systeme</h2>
        </div>
        <div class="card-body">
            <p class="small text-muted mb-2">
                Cron Linux a installer pour activer l'execution automatique:
            </p>
            <code>* * * * * cd /path/to/catmin && php artisan schedule:run >> /dev/null 2>&1</code>
        </div>
    </div>

    <x-admin.crud.table-card title="Historique d'execution (50 derniers)">
        <x-slot:head>
            <tr>
                <th>Date</th>
                <th>Tache</th>
                <th>Statut</th>
                <th>Sortie</th>
            </tr>
        </x-slot:head>
        <x-slot:rows>
            @forelse($logs as $log)
                <tr>
                    <td class="text-nowrap small text-muted">{{ \Carbon\Carbon::parse($log->created_at)->format('d/m/Y H:i:s') }}</td>
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
                            -
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center text-muted py-3">Aucune execution enregistree.</td>
                </tr>
            @endforelse
        </x-slot:rows>
    </x-admin.crud.table-card>
</div>
@endsection
