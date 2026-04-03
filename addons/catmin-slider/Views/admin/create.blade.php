@extends('admin.layouts.catmin')

@section('page_title', 'Sliders · Nouveau')

@section('content')
<header class="catmin-page-header d-flex justify-content-between align-items-end">
    <div>
        <h1 class="h3 mb-1">Nouveau slider</h1>
        <p class="text-muted mb-0">Configurez le type et les paramètres initiaux du slider.</p>
    </div>
    <a href="{{ route('admin.slider.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Retour
    </a>
</header>

<div class="catmin-page-body">
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.slider.store') }}" id="slider-form">
        @csrf

        <div class="row g-4">
            {{-- Main --}}
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header"><strong>Informations</strong></div>
                    <div class="card-body row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Nom <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                value="{{ old('name') }}" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Slug</label>
                            <input type="text" name="slug" class="form-control @error('slug') is-invalid @enderror"
                                value="{{ old('slug') }}" placeholder="auto-généré">
                            @error('slug')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="2">{{ old('description') }}</textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Type <span class="text-danger">*</span></label>
                            <select name="type" id="slider-type" class="form-select @error('type') is-invalid @enderror" required>
                                @foreach($types as $value => $label)
                                    <option value="{{ $value }}" @selected(old('type', 'fullwidth') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Début</label>
                            <input type="datetime-local" name="starts_at" class="form-control" value="{{ old('starts_at') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Fin</label>
                            <input type="datetime-local" name="ends_at" class="form-control" value="{{ old('ends_at') }}">
                        </div>
                    </div>
                </div>

                {{-- Settings: fullwidth --}}
                <div class="card mt-4 settings-panel" id="settings-fullwidth">
                    <div class="card-header"><strong>Paramètres — Pleine largeur</strong></div>
                    <div class="card-body row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Hauteur</label>
                            <input type="text" name="settings_height" class="form-control"
                                value="{{ old('settings_height', '500px') }}" placeholder="500px ou 60vh">
                            <div class="form-text">ex: 400px, 60vh, 30rem</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Intervalle (ms)</label>
                            <input type="number" name="settings_interval" class="form-control"
                                value="{{ old('settings_interval', 5000) }}" min="500" max="30000" step="100">
                        </div>
                        <div class="col-md-4 d-flex flex-column gap-2 pt-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="settings_autoplay"
                                    id="autoplay" value="1" @checked(old('settings_autoplay', 1))>
                                <label class="form-check-label" for="autoplay">Défilement automatique</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="settings_show_controls"
                                    id="show_controls" value="1" @checked(old('settings_show_controls', 1))>
                                <label class="form-check-label" for="show_controls">Flèches nav</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="settings_show_indicators"
                                    id="show_indicators" value="1" @checked(old('settings_show_indicators', 1))>
                                <label class="form-check-label" for="show_indicators">Indicateurs bas</label>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Settings: carousel --}}
                <div class="card mt-4 settings-panel d-none" id="settings-carousel">
                    <div class="card-header"><strong>Paramètres — Carrousel continu</strong></div>
                    <div class="card-body row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Hauteur items</label>
                            <input type="text" name="settings_height" class="form-control"
                                value="{{ old('settings_height', '120px') }}" placeholder="120px">
                            <div class="form-text">Hauteur de chaque image/logo</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Vitesse défilement (ms)</label>
                            <input type="number" name="settings_scroll_speed" class="form-control"
                                value="{{ old('settings_scroll_speed', 3000) }}" min="500" max="30000" step="100">
                            <div class="form-text">Durée d'un cycle complet</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Espacement</label>
                            <input type="text" name="settings_gap" class="form-control"
                                value="{{ old('settings_gap', '24px') }}" placeholder="24px">
                        </div>
                    </div>
                </div>

                {{-- Settings: grid --}}
                <div class="card mt-4 settings-panel d-none" id="settings-grid">
                    <div class="card-header"><strong>Paramètres — Grille</strong></div>
                    <div class="card-body row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Colonnes</label>
                            <select name="settings_columns" class="form-select">
                                <option value="4" @selected(old('settings_columns') == 4)>4 colonnes</option>
                                <option value="5" @selected(old('settings_columns') == 5)>5 colonnes</option>
                                <option value="6" @selected(old('settings_columns') == 6)>6 colonnes</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Hauteur cellule</label>
                            <input type="text" name="settings_height" class="form-control"
                                value="{{ old('settings_height', '300px') }}" placeholder="300px">
                            <div class="form-text">ex: 200px, 25vh</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Sidebar --}}
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header"><strong>Publication</strong></div>
                    <div class="card-body">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="is_active" id="is_active"
                                value="1" @checked(old('is_active', 1))>
                            <label class="form-check-label" for="is_active">Activer immédiatement</label>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg me-1"></i>Créer le slider
                            </button>
                        </div>
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-header"><strong>Types disponibles</strong></div>
                    <div class="card-body small text-muted">
                        <p class="mb-1"><strong>Pleine largeur</strong> — hero banner avec défilement Bootstrap, hauteur libre.</p>
                        <p class="mb-1"><strong>Carrousel continu</strong> — défilement CSS infini, idéal logos/marques/photos.</p>
                        <p class="mb-0"><strong>Grille</strong> — 4, 5 ou 6 colonnes, hauteur fixe définie.</p>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
(function() {
    const typeSelect = document.getElementById('slider-type');
    const panels = document.querySelectorAll('.settings-panel');

    function showPanel(type) {
        panels.forEach(p => p.classList.add('d-none'));
        const target = document.getElementById('settings-' + type);
        if (target) target.classList.remove('d-none');
    }

    typeSelect.addEventListener('change', () => showPanel(typeSelect.value));
    showPanel(typeSelect.value);
})();
</script>
@endpush
@endsection
