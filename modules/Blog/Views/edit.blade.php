@extends('admin.layouts.catmin')

@section('page_title', 'Edition article blog')

@section('content')
<x-admin.crud.page-header
    title="Modifier un article blog"
    subtitle="Edition longue et evolutive, separee du rythme News."
>
    <a class="btn btn-outline-secondary" href="{{ admin_route('blog.manage') }}">Retour liste</a>
</x-admin.crud.page-header>

<div class="catmin-page-body">
    <div class="card">
        <div class="card-header bg-white"><h2 class="h6 mb-0">Article #{{ $item->id }}</h2></div>
        <div class="card-body">
            <form method="post" action="{{ admin_route('blog.update', ['blogPost' => $item->id]) }}" class="row g-3">
                @csrf
                @method('PUT')

                <div class="col-12 col-lg-8"><label class="form-label" for="title">Titre</label><input id="title" name="title" type="text" class="form-control @error('title') is-invalid @enderror" value="{{ old('title', $item->title) }}" required>@error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-12 col-lg-4"><label class="form-label" for="slug">Slug</label><input id="slug" name="slug" type="text" class="form-control @error('slug') is-invalid @enderror" value="{{ old('slug', $item->slug) }}">@error('slug')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-12"><label class="form-label" for="excerpt">Extrait</label><textarea id="excerpt" name="excerpt" rows="3" class="form-control @error('excerpt') is-invalid @enderror">{{ old('excerpt', $item->excerpt) }}</textarea>@error('excerpt')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-12"><label class="form-label" for="content">Contenu long</label><textarea id="content" name="content" rows="10" class="form-control @error('content') is-invalid @enderror">{{ old('content', $item->content) }}</textarea>@error('content')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-12 col-lg-3"><label class="form-label" for="status">Statut</label><select id="status" name="status" class="form-select @error('status') is-invalid @enderror"><option value="draft" @selected(old('status', $item->status) === 'draft')>Brouillon</option><option value="published" @selected(old('status', $item->status) === 'published')>Publie</option></select>@error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-12 col-lg-3"><label class="form-label" for="published_at">Publication</label><input id="published_at" name="published_at" type="datetime-local" class="form-control @error('published_at') is-invalid @enderror" value="{{ old('published_at', optional($item->published_at)->format('Y-m-d\\TH:i')) }}">@error('published_at')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-12 col-lg-3"><label class="form-label" for="media_asset_id">Media ID</label><input id="media_asset_id" name="media_asset_id" type="number" class="form-control @error('media_asset_id') is-invalid @enderror" value="{{ old('media_asset_id', $item->media_asset_id) }}">@error('media_asset_id')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-12 col-lg-3"><label class="form-label" for="seo_meta_id">SEO ID</label><input id="seo_meta_id" name="seo_meta_id" type="number" class="form-control @error('seo_meta_id') is-invalid @enderror" value="{{ old('seo_meta_id', $item->seo_meta_id) }}">@error('seo_meta_id')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-12 d-flex gap-2"><button class="btn btn-primary" type="submit">Enregistrer</button><a class="btn btn-outline-secondary" href="{{ admin_route('blog.manage') }}">Annuler</a></div>
            </form>
        </div>
    </div>
</div>
@endsection
