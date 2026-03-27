@extends('admin.layouts.catmin')

@section('page_title', 'Nouvel article')

@section('content')
<x-admin.crud.page-header
    title="Creer un article"
    subtitle="Un seul flux pour ex-blog et ex-news."
>
    <a class="btn btn-outline-secondary" href="{{ admin_route('articles.manage') }}">Retour liste</a>
</x-admin.crud.page-header>

<div class="catmin-page-body">
    <div class="card">
        <div class="card-header bg-white"><h2 class="h6 mb-0">Contenu article</h2></div>
        <div class="card-body">
            <form method="post" action="{{ admin_route('articles.store') }}" class="row g-3">
                @csrf

                <div class="col-12 col-lg-6"><label class="form-label" for="title">Titre</label><input id="title" name="title" type="text" class="form-control @error('title') is-invalid @enderror" value="{{ old('title') }}" required>@error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-12 col-lg-3"><label class="form-label" for="content_type">Type</label><select id="content_type" name="content_type" class="form-select @error('content_type') is-invalid @enderror"><option value="article" @selected(old('content_type', 'article') === 'article')>Article</option><option value="news" @selected(old('content_type') === 'news')>News</option></select>@error('content_type')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-12 col-lg-3"><label class="form-label" for="slug">Slug</label><input id="slug" name="slug" type="text" class="form-control @error('slug') is-invalid @enderror" value="{{ old('slug') }}">@error('slug')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-12"><label class="form-label" for="excerpt">Extrait</label><textarea id="excerpt" name="excerpt" rows="3" class="form-control @error('excerpt') is-invalid @enderror">{{ old('excerpt') }}</textarea>@error('excerpt')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-12"><label class="form-label" for="content">Contenu</label><textarea id="content" name="content" rows="10" class="form-control @error('content') is-invalid @enderror">{{ old('content') }}</textarea>@error('content')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-12 col-lg-3"><label class="form-label" for="status">Statut</label><select id="status" name="status" class="form-select @error('status') is-invalid @enderror"><option value="draft" @selected(old('status', 'draft') === 'draft')>Brouillon</option><option value="published" @selected(old('status') === 'published')>Publie</option></select>@error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-12 col-lg-3"><label class="form-label" for="published_at">Publication</label><input id="published_at" name="published_at" type="datetime-local" class="form-control @error('published_at') is-invalid @enderror" value="{{ old('published_at') }}">@error('published_at')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-12 col-lg-3"><label class="form-label" for="media_asset_id">Media ID</label><input id="media_asset_id" name="media_asset_id" type="number" class="form-control @error('media_asset_id') is-invalid @enderror" value="{{ old('media_asset_id') }}">@error('media_asset_id')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-12 col-lg-3"><label class="form-label" for="seo_meta_id">SEO ID</label><input id="seo_meta_id" name="seo_meta_id" type="number" class="form-control @error('seo_meta_id') is-invalid @enderror" value="{{ old('seo_meta_id') }}">@error('seo_meta_id')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-12 d-flex gap-2"><button class="btn btn-primary" type="submit">Creer</button><a class="btn btn-outline-secondary" href="{{ admin_route('articles.manage') }}">Annuler</a></div>
            </form>
        </div>
    </div>
</div>
@endsection
