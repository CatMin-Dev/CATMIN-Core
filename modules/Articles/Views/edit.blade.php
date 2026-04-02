@extends('admin.layouts.catmin')

@section('page_title', 'Edition article')

@section('content')
<x-admin.crud.page-header
    title="Modifier un article"
    subtitle="Article #{{ $item->id }} — {{ $item->title }}"
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

    <form method="post" action="{{ admin_route('articles.update', ['article' => $item->id]) }}" id="article-form">
        @csrf
        @method('PUT')

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
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-taxonomy" type="button" role="tab">
                    <i class="bi bi-tags me-1"></i>Taxonomie
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
                               value="{{ old('title', $item->title) }}" required autofocus>
                        @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12 col-lg-3">
                        <label class="form-label fw-semibold" for="content_type">Type</label>
                        <select id="content_type" name="content_type" class="form-select @error('content_type') is-invalid @enderror">
                            <option value="article" @selected(old('content_type', $item->content_type) === 'article')>Article</option>
                            <option value="news"    @selected(old('content_type', $item->content_type) === 'news')>News</option>
                        </select>
                        @error('content_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12 col-lg-3">
                        <label class="form-label fw-semibold" for="slug">Slug</label>
                        <input id="slug" name="slug" type="text"
                               class="form-control @error('slug') is-invalid @enderror"
                               value="{{ old('slug', $item->slug) }}">
                        @error('slug')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold" for="excerpt">Extrait</label>
                        <textarea id="excerpt" name="excerpt" rows="2"
                                  class="form-control @error('excerpt') is-invalid @enderror"
                                  placeholder="Court résumé affiché dans les listings">{{ old('excerpt', $item->excerpt) }}</textarea>
                        @error('excerpt')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <x-admin.editor.wysiwyg-field
                            name="content"
                            id="content"
                            label="Contenu"
                            :value="old('content', $item->content)"
                            scope="articles.edit"
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
                            <option value="draft"     @selected(old('status', $item->status) === 'draft')>Brouillon</option>
                            <option value="scheduled" @selected(old('status', $item->status) === 'scheduled')>Programme</option>
                            <option value="published" @selected(old('status', $item->status) === 'published')>Publié</option>
                        </select>
                        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12 col-lg-4">
                        <label class="form-label fw-semibold" for="published_at">Date de publication</label>
                        <input id="published_at" name="published_at" type="datetime-local"
                               class="form-control @error('published_at') is-invalid @enderror"
                               value="{{ old('published_at', optional($item->published_at)->format('Y-m-d\\TH:i')) }}">
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
                               value="{{ old('meta_title', $item->meta_title) }}" maxlength="255"
                               placeholder="Titre SEO (défaut : titre de l'article)">
                        @error('meta_title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">Recommandé : 50–60 caractères.</div>
                    </div>
                    <div class="col-12 col-lg-8">
                        <label class="form-label fw-semibold" for="meta_description">Meta description</label>
                        <textarea id="meta_description" name="meta_description" rows="3"
                                  class="form-control @error('meta_description') is-invalid @enderror"
                                  maxlength="320"
                                  placeholder="Description affichée dans les résultats de recherche">{{ old('meta_description', $item->meta_description) }}</textarea>
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
                            :value="old('media_asset_id', $item->media_asset_id)"
                            help-text="Selectionnez un media de couverture ou retirez l'actuel."
                        />
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="tab-taxonomy" role="tabpanel">
                <div class="row g-3">
                    <div class="col-12 col-lg-6">
                        <label class="form-label fw-semibold" for="article_category_id">Catégorie</label>
                        <select id="article_category_id" name="article_category_id" class="form-select @error('article_category_id') is-invalid @enderror">
                            <option value="">Aucune</option>
                            @foreach(($categories ?? collect()) as $category)
                                <option value="{{ $category['id'] }}" @selected((string) old('article_category_id', $item->article_category_id) === (string) $category['id'])>{{ $category['name'] }}</option>
                            @endforeach
                        </select>
                        @error('article_category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12 col-lg-6">
                        <label class="form-label fw-semibold" for="tag_ids">Tags</label>
                        @php($selectedTags = collect(old('tag_ids', $item->tags->pluck('id')->all()))->map(fn ($id) => (int) $id)->all())
                        <select id="tag_ids" name="tag_ids[]" class="form-select @error('tag_ids') is-invalid @enderror" multiple size="8">
                            @foreach(($tags ?? collect()) as $tag)
                                <option value="{{ $tag->id }}" @selected(in_array((int) $tag->id, $selectedTags, true))>{{ $tag->name }}</option>
                            @endforeach
                        </select>
                        @error('tag_ids')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">Maintenez Ctrl/Cmd pour sélectionner plusieurs tags.</div>
                    </div>
                </div>
            </div>

        </div>

        <div class="d-flex gap-2">
            <button class="btn btn-outline-dark" type="submit" formaction="{{ admin_route('articles.preview') }}" formtarget="_blank">
                <i class="bi bi-eye me-1"></i>Prévisualiser
            </button>
            <button class="btn btn-outline-warning" type="button" data-submit-mode="schedule">
                <i class="bi bi-clock me-1"></i>Programmer
            </button>
            <button class="btn btn-outline-success" type="button" data-submit-mode="publish-now">
                <i class="bi bi-lightning-charge me-1"></i>Publier maintenant
            </button>
            <button class="btn btn-primary" type="submit">
                <i class="bi bi-save me-1"></i>Enregistrer
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
    const form = document.getElementById('article-form');
    const statusInput = document.getElementById('status');
    const publishedAtInput = document.getElementById('published_at');

    const asLocalDateTime = function (date) {
        const pad = function (n) { return String(n).padStart(2, '0'); };
        return [
            date.getFullYear(),
            '-',
            pad(date.getMonth() + 1),
            '-',
            pad(date.getDate()),
            'T',
            pad(date.getHours()),
            ':',
            pad(date.getMinutes()),
        ].join('');
    };

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

    if (form && statusInput && publishedAtInput) {
        form.querySelector('[data-submit-mode="schedule"]')?.addEventListener('click', function () {
            statusInput.value = 'scheduled';
            if (!publishedAtInput.value) {
                const date = new Date();
                date.setMinutes(date.getMinutes() + 10);
                publishedAtInput.value = asLocalDateTime(date);
            }
            form.submit();
        });

        form.querySelector('[data-submit-mode="publish-now"]')?.addEventListener('click', function () {
            statusInput.value = 'published';
            publishedAtInput.value = asLocalDateTime(new Date());
            form.submit();
        });
    }

}());
</script>
@endpush
