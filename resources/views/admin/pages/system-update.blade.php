@extends('admin.layouts.catmin')

@section('page_title', 'System Update')

@section('content')
<header class="catmin-page-header d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-3">
    <div>
        <h1 class="h3 mb-1">System Update</h1>
        <p class="text-muted mb-0">Mise a jour en 1 clic avec verification SHA-256, backup pre-update et rollback.</p>
    </div>
    <div class="d-flex gap-2 align-items-center">
        <span class="badge text-bg-secondary">Version {{ $status['current_version'] ?? 'n/a' }}</span>
        <span class="badge text-bg-light border">{{ $status['branch'] ?? 'n/a' }} · {{ $status['revision'] ?? 'n/a' }}</span>
    </div>
</header>

<div class="catmin-page-body">
    <div class="row g-4">
        <div class="col-12 col-xl-7">
            <div class="card mb-4">
                <div class="card-header">
                    <h2 class="h6 mb-0">1) Telecharger un package update signe</h2>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.system-update.download') }}" class="row g-3">
                        @csrf
                        <div class="col-12">
                            <label class="form-label">URL package (.zip)</label>
                            <input type="url" class="form-control" name="package_url" placeholder="https://.../catmin-update.zip" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Checksum SHA-256</label>
                            <input type="text" class="form-control font-monospace" name="package_sha256" minlength="64" maxlength="64" placeholder="64 caracteres hex" required>
                            <div class="form-text">Le package est refuse si le hash ne correspond pas.</div>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">Telecharger et verifier</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h2 class="h6 mb-0">2) Appliquer l'update</h2>
                    @if($activeUpdate)
                        <span class="badge text-bg-success">Package pret</span>
                    @else
                        <span class="badge text-bg-warning">Aucun package</span>
                    @endif
                </div>
                <div class="card-body">
                    @if($activeUpdate)
                        <dl class="row mb-3">
                            <dt class="col-sm-4">Update ID</dt>
                            <dd class="col-sm-8 font-monospace small">{{ $activeUpdate['id'] ?? 'n/a' }}</dd>
                            <dt class="col-sm-4">Version cible</dt>
                            <dd class="col-sm-8">{{ $activeUpdate['target_version'] ?? 'unknown' }}</dd>
                            <dt class="col-sm-4">Telechargee</dt>
                            <dd class="col-sm-8">{{ \Carbon\Carbon::parse($activeUpdate['downloaded_at'] ?? now())->format('d/m/Y H:i:s') }}</dd>
                        </dl>
                    @endif

                    <form method="POST" action="{{ route('admin.system-update.apply') }}" class="d-flex flex-column gap-3">
                        @csrf
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="1" id="with_core_migrate" name="with_core_migrate" checked>
                            <label class="form-check-label" for="with_core_migrate">
                                Inclure migrations core
                            </label>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-success" {{ $activeUpdate ? '' : 'disabled' }}>
                                Update 1 clic
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card border-danger">
                <div class="card-header bg-danger-subtle">
                    <h2 class="h6 mb-0">3) Rollback urgence</h2>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.system-update.rollback') }}">
                        @csrf
                        <button type="submit" class="btn btn-outline-danger">
                            Restaurer le dernier backup pre-update
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-5">
            <div class="card mb-4">
                <div class="card-header">
                    <h2 class="h6 mb-0">Securite update</h2>
                </div>
                <div class="card-body small text-muted">
                    <ul class="mb-0">
                        <li>Verification SHA-256 obligatoire avant application.</li>
                        <li>Backup auto (DB, media, addons, settings) avant update.</li>
                        <li>Rollback automatique si echec apply.</li>
                        <li>Journalisation complete dans storage/logs/update-history.jsonl.</li>
                    </ul>
                </div>
            </div>

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h2 class="h6 mb-0">Historique update</h2>
                    <span class="badge text-bg-light border">{{ count($history) }} events</span>
                </div>
                <div class="card-body p-0">
                    @if(empty($history))
                        <div class="p-3 text-muted small">Aucun evenement pour le moment.</div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm mb-0">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Event</th>
                                        <th>Details</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($history as $entry)
                                        <tr>
                                            <td class="small">{{ \Carbon\Carbon::parse($entry['timestamp'] ?? now())->format('d/m H:i:s') }}</td>
                                            <td><span class="badge text-bg-secondary">{{ $entry['event'] ?? 'n/a' }}</span></td>
                                            <td class="small text-muted">{{ json_encode($entry['payload'] ?? [], JSON_UNESCAPED_SLASHES) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
