@extends('admin.layouts.catmin')

@section('page_title', 'Import / Export')

@section('content')
<header class="catmin-page-header d-flex justify-content-between align-items-end">
    <div>
        <h1 class="h3 mb-1"><i class="bi bi-arrow-left-right me-2"></i>Import / Export</h1>
        <p class="text-muted mb-0">Transfert JSON/CSV pour pages, articles, users, booking et CRM avec validation et dry-run.</p>
    </div>
</header>

<div class="catmin-page-body">
    @if(session('status'))
        <div class="alert alert-info">{{ session('status') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger mb-4">{{ implode(' | ', $errors->all()) }}</div>
    @endif

    @php($importResult = session('import_result'))

    @if($importResult)
        <div class="card mb-4">
            <div class="card-header bg-white"><strong>Dernier résultat d'import</strong></div>
            <div class="card-body">
                <div class="row g-3 mb-3">
                    <div class="col-sm-2"><div class="border rounded p-3 text-center"><div class="small text-muted">Lignes</div><div class="h4 mb-0">{{ $importResult['rows'] }}</div></div></div>
                    <div class="col-sm-2"><div class="border rounded p-3 text-center"><div class="small text-muted">Valides</div><div class="h4 mb-0">{{ $importResult['valid_rows'] }}</div></div></div>
                    <div class="col-sm-2"><div class="border rounded p-3 text-center"><div class="small text-muted">Créées</div><div class="h4 mb-0 text-success">{{ $importResult['created'] }}</div></div></div>
                    <div class="col-sm-2"><div class="border rounded p-3 text-center"><div class="small text-muted">Màj</div><div class="h4 mb-0 text-primary">{{ $importResult['updated'] }}</div></div></div>
                    <div class="col-sm-2"><div class="border rounded p-3 text-center"><div class="small text-muted">Ignorées</div><div class="h4 mb-0 text-warning">{{ $importResult['skipped'] }}</div></div></div>
                    <div class="col-sm-2"><div class="border rounded p-3 text-center"><div class="small text-muted">Erreurs</div><div class="h4 mb-0 text-danger">{{ count($importResult['errors']) }}</div></div></div>
                </div>

                @if(!empty($importResult['errors']))
                    <div class="alert alert-warning mb-0">
                        <strong>Erreurs détectées :</strong>
                        <ul class="mb-0 mt-2 ps-3">
                            @foreach(array_slice($importResult['errors'], 0, 8) as $error)
                                <li>Ligne {{ $error['row'] }} : {{ implode(', ', $error['errors']) }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </div>
    @endif

    <div class="row g-4">
        <div class="col-xl-5">
            <div class="card h-100">
                <div class="card-header bg-white"><strong>Exporter</strong></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.import_export.export') }}" class="row g-3">
                        @csrf
                        <div class="col-12">
                            <label class="form-label">Module</label>
                            <select name="module" class="form-select" required>
                                @foreach($modules as $slug => $module)
                                    <option value="{{ $slug }}">{{ $module['name'] }} — {{ $module['description'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Format</label>
                            <div class="d-flex gap-3">
                                @foreach($formats as $format)
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="format" value="{{ $format }}" id="export_{{ $format }}" {{ $format === 'json' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="export_{{ $format }}">{{ strtoupper($format) }}</label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="col-12"><button class="btn btn-primary"><i class="bi bi-download me-1"></i>Télécharger</button></div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-xl-7">
            <div class="card h-100">
                <div class="card-header bg-white"><strong>Importer</strong></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.import_export.import') }}" enctype="multipart/form-data" class="row g-3">
                        @csrf
                        <div class="col-md-6">
                            <label class="form-label">Module</label>
                            <select name="module" class="form-select" required>
                                @foreach($modules as $slug => $module)
                                    <option value="{{ $slug }}" {{ old('module') === $slug ? 'selected' : '' }}>{{ $module['name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Format</label>
                            <select name="format" class="form-select" required>
                                @foreach($formats as $format)
                                    <option value="{{ $format }}" {{ old('format', 'json') === $format ? 'selected' : '' }}>{{ strtoupper($format) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Fichier</label>
                            <input type="file" name="file" class="form-control" accept=".json,.csv,text/csv,application/json">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Ou coller le contenu</label>
                            <textarea name="payload" class="form-control font-monospace" rows="10" placeholder="JSON ou CSV">{{ old('payload') }}</textarea>
                        </div>
                        <div class="col-12 d-flex gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="dry_run" value="1" id="dry_run" {{ old('dry_run', '1') ? 'checked' : '' }}>
                                <label class="form-check-label" for="dry_run">Dry-run</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="overwrite" value="1" id="overwrite" {{ old('overwrite') ? 'checked' : '' }}>
                                <label class="form-check-label" for="overwrite">Écraser les enregistrements matchés</label>
                            </div>
                        </div>
                        <div class="col-12"><button class="btn btn-success"><i class="bi bi-upload me-1"></i>Lancer l'import</button></div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <strong>Logs récents</strong>
            <span class="badge text-bg-light">{{ $logs->count() }}</span>
        </div>
        <div class="table-responsive">
            <table class="table table-striped align-middle mb-0">
                <thead>
                    <tr><th>Date</th><th>Événement</th><th>Niveau</th><th>Message</th><th>Contexte</th></tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td><small>{{ optional($log->created_at)->format('d/m/Y H:i:s') }}</small></td>
                            <td><code>{{ $log->event }}</code></td>
                            <td><span class="badge text-bg-{{ $log->level === 'warning' ? 'warning' : 'secondary' }}">{{ $log->level }}</span></td>
                            <td>{{ $log->message }}</td>
                            <td><small class="text-muted">{{ json_encode($log->context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</small></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">Aucun log import/export disponible.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection