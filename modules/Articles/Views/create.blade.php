@extends('admin.layouts.catmin')

@section('page_title', 'Nouvel article')

@section('content')
<x-admin.crud.page-header
    title="Créer un article"
    subtitle="Contenu enrichi, SEO et médias."
>
    <a class="btn btn-outline-secondary" href="{{ admin_route('articles.manage') }}">
        <i class="bi bi-arrow-left me-1"></i>Retour liste
    </a>
</x-admin.crud.page-header>

<div class="catmin-page-body">
    @if ($errors->any())
    <div class="alert alert-danger mb-3">
        <ul class="mb-0 ps-3">
            @foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul>
    </div>
    @endif

    <form method="post" action="{{ admin_route('articles.store') }}" id="article-form">
        @csrf

        <ul class="nav nav-tabs mb-0" id="articleTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-content" type="button" role="tab">
                    <i class="bi bi-pencil me-1"></i>Contenu
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-publish" type="button" role="tab">
                    <i class="bi bi-calendar-check me-1"></i>Publication
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-seo" type="button" role="tab">
                    <i class="bi bi-search me-1"></i>SEO
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-media" type="button" role="tab">
                    <i class="bi bi-image me-1"></i>Médias
                </button>
            </li>
        </ul>

        <div class="tab-content border border-top-0 rounded-bottom p-4 bg-white mb-4">

            <div class="tab-pane fade show active" id="tab-content" role="tabpanel">
                <div class="row g-3">
                    <div class="col-12 col-lg-6">
                        <label class="form-label fw-semibold" for="title">Titre <span class="text-danger">*</span></label>
                        <input id="title" name="title" type="text"
                               class="form-control @error('title') is-invalid @enderror"
                               value="{{ old('title') }}" required autofocus>
                        @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12 col-lg-3">
                        <label class="form-label fw-semibold" for="content_type">Type</label>
                        <select id="content_type" name="content_type" class="form-select @error('content_type') is-invalid @enderror">
                            <option value="article" @selected(old('content_type', 'article') === 'article')>Article</option>
                            <option value="news"    @selected(old('content_type') === 'news')>News</option>
                        </select>
                        @error('content_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12 col-lg-3">
                        <label class="form-label fw-semibold" for="slug">Slug</label>
                        <input id="slug" name="slug" type="text"
                               class="form-control @error('slug') is-invalid @enderror"
                               value="{{ old('slug') }}" placeholder="auto depuis le titre">
                        @error('slug')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold" for="excerpt">Extrait</label>
                        <textarea id="excerpt" name="excerpt" rows="2"
                                  class="form-control @error('excerpt') is-invalid @enderror"
                                  placeholder="Court résumé affiché dans les listings">{{ old('excerpt') }}</textarea>
                        @error('excerpt')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <x-admin.editor.wysiwyg-field
                            name="content"
                            id="content"
                            label="Contenu"
                            :value="old('content')"
                            scope="articles.create"
                            field="content"
                            placeholder="Contenu riche de l'article"
                            help-text="Editeur CATMIN maison: snippets, media, blocs et formatage riche."
                        />
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="tab-publish" role="tabpanel">
                <div class="row g-3">
                    <div class="col-12 col-lg-4">
                        <label class="form-label fw-semibold" for="status">Statut</label>
                        <select id="status" name="status" class="form-select @error('status') is-invalid @enderror">
                            <option value="draft"     @selected(old('status', 'draft') === 'draft')>Brouillon</option>
                            <option value="published" @selected(old('status') === 'published')>Publié</option>
                        </select>
                        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12 col-lg-4">
                        <label class="form-label fw-semibold" for="published_at">Date de publication</label>
                        <input id="published_at" name="published_at" type="datetime-local"
                               class="form-control @error('published_at') is-invalid @enderror"
                               value="{{ old('published_at') }}">
                        @error('published_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="tab-seo" role="tabpanel">
                <div class="row g-3">
                    <div class="col-12 col-lg-8">
                        <label class="form-label fw-semibold" for="meta_title">Meta titre</label>
                        <input id="meta_title" name="meta_title" type="text"
                               class="form-control @error('meta_title') is-invalid @enderror"
                               value="{{ old('meta_title') }}" maxlength="255"
                               placeholder="Titre SEO (défaut : titre de l'article)">
                        @error('meta_title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">Recommandé : 50–60 caractères.</div>
                    </div>
                    <div class="col-12 col-lg-8">
                        <label class="form-label fw-semibold" for="meta_description">Meta description</label>
                        <textarea id="meta_description" name="meta_description" rows="3"
                                  class="form-control @error('meta_description') is-invalid @enderror"
                                  maxlength="320"
                                  placeholder="Description affichée dans les résultats de recherche">{{ old('meta_description') }}</textarea>
                        @error('meta_description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">Recommandé : 150–160 caractères.</div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="tab-media" role="tabpanel">
                <div class="row g-3">
                    <div class="col-12 col-lg-8">
                        <x-admin.media.picker-field
                            input-name="media_asset_id"
                            input-id="article_media_asset_id"
                            label="Image de couverture"
                            :value="old('media_asset_id')"
                            help-text="Selectionnez une image ou un fichier de couverture depuis la bibliotheque."
                        />
                    </div>
                </div>
            </div>

        </div>

        <div class="d-flex gap-2">
            <button class="btn btn-primary" type="submit">
                <i class="bi bi-check2-circle me-1"></i>Créer l'article
            </button>
            <a class="btn btn-outline-secondary" href="{{ admin_route('articles.manage') }}">Annuler</a>
        </div>
    </form>
</div>

<x-admin.media.picker-modal />
@endsection

@push('scripts')
<script>
(function () {
    const titleInput = document.getElementById('title');
    const slugInput = document.getElementById('slug');

    if (titleInput && slugInput) {
        titleInput.addEventListener('input', function () {
            if (slugInput.dataset.manual) return;
            slugInput.value = titleInput.value
                .toLowerCase()
                .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '');
        });

        slugInput.addEventListener('input', function () {
            slugInput.dataset.manual = '1';
        });
    }

}());
</script>
@endpush
