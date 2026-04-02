@extends('admin.layouts.catmin')

@section('page_title', 'Check-in — ' . $event->title)

@section('content')
<header class="catmin-page-header">
    <div>
        <h1 class="h3 mb-1">Check-in</h1>
        <p class="text-muted mb-0"><strong>{{ $event->title }}</strong></p>
        <a href="{{ route('admin.events.edit', $event->id) }}" class="text-muted small">← Retour à l'événement</a>
    </div>
</header>

<div class="catmin-page-body">
    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">@foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach</ul>
        </div>
    @endif

    {{-- Stats --}}
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <div class="fs-2 fw-bold text-primary">{{ $stats['total_tickets'] }}</div>
                    <div class="text-muted small">Billets actifs / émis</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <div class="fs-2 fw-bold text-success">{{ $stats['checkins_done'] }}</div>
                    <div class="text-muted small">Check-ins effectués</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <div class="fs-2 fw-bold text-warning">{{ $stats['remaining'] }}</div>
                    <div class="text-muted small">Billets non scannés</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Scanner --}}
    @if(catmin_can('module.events.checkin'))
    <div class="card mb-4">
        <div class="card-header bg-white"><h5 class="h6 mb-0">Scanner / Saisir un billet</h5></div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.events.checkin.store', $event->id) }}" class="row g-3 align-items-end">
                @csrf
                <div class="col-md-6">
                    <label class="form-label">Numéro de billet</label>
                    <input type="text" name="ticket_number" class="form-control form-control-lg font-monospace"
                           placeholder="EVT-1-XXXXXXXX" autofocus required>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-success btn-lg">Valider le check-in</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- Historique --}}
    <div class="card">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h2 class="h6 mb-0">Historique des check-ins</h2>
            <span class="badge text-bg-light">{{ $checkins->total() }}</span>
        </div>
        <div class="table-responsive">
            <table class="table table-striped align-middle mb-0">
                <thead>
                    <tr>
                        <th>Participant</th>
                        <th>Email</th>
                        <th>Billet</th>
                        <th>Méthode</th>
                        <th>Date check-in</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($checkins as $checkin)
                    <tr>
                        <td>{{ $checkin->participant?->fullName() ?? '—' }}</td>
                        <td>{{ $checkin->participant?->email ?? '—' }}</td>
                        <td><code class="small">{{ $checkin->ticket?->ticket_number ?? '—' }}</code></td>
                        <td>{{ ucfirst($checkin->checkin_method) }}</td>
                        <td>{{ $checkin->checkin_at->format('d/m/Y H:i:s') }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">Aucun check-in enregistré.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($checkins->hasPages())
        <div class="card-footer">{{ $checkins->links() }}</div>
        @endif
    </div>
</div>
@endsection
