@extends('admin.layouts.catmin')

@section('page_title', 'Événements')

@section('content')
<header class="catmin-page-header d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-3">
    <div>
        <h1 class="h3 mb-1">Événements</h1>
        <p class="text-muted mb-0">Gestion des événements et des inscriptions.</p>
    </div>
    @if(catmin_can('module.events.create'))
    <a class="btn btn-primary" href="{{ route('admin.events.create') }}">Nouvel événement</a>
    @endif
</header>

<div class="catmin-page-body">
    {{-- Filtres --}}
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.events.index') }}" class="row g-3 align-items-end">
                <div class="col-12 col-md-3">
                    <label class="form-label">Statut</label>
                    <select name="status" class="form-select">
                        <option value="">Tous</option>
                        @foreach($statuses as $s)
                            <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">Lieu</label>
                    <input type="text" name="location" class="form-control" value="{{ request('location') }}" placeholder="Ex: Paris">
                </div>
                <div class="col-12 col-md-2">
                    <label class="form-label">Du</label>
                    <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                </div>
                <div class="col-12 col-md-2">
                    <label class="form-label">Au</label>
                    <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                </div>
                <div class="col-12 col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-outline-primary">Filtrer</button>
                    <a href="{{ route('admin.events.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="card">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h2 class="h6 mb-0">Liste des événements</h2>
            <span class="badge text-bg-light">{{ $events->total() }}</span>
        </div>
        <div class="table-responsive catmin-table-scroll">
            <table class="table table-striped table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Titre</th>
                        <th>Lieu</th>
                        <th>Début</th>
                        <th>Fin</th>
                        <th>Capacité</th>
                        <th>Participants</th>
                        <th>Statut</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($events as $event)
                    <tr>
                        <td>
                            <strong>{{ $event->title }}</strong>
                            @if(!$event->is_free)
                                <br><small class="text-muted">{{ number_format($event->ticket_price, 2) }} €</small>
                            @else
                                <br><small class="text-success">Gratuit</small>
                            @endif
                        </td>
                        <td>{{ $event->location ?? '—' }}</td>
                        <td>{{ $event->start_at->format('d/m/Y H:i') }}</td>
                        <td>{{ $event->end_at->format('d/m/Y H:i') }}</td>
                        <td>{{ $event->capacity ?? '∞' }}</td>
                        <td>
                            <a href="{{ route('admin.events.participants', $event->id) }}" class="text-decoration-none">
                                {{ $event->participants_count }}
                            </a>
                        </td>
                        <td>
                            @php
                                $badgeClass = match($event->status) {
                                    'published'  => 'text-bg-success',
                                    'draft'      => 'text-bg-warning',
                                    'cancelled'  => 'text-bg-danger',
                                    'completed'  => 'text-bg-secondary',
                                    default      => 'text-bg-light',
                                };
                            @endphp
                            <span class="badge {{ $badgeClass }}">{{ ucfirst($event->status) }}</span>
                        </td>
                        <td class="text-end">
                            @if(catmin_can('module.events.edit'))
                            <a href="{{ route('admin.events.edit', $event->id) }}" class="btn btn-sm btn-outline-secondary">Modifier</a>
                            @endif
                            @if(catmin_can('module.events.list'))
                            <a href="{{ route('admin.events.participants', $event->id) }}" class="btn btn-sm btn-outline-info">Inscrits</a>
                            <a href="{{ route('admin.events.tickets', $event->id) }}" class="btn btn-sm btn-outline-dark">Billets</a>
                            <a href="{{ route('admin.events.checkin', $event->id) }}" class="btn btn-sm btn-outline-success">Check-in</a>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">Aucun événement trouvé.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($events->hasPages())
        <div class="card-footer">{{ $events->links() }}</div>
        @endif
    </div>
</div>
@endsection
