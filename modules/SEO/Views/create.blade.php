@extends('admin.layouts.catmin')

@section('page_title', 'Nouvelle entree SEO')

@section('content')
<x-admin.crud.page-header
    title="Ajouter une entree SEO"
    subtitle="Meta title, description, robots, canonical et slug override."
>
    <a class="btn btn-outline-secondary" href="{{ admin_route('seo.manage') }}">Retour liste</a>
</x-admin.crud.page-header>

<div class="catmin-page-body">
    <div class="card">
        <div class="card-header bg-white"><h2 class="h6 mb-0">Configuration SEO</h2></div>
        <div class="card-body">
            <form method="post" action="{{ admin_route('seo.store') }}" class="row g-3">
                @csrf

                <div class="col-12 col-lg-6">
                    <label class="form-label" for="target_type">Target type</label>
                    <input id="target_type" name="target_type" type="text" class="form-control @error('target_type') is-invalid @enderror" value="{{ old('target_type') }}" placeholder="pages|articles|product|global...">
                    @error('target_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12 col-lg-6">
                    <label class="form-label" for="target_id">Target id</label>
                    <input id="target_id" name="target_id" type="number" class="form-control @error('target_id') is-invalid @enderror" value="{{ old('target_id') }}">
                    @error('target_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12">
                    <label class="form-label" for="meta_title">Meta title</label>
                    <input id="meta_title" name="meta_title" type="text" class="form-control @error('meta_title') is-invalid @enderror" value="{{ old('meta_title') }}">
                    @error('meta_title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12">
                    <label class="form-label" for="meta_description">Meta description</label>
                    <textarea id="meta_description" name="meta_description" rows="3" class="form-control @error('meta_description') is-invalid @enderror">{{ old('meta_description') }}</textarea>
                    @error('meta_description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12 col-lg-4">
                    <label class="form-label" for="meta_robots">Meta robots</label>
                    <input id="meta_robots" name="meta_robots" type="text" class="form-control @error('meta_robots') is-invalid @enderror" value="{{ old('meta_robots') }}" placeholder="index,follow">
                    @error('meta_robots')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12 col-lg-8">
                    <label class="form-label" for="canonical_url">Canonical URL</label>
                    <input id="canonical_url" name="canonical_url" type="url" class="form-control @error('canonical_url') is-invalid @enderror" value="{{ old('canonical_url') }}">
                    @error('canonical_url')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12">
                    <label class="form-label" for="slug_override">Slug override</label>
                    <input id="slug_override" name="slug_override" type="text" class="form-control @error('slug_override') is-invalid @enderror" value="{{ old('slug_override') }}">
                    @error('slug_override')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12 d-flex gap-2">
                    <button class="btn btn-primary" type="submit">Enregistrer</button>
                    <a class="btn btn-outline-secondary" href="{{ admin_route('seo.manage') }}">Annuler</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
