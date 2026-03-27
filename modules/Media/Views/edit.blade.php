@extends('admin.layouts.catmin')

@section('page_title', 'Edit media')

@section('content')
<x-admin.crud.page-header
    title="Modifier un media"
    subtitle="Mise a jour des metadonnees du fichier."
>
    <a class="btn btn-outline-secondary" href="{{ admin_route('media.manage') }}">Retour liste</a>
</x-admin.crud.page-header>

<div class="catmin-page-body d-grid gap-4">
    <div class="card">
        <div class="card-header bg-white"><h2 class="h6 mb-0">Apercu</h2></div>
        <div class="card-body">
            @if($previewUrl)
                <img src="{{ $previewUrl }}" alt="Apercu" class="img-fluid rounded" style="max-height:320px;">
            @else
                <p class="text-muted mb-0">Apercu non disponible pour ce type de fichier.</p>
            @endif
            <p class="small text-muted mt-3 mb-0">{{ $asset->original_name }} · {{ $asset->mime_type ?: 'n/a' }} · {{ $mediaService->humanSize((int) $asset->size_bytes) }}</p>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-white"><h2 class="h6 mb-0">Metadonnees</h2></div>
        <div class="card-body">
            <form method="post" action="{{ admin_route('media.update', ['asset' => $asset->id]) }}" class="row g-3">
                @csrf
                @method('PUT')

                <div class="col-12">
                    <label class="form-label" for="alt_text">Texte alternatif</label>
                    <input id="alt_text" name="alt_text" type="text" class="form-control @error('alt_text') is-invalid @enderror" value="{{ old('alt_text', $asset->alt_text) }}">
                    @error('alt_text')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12">
                    <label class="form-label" for="caption">Legende</label>
                    <textarea id="caption" name="caption" rows="3" class="form-control @error('caption') is-invalid @enderror">{{ old('caption', $asset->caption) }}</textarea>
                    @error('caption')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12 d-flex gap-2">
                    <button class="btn btn-primary" type="submit">Enregistrer</button>
                    <a class="btn btn-outline-secondary" href="{{ admin_route('media.manage') }}">Annuler</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
