@extends('admin.layouts.catmin')

@section('page_title', 'Modifier — ' . $event->title)

@section('content')
<header class="catmin-page-header d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-3">
    <div>
        <h1 class="h3 mb-1">{{ $event->title }}</h1>
        <a href="{{ route('admin.events.index') }}" class="text-muted small">← Retour à la liste</a>
    </div>
    <div class="d-flex gap-2">
        @if(catmin_can('module.events.edit'))
        <form method="POST" action="{{ route('admin.events.toggle_status', $event->id) }}">
            @csrf @method('PATCH')
            <button class="btn btn-outline-{{ $event->status === 'published' ? 'warning' : 'success' }}" type="submit">
                {{ $event->status === 'published' ? 'Dépublier' : 'Publier' }}
            </button>
        </form>
        @endif
        @if(catmin_can('module.events.delete'))
        <form method="POST" action="{{ route('admin.events.destroy', $event->id) }}" onsubmit="return confirm('Supprimer cet événement ?')">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-outline-danger">Supprimer</button>
        </form>
        @endif
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

    <form method="POST" action="{{ route('admin.events.update', $event->id) }}">
        @csrf @method('PUT')
        @include('cat-event::_form', ['event' => $event, 'statuses' => $statuses])
        <div class="mt-4 d-flex gap-2">
            <button type="submit" class="btn btn-primary">Enregistrer</button>
            <a href="{{ route('admin.events.participants', $event->id) }}" class="btn btn-outline-info">Inscrits ({{ $event->participants->count() }})</a>
            <a href="{{ route('admin.events.tickets', $event->id) }}" class="btn btn-outline-dark">Billets</a>
            <a href="{{ route('admin.events.checkin', $event->id) }}" class="btn btn-outline-success">Check-in</a>
        </div>
    </form>

    {{-- Sessions --}}
    <div class="card mt-5">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h2 class="h6 mb-0">Sessions / dates multiples</h2>
        </div>
        <div class="card-body">
            @forelse($event->sessions as $session)
            <div class="d-flex justify-content-between align-items-start border-bottom pb-2 mb-2">
                <div>
                    <strong>{{ $session->title }}</strong><br>
                    <small class="text-muted">{{ $session->start_at->format('d/m/Y H:i') }} → {{ $session->end_at->format('d/m/Y H:i') }} | {{ $session->location ?? 'Même lieu' }}</small>
                </div>
                <form method="POST" action="{{ route('admin.events.sessions.destroy', [$event->id, $session->id]) }}" onsubmit="return confirm('Supprimer cette session ?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger">Sup.</button>
                </form>
            </div>
            @empty
            <p class="text-muted">Aucune session secondaire.</p>
            @endforelse

            @if(catmin_can('module.events.edit'))
            <form method="POST" action="{{ route('admin.events.sessions.store', $event->id) }}" class="row g-3 mt-2">
                @csrf
                <div class="col-md-3">
                    <label class="form-label">Titre session</label>
                    <input type="text" name="title" class="form-control" placeholder="Atelier 1" required>
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
                    <label class="form-label">Lieu (optionnel)</label>
                    <input type="text" name="location" class="form-control">
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button type="submit" class="btn btn-outline-primary w-100">+</button>
                </div>
            </form>
            @endif
        </div>
    </div>
</div>
@endsection
