@extends('admin.layouts.catmin')

@section('page_title', 'Upload media')

@section('content')
<x-admin.crud.page-header
    title="Ajouter des medias"
    subtitle="Upload multiple avec dossier optionnel."
>
    <a class="btn btn-outline-secondary" href="{{ admin_route('media.manage') }}">Retour liste</a>
</x-admin.crud.page-header>

<div class="catmin-page-body">
    <div class="card">
        <div class="card-header bg-white"><h2 class="h6 mb-0">Nouveaux fichiers</h2></div>
        <div class="card-body">
            <form method="post" action="{{ admin_route('media.store') }}" enctype="multipart/form-data" class="row g-3">
                @csrf

                <div class="col-12">
                    <label class="form-label" for="files">Fichiers <span class="text-muted small">(sélection multiple autorisée)</span></label>
                    <div class="catmin-media-dropzone" data-media-dropzone>
                        <input id="files" name="files[]" type="file" class="@error('files') is-invalid @enderror @error('files.*') is-invalid @enderror" multiple required>
                        <p class="mb-1 fw-semibold">Glissez-déposez vos fichiers ici</p>
                        <p class="mb-2 small text-muted">ou cliquez pour parcourir vos dossiers.</p>
                        <button class="btn btn-sm btn-outline-primary" type="button" data-media-dropzone-browse>Parcourir</button>
                        <p class="small text-muted mt-2 mb-0" data-media-dropzone-feedback>Aucun fichier selectionne.</p>
                    </div>
                    <div class="form-text">
                        Types autorises: {{ implode(', ', (array) config('catmin.uploads.allowed_extensions', [])) }}.
                        Taille max: {{ (int) config('catmin.uploads.max_file_kb', 20480) / 1024 }} MB par fichier.
                    </div>
                    @error('files')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    @error('files.*')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>

                <div class="col-12 col-lg-4">
                    <label class="form-label" for="folder">Dossier <span class="text-muted small">(optionnel, ex: images)</span></label>
                    <input id="folder" name="folder" type="text" class="form-control @error('folder') is-invalid @enderror"
                        value="{{ old('folder') }}" placeholder="images" pattern="[a-zA-Z0-9_\-]*">
                    @error('folder')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12 col-lg-4">
                    <label class="form-label" for="alt_text">Texte alternatif</label>
                    <input id="alt_text" name="alt_text" type="text" class="form-control @error('alt_text') is-invalid @enderror" value="{{ old('alt_text') }}">
                    @error('alt_text')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12 col-lg-4">
                    <label class="form-label" for="caption">Legende</label>
                    <input id="caption" name="caption" type="text" class="form-control @error('caption') is-invalid @enderror" value="{{ old('caption') }}">
                    @error('caption')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12 d-flex gap-2">
                    <button class="btn btn-primary" type="submit">Uploader</button>
                    <a class="btn btn-outline-secondary" href="{{ admin_route('media.manage') }}">Annuler</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
