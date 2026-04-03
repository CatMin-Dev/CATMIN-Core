@extends('admin.layouts.catmin')

@section('page_title', 'Sliders · ' . $slider->name)

@section('content')
<header class="catmin-page-header d-flex justify-content-between align-items-end">
    <div>
        <h1 class="h3 mb-1">{{ $slider->name }}</h1>
        <p class="text-muted mb-0">
            Type : <strong>{{ $types[$slider->type] ?? $slider->type }}</strong> &bull;
            Slug : <code>{{ $slider->slug }}</code>
        </p>
    </div>
    <div class="d-flex gap-2">
        <form method="POST" action="{{ route('admin.slider.toggle', $slider->id) }}">
            @csrf @method('PATCH')
            <button class="btn btn-outline-{{ $slider->is_active ? 'warning' : 'success' }}">
                <i class="bi {{ $slider->is_active ? 'bi-eye-slash' : 'bi-eye' }} me-1"></i>
                {{ $slider->is_active ? 'Désactiver' : 'Activer' }}
            </button>
        </form>
        <a href="{{ route('admin.slider.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Retour
        </a>
    </div>
</header>

<div class="catmin-page-body">
    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="row g-4">
        {{-- Left: edit form --}}
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header"><strong>Paramètres du slider</strong></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.slider.update', $slider->id) }}">
                        @csrf @method('PUT')

                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label">Nom</label>
                                <input type="text" name="name" class="form-control" value="{{ old('name', $slider->name) }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Slug</label>
                                <input type="text" name="slug" class="form-control" value="{{ old('slug', $slider->slug) }}">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="2">{{ old('description', $slider->description) }}</textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Type</label>
                                <select name="type" id="slider-type-edit" class="form-select">
                                    @foreach($types as $val => $lbl)
                                        <option value="{{ $val }}" @selected(old('type', $slider->type) === $val)>{{ $lbl }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Début</label>
                                <input type="datetime-local" name="starts_at" class="form-control"
                                    value="{{ old('starts_at', $slider->starts_at?->format('Y-m-d\TH:i')) }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Fin</label>
                                <input type="datetime-local" name="ends_at" class="form-control"
                                    value="{{ old('ends_at', $slider->ends_at?->format('Y-m-d\TH:i')) }}">
                            </div>

                            @php($s = $slider->mergedSettings())

                            {{-- Fullwidth --}}
                            <div class="col-12 settings-edit-panel" id="edit-settings-fullwidth">
                                <hr class="my-1">
                                <small class="text-muted fw-semibold d-block mb-2">Pleine largeur</small>
                                <div class="row g-2">
                                    <div class="col-md-5">
                                        <label class="form-label small">Hauteur</label>
                                        <input type="text" name="settings_height" class="form-control form-control-sm"
                                            value="{{ old('settings_height', $s['height'] ?? '500px') }}" placeholder="500px">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small">Intervalle (ms)</label>
                                        <input type="number" name="settings_interval" class="form-control form-control-sm"
                                            value="{{ old('settings_interval', $s['interval'] ?? 5000) }}" min="500" max="30000">
                                    </div>
                                    <div class="col-md-3 d-flex flex-column gap-1 pt-3">
                                        <div class="form-check form-check-sm">
                                            <input class="form-check-input" type="checkbox" name="settings_autoplay" value="1"
                                                id="e_autoplay" @checked(old('settings_autoplay', $s['autoplay'] ?? true))>
                                            <label class="form-check-label small" for="e_autoplay">Autoplay</label>
                                        </div>
                                        <div class="form-check form-check-sm">
                                            <input class="form-check-input" type="checkbox" name="settings_show_controls" value="1"
                                                id="e_controls" @checked(old('settings_show_controls', $s['show_controls'] ?? true))>
                                            <label class="form-check-label small" for="e_controls">Flèches</label>
                                        </div>
                                        <div class="form-check form-check-sm">
                                            <input class="form-check-input" type="checkbox" name="settings_show_indicators" value="1"
                                                id="e_indicators" @checked(old('settings_show_indicators', $s['show_indicators'] ?? true))>
                                            <label class="form-check-label small" for="e_indicators">Indicateurs</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Carousel --}}
                            <div class="col-12 settings-edit-panel d-none" id="edit-settings-carousel">
                                <hr class="my-1">
                                <small class="text-muted fw-semibold d-block mb-2">Carrousel continu</small>
                                <div class="row g-2">
                                    <div class="col-md-4">
                                        <label class="form-label small">Hauteur</label>
                                        <input type="text" name="settings_height" class="form-control form-control-sm"
                                            value="{{ old('settings_height', $s['height'] ?? '120px') }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small">Vitesse (ms)</label>
                                        <input type="number" name="settings_scroll_speed" class="form-control form-control-sm"
                                            value="{{ old('settings_scroll_speed', $s['scroll_speed'] ?? 3000) }}" min="500" max="30000">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small">Gap</label>
                                        <input type="text" name="settings_gap" class="form-control form-control-sm"
                                            value="{{ old('settings_gap', $s['gap'] ?? '24px') }}">
                                    </div>
                                </div>
                            </div>

                            {{-- Grid --}}
                            <div class="col-12 settings-edit-panel d-none" id="edit-settings-grid">
                                <hr class="my-1">
                                <small class="text-muted fw-semibold d-block mb-2">Grille</small>
                                <div class="row g-2">
                                    <div class="col-md-4">
                                        <label class="form-label small">Colonnes</label>
                                        <select name="settings_columns" class="form-select form-select-sm">
                                            @foreach([4, 5, 6] as $c)
                                                <option value="{{ $c }}" @selected(old('settings_columns', $s['columns'] ?? 4) == $c)>{{ $c }} colonnes</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small">Hauteur cellule</label>
                                        <input type="text" name="settings_height" class="form-control form-control-sm"
                                            value="{{ old('settings_height', $s['height'] ?? '300px') }}">
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 d-flex justify-content-between align-items-center pt-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_active" id="e_active"
                                        value="1" @checked($slider->is_active)>
                                    <label class="form-check-label" for="e_active">Actif</label>
                                </div>
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="bi bi-check-lg me-1"></i>Enregistrer
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Right: items --}}
        <div class="col-lg-6">
            {{-- Add item form --}}
            <div class="card mb-3">
                <div class="card-header"><strong>Ajouter un élément</strong></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.slider.items.store', $slider->id) }}">
                        @csrf
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label class="form-label small">Titre</label>
                                <input type="text" name="title" class="form-control form-control-sm" placeholder="Titre ou légende">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">Sous-titre</label>
                                <input type="text" name="subtitle" class="form-control form-control-sm">
                            </div>
                            <div class="col-12">
                                <label class="form-label small">URL image</label>
                                <input type="url" name="media_url" class="form-control form-control-sm" placeholder="https://…">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">Label CTA</label>
                                <input type="text" name="cta_label" class="form-control form-control-sm" placeholder="En savoir plus">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">URL CTA</label>
                                <input type="url" name="cta_url" class="form-control form-control-sm" placeholder="https://…">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">Type lien</label>
                                <select name="link_type" class="form-select form-select-sm">
                                    <option value="">— aucun —</option>
                                    @foreach($linkTypes as $lv => $ll)
                                        <option value="{{ $lv }}">{{ $ll }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">ID lié</label>
                                <input type="number" name="link_id" class="form-control form-control-sm" placeholder="ID">
                            </div>
                            <div class="col-12 d-flex justify-content-between align-items-center">
                                <div class="form-check form-check-sm">
                                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="item_active" checked>
                                    <label class="form-check-label small" for="item_active">Actif</label>
                                </div>
                                <button type="submit" class="btn btn-sm btn-success">
                                    <i class="bi bi-plus-lg me-1"></i>Ajouter
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Items list --}}
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong>Éléments ({{ $slider->items->count() }})</strong>
                    <small class="text-muted">Glissez pour réordonner</small>
                </div>
                <div id="items-list" class="list-group list-group-flush">
                    @forelse($slider->items as $item)
                        <div class="list-group-item" data-item-id="{{ $item->id }}">
                            <div class="d-flex gap-3 align-items-start">
                                <div class="text-muted" style="cursor:grab;line-height:2" title="Déplacer">
                                    <i class="bi bi-grip-vertical"></i>
                                </div>
                                @if($item->resolvedImageUrl())
                                    <img src="{{ $item->resolvedImageUrl() }}" alt="" style="width:56px;height:40px;object-fit:cover;border-radius:4px">
                                @else
                                    <div style="width:56px;height:40px;background:#f0f0f0;border-radius:4px;display:flex;align-items:center;justify-content:center">
                                        <i class="bi bi-image text-muted"></i>
                                    </div>
                                @endif
                                <div class="flex-fill min-w-0">
                                    <div class="fw-semibold text-truncate">{{ $item->title ?: '(sans titre)' }}</div>
                                    @if($item->subtitle)
                                        <div class="small text-muted text-truncate">{{ $item->subtitle }}</div>
                                    @endif
                                    @if($item->cta_url)
                                        <a href="{{ $item->cta_url }}" class="small text-primary text-truncate d-block" target="_blank">
                                            {{ $item->cta_label ?: $item->cta_url }}
                                        </a>
                                    @endif
                                </div>
                                <div class="d-flex flex-column gap-1 flex-shrink-0">
                                    @if($item->is_active)
                                        <span class="badge text-bg-success">Actif</span>
                                    @else
                                        <span class="badge text-bg-secondary">Inactif</span>
                                    @endif
                                    <form method="POST" action="{{ route('admin.slider.items.destroy', [$slider->id, $item->id]) }}">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger w-100" onclick="return confirm('Supprimer ?')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="list-group-item text-center text-muted py-4">Aucun élément.</div>
                    @endforelse
                </div>
            </div>

            {{-- Preview --}}
            @if($slider->items->isNotEmpty())
                <div class="card mt-3">
                    <div class="card-header"><strong>Aperçu</strong></div>
                    <div class="card-body p-0">
                        @include('catmin-slider::preview.preview-card', ['slider' => $slider])
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
(function() {
    // Settings panel toggle on edit
    const typeSelect = document.getElementById('slider-type-edit');
    const panels = document.querySelectorAll('.settings-edit-panel');

    function showPanel(type) {
        panels.forEach(p => p.classList.add('d-none'));
        const target = document.getElementById('edit-settings-' + type);
        if (target) target.classList.remove('d-none');
    }

    if (typeSelect) {
        typeSelect.addEventListener('change', () => showPanel(typeSelect.value));
        showPanel(typeSelect.value);
    }

    // Drag-to-reorder items
    const list = document.getElementById('items-list');
    if (list && typeof Sortable !== 'undefined') {
        Sortable.create(list, {
            handle: '.bi-grip-vertical',
            animation: 150,
            onEnd: function() {
                const order = [...list.querySelectorAll('[data-item-id]')].map(el => el.dataset.itemId);
                fetch('{{ route('admin.slider.items.reorder', $slider->id) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ order })
                });
            }
        });
    }
})();
</script>
@endpush
@endsection
