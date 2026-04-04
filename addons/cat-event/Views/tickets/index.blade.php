@extends('admin.layouts.catmin')

@section('page_title', 'Billets — ' . $event->title)

@section('content')
<header class="catmin-page-header">
    <div>
        <h1 class="h3 mb-1">Billets</h1>
        <p class="text-muted mb-0"><strong>{{ $event->title }}</strong></p>
        <a href="{{ route('admin.events.edit', $event->id) }}" class="text-muted small">← Retour à l'événement</a>
    </div>
</header>

<div class="catmin-page-body">
    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="card">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h2 class="h6 mb-0">Billets émis</h2>
            <span class="badge text-bg-light">{{ $tickets->total() }}</span>
        </div>
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Participant</th>
                        <th>Email</th>
                        <th>Source</th>
                        <th>Statut</th>
                        <th>Émis le</th>
                        <th>Check-in</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tickets as $ticket)
                    <tr>
                        <td><code>{{ $ticket->publicCode() }}</code></td>
                        <td>{{ $ticket->participant?->fullName() ?? '—' }}</td>
                        <td>{{ $ticket->participant?->email ?? '—' }}</td>
                        <td>{{ $ticket->source ?? 'manual' }}</td>
                        <td>
                            @php
                                $tc = match($ticket->status) {
                                    'active', 'issued' => 'text-bg-success',
                                    'used'      => 'text-bg-primary',
                                    'cancelled' => 'text-bg-danger',
                                    'invalid'   => 'text-bg-dark',
                                    default     => 'text-bg-secondary',
                                };
                            @endphp
                            <span class="badge {{ $tc }}">{{ ucfirst($ticket->status) }}</span>
                        </td>
                        <td>{{ $ticket->issued_at?->format('d/m/Y H:i') ?? '—' }}</td>
                        <td>{{ ($ticket->used_at ?? $ticket->checkin_at)?->format('d/m/Y H:i') ?? '—' }}</td>
                        <td>
                            <div class="d-flex gap-2">
                            @if(in_array($ticket->status, ['active', 'issued'], true) && catmin_can('module.events.edit'))
                            <form method="POST" action="{{ route('admin.events.tickets.cancel', [$event->id, $ticket->id]) }}"
                                  onsubmit="return confirm('Annuler ce billet ?')">
                                @csrf @method('PATCH')
                                <button type="submit" class="btn btn-sm btn-outline-danger">Annuler billet</button>
                            </form>
                            @endif

                                                        @if(catmin_can('event.ticket.regenerate'))
                            <form method="POST" action="{{ route('admin.events.tickets.regenerate', [$event->id, $ticket->id]) }}"
                                  onsubmit="return confirm('Régénérer le token/QR de ce billet ?')">
                                @csrf @method('PATCH')
                                <button type="submit" class="btn btn-sm btn-outline-secondary">Régénérer</button>
                            </form>
                            @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">Aucun billet émis.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($tickets->hasPages())
        <div class="card-footer">{{ $tickets->links() }}</div>
        @endif
    </div>
</div>
@endsection
