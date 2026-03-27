@extends('admin.layouts.catmin')

@section('page_title', 'Edition page')

@section('content')
<x-admin.crud.page-header
    title="Modifier une page"
    subtitle="Mise a jour des champs de base du module Pages."
>
    <a class="btn btn-outline-secondary" href="{{ admin_route('pages.manage') }}">
        <i class="bi bi-arrow-left me-1"></i>Retour liste
    </a>
</x-admin.crud.page-header>

<div class="catmin-page-body">
    <div class="card">
        <div class="card-header bg-white">
            <h2 class="h6 mb-0">Page #{{ $page->id }}</h2>
        </div>
        <div class="card-body">
            <form method="post" action="{{ admin_route('pages.update', ['page' => $page->id]) }}" class="row g-3">
                @csrf
                @method('PUT')

                <div class="col-12 col-lg-8">
                    <label class="form-label" for="title">Titre</label>
                    <input id="title" name="title" type="text" class="form-control @error('title') is-invalid @enderror" value="{{ old('title', $page->title) }}" required>
                    @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12 col-lg-4">
                    <label class="form-label" for="slug">Slug</label>
                    <input id="slug" name="slug" type="text" class="form-control @error('slug') is-invalid @enderror" value="{{ old('slug', $page->slug) }}">
                    @error('slug')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12">
                    <label class="form-label" for="content">Contenu</label>
                    <textarea id="content" name="content" rows="8" class="form-control @error('content') is-invalid @enderror">{{ old('content', $page->content) }}</textarea>
                    @error('content')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12 col-lg-4">
                    <label class="form-label" for="status">Statut</label>
                    <select id="status" name="status" class="form-select @error('status') is-invalid @enderror">
                        <option value="draft" @selected(old('status', $page->status) === 'draft')>Brouillon</option>
                        <option value="published" @selected(old('status', $page->status) === 'published')>Publie</option>
                    </select>
                    @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12 col-lg-4">
                    <label class="form-label" for="published_at">Date de publication</label>
                    <input id="published_at" name="published_at" type="datetime-local" class="form-control @error('published_at') is-invalid @enderror" value="{{ old('published_at', optional($page->published_at)->format('Y-m-d\\TH:i')) }}">
                    @error('published_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12 d-flex gap-2">
                    <button class="btn btn-primary" type="submit">
                        <i class="bi bi-save me-1"></i>Enregistrer
                    </button>
                    <a class="btn btn-outline-secondary" href="{{ admin_route('pages.manage') }}">Annuler</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
