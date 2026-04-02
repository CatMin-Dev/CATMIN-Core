@extends('admin.layouts.catmin')

@section('page_title', 'Participants — ' . $event->title)

@section('content')
<header class="catmin-page-header d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-3">
    <div>
        <h1 class="h3 mb-1">Participants</h1>
        <p class="text-muted mb-0">
            <strong>{{ $event->title }}</strong> — {{ $event->start_at->format('d/m/Y') }}
        </p>
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

    {{-- Formulaire ajout participant --}}
    @if(catmin_can('module.events.create'))
    <div class="card mb-4">
        <div class="card-header bg-white"><h5 class="h6 mb-0">Inscrire un participant</h5></div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.events.participants.store', $event->id) }}" class="row g-3">
                @csrf
                <div class="col-md-3">
                    <label class="form-label">Prénom <span class="text-danger">*</span></label>
                    <input type="text" name="first_name" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Nom</label>
                    <input type="text" name="last_name" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Email <span class="text-danger">*</span></label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Téléphone</label>
                    <input type="tel" name="phone" class="form-control">
                </div>
                @if($event->sessions->isNotEmpty())
                <div class="col-md-3">
                    <label class="form-label">Session</label>
                    <select name="event_session_id" class="form-select">
                        <option value="">Événement principal</option>
                        @foreach($event->sessions as $session)
                            <option value="{{ $session->id }}">{{ $session->title }} — {{ $session->start_at->format('d/m H:i') }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Inscrire</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- Liste participants --}}
    <div class="card">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h2 class="h6 mb-0">Inscrits</h2>
            <span class="badge text-bg-light">{{ $participants->total() }}</span>
        </div>
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Nom</th>
                        <th>Email</th>
                        <th>Téléphone</th>
                        <th>Session</th>
                        <th>Billet</th>
                        <th>Statut</th>
                        <th>Inscrit le</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($participants as $participant)
                    <tr>
                        <td>{{ $participant->fullName() }}</td>
                        <td>{{ $participant->email }}</td>
                        <td>{{ $participant->phone ?? '—' }}</td>
                        <td>{{ $participant->session?->title ?? '—' }}</td>
                        <td>
                            @if($participant->ticket)
                                <code class="small">{{ $participant->ticket->ticket_number }}</code>
                                <span class="badge ms-1 text-bg-{{ $participant->ticket->status === 'used' ? 'success' : ($participant->ticket->status === 'cancelled' ? 'danger' : 'info') }}">
                                    {{ $participant->ticket->status }}
                                </span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            @if(catmin_can('module.events.edit'))
                            <form method="POST" action="{{ route('admin.events.participants.status', [$event->id, $participant->id]) }}" class="d-inline">
                                @csrf @method('PATCH')
                                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                                    @foreach(['pending','confirmed','cancelled','attended'] as $s)
                                        <option value="{{ $s }}" @selected($participant->status === $s)>{{ ucfirst($s) }}</option>
                                    @endforeach
                                </select>
                            </form>
                            @else
                                <span class="badge text-bg-secondary">{{ $participant->status }}</span>
                            @endif
                        </td>
                        <td>{{ $participant->registered_at?->format('d/m/Y H:i') ?? '—' }}</td>
                        <td>
                            @if(catmin_can('module.events.delete'))
                            <form method="POST" action="{{ route('admin.events.participants.destroy', [$event->id, $participant->id]) }}"
                                  onsubmit="return confirm('Supprimer ce participant ?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">Sup.</button>
                            </form>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">Aucun participant inscrit.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($participants->hasPages())
        <div class="card-footer">{{ $participants->links() }}</div>
        @endif
    </div>
</div>
@endsection
