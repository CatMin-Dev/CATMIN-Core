<div class="row g-4">
    {{-- Informations principales --}}
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header bg-white"><h5 class="mb-0">Informations</h5></div>
            <div class="card-body row g-3">
                <div class="col-12">
                    <label class="form-label fw-semibold">Titre <span class="text-danger">*</span></label>
                    <input type="text" name="title" class="form-control @error('title') is-invalid @enderror"
                        value="{{ old('title', $event?->title) }}" required>
                    @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Slug</label>
                    <input type="text" name="slug" class="form-control" value="{{ old('slug', $event?->slug) }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Lieu</label>
                    <input type="text" name="location" class="form-control" value="{{ old('location', $event?->location) }}" placeholder="Paris, Salle XYZ...">
                </div>
                <div class="col-12">
                    <label class="form-label">Adresse</label>
                    <input type="text" name="address" class="form-control" value="{{ old('address', $event?->address) }}" placeholder="12 rue de l'Exemple, 75001 Paris">
                </div>
                <div class="col-12">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="6">{{ old('description', $event?->description) }}</textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Début <span class="text-danger">*</span></label>
                    <input type="datetime-local" name="start_at" class="form-control @error('start_at') is-invalid @enderror"
                        value="{{ old('start_at', $event?->start_at?->format('Y-m-d\TH:i')) }}" required>
                    @error('start_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Fin <span class="text-danger">*</span></label>
                    <input type="datetime-local" name="end_at" class="form-control @error('end_at') is-invalid @enderror"
                        value="{{ old('end_at', $event?->end_at?->format('Y-m-d\TH:i')) }}" required>
                    @error('end_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>

        {{-- Organisateur --}}
        <div class="card mt-4">
            <div class="card-header bg-white"><h5 class="mb-0">Organisateur</h5></div>
            <div class="card-body row g-3">
                <div class="col-md-6">
                    <label class="form-label">Nom</label>
                    <input type="text" name="organizer_name" class="form-control" value="{{ old('organizer_name', $event?->organizer_name) }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Email</label>
                    <input type="email" name="organizer_email" class="form-control" value="{{ old('organizer_email', $event?->organizer_email) }}">
                </div>
            </div>
        </div>
    </div>

    {{-- Sidebar --}}
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header bg-white"><h5 class="mb-0">Publication</h5></div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Statut <span class="text-danger">*</span></label>
                    <select name="status" class="form-select @error('status') is-invalid @enderror">
                        @foreach($statuses as $s)
                            <option value="{{ $s }}" @selected(old('status', $event?->status ?? 'draft') === $s)>{{ ucfirst($s) }}</option>
                        @endforeach
                    </select>
                    @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Visuel (chemin/URL)</label>
                    <input type="text" name="featured_image" class="form-control" value="{{ old('featured_image', $event?->featured_image) }}">
                </div>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header bg-white"><h5 class="mb-0">Inscriptions</h5></div>
            <div class="card-body">
                <div class="mb-3 form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="registration_enabled" id="registration_enabled" value="1"
                        @checked(old('registration_enabled', $event?->registration_enabled ?? true))>
                    <label class="form-check-label" for="registration_enabled">Inscriptions ouvertes</label>
                </div>
                <div class="mb-3">
                    <label class="form-label">Capacité max</label>
                    <input type="number" name="capacity" class="form-control" min="1" value="{{ old('capacity', $event?->capacity) }}" placeholder="Illimitée">
                </div>
                <div class="mb-3">
                    <label class="form-label">Date limite inscription</label>
                    <input type="datetime-local" name="registration_deadline" class="form-control"
                        value="{{ old('registration_deadline', $event?->registration_deadline?->format('Y-m-d\TH:i')) }}">
                </div>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header bg-white"><h5 class="mb-0">Tarification</h5></div>
            <div class="card-body">
                <div class="mb-3 form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="is_free" id="is_free" value="1"
                        @checked(old('is_free', $event?->is_free ?? true))>
                    <label class="form-check-label" for="is_free">Événement gratuit</label>
                </div>
                <div class="mb-3">
                    <label class="form-label">Prix du billet (€)</label>
                    <input type="number" name="ticket_price" class="form-control" min="0" step="0.01"
                        value="{{ old('ticket_price', $event?->ticket_price ?? '0') }}">
                </div>
            </div>
        </div>
    </div>
</div>
