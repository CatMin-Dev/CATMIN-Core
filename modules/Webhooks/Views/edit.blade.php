@extends('admin.layouts.catmin')

@section('page_title', 'Modifier webhook')

@section('content')
<x-admin.crud.page-header
    title="Modifier : {{ $webhook->name }}"
    subtitle="Mettre à jour les paramètres du webhook sortant."
>
    <a href="{{ route('admin.webhooks.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Retour
    </a>
</x-admin.crud.page-header>

<div class="catmin-page-body">
    <x-admin.crud.flash-messages />

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.webhooks.update', $webhook->id) }}">
                @csrf
                @method('PUT')
                @php $selectedEvents = old('events', $webhook->events ?? []); @endphp

                <div class="mb-3">
                    <label for="wh-name" class="form-label">Nom <span class="text-danger">*</span></label>
                    <input id="wh-name" type="text" class="form-control @error('name') is-invalid @enderror"
                           name="name" value="{{ old('name', $webhook->name) }}" required maxlength="191">
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label for="wh-url" class="form-label">URL de destination <span class="text-danger">*</span></label>
                    <input id="wh-url" type="url" class="form-control @error('url') is-invalid @enderror"
                           name="url" value="{{ old('url', $webhook->url) }}" required maxlength="500"
                           placeholder="https://example.com/webhook">
                    @error('url')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Événements déclencheurs</label>
                    <div class="row g-2">
                        @foreach($availableEvents as $event)
                        <div class="col-12 col-sm-6 col-md-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox"
                                       name="events[]" value="{{ $event }}"
                                       id="ev-{{ $loop->index }}"
                                       @checked(in_array($event, $selectedEvents))>
                                <label class="form-check-label" for="ev-{{ $loop->index }}">
                                    <code>{{ $event }}</code>
                                </label>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

                <div class="mb-3">
                    <label for="wh-secret" class="form-label">Secret HMAC (optionnel)</label>
                    <input id="wh-secret" type="text" class="form-control font-monospace @error('secret') is-invalid @enderror"
                           name="secret" value="{{ old('secret', $webhook->secret) }}" maxlength="255"
                           placeholder="Laisser vide pour désactiver la signature">
                    <div class="form-text">Si renseigné, le header <code>X-Catmin-Signature: sha256=…</code> sera ajouté.</div>
                    @error('secret')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label for="wh-status" class="form-label">Statut</label>
                    <select id="wh-status" name="status" class="form-select @error('status') is-invalid @enderror">
                        <option value="active" @selected(old('status', $webhook->status) === 'active')>Actif</option>
                        <option value="inactive" @selected(old('status', $webhook->status) === 'inactive')>Inactif</option>
                    </select>
                    @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="d-flex gap-2 mt-4">
                    <button class="btn btn-primary" type="submit">
                        <i class="bi bi-floppy me-1"></i>Enregistrer
                    </button>
                    <a href="{{ route('admin.webhooks.index') }}" class="btn btn-outline-secondary">Annuler</a>
                </div>
            </form>
        </div>
    </div>

    {{-- Secret rotation panel --}}
    @php($canEdit = catmin_can('module.webhooks.edit'))
    <div class="card border-warning mt-4">
        <div class="card-header bg-warning-subtle">
            <h2 class="h6 mb-0"><i class="bi bi-key me-1"></i>Rotation du secret HMAC</h2>
        </div>
        <div class="card-body">
            @if($webhook->rotation_status === 'pending')
                <div class="alert alert-warning mb-3">
                    <strong>Rotation en cours</strong> — Un nouveau secret est en attente depuis
                    {{ optional($webhook->pending_rotation_at)->diffForHumans() ?? 'bientôt' }}.
                    Les deux secrets sont actuellement acceptés.
                </div>
                <p class="text-muted small mb-3">
                    Configurez le nouveau secret chez votre partenaire, puis cliquez sur <em>Valider</em> pour désactiver l'ancien.
                </p>
                <form method="POST" action="{{ route('admin.webhooks.complete-rotation', $webhook->id) }}" class="d-inline">
                    @csrf
                    <button class="btn btn-success btn-sm" type="submit" @disabled(!$canEdit)>
                        <i class="bi bi-check-circle me-1"></i>Valider & activer le nouveau secret
                    </button>
                </form>
            @else
                <p class="text-muted small mb-3">
                    Initier une rotation génère un nouveau secret aléatoire.
                    L'ancien et le nouveau sont tous les deux acceptés pendant 24h pour permettre la mise à jour côté partenaire.
                </p>
                <form method="POST" action="{{ route('admin.webhooks.rotate-secret', $webhook->id) }}" class="d-inline"
                      onsubmit="return confirm('Initier la rotation du secret ?')">
                    @csrf
                    <button class="btn btn-warning btn-sm" type="submit" @disabled(!$canEdit || empty($webhook->secret))>
                        <i class="bi bi-arrow-repeat me-1"></i>Initier la rotation du secret
                    </button>
                    @if(empty($webhook->secret))
                        <span class="text-muted ms-2 small">Définissez d'abord un secret pour activer la rotation.</span>
                    @endif
                </form>
            @endif
        </div>
    </div>
</div>
@endsection
