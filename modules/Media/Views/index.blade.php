@extends('admin.layouts.catmin')

@section('page_title', 'Media')

@section('content')
<x-admin.crud.page-header
    title="Media"
    subtitle="Bibliotheque media centrale avec recherche, filtres et upload rapide."
>
    @if(catmin_can('module.media.create'))
        <a class="btn btn-primary" href="{{ admin_route('media.create') }}">
            <i class="bi bi-upload me-1"></i>Uploader
        </a>
    @endif

    <div class="btn-group" role="group" aria-label="Filtre medias">
        <a class="btn btn-outline-secondary {{ ($scope ?? 'active') === 'active' ? 'active' : '' }}" href="{{ admin_route('media.manage', ['scope' => 'active']) }}">Actifs</a>
        <a class="btn btn-outline-secondary {{ ($scope ?? 'active') === 'all' ? 'active' : '' }}" href="{{ admin_route('media.manage', ['scope' => 'all']) }}">Tous</a>
        <a class="btn btn-outline-secondary {{ ($scope ?? 'active') === 'trash' ? 'active' : '' }}" href="{{ admin_route('media.manage', ['scope' => 'trash']) }}">Corbeille ({{ (int) ($trashedCount ?? 0) }})</a>
    </div>

    @if(($scope ?? 'active') === 'trash' && catmin_can('module.media.trash'))
        <form method="post" action="{{ admin_route('media.trash.empty') }}" onsubmit="return confirm('Vider toute la corbeille media ? Suppression definitive irreversible.');">
            @csrf
            @method('DELETE')
            <button class="btn btn-outline-danger" type="submit">
                <i class="bi bi-trash3 me-1"></i>Vider la corbeille
            </button>
        </form>
    @endif
</x-admin.crud.page-header>

