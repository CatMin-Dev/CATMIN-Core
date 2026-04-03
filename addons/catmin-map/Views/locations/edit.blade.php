@extends('admin.layouts.catmin')

@section('page_title', $location ? 'Map · Modifier ' . $location->name : 'Map · Nouveau lieu')

@section('content')
<header class="catmin-page-header d-flex justify-content-between align-items-end">
    <div>
        <h1 class="h3 mb-1">
            <i class="bi bi-geo-alt me-2"></i>
            {{ $location ? 'Modifier : ' . $location->name : 'Nouveau lieu' }}
        </h1>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.map.locations.index') }}" class="btn btn-sm btn-outline-secondary">← Retour</a>
        <a href="{{ route('admin.map.index') }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-map me-1"></i>Carte</a>
    </div>
</header>

<div class="catmin-page-body">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger">{{ implode(', ', $errors->all()) }}</div>@endif

    <form method="POST"
          action="{{ $location ? route('admin.map.locations.update', $location->id) : route('admin.map.locations.store') }}"
          id="locationForm">
        @csrf
        @if($location) @method('PUT') @endif

        <div class="row g-4">
            {{-- Infos principales --}}
            <div class="col-xl-8">
                <div class="card mb-4">
                    <div class="card-header bg-white"><strong>Informations</strong></div>
                    <div class="card-body row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Nom <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" required maxlength="191"
                                value="{{ old('name', $location->name ?? '') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Catégorie</label>
                            <select name="geo_category_id" class="form-select">
                                <option value="">— Aucune —</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}"
                                        {{ old('geo_category_id', $location->geo_category_id ?? '') == $cat->id ? 'selected' : '' }}>
                                        {{ $cat->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3">{{ old('description', $location->description ?? '') }}</textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Téléphone</label>
                            <input type="text" name="phone" class="form-control" maxlength="64"
                                value="{{ old('phone', $location->phone ?? '') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" maxlength="191"
                                value="{{ old('email', $location->email ?? '') }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Site web</label>
                            <input type="url" name="website" class="form-control" maxlength="255"
                                value="{{ old('website', $location->website ?? '') }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Horaires d'ouverture</label>
                            <textarea name="opening_hours" class="form-control" rows="3"
                                placeholder="Lun–Ven : 9h–18h&#10;Sam : 10h–16h">{{ old('opening_hours', $location->opening_hours ?? '') }}</textarea>
                        </div>
                    </div>
                </div>

                {{-- Adresse & Coordonnées --}}
                <div class="card mb-4">
                    <div class="card-header bg-white"><strong>Adresse &amp; Coordonnées GPS</strong></div>
                    <div class="card-body row g-3">
                        <div class="col-12">
                            <label class="form-label">Adresse <span class="text-danger">*</span></label>
                            <input type="text" name="address" class="form-control" maxlength="255"
                                required
                                value="{{ old('address', $location->address ?? '') }}">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">Ville</label>
                            <input type="text" name="city" class="form-control" maxlength="120"
                                value="{{ old('city', $location->city ?? '') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Pays</label>
                            <input type="text" name="country" class="form-control" maxlength="120"
                                value="{{ old('country', $location->country ?? '') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Code postal</label>
                            <input type="text" name="zip" class="form-control" maxlength="32"
                                value="{{ old('zip', $location->zip ?? '') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Latitude (auto)</label>
                            <input type="text" class="form-control" value="{{ $location?->lat ?? 'générée à l\'enregistrement' }}" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Longitude (auto)</label>
                            <input type="text" class="form-control" value="{{ $location?->lng ?? 'générée à l\'enregistrement' }}" readonly>
                        </div>

                        {{-- Mini-map aperçu des coords générées --}}
                        <div class="col-12">
                            <p class="small text-muted mb-1">Les coordonnées sont générées automatiquement à partir de l'adresse.</p>
                            <div id="pickMap"
                                 data-lat="{{ $location?->lat ?? '' }}"
                                 data-lng="{{ $location?->lng ?? '' }}"
                                 style="height:300px;border-radius:8px;"></div>
                        </div>
                    </div>
                </div>

                {{-- Intégrations --}}
                <div class="card mb-4">
                    <div class="card-header bg-white"><strong>Intégrations</strong></div>
                    <div class="card-body row g-3">
                        <div class="col-md-4">
                            <label class="form-label">ID Événement lié</label>
                            <input type="number" name="linked_event_id" class="form-control" min="1"
                                value="{{ old('linked_event_id', $location->linked_event_id ?? '') }}"
                                placeholder="ID événement">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">ID Shop lié</label>
                            <input type="number" name="linked_shop_id" class="form-control" min="1"
                                value="{{ old('linked_shop_id', $location->linked_shop_id ?? '') }}"
                                placeholder="ID boutique">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">ID Page liée</label>
                            <input type="number" name="linked_page_id" class="form-control" min="1"
                                value="{{ old('linked_page_id', $location->linked_page_id ?? '') }}"
                                placeholder="ID page">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Sidebar --}}
            <div class="col-xl-4">
                <div class="card mb-4">
                    <div class="card-header bg-white"><strong>Publication</strong></div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Statut</label>
                            <select name="status" class="form-select">
                                <option value="published" {{ old('status', $location->status ?? 'published') === 'published' ? 'selected' : '' }}>Publié</option>
                                <option value="draft" {{ old('status', $location->status ?? '') === 'draft' ? 'selected' : '' }}>Brouillon</option>
                                <option value="archived" {{ old('status', $location->status ?? '') === 'archived' ? 'selected' : '' }}>Archivé</option>
                            </select>
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="featured" value="1" id="featuredCheck"
                                {{ old('featured', $location->featured ?? false) ? 'checked' : '' }}>
                            <label class="form-check-label" for="featuredCheck">Mis en avant</label>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            {{ $location ? 'Enregistrer' : 'Créer le lieu' }}
                        </button>
                    </div>
                </div>

                @if($location)
                <div class="card mb-4">
                    <div class="card-header bg-white"><strong>Informations</strong></div>
                    <div class="card-body small text-muted">
                        <div>Créé : {{ $location->created_at->format('d/m/Y H:i') }}</div>
                        <div>Modifié : {{ $location->updated_at->format('d/m/Y H:i') }}</div>
                        <div class="mt-2">Slug : <code>{{ $location->slug }}</code></div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </form>
</div>

@push('scripts')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
(function () {
    var mapNode = document.getElementById('pickMap');
    if (!mapNode) {
        return;
    }

    var latValue = parseFloat(mapNode.dataset.lat || '');
    var lngValue = parseFloat(mapNode.dataset.lng || '');
    var hasCoords = !isNaN(latValue) && !isNaN(lngValue);
    var initLat = hasCoords ? latValue : 46.5;
    var initLng = hasCoords ? lngValue : 2.3;
    var zoom = hasCoords ? 13 : 5;

    var map = L.map('pickMap').setView([initLat, initLng], zoom);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 19
    }).addTo(map);

    if (hasCoords) {
        L.marker([initLat, initLng]).addTo(map);
    }
})();
</script>
@endpush
@endsection
