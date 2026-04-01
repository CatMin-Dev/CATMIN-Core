@extends('admin.layouts.catmin')

@section('page_title', 'Recovery Center')

@section('content')
<header class="catmin-page-header d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-3">
    <div>
        <h1 class="h3 mb-1">Recovery Center</h1>
        <p class="text-muted mb-0">Moteur de reprise rapide apres incident: rollback code, rollback DB, restore backup et health check.</p>
    </div>
    <div>
        @php $healthOk = (bool)($status['health']['ok'] ?? false); @endphp
        <span class="badge {{ $healthOk ? 'text-bg-success' : 'text-bg-danger' }}">
            {{ $healthOk ? 'Systeme sain' : 'Systeme degrade' }}
        </span>
    </div>
</header>

<div class="catmin-page-body">
    <div class="row g-4">
        <div class="col-12 col-xl-7">
            <div class="card mb-4">
                <div class="card-header">
                    <h2 class="h6 mb-0">Recovery Run (one-click)</h2>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.recovery.run') }}" class="d-flex flex-column gap-3">
                        @csrf
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="maintenance_mode" id="maintenance_mode" value="1" checked>
                            <label class="form-check-label" for="maintenance_mode">Activer le mode maintenance pendant recovery</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="rollback_code" id="rollback_code" value="1" checked>
                            <label class="form-check-label" for="rollback_code">Rollback code vers le dernier tag release/*</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="restore_backup" id="restore_backup" value="1" checked>
                            <label class="form-check-label" for="restore_backup">Restore dernier backup update (DB + settings + media + addons)</label>
                        </div>
                        <div>
                            <button class="btn btn-danger" type="submit">Executer recovery maintenant</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2 class="h6 mb-0">Health checks</h2>
                </div>
                <div class="card-body p-0">
                    @foreach(($status['health']['checks'] ?? []) as $check)
                        <div class="d-flex justify-content-between align-items-start px-3 py-2 border-bottom">
                            <div>
                                <div class="fw-semibold small">{{ $check['label'] ?? $check['key'] ?? 'check' }}</div>
                                <div class="small text-muted">{{ $check['details'] ?? '' }}</div>
                            </div>
                            <span class="badge {{ ($check['ok'] ?? false) ? 'text-bg-success' : 'text-bg-danger' }}">{{ ($check['ok'] ?? false) ? 'OK' : 'FAIL' }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-5">
            <div class="card mb-4">
                <div class="card-header">
                    <h2 class="h6 mb-0">Release tags disponibles</h2>
                </div>
                <div class="card-body">
                    @if(empty($status['release_tags'] ?? []))
                        <div class="small text-muted">Aucun tag release detecte.</div>
                    @else
                        <ul class="mb-0 small">
                            @foreach($status['release_tags'] as $tag)
                                <li><span class="font-monospace">{{ $tag }}</span></li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h2 class="h6 mb-0">Historique recovery</h2>
                    <span class="badge text-bg-light border">{{ count($history) }} events</span>
                </div>
                <div class="card-body p-0">
                    @if(empty($history))
                        <div class="p-3 text-muted small">Aucun evenement recovery.</div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm mb-0">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Event</th>
                                        <th>Payload</th>
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