<div class="catmin-page-body">
    <x-admin.crud.flash-messages />

    <div class="card mb-3">
        <div class="card-header bg-white">
            <h2 class="h6 mb-0">Upload rapide (drag & drop)</h2>
        </div>
        <div class="card-body">
            <form method="post" action="{{ admin_route('media.store') }}" enctype="multipart/form-data" class="row g-3">
                @csrf

                <div class="col-12">
                    <div class="catmin-media-dropzone" data-media-dropzone>
                        <input id="quick-files" name="files[]" type="file" multiple required>
                        <p class="mb-1 fw-semibold">Glissez-déposez vos fichiers ici</p>
                        <p class="mb-2 small text-muted">ou cliquez pour parcourir.</p>
                        <button class="btn btn-sm btn-outline-primary" type="button" data-media-dropzone-browse>Parcourir</button>
                        <p class="small text-muted mt-2 mb-0" data-media-dropzone-feedback>Aucun fichier selectionne.</p>
                    </div>
                    @error('files')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    @error('files.*')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>

                <div class="col-12 col-lg-3">
                    <label class="form-label" for="quick-folder">Dossier</label>
                    <input id="quick-folder" name="folder" type="text" class="form-control" value="{{ old('folder', $currentFolder ?? '') }}" placeholder="images" pattern="[a-zA-Z0-9_\-]*">
                </div>
                <div class="col-12 col-lg-4">
                    <label class="form-label" for="quick-alt">Texte alternatif</label>
                    <input id="quick-alt" name="alt_text" type="text" class="form-control" value="{{ old('alt_text') }}">
                </div>
                <div class="col-12 col-lg-5">
                    <label class="form-label" for="quick-caption">Legende</label>
                    <input id="quick-caption" name="caption" type="text" class="form-control" value="{{ old('caption') }}">
                </div>

                <div class="col-12">
                    <button class="btn btn-primary" type="submit"><i class="bi bi-cloud-upload me-1"></i>Uploader les fichiers</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h2 class="h6 mb-0">Filtres bibliotheque</h2>
            <span class="badge text-bg-light">{{ $assets->total() }}</span>
        </div>
        <div class="card-body">
            <form method="get" action="{{ admin_route('media.manage') }}" class="row g-2 align-items-end">
                <input type="hidden" name="scope" value="{{ $scope ?? 'active' }}">
                <input type="hidden" name="view" id="filter-view" value="{{ $selectedView ?? 'grid' }}">
                <div class="col-12 col-lg-4">
                    <label class="form-label" for="filter-q">Recherche</label>
                    <input id="filter-q" name="q" type="search" class="form-control" value="{{ $search ?? '' }}" placeholder="nom, alt, legende, type...">
                </div>
                <div class="col-6 col-lg-2">
                    <label class="form-label" for="filter-folder">Dossier</label>
                    <select id="filter-folder" name="folder" class="form-select">
                        <option value="">Tous</option>
                        @foreach(($folders ?? []) as $folder)
                            <option value="{{ $folder }}" @selected(($currentFolder ?? '') === $folder)>{{ $folder }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-lg-2">
                    <label class="form-label" for="filter-kind">Type</label>
                    <select id="filter-kind" name="kind" class="form-select">
                        <option value="" @selected(($selectedKind ?? '') === '')>Tous</option>
                        <option value="image" @selected(($selectedKind ?? '') === 'image')>Images</option>
                        <option value="document" @selected(($selectedKind ?? '') === 'document')>Documents</option>
                        <option value="video" @selected(($selectedKind ?? '') === 'video')>Videos</option>
                        <option value="audio" @selected(($selectedKind ?? '') === 'audio')>Audio</option>
                        <option value="other" @selected(($selectedKind ?? '') === 'other')>Autres</option>
                    </select>
                </div>
                <div class="col-6 col-lg-2">
                    <label class="form-label" for="filter-from">Du</label>
                    <input id="filter-from" name="from" type="date" class="form-control" value="{{ $selectedFrom ?? '' }}">
                </div>
                <div class="col-6 col-lg-2">
                    <label class="form-label" for="filter-to">Au</label>
                    <input id="filter-to" name="to" type="date" class="form-control" value="{{ $selectedTo ?? '' }}">
                </div>
                <div class="col-6 col-lg-2">
                    <label class="form-label" for="filter-sort">Tri</label>
                    <select id="filter-sort" name="sort" class="form-select">
                        <option value="newest" @selected(($selectedSort ?? 'newest') === 'newest')>Plus recents</option>
                        <option value="oldest" @selected(($selectedSort ?? '') === 'oldest')>Plus anciens</option>
                        <option value="name" @selected(($selectedSort ?? '') === 'name')>Nom</option>
                        <option value="type" @selected(($selectedSort ?? '') === 'type')>Type</option>
                        <option value="size_desc" @selected(($selectedSort ?? '') === 'size_desc')>Taille desc</option>
                        <option value="size_asc" @selected(($selectedSort ?? '') === 'size_asc')>Taille asc</option>
                        <option value="updated" @selected(($selectedSort ?? '') === 'updated')>Maj recentes</option>
                    </select>
                </div>
                <div class="col-6 col-lg-2">
                    <label class="form-label" for="filter-per-page">Par page</label>
                    <select id="filter-per-page" name="per_page" class="form-select">
                        <option value="12" @selected(($selectedPerPage ?? '24') === '12')>12</option>
                        <option value="24" @selected(($selectedPerPage ?? '24') === '24')>24</option>
                        <option value="48" @selected(($selectedPerPage ?? '') === '48')>48</option>
                        <option value="96" @selected(($selectedPerPage ?? '') === '96')>96</option>
                    </select>
                </div>
                <div class="col-12 col-lg-4 d-flex gap-2">
                    <button class="btn btn-primary" type="submit"><i class="bi bi-funnel me-1"></i>Filtrer</button>
                    <a class="btn btn-outline-secondary" href="{{ admin_route('media.manage', ['scope' => $scope ?? 'active']) }}">Reset</a>
                </div>
                <div class="col-12 col-lg-4 d-flex gap-2 justify-content-lg-end">
                    <div class="btn-group" role="group" aria-label="Mode d'affichage">
                        <button class="btn btn-outline-secondary {{ ($selectedView ?? 'grid') === 'grid' ? 'active' : '' }}" type="button" data-media-view="grid">
                            <i class="bi bi-grid-3x3-gap"></i>
                        </button>
                        <button class="btn btn-outline-secondary {{ ($selectedView ?? 'grid') === 'list' ? 'active' : '' }}" type="button" data-media-view="list">
                            <i class="bi bi-list-ul"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if($assets->count() > 0)
        <form id="bulk-form" method="post" action="{{ admin_route('media.bulk') }}" class="{{!catmin_can('module.media.trash') ? 'd-none' : ''}}">
            @csrf

            <div class="row mb-3">
                <div class="col-auto">
                    @if(catmin_can('module.media.trash'))
                        <label class="form-check form-check-inline">
                            <input type="checkbox" id="select-all" class="form-check-input">
                            <span class="form-check-label">Selectionner tout</span>
                        </label>
                    @endif
                </div>
                <div class="col-auto ms-auto">
                    <span class="selected-count" id="selected-info" style="display: none;">
                        <span id="selected-count-value">0</span> sélectionné(s)
                    </span>
                </div>
            </div>

            <div class="catmin-media-grid {{ ($selectedView ?? 'grid') === 'list' ? 'catmin-media-grid-list' : '' }}" id="media-results-grid">
                @foreach($assets as $asset)
                    @php
                        $previewUrl = $mediaService->previewUrl($asset);
                        $fileUrl = $mediaService->fileUrl($asset);
                        $previewMode = $mediaService->previewMode($asset);
                        $assetFolder = $mediaService->folderFromPath((string) $asset->path);
                        $kind = $mediaService->assetKind($asset);
                    @endphp
                    <article class="catmin-media-card"
                        data-media-item="1"
                        data-media-id="{{ $asset->id }}"
                        data-media-name="{{ $asset->original_name }}"
                        data-media-kind="{{ $kind }}"
                        data-media-mime="{{ $asset->mime_type ?: 'n/a' }}"
                        data-media-size="{{ $mediaService->humanSize((int) $asset->size_bytes) }}"
                        data-media-folder="{{ $assetFolder }}"
                        data-media-created="{{ optional($asset->created_at)->format('d/m/Y H:i') ?: 'n/a' }}"
                        data-media-preview-mode="{{ $previewMode }}"
                        data-media-preview-url="{{ $previewUrl ?: '' }}"
                        data-media-file-url="{{ $fileUrl ?: '' }}"
                    >
                        @if(catmin_can('module.media.trash'))
                            <div style="position: absolute; top: 10px; right: 10px; z-index: 10;">
                                <input type="checkbox" name="bulk_select[]" value="{{ $asset->id }}" class="form-check-input bulk-checkbox" style="width: 20px; height: 20px;">
                            </div>
                        @endif
                        <div class="catmin-media-card-preview">
                            @if($previewUrl)
                                <img src="{{ $previewUrl }}" alt="Apercu {{ $asset->original_name }}">
                            @else
                                <span class="badge text-bg-light">{{ strtoupper($asset->extension ?: 'file') }}</span>
                            @endif
                        </div>
                        <div class="catmin-media-card-body">
                            <div>
                                <p class="mb-0 fw-semibold text-truncate" title="{{ $asset->original_name }}">{{ $asset->original_name }}</p>
                                <p class="mb-0 small text-muted">#{{ $asset->id }} · {{ $mediaService->humanSize((int) $asset->size_bytes) }}</p>
                            </div>
                            <div class="d-flex flex-wrap gap-1 align-items-center">
                                <span class="badge text-bg-light">{{ $kind }}</span>
                                @if($assetFolder !== '')
                                    <a class="badge text-bg-light text-dark text-decoration-none" href="{{ admin_route('media.manage', ['folder' => $assetFolder]) }}">
                                        <i class="bi bi-folder me-1"></i>{{ $assetFolder }}
                                    </a>
                                @endif
                            </div>
                            <p class="mb-0 small text-muted text-truncate" title="{{ $asset->mime_type }}">{{ $asset->mime_type ?: 'n/a' }}</p>
                            <p class="mb-0 small text-muted">{{ optional($asset->created_at)->format('d/m/Y H:i') ?: 'n/a' }}</p>
                            <button class="btn btn-sm btn-outline-primary" type="button" data-media-open-detail>Details</button>
                            <div class="d-flex gap-2">
                                @if(method_exists($asset, 'trashed') && $asset->trashed())
                                    @if(catmin_can('module.media.trash'))
                                        <form method="post" action="{{ admin_route('media.restore', ['asset' => $asset->id]) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button class="btn btn-sm btn-outline-success" type="submit">Restaurer</button>
                                        </form>
                                        <form method="post" action="{{ admin_route('media.force_delete', ['asset' => $asset->id]) }}" onsubmit="return confirm('Supprimer définitivement ce media ? Action irreversible.');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger" type="submit"><i class="bi bi-trash3"></i></button>
                                        </form>
                                    @endif
                                @else
                                    @if(catmin_can('module.media.edit'))
                                        <a class="btn btn-sm btn-outline-secondary" href="{{ admin_route('media.edit', ['asset' => $asset->id]) }}">Modifier</a>
                                    @endif
                                    @if(catmin_can('module.media.trash'))
                                        <form method="post" action="{{ admin_route('media.destroy', ['asset' => $asset->id]) }}" onsubmit="return confirm('Deplacer ce media dans la corbeille ?');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger" type="submit"><i class="bi bi-trash"></i></button>
                                        </form>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="card mt-3" id="media-detail-panel">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h2 class="h6 mb-0">Panneau detail</h2>
                    <span class="badge text-bg-light" id="media-detail-kind">-</span>
                </div>
                <div class="card-body">
                    <p class="mb-1 fw-semibold" id="media-detail-name">Selectionnez un media</p>
                    <p class="small text-muted mb-3" id="media-detail-meta">Cliquez sur Details pour afficher les informations et la preview.</p>
                    <div id="media-detail-preview" class="border rounded p-3 bg-body-tertiary text-center small text-muted">
                        Aucune preview active.
                    </div>
                </div>
            </div>

            @if(catmin_can('module.media.trash') && $assets->count() > 0)
                <div class="bulk-actions-toolbar mt-3" id="bulk-toolbar" style="display: none;gap: 1rem; align-items: center;">
                    <button type="button" class="btn btn-sm btn-outline-danger" data-bulk-action="trash" data-requires-confirmation="true" data-confirmation-message="Envoyer les medias selectionnés à la corbeille ?">
                        <i class="bi bi-trash me-1"></i>Envoyer en corbeille
                    </button>
                </div>
            @endif
        </form>
    @else
        <div class="card border-0 bg-body-tertiary">
            <div class="card-body text-center py-5">
                <p class="mb-2 fw-semibold">Aucun media pour ces filtres.</p>
                <p class="mb-0 text-muted small">Essayez une autre recherche ou uploadez de nouveaux fichiers.</p>
            </div>
        </div>
    @endif

    @if($assets->hasPages())
        <div class="mt-3">
            <x-admin.crud.pagination :paginator="$assets" />
        </div>
    @endif
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAllCheckbox = document.getElementById('select-all');
    const toolbar = document.getElementById('bulk-toolbar');
    const selectedInfo = document.getElementById('selected-info');
    const countValue = document.getElementById('selected-count-value');
    const bulkForm = document.getElementById('bulk-form');
    const viewInput = document.getElementById('filter-view');
    const filterForm = viewInput ? viewInput.closest('form') : null;
    const detailName = document.getElementById('media-detail-name');
    const detailMeta = document.getElementById('media-detail-meta');
    const detailKind = document.getElementById('media-detail-kind');
    const detailPreview = document.getElementById('media-detail-preview');

    document.querySelectorAll('[data-media-view]').forEach(button => {
        button.addEventListener('click', function () {
            if (!viewInput || !filterForm) {
                return;
            }
            viewInput.value = this.dataset.mediaView || 'grid';
            filterForm.submit();
        });
    });
    
    function updateToolbarVisibility() {
        const checkedCount = document.querySelectorAll('input[name="bulk_select[]"]:checked').length;
        countValue.textContent = checkedCount;
        selectedInfo.style.display = checkedCount > 0 ? 'block' : 'none';
        if (toolbar) {
            toolbar.style.display = checkedCount > 0 ? 'flex' : 'none';
        }
    }
    
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            const isChecked = this.checked;
            document.querySelectorAll('input[name="bulk_select[]"]').forEach(checkbox => {
                checkbox.checked = isChecked;
            });
            updateToolbarVisibility();
        });
    }
    
    document.querySelectorAll('input[name="bulk_select[]"]').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateToolbarVisibility();
            if (selectAllCheckbox) {
                const totalCount = document.querySelectorAll('input[name="bulk_select[]"]').length;
                const checkedCount = document.querySelectorAll('input[name="bulk_select[]"]:checked').length;
                selectAllCheckbox.checked = (checkedCount === totalCount && checkedCount > 0);
            }
        });
    });
    
    document.querySelectorAll('[data-bulk-action]').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const action = this.dataset.bulkAction;
            const requiresConfirmation = this.dataset.requiresConfirmation === 'true';
            const message = this.dataset.confirmationMessage || 'Êtes-vous sûr ?';
            
            const selectedCount = document.querySelectorAll('input[name="bulk_select[]"]:checked').length;
            
            if (selectedCount === 0) {
                alert('Veuillez sélectionner au moins un media');
                return;
            }
            
            if (requiresConfirmation && !confirm(message)) {
                return;
            }
            
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'bulk_action';
            actionInput.value = action;
            bulkForm.appendChild(actionInput);
            bulkForm.submit();
        });
    });

    document.querySelectorAll('[data-media-open-detail]').forEach(button => {
        button.addEventListener('click', function () {
            const card = this.closest('[data-media-item]');
            if (!card || !detailName || !detailMeta || !detailPreview || !detailKind) {
                return;
            }

            const name = card.dataset.mediaName || 'media';
            const kind = card.dataset.mediaKind || '-';
            const id = card.dataset.mediaId || '-';
            const mime = card.dataset.mediaMime || 'n/a';
            const size = card.dataset.mediaSize || 'n/a';
            const folder = card.dataset.mediaFolder || '-';
            const created = card.dataset.mediaCreated || 'n/a';
            const previewMode = card.dataset.mediaPreviewMode || 'none';
            const previewUrl = card.dataset.mediaPreviewUrl || '';
            const fileUrl = card.dataset.mediaFileUrl || '';

            detailName.textContent = name;
            detailKind.textContent = kind;
            detailMeta.textContent = '#' + id + ' · ' + mime + ' · ' + size + ' · dossier: ' + folder + ' · ' + created;

            if (previewMode === 'image' && previewUrl) {
                detailPreview.innerHTML = '<img src="' + previewUrl + '" alt="Apercu" class="img-fluid rounded" style="max-height: 320px;">';
                return;
            }

            if (previewMode === 'document' && fileUrl) {
                detailPreview.innerHTML = '<iframe src="' + fileUrl + '" title="Preview document" style="width:100%;height:320px;border:0;"></iframe>';
                return;
            }

            if (fileUrl) {
                detailPreview.innerHTML = '<a class="btn btn-outline-secondary" target="_blank" rel="noopener" href="' + fileUrl + '">Ouvrir le fichier</a>';
                return;
            }

            detailPreview.textContent = 'Preview indisponible pour ce media.';
        });
    });
});
</script>

<style>
.bulk-actions-toolbar {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.5rem;
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 0.25rem;
}

.selected-count {
    font-weight: 600;
    color: #495057;
}

.catmin-media-card {
    position: relative;
}

.catmin-media-grid-list {
    display: grid;
    grid-template-columns: 1fr;
    gap: 0.75rem;
}

.catmin-media-grid-list .catmin-media-card {
    display: grid;
    grid-template-columns: 140px 1fr;
    align-items: stretch;
}

.catmin-media-grid-list .catmin-media-card-preview {
    min-height: 120px;
}
</style>
@endsection
