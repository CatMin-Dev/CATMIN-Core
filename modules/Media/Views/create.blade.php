@extends('admin.layouts.catmin')

@section('page_title', 'Upload media')

@section('content')
<x-admin.crud.page-header
    title="Ajouter un media"
    subtitle="Upload simple avec metadonnees minimales."
>
    <a class="btn btn-outline-secondary" href="{{ admin_route('media.manage') }}">Retour liste</a>
</x-admin.crud.page-header>

<div class="catmin-page-body">
    <div class="card">
        <div class="card-header bg-white"><h2 class="h6 mb-0">Nouveau fichier</h2></div>
        <div class="card-body">
            <form method="post" action="{{ admin_route('media.store') }}" enctype="multipart/form-data" class="row g-3">
                @csrf

                <div class="col-12">
                    <label class="form-label" for="file">Fichier</label>
                    <input id="file" name="file" type="file" class="form-control @error('file') is-invalid @enderror" required>
                    @error('file')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12 col-lg-6">
                    <label class="form-label" for="alt_text">Texte alternatif</label>
                    <input id="alt_text" name="alt_text" type="text" class="form-control @error('alt_text') is-invalid @enderror" value="{{ old('alt_text') }}">
                    @error('alt_text')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12 col-lg-6">
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
