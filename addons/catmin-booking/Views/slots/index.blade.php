@extends('admin.layouts.catmin')

@section('page_title', 'Booking · Créneaux')

@section('content')
<header class="catmin-page-header d-flex justify-content-between align-items-end">
    <div>
        <h1 class="h3 mb-1">Créneaux</h1>
        <p class="text-muted mb-0">Planification des disponibilités par service.</p>
    </div>
</header>

<div class="catmin-page-body">
    @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif

    <div class="card mb-4">
        <div class="card-header bg-white"><strong>Nouveau créneau</strong></div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.booking.slots.store') }}" class="row g-3">
                @csrf
                <div class="col-md-3">
                    <label class="form-label">Service</label>
                    <select name="booking_service_id" class="form-select" required>
                        <option value="">Choisir…</option>
                        @foreach($servicesList as $s)
                            <option value="{{ $s->id }}">{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Début</label>
                    <input type="datetime-local" name="start_at" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Fin</label>
                    <input type="datetime-local" name="end_at" class="form-control" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Capacité</label>
                    <input type="number" min="1" max="300" name="capacity" class="form-control" value="1" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Statut</label>
                    <select name="status" class="form-select">
                        <option value="open">Ouvert</option>
                        <option value="closed">Fermé</option>
                        <option value="blocked">Bloqué</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Raison blocage (optionnel)</label>
                    <input type="text" name="blocked_reason" class="form-control" maxlength="255">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="1" name="allow_overbooking" id="allow_overbooking">
                        <label class="form-check-label" for="allow_overbooking">Surbooking</label>
                    </div>
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="1" name="is_active" checked>
                    </div>
                </div>
                <div class="col-12">
                    <button class="btn btn-primary">Créer</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <strong>Liste des créneaux</strong>
            <span class="badge text-bg-light">{{ $slots->total() }}</span>
        </div>
        <div class="table-responsive catmin-table-scroll">
            <table class="table table-striped table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Service</th>
                        <th>Début</th>
                        <th>Fin</th>
                        <th>Capacité</th>
                        <th>Réservé</th>
                        <th>Statut</th>
                        <th>Actif</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($slots as $slot)
                        <tr>
                            <td>{{ $slot->service->name ?? '—' }}</td>
                            <td>{{ optional($slot->start_at)->format('d/m/Y H:i') }}</td>
                            <td>{{ optional($slot->end_at)->format('d/m/Y H:i') }}</td>
                            <td>{{ $slot->capacity }}</td>
                            <td>{{ $slot->booked_count }}</td>
                            <td>
                                <span class="badge {{ $slot->status === 'open' ? 'text-bg-success' : ($slot->status === 'closed' ? 'text-bg-warning' : 'text-bg-danger') }}">
                                    {{ ucfirst($slot->status ?? 'open') }}
                                </span>
                            </td>
                            <td><span class="badge {{ $slot->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $slot->is_active ? 'Oui' : 'Non' }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">Aucun créneau.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($slots->hasPages())
            <div class="card-footer">{{ $slots->links() }}</div>
        @endif
    </div>
</div>
@endsection
