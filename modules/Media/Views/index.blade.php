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
                    <a class="btn btn-outline-secondary" href="{{ admin_route('media.manage') }}">Reset</a>
                </div>
            </form>
        </div>
    </div>

    @if($assets->count() > 0)
        <div class="catmin-media-grid">
            @foreach($assets as $asset)
                @php
                    $previewUrl = $mediaService->previewUrl($asset);
                    $assetFolder = $mediaService->folderFromPath((string) $asset->path);
                    $kind = $mediaService->assetKind($asset);
                @endphp
                <article class="catmin-media-card">
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
                        <div class="d-flex gap-2">
                            @if(catmin_can('module.media.edit'))
                                <a class="btn btn-sm btn-outline-secondary" href="{{ admin_route('media.edit', ['asset' => $asset->id]) }}">Modifier</a>
                            @endif
                            @if(catmin_can('module.media.delete'))
                                <form method="post" action="{{ admin_route('media.destroy', ['asset' => $asset->id]) }}" onsubmit="return confirm('Supprimer définitivement ce media ?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" type="submit"><i class="bi bi-trash"></i></button>
                                </form>
                            @endif
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
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
@endsection
