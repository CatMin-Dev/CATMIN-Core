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

    {{-- Scanner / Saisie --}}
    @if(catmin_can('event.checkin.validate'))
    <div class="card mb-4">
        <div class="card-header bg-white"><h5 class="h6 mb-0">Scanner / Saisir un billet</h5></div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.events.checkin.store', $event->id) }}" class="row g-3 align-items-end">
                @csrf
                <div class="col-12 col-lg-7">
                    <label class="form-label">Code billet / payload QR</label>
                    <input type="text" name="ticket_code" class="form-control form-control-lg font-monospace"
                           placeholder="EVT-1-XXXXXXXX ou JSON QR" value="{{ old('ticket_code') }}" autofocus required>
                </div>
                <div class="col-6 col-lg-2">
                    <label class="form-label">Méthode</label>
                    <select name="checkin_method" class="form-select form-select-lg">
                        <option value="manual">Manuel</option>
                        <option value="qr">QR</option>
                    </select>
                </div>
                <div class="col-6 col-lg-2">
                    <label class="form-label">Porte / zone</label>
                    <input type="text" name="gate" class="form-control form-control-lg" placeholder="Gate A" value="{{ old('gate') }}">
                </div>
                <div class="col-12 col-lg-1 d-grid">
                    <button type="submit" class="btn btn-success btn-lg">OK</button>
                </div>
                <div class="col-12">
                    <label class="form-label">Notes</label>
                    <input type="text" name="notes" class="form-control" placeholder="Optionnel" value="{{ old('notes') }}">
                </div>
            </form>

            <hr>

            <div class="row g-3">
                <div class="col-12 col-lg-6">
                    <h3 class="h6 mb-2">Scan caméra (mobile/tablette)</h3>
                    <div id="qr-reader" style="width:100%;max-width:420px"></div>
                    <p class="text-muted small mt-2 mb-0">Le scan remplit automatiquement le champ code, puis vous validez.</p>
                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.events.checkin', $event->id) }}" class="row g-2 align-items-end">
                <div class="col-12 col-md-6 col-lg-7">
                    <label class="form-label">Recherche participant / email / code</label>
                    <input type="text" name="q" class="form-control" value="{{ $filters['q'] ?? '' }}" placeholder="nom, email, EVT-...">
                </div>
                <div class="col-6 col-md-3 col-lg-3">
                    <label class="form-label">Statut ticket</label>
                    <select name="status" class="form-select">
                        <option value="">Tous</option>
                        @foreach(['issued', 'used', 'cancelled', 'invalid'] as $status)
                            <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-3 col-lg-2 d-grid">
                    <button type="submit" class="btn btn-outline-secondary">Filtrer</button>
                </div>
            </form>
        </div>
    </div>

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
                        <th>Source</th>
                        <th>Méthode</th>
                        <th>Porte</th>
                        <th>Date check-in</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($checkins as $checkin)
                    <tr>
                        <td>{{ $checkin->participant?->fullName() ?? '—' }}</td>
                        <td>{{ $checkin->participant?->email ?? '—' }}</td>
                        <td><code class="small">{{ $checkin->ticket?->publicCode() ?? '—' }}</code></td>
                        <td>{{ $checkin->ticket?->source ?? '—' }}</td>
                        <td>{{ ucfirst($checkin->checkin_method) }}</td>
                        <td>{{ $checkin->location ?? '—' }}</td>
                        <td>{{ optional($checkin->checked_in_at ?? $checkin->checkin_at)?->format('d/m/Y H:i:s') }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">Aucun check-in enregistré.</td></tr>
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

@push('scripts')
    <script src="https://unpkg.com/html5-qrcode" defer></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const input = document.querySelector('input[name="ticket_code"]');
            if (!input || typeof Html5Qrcode === 'undefined') {
                return;
            }

            const scanner = new Html5Qrcode('qr-reader');
            scanner.start(
                { facingMode: 'environment' },
                { fps: 10, qrbox: 220 },
                function (decodedText) {
                    input.value = decodedText;
                    try { scanner.stop(); } catch (e) {}
                },
                function () {}
            ).catch(function () {
                // Keep manual entry as fallback.
            });
        });
    </script>
@endpush
