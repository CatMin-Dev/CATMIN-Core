@extends('admin.layouts.catmin')

@section('page_title', 'Modifier la page')

@push('head')
<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-bs5.min.css" rel="stylesheet">
@endpush

@section('content')
<x-admin.crud.page-header
    title="Modifier la page"
    subtitle="{{ $page->title }}"
>
    <a class="btn btn-outline-secondary" href="{{ admin_route('pages.manage') }}">
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

    <form method="post" action="{{ admin_route('pages.update', $page->id) }}" id="page-form">
        @csrf
        @method('PUT')

        <ul class="nav nav-tabs mb-0" id="pageTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="tab-content-btn" data-bs-toggle="tab" data-bs-target="#tab-content" type="button" role="tab">
                    <i class="bi bi-pencil me-1"></i>Contenu
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-publish-btn" data-bs-toggle="tab" data-bs-target="#tab-publish" type="button" role="tab">
                    <i class="bi bi-calendar-check me-1"></i>Publication
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-seo-btn" data-bs-toggle="tab" data-bs-target="#tab-seo" type="button" role="tab">
                    <i class="bi bi-search me-1"></i>SEO
                </button>
            </li>
        </ul>

        <div class="tab-content border border-top-0 rounded-bottom p-4 bg-white mb-4">

            <div class="tab-pane fade show active" id="tab-content" role="tabpanel">
                <div class="row g-3">
                    <div class="col-12 col-lg-8">
                        <label class="form-label fw-semibold" for="title">Titre <span class="text-danger">*</span></label>
                        <input id="title" name="title" type="text"
                               class="form-control @error('title') is-invalid @enderror"
                               value="{{ old('title', $page->title) }}" required autofocus>
                        @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12 col-lg-4">
                        <label class="form-label fw-semibold" for="slug">Slug</label>
                        <input id="slug" name="slug" type="text"
                               class="form-control @error('slug') is-invalid @enderror"
                               value="{{ old('slug', $page->slug) }}">
                        @error('slug')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold" for="excerpt">Extrait / résumé</label>
                        <textarea id="excerpt" name="excerpt" rows="2"
                                  class="form-control @error('excerpt') is-invalid @enderror"
                                  placeholder="Court résumé affiché dans les listings (500 car. max)">{{ old('excerpt', $page->excerpt) }}</textarea>
                        @error('excerpt')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Contenu</label>
                        <textarea id="content" name="content" class="form-control @error('content') is-invalid @enderror">{{ old('content', $page->content) }}</textarea>
                        @error('content')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="tab-publish" role="tabpanel">
                <div class="row g-3">
                    <div class="col-12 col-lg-4">
                        <label class="form-label fw-semibold" for="status">Statut</label>
                        <select id="status" name="status" class="form-select @error('status') is-invalid @enderror">
                            <option value="draft"     @selected(old('status', $page->status) === 'draft')>Brouillon</option>
                            <option value="published" @selected(old('status', $page->status) === 'published')>Publié</option>
                        </select>
                        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12 col-lg-4">
                        <label class="form-label fw-semibold" for="published_at">Date de publication</label>
                        <input id="published_at" name="published_at" type="datetime-local"
                               class="form-control @error('published_at') is-invalid @enderror"
                               value="{{ old('published_at', optional($page->published_at)->format('Y-m-d\\TH:i')) }}">
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
                               value="{{ old('meta_title', $page->meta_title) }}" maxlength="255"
                               placeholder="Titre SEO (défaut : titre de la page)">
                        @error('meta_title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">Recommandé : 50–60 caractères.</div>
                    </div>
                    <div class="col-12 col-lg-8">
                        <label class="form-label fw-semibold" for="meta_description">Meta description</label>
                        <textarea id="meta_description" name="meta_description" rows="3"
                                  class="form-control @error('meta_description') is-invalid @enderror"
                                  maxlength="320"
                                  placeholder="Description affichée dans les résultats de recherche">{{ old('meta_description', $page->meta_description) }}</textarea>
                        @error('meta_description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">Recommandé : 150–160 caractères.</div>
                    </div>
                </div>
            </div>

        </div>

        <div class="d-flex gap-2">
            <button class="btn btn-primary" type="submit">
                <i class="bi bi-check2-circle me-1"></i>Enregistrer les modifications
            </button>
            <a class="btn btn-outline-secondary" href="{{ admin_route('pages.manage') }}">Annuler</a>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-bs5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/lang/summernote-fr-FR.min.js"></script>
<script>
$(function() {
    $('#content').summernote({
        lang: 'fr-FR',
        height: 300,
        toolbar: [
            [ 'style', [ 'style' ] ],
            [ 'font', [ 'bold', 'italic', 'underline', 'strikethrough', 'superscript', 'subscript', 'clear' ] ],
            [ 'fontsize', [ 'fontsize' ] ],
            [ 'color', [ 'color' ] ],
            [ 'para', [ 'ul', 'ol', 'paragraph' ] ],
            [ 'height', [ 'height' ] ],
            [ 'table', [ 'table' ] ],
            [ 'insert', [ 'link', 'picture', 'video' ] ],
            [ 'view', [ 'fullscreen', 'codeview', 'help' ] ]
        ]
    });

    // Auto-slug from title
    const titleInput = document.getElementById('title');
    const slugInput = document.getElementById('slug');
    titleInput.addEventListener('input', function() {
        if (slugInput.dataset.manual) return;
        slugInput.value = titleInput.value
            .toLowerCase()
            .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '');
    });
    slugInput.addEventListener('input', function() { slugInput.dataset.manual = '1'; });

    // Restore active tab if there are errors
    @if ($errors->any())
    const tabFields = {
        'tab-content': ['title', 'slug', 'excerpt', 'content'],
        'tab-publish': ['status', 'published_at'],
        'tab-seo':     ['meta_title', 'meta_description'],
    };
    const errorKeys = @json(array_keys($errors->messages()));
    for (const [tabId, fields] of Object.entries(tabFields)) {
        if (fields.some(f => errorKeys.includes(f))) {
            bootstrap.Tab.getOrCreateInstance(document.querySelector('[data-bs-target="#' + tabId + '"]')).show();
            break;
        }
    }
    @endif
});
</script>
@endpush
