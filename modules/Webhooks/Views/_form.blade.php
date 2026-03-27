@php
    $selectedEvents = old('events', $webhook?->events ?? []);
@endphp

<div class="mb-3">
    <label for="wh-name" class="form-label">Nom <span class="text-danger">*</span></label>
    <input id="wh-name" type="text" class="form-control @error('name') is-invalid @enderror"
           name="name" value="{{ old('name', $webhook?->name) }}" required maxlength="191">
    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<div class="mb-3">
    <label for="wh-url" class="form-label">URL de destination <span class="text-danger">*</span></label>
    <input id="wh-url" type="url" class="form-control @error('url') is-invalid @enderror"
           name="url" value="{{ old('url', $webhook?->url) }}" required maxlength="500"
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
    @error('events')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
</div>

<div class="mb-3">
    <label for="wh-secret" class="form-label">Secret HMAC (optionnel)</label>
    <input id="wh-secret" type="text" class="form-control font-monospace @error('secret') is-invalid @enderror"
           name="secret" value="{{ old('secret', $webhook?->secret) }}" maxlength="255"
           placeholder="Laisser vide pour désactiver la signature">
    <div class="form-text">Si renseigné, le header <code>X-Catmin-Signature: sha256=…</code> sera ajouté à chaque requête.</div>
    @error('secret')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<div class="mb-3">
    <label for="wh-status" class="form-label">Statut</label>
    <select id="wh-status" name="status" class="form-select @error('status') is-invalid @enderror">
        <option value="active" @selected(old('status', $webhook?->status ?? 'active') === 'active')>Actif</option>
        <option value="inactive" @selected(old('status', $webhook?->status) === 'inactive')>Inactif</option>
    </select>
    @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
