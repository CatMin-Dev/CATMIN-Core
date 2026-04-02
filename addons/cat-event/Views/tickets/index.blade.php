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
                        <th>Numéro</th>
                        <th>Participant</th>
                        <th>Email</th>
                        <th>Statut</th>
                        <th>Émis le</th>
                        <th>Check-in</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tickets as $ticket)
                    <tr>
                        <td><code>{{ $ticket->ticket_number }}</code></td>
                        <td>{{ $ticket->participant?->fullName() ?? '—' }}</td>
                        <td>{{ $ticket->participant?->email ?? '—' }}</td>
                        <td>
                            @php
                                $tc = match($ticket->status) {
                                    'active'    => 'text-bg-success',
                                    'used'      => 'text-bg-primary',
                                    'cancelled' => 'text-bg-danger',
                                    default     => 'text-bg-secondary',
                                };
                            @endphp
                            <span class="badge {{ $tc }}">{{ ucfirst($ticket->status) }}</span>
                        </td>
                        <td>{{ $ticket->issued_at?->format('d/m/Y H:i') ?? '—' }}</td>
                        <td>{{ $ticket->checkin_at?->format('d/m/Y H:i') ?? '—' }}</td>
                        <td>
                            @if($ticket->status === 'active' && catmin_can('module.events.edit'))
                            <form method="POST" action="{{ route('admin.events.tickets.cancel', [$event->id, $ticket->id]) }}"
                                  onsubmit="return confirm('Annuler ce billet ?')">
                                @csrf @method('PATCH')
                                <button type="submit" class="btn btn-sm btn-outline-danger">Annuler billet</button>
                            </form>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">Aucun billet émis.</td></tr>
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
